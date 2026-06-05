<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GmAction;
use App\Models\TomPurchaseLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Khóa hành vi đối soát giao hàng Tôm (lỗ hổng tiền thật đã vá).
 * Trước đây log bị đánh 'delivered' ngay khi dispatch, nên job GM lỗi async thì
 * không ai biết. Giờ log chỉ 'dispatched' và lệnh tom:reconcile-deliveries đối soát thật.
 */
class ReconcileTomDeliveriesTest extends TestCase
{
    use RefreshDatabase;

    private function pendingLog(string $gmStatus, int $attempts = 1): TomPurchaseLog
    {
        $user = User::factory()->create([
            'username' => 'rec'.Str::random(8),
            'portal_uid' => 'gj-uid-rec',
        ]);

        $gm = GmAction::create([
            'action_uuid' => (string) Str::uuid(),
            'admin_id' => null,
            'server_id' => 1,
            'action_type' => 'send_mail',
            'target_user' => $user->username,
            'payload' => ['player_id' => '1', 'title' => 't', 'body' => 'b', 'item_payload' => '2,10'],
            'status' => $gmStatus,
        ]);

        $log = TomPurchaseLog::create([
            'user_id' => $user->id,
            'item_id' => 'tom_to_kc_10m',
            'server_id' => 1,
            'tom_spent' => 10,
            'idempotency_key' => 'muh5-pshop-'.Str::ulid(),
            'status' => 'dispatched',
            'meta' => ['item_name' => 'KC 10M', 'gm_action_id' => $gm->id, 'delivery_attempts' => $attempts],
        ]);

        // Đẩy updated_at về quá khứ để vượt cutoff 2 phút của lệnh (query-builder update không tự touch timestamp).
        TomPurchaseLog::where('id', $log->id)->update(['updated_at' => now()->subMinutes(5)]);

        return $log->fresh();
    }

    public function test_dispatched_with_executed_gm_becomes_delivered(): void
    {
        $log = $this->pendingLog('executed');

        $this->artisan('tom:reconcile-deliveries')->assertSuccessful();

        $this->assertSame('delivered', $log->fresh()->status);
    }

    public function test_dispatched_with_failed_gm_past_cap_is_terminal_not_delivered(): void
    {
        // attempts >= cap (mặc định 5) -> terminal, KHÔNG được tự đánh delivered.
        $log = $this->pendingLog('failed', attempts: 5);

        $this->artisan('tom:reconcile-deliveries')->assertSuccessful();

        $fresh = $log->fresh();
        $this->assertSame('delivery_failed', $fresh->status);
        $this->assertTrue((bool) ($fresh->meta['terminal'] ?? false));
        $this->assertNotSame('delivered', $fresh->status);
    }

    public function test_dispatched_with_pending_gm_waits_and_is_not_falsely_delivered(): void
    {
        $log = $this->pendingLog('pending');

        $this->artisan('tom:reconcile-deliveries')->assertSuccessful();

        // Job còn đang chạy: giữ nguyên 'dispatched', tuyệt đối không tự đánh 'delivered'.
        $this->assertSame('dispatched', $log->fresh()->status);
    }
}
