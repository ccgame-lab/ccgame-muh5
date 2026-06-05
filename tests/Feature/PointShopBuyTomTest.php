<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\GreenJadeClient;
use App\Services\InsufficientTomException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PointShopBuyTomTest extends TestCase
{
    use RefreshDatabase;

    private function tomUser(): User
    {
        return User::factory()->create([
            'username' => 'tomtester',
            'portal_uid' => 'gj-uid-123',
            'points' => 0,
        ]);
    }

    public function test_items_endpoint_lists_only_tom_priced_items(): void
    {
        $response = $this->getJson('/api/pshop/items');

        $response->assertOk()->assertJsonStructure([
            'items' => [['id', 'name', 'price_tom', 'limit_per_user', 'purchased', 'sold_out']],
        ]);

        // Every returned item must carry a Tom price.
        foreach ($response->json('items') as $item) {
            $this->assertGreaterThan(0, $item['price_tom']);
        }
    }

    public function test_buy_with_unknown_user_is_unauthorized(): void
    {
        $this->postJson('/api/pshop/buy-tom', [
            'u' => 'ghost',
            'item_id' => 'tom_to_kc_10m',
            'server_id' => 1,
        ])->assertStatus(401);
    }

    public function test_buy_with_invalid_item_is_rejected(): void
    {
        $this->tomUser();

        $this->postJson('/api/pshop/buy-tom', [
            'u' => 'tomtester',
            'item_id' => 'not_a_real_item',
            'server_id' => 1,
        ])->assertStatus(400)->assertJsonStructure(['error']);
    }

    public function test_missing_item_id_fails_validation(): void
    {
        $this->tomUser();

        $this->postJson('/api/pshop/buy-tom', [
            'u' => 'tomtester',
        ])->assertStatus(422);
    }

    public function test_insufficient_tom_returns_422(): void
    {
        $this->tomUser();

        $this->mock(GreenJadeClient::class, function (Mockery\MockInterface $mock) {
            $mock->shouldReceive('spend')
                ->once()
                ->andThrow(new InsufficientTomException('Số Tôm không đủ để thực hiện giao dịch.'));
        });

        $this->postJson('/api/pshop/buy-tom', [
            'u' => 'tomtester',
            'item_id' => 'tom_to_kc_10m',
            'server_id' => 1,
        ])->assertStatus(422)->assertJsonStructure(['error']);

        // The pending log must remain — no Tom was deducted, nothing to refund.
        $this->assertDatabaseHas('tom_purchase_logs', [
            'user_id' => User::where('username', 'tomtester')->value('id'),
            'item_id' => 'tom_to_kc_10m',
            'status' => 'pending',
        ]);
    }
}
