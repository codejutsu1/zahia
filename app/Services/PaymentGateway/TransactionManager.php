<?php

namespace App\Services\PaymentGateway;

use App\Contracts\InteractWithTransaction;
use App\Services\PaymentGateway\Drivers\FlutterwaveDriver;
use Illuminate\Support\Manager;

class TransactionManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('services.payment_default');
    }

    public function createFlutterwaveDriver(): InteractWithTransaction
    {
        return new FlutterwaveDriver;
    }
}
