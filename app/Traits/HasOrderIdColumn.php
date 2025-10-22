<?php

namespace App\Traits;

trait HasOrderIdColumn
{
    protected $hasOrderIdColumn = true;

    protected $orderIdColumnName = 'order_id';

    public static function bootHasOrderIdColumn()
    {
        static::creating(function (self $model) {
            if ($model->hasOrderIdColumn) {
                $model->attributes[$model->orderIdColumnName] = self::generateOrderId($model);
            }
        });
    }

    protected static function generateOrderId(self $order): string
    {
        $uuidPrefix = substr($order->uuid, 0, 2);

        $randomDigit = random_int(0, 9);

        $orderId = (string) $order->id;

        $orderIdPart = $orderId < 1000
            ? str_pad($orderId, 3, '0', STR_PAD_LEFT)
            : $orderId;

        return strtoupper("ZAH-{$uuidPrefix}-{$randomDigit}{$orderIdPart}");
    }
}
