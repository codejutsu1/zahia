<?php

namespace App\Traits;

trait HasDeliveryUniqueIdColumn
{
    protected $hasUniqueIdColumn = true;

    protected $uniqueIdColumnName = 'unique_id';

    public static function bootHasDeliveryUniqueIdColumn()
    {
        static::creating(function (self $model) {
            if ($model->hasUniqueIdColumn) {
                $model->attributes[$model->uniqueIdColumnName] = self::generateUniqueId();
            }
        });
    }

    protected static function generateUniqueId(): string
    {

        do {
            $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $exists = self::where('unique_id', $code)->exists();

        } while ($exists);

        return $code;
    }
}
