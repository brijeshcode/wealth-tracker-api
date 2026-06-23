<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Holding;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockTransaction;
use App\Services\Stocks\FifoService;
use App\Services\Stocks\HoldingsCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransactionController extends Controller
{
    public function __construct(
        private HoldingsCalculator $calculator,
        private FifoService $fifo,
    ) {}

    public function index(StockHolding $stockHolding): JsonResponse
    {
        $transactions = $stockHolding->transactions()
            ->orderByDesc('transaction_date')
            ->get();

        return ApiResponse::index('Transactions retrieved', $transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stock_id'         => ['required', 'integer', 'exists:stocks,id'],
            'platform_id'      => ['required', 'integer', 'exists:platforms,id'],
            'exchange'         => ['required', 'in:NSE,BSE'],
            'type'             => ['required', 'in:buy,sell,dividend,bonus,split'],
            'quantity'         => ['nullable', 'numeric', 'min:0.0001'],
            'price_per_unit'   => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'source'           => ['nullable', 'in:manual,csv_import,api_sync'],
            'reference'        => ['nullable', 'string', 'max:255'],
            // Only applied when a new holding is created
            'nickname'         => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        $validated['source'] ??= 'manual';

        if (isset($validated['quantity'], $validated['price_per_unit'])) {
            $validated['amount'] = $validated['quantity'] * $validated['price_per_unit'];
        }

        $transaction = DB::transaction(function () use ($validated, $request) {
            $stockHolding = $this->findOrCreateHolding($request->user()->id, $validated);

            $txn = $stockHolding->transactions()->create([
                'type'             => $validated['type'],
                'quantity'         => $validated['quantity'] ?? null,
                'price_per_unit'   => $validated['price_per_unit'] ?? null,
                'amount'           => $validated['amount'] ?? 0,
                'transaction_date' => $validated['transaction_date'],
                'source'           => $validated['source'],
                'reference'        => $validated['reference'] ?? null,
            ]);

            if ($txn->type === 'sell') {
                $this->fifo->consumeLots($stockHolding, (float) $txn->quantity);
            } elseif ($txn->type === 'buy') {
                $this->fifo->createLot($txn);
            }

            $this->calculator->sync($stockHolding);

            return $txn->load('stockHolding');
        });

        return ApiResponse::store('Transaction added', $transaction);
    }

    public function update(Request $request, StockTransaction $transaction): JsonResponse
    {
        $stockHolding = $transaction->stockHolding()->withoutGlobalScopes()->firstOrFail();
        abort_if($stockHolding->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'quantity'         => ['nullable', 'numeric', 'min:0.0001'],
            'price_per_unit'   => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['nullable', 'date'],
            'reference'        => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($validated['quantity'], $validated['price_per_unit'])) {
            $validated['amount'] = $validated['quantity'] * $validated['price_per_unit'];
        }

        DB::transaction(function () use ($validated, $transaction, $stockHolding) {
            if ($transaction->type === 'sell') {
                $oldQty = (float) $transaction->quantity;
                $newQty = (float) ($validated['quantity'] ?? $oldQty);

                if ($oldQty !== $newQty) {
                    $this->fifo->restoreLots($stockHolding, $oldQty);
                    $this->fifo->consumeLots($stockHolding, $newQty);
                }
            }

            $transaction->update($validated);
            $this->calculator->sync($stockHolding);
        });

        return ApiResponse::update('Transaction updated', $transaction->fresh());
    }

    public function destroy(Request $request, StockTransaction $transaction): JsonResponse
    {
        $stockHolding = $transaction->stockHolding()->withoutGlobalScopes()->firstOrFail();
        abort_if($stockHolding->user_id !== $request->user()->id, 403);

        DB::transaction(function () use ($transaction, $stockHolding) {
            if ($transaction->type === 'sell') {
                $this->fifo->restoreLots($stockHolding, (float) $transaction->quantity);
            } elseif ($transaction->type === 'buy') {
                $transaction->lot?->delete();
            }

            $transaction->delete();
            $this->calculator->sync($stockHolding);
        });

        return ApiResponse::successMessage('Transaction deleted');
    }

    private function findOrCreateHolding(int $userId, array $data): StockHolding
    {
        $existing = StockHolding::where('user_id', $userId)
            ->where('stock_id', $data['stock_id'])
            ->where('exchange', $data['exchange'])
            ->whereHas('holding', fn ($q) => $q->where('platform_id', $data['platform_id']))
            ->first();

        if ($existing) {
            return $existing;
        }

        $parent = Holding::create([
            'user_id'          => $userId,
            'platform_id'      => $data['platform_id'],
            'type'             => 'stock',
            'status'           => 'active',
            'principal_amount' => 0,
            'current_value'    => 0,
            'start_date'       => $data['transaction_date'],
            'nickname'         => $data['nickname'] ?? null,
            'notes'            => $data['notes'] ?? null,
        ]);

        return StockHolding::create([
            'holding_id'    => $parent->id,
            'user_id'       => $userId,
            'stock_id'      => $data['stock_id'],
            'exchange'      => $data['exchange'],
            'quantity'      => 0,
            'avg_buy_price' => 0,
        ]);
    }
}
