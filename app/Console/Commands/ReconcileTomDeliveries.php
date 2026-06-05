<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TomPurchaseLog;
use App\Services\PointShopService;
use Illuminate\Console\Command;

/**
 * Đối soát và tự giao lại các đơn mua bằng Tôm chưa hoàn tất.
 *
 * Tôm đã bị trừ đúng từ trước (spend qua GreenJade thành công); đây chỉ xử lý
 * khâu giao hàng trong game (KHÔNG hoàn tiền tự động, KHÔNG gọi lại ví GreenJade).
 * Đóng lỗ hổng: trước đây log bị đánh 'delivered' ngay khi dispatch, nên nếu job
 * gửi mail GM thất bại async thì không ai biết. Giờ log chỉ 'dispatched', lệnh này
 * xác nhận kết quả thật và tự giao lại nếu lỗi.
 */
class ReconcileTomDeliveries extends Command
{
    protected $signature = 'tom:reconcile-deliveries {--limit=300 : Số đơn xử lý mỗi lượt}';

    protected $description = 'Đối soát + tự giao lại đơn mua bằng Tôm chưa hoàn tất (chống mất hàng sau khi đã trừ Tôm)';

    public function handle(PointShopService $shop): int
    {
        $cap = (int) config('pshop.delivery_max_attempts', 5);
        $cutoff = now()->subMinutes(2);

        $logs = TomPurchaseLog::whereIn('status', ['dispatched', 'spent', 'delivery_failed'])
            ->where('updated_at', '<', $cutoff)
            ->orderBy('created_at')
            ->limit((int) $this->option('limit'))
            ->get()
            ->reject(fn (TomPurchaseLog $log) => ! empty($log->meta['terminal']));

        foreach ($logs as $log) {
            $shop->retryOrFailDelivery($log, $cap);
        }

        $this->info('Đã đối soát '.$logs->count().' đơn giao hàng Tôm.');

        return self::SUCCESS;
    }
}
