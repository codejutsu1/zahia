<?php

namespace App\Jobs\Order;

use App\Models\Order;
use App\Services\Order\Actions\ProcessOrderAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $orderId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::find($this->orderId);

        if (! $order) {
            return;
        }

        try {
            DB::transaction(function () use ($order) {
                app(ProcessOrderAction::class)->execute($order);
            });
        } catch (\Throwable $th) {
            Log::error('Error processing order', [
                'order_id' => $order->id,
                'error' => $th->getMessage(),
            ]);

            throw $th;
        }
    }
}
