<?php

use App\Models\Platform;
use App\Models\Stocks\Stock;
use App\Models\Stocks\StockHolding;
use App\Models\Stocks\StockLot;
use App\Models\Stocks\StockTransaction;
use App\Models\User;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function stockPayload(array $overrides = []): array
{
    $platform = Platform::factory()->create();
    $stock    = Stock::factory()->create();

    return array_merge([
        'stock_id'         => $stock->id,
        'platform_id'      => $platform->id,
        'exchange'         => 'NSE',
        'type'             => 'buy',
        'quantity'         => 10,
        'price_per_unit'   => 100,
        'transaction_date' => '2024-01-10',
        'source'           => 'manual',
    ], $overrides);
}

// ---------------------------------------------------------------------------
// Auth
// ---------------------------------------------------------------------------

describe('auth', function () {
    it('rejects unauthenticated requests to stock holdings', function () {
        $this->getJson('/api/stock-holdings')->assertStatus(401);
    });

    it('rejects unauthenticated transaction store', function () {
        $this->postJson('/api/stock-transactions', [])->assertStatus(401);
    });
});

// ---------------------------------------------------------------------------
// User scoping
// ---------------------------------------------------------------------------

describe('scoping', function () {
    it('user A cannot see user B holdings', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // userB creates a holding via a transaction
        $this->actingAs($userB)
            ->postJson('/api/stock-transactions', stockPayload())
            ->assertStatus(201);

        // userA sees empty list
        $this->actingAs($userA)
            ->getJson('/api/stock-holdings')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('user A cannot update user B transaction', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userB)
            ->postJson('/api/stock-transactions', stockPayload())
            ->assertStatus(201);

        $txn = StockTransaction::first();

        $this->actingAs($userA)
            ->putJson("/api/stock-transactions/{$txn->id}", ['quantity' => 5])
            ->assertStatus(403);
    });

    it('user A cannot delete user B transaction', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userB)
            ->postJson('/api/stock-transactions', stockPayload())
            ->assertStatus(201);

        $txn = StockTransaction::first();

        $this->actingAs($userA)
            ->deleteJson("/api/stock-transactions/{$txn->id}")
            ->assertStatus(403);
    });
});

// ---------------------------------------------------------------------------
// Find-or-create holding
// ---------------------------------------------------------------------------

describe('find or create holding', function () {
    it('first buy creates holding and lot', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/stock-transactions', stockPayload())
            ->assertStatus(201);

        expect(StockHolding::count())->toBe(1);
        expect(StockTransaction::count())->toBe(1);
        expect(StockLot::count())->toBe(1);
    });

    it('second buy on same stock + platform + exchange reuses holding', function () {
        $user    = User::factory()->create();
        $payload = stockPayload();

        $this->actingAs($user)->postJson('/api/stock-transactions', $payload)->assertStatus(201);
        $this->actingAs($user)->postJson('/api/stock-transactions', $payload)->assertStatus(201);

        expect(StockHolding::count())->toBe(1);
        expect(StockTransaction::count())->toBe(2);
        expect(StockLot::count())->toBe(2);
    });

    it('same stock on different exchange creates separate holding', function () {
        $user    = User::factory()->create();
        $platform = Platform::factory()->create();
        $stock    = Stock::factory()->create();

        $base = ['stock_id' => $stock->id, 'platform_id' => $platform->id,
                 'type' => 'buy', 'quantity' => 5, 'price_per_unit' => 100,
                 'transaction_date' => '2024-01-10'];

        $this->actingAs($user)->postJson('/api/stock-transactions', array_merge($base, ['exchange' => 'NSE']))->assertStatus(201);
        $this->actingAs($user)->postJson('/api/stock-transactions', array_merge($base, ['exchange' => 'BSE']))->assertStatus(201);

        expect(StockHolding::count())->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// Holdings calculation — quantity & avg buy price
// ---------------------------------------------------------------------------

describe('holdings calculation', function () {
    it('buy syncs quantity and avg_buy_price', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/stock-transactions', stockPayload([
                'quantity' => 10, 'price_per_unit' => 100,
            ]))->assertStatus(201);

        $holding = StockHolding::first();
        expect((float) $holding->quantity)->toBe(10.0);
        expect((float) $holding->avg_buy_price)->toBe(100.0);
    });

    it('two buys produce correct weighted average', function () {
        $user    = User::factory()->create();
        $payload = stockPayload();

        // Buy 10 @ 100 → avg = 100
        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'price_per_unit' => 100])
        )->assertStatus(201);

        // Buy 10 @ 200 → avg = (1000 + 2000) / 20 = 150
        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'price_per_unit' => 200])
        )->assertStatus(201);

        $holding = StockHolding::first();
        expect((float) $holding->quantity)->toBe(20.0);
        expect((float) $holding->avg_buy_price)->toBe(150.0);
    });

    it('sell reduces quantity but does not change avg_buy_price', function () {
        $user    = User::factory()->create();
        $payload = stockPayload(['quantity' => 10, 'price_per_unit' => 100]);

        $this->actingAs($user)->postJson('/api/stock-transactions', $payload)->assertStatus(201);

        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['type' => 'sell', 'quantity' => 4, 'price_per_unit' => 120])
        )->assertStatus(201);

        $holding = StockHolding::first();
        expect((float) $holding->quantity)->toBe(6.0);
        expect((float) $holding->avg_buy_price)->toBe(100.0);
    });

    it('deleting a buy transaction recomputes correctly', function () {
        $user    = User::factory()->create();
        $payload = stockPayload();

        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'price_per_unit' => 100])
        )->assertStatus(201);

        $txn1Id = StockTransaction::first()->id;

        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'price_per_unit' => 200])
        )->assertStatus(201);

        // Delete first buy — only the 10@200 remains
        $this->actingAs($user)->deleteJson("/api/stock-transactions/{$txn1Id}")->assertStatus(200);

        $holding = StockHolding::first();
        expect((float) $holding->quantity)->toBe(10.0);
        expect((float) $holding->avg_buy_price)->toBe(200.0);
    });
});

// ---------------------------------------------------------------------------
// FIFO lot management
// ---------------------------------------------------------------------------

describe('FIFO lots', function () {
    it('buy creates a lot with quantity_remaining equal to buy quantity', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/stock-transactions', stockPayload(['quantity' => 10]))
            ->assertStatus(201);

        $lot = StockLot::first();
        expect((float) $lot->quantity_remaining)->toBe(10.0);
        expect($lot->is_exhausted)->toBeFalse();
    });

    it('sell decrements the oldest lot first', function () {
        $user    = User::factory()->create();
        $payload = stockPayload();

        // Lot 1: buy 10 @ 2024-01-10
        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'transaction_date' => '2024-01-10'])
        )->assertStatus(201);

        // Lot 2: buy 10 @ 2024-02-01
        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'transaction_date' => '2024-02-01'])
        )->assertStatus(201);

        // Sell 6 — should consume from lot 1 (oldest)
        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['type' => 'sell', 'quantity' => 6, 'transaction_date' => '2024-03-01'])
        )->assertStatus(201);

        $lots = StockLot::orderBy('id')->get();
        expect((float) $lots[0]->quantity_remaining)->toBe(4.0);
        expect($lots[0]->is_exhausted)->toBeFalse();
        expect((float) $lots[1]->quantity_remaining)->toBe(10.0);
    });

    it('sell spanning two lots exhausts the first and partially consumes the second', function () {
        $user    = User::factory()->create();
        $payload = stockPayload();

        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 5, 'transaction_date' => '2024-01-10'])
        )->assertStatus(201);

        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['quantity' => 10, 'transaction_date' => '2024-02-01'])
        )->assertStatus(201);

        // Sell 8 — exhausts lot 1 (5), takes 3 from lot 2
        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['type' => 'sell', 'quantity' => 8, 'transaction_date' => '2024-03-01'])
        )->assertStatus(201);

        $lots = StockLot::orderBy('id')->get();
        expect((float) $lots[0]->quantity_remaining)->toBe(0.0);
        expect($lots[0]->is_exhausted)->toBeTrue();
        expect((float) $lots[1]->quantity_remaining)->toBe(7.0);
    });

    it('deleting a sell restores lot quantities', function () {
        $user    = User::factory()->create();
        $payload = stockPayload(['quantity' => 10, 'price_per_unit' => 100]);

        $this->actingAs($user)->postJson('/api/stock-transactions', $payload)->assertStatus(201);

        $this->actingAs($user)->postJson('/api/stock-transactions',
            array_merge($payload, ['type' => 'sell', 'quantity' => 6])
        )->assertStatus(201);

        $sellTxn = StockTransaction::where('type', 'sell')->first();
        $this->actingAs($user)->deleteJson("/api/stock-transactions/{$sellTxn->id}")->assertStatus(200);

        $lot = StockLot::first();
        expect((float) $lot->quantity_remaining)->toBe(10.0);
        expect($lot->is_exhausted)->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// Lock-in
// ---------------------------------------------------------------------------

describe('lock-in', function () {
    it('selling a locked lot returns 422', function () {
        $user    = User::factory()->create();
        $payload = stockPayload(['quantity' => 10, 'price_per_unit' => 100]);

        $this->actingAs($user)->postJson('/api/stock-transactions', $payload)->assertStatus(201);

        // Lock the lot
        StockLot::first()->update(['locked_until' => now()->addYear()->format('Y-m-d')]);

        $this->actingAs($user)
            ->postJson('/api/stock-transactions',
                array_merge($payload, ['type' => 'sell', 'quantity' => 5])
            )
            ->assertStatus(422);
    });
});

// ---------------------------------------------------------------------------
// Holdings CRUD
// ---------------------------------------------------------------------------

describe('holdings CRUD', function () {
    it('returns holdings for authenticated user', function () {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/stock-transactions', stockPayload())->assertStatus(201);

        $this->actingAs($user)
            ->getJson('/api/stock-holdings')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('can update nickname and notes', function () {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/stock-transactions', stockPayload())->assertStatus(201);

        $holding = StockHolding::first();

        $this->actingAs($user)
            ->putJson("/api/stock-holdings/{$holding->id}", [
                'nickname' => 'My INFY position',
                'notes'    => 'Long term hold',
            ])
            ->assertStatus(200);

        expect($holding->fresh()->holding->nickname)->toBe('My INFY position');
        expect($holding->fresh()->holding->notes)->toBe('Long term hold');
    });

    it('soft-deletes holding and parent when destroyed', function () {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/stock-transactions', stockPayload())->assertStatus(201);

        $holding = StockHolding::first();

        $this->actingAs($user)
            ->deleteJson("/api/stock-holdings/{$holding->id}")
            ->assertStatus(200);

        expect(StockHolding::count())->toBe(0);
        expect(StockHolding::withTrashed()->count())->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// Computed endpoint
// ---------------------------------------------------------------------------

describe('computed', function () {
    it('returns correct quantity cost basis and null price when no stock_price row', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/stock-transactions', stockPayload([
                'quantity' => 10, 'price_per_unit' => 150,
            ]))->assertStatus(201);

        $holding = StockHolding::first();

        $this->actingAs($user)
            ->getJson("/api/stock-holdings/{$holding->id}/computed")
            ->assertStatus(200)
            ->assertJsonFragment([
                'quantity'      => 10.0,
                'avg_buy_price' => 150.0,
                'cost_basis'    => 1500.0,
                'current_price' => null,
            ]);
    });
});
