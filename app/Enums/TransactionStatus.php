<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
}
