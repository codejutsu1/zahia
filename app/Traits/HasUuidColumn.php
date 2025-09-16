<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUuidColumn
{
    protected $hasUuidColumn = true;

    protected $uuidColumnName = 'uuid';

    public static function bootHasUuidColumn()
    {
        static::creating(function (self $model) {
            if ($model->hasUuidColumn) {
                $model->attributes[$model->uuidColumnName] = Str::uuid();
            }
        });
    }
}
