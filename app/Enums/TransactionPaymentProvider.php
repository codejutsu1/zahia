<?php

namespace App\Enums;

enum TransactionPaymentProvider: string
{
    case Flutterwave = 'flutterwave';

    case Paystack = 'paystack';

    case Internal = 'internal';
}
