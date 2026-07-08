<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Stocks\StockPriceSyncLog;
use App\Services\Stocks\StockPriceSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockPriceController extends Controller
{
    public function __construct(private StockPriceSyncService $syncService) {}

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $date   = Carbon::parse($request->query('date', today()->toDateString()));
        $result = $this->syncService->sync($date, 'api');

        return ApiResponse::send("Price sync {$result['status']}", 200, $result);
    }

    public function backfill(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from    = Carbon::parse($request->input('from'));
        $to      = Carbon::parse($request->input('to'));
        $results = [];
        $current = $from->copy();

        while ($current->lte($to)) {
            $results[$current->toDateString()] = $this->syncService->sync($current->copy(), 'api');
            $current->addDay();
        }

        $summary = [
            'success' => collect($results)->where('status', 'success')->count(),
            'skipped' => collect($results)->where('status', 'skipped')->count(),
            'failed'  => collect($results)->where('status', 'failed')->count(),
            'detail'  => $results,
        ];

        return ApiResponse::send('Backfill complete', 200, $summary);
    }

    public function logs(Request $request): JsonResponse
    {
        $request->validate([
            'status'    => ['sometimes', 'in:success,failed,skipped'],
            'page_size' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $query = StockPriceSyncLog::orderByDesc('price_date');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $pageSize = (int) $request->query('page_size', 30);
        $logs     = $query->paginate($pageSize);

        return ApiResponse::paginated('Sync logs', $logs);
    }
}
