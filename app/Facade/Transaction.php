<?php

namespace App\Facade;

use App\Services\PaymentGateway\TransactionManager;
use Illuminate\Support\Facades\Facade;

class Transaction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TransactionManager::class;
    }
}
