<?php

namespace App\Enums;

enum TransactionFlow: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
