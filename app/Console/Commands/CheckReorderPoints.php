<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert;
use App\Models\User;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckReorderPoints extends Command
{
    protected $signature = 'inventory:check-reorders';
    protected $description = 'Check inventory positions and create supplier-grouped draft purchase orders';

    public function handle(ReorderService $service): int
    {
        try {
            $orders = $service->generatePurchaseOrders();
            $this->info('Created '.$orders->count().' draft purchase order(s).');

            foreach ($orders as $order) {
                $this->line($order->reference.' | '.$order->supplier->company_name.' | PHP '.number_format((float) $order->total, 2));
            }

            if ($orders->isNotEmpty()) {
                $summary = $orders->map(fn ($order) => [
                    'reference' => $order->reference, 'supplier' => $order->supplier->company_name,
                    'total' => $order->total,
                ])->all();

                if (Cache::add('reorder.summary.'.today()->toDateString(), true, now()->endOfDay())) {
                    Mail::to(User::whereIn('role', ['manager', 'admin'])->pluck('email')->all())
                        ->queue(new LowStockAlert($summary));
                }
            }

            $this->info('Inventory checks finished successfully. Existing outstanding orders were included.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Scheduled reorder check failed.', ['exception' => $exception]);

            try {
                Mail::to(User::where('role', 'admin')->pluck('email')->all())
                    ->queue(new LowStockAlert([], 'The reorder check failed. Review the application log and retry.'));
            } catch (Throwable $mailException) {
                Log::error('Could not queue the reorder failure alert.', ['exception' => $mailException]);
            }

            $this->error('Reorder check failed. No incomplete order was saved. Check the application log.');

            return self::FAILURE;
        }
    }
}
