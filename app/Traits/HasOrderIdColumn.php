<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait HasOrderIdColumn
{
    protected $hasOrderIdColumn = true;

    protected $orderIdColumnName = 'order_id';

    public static function bootHasOrderIdColumn()
    {
        static::created(function (self $model) {
            if ($model->hasOrderIdColumn && empty($model->{$model->orderIdColumnName})) {
                $orderId = self::generateOrderId($model);
                $model->update([$model->orderIdColumnName => $orderId]);
            }
        });
    }

    protected static function generateOrderId(self $order): string
    {
        $uuidPrefix = substr($order->attributes['uuid'] ?? $order->uuid, 0, 2);

        Log::info('UUID Prefix: '.$uuidPrefix);

        $randomDigit = random_int(0, 9);

        $orderId = (string) $order->id;

        $orderIdPart = $orderId < 1000
            ? str_pad($orderId, 3, '0', STR_PAD_LEFT)
            : $orderId;

        Log::info('Order ID Part: '.$orderIdPart);

        return strtoupper("ZAH-{$uuidPrefix}-{$randomDigit}{$orderIdPart}");
    }
}
