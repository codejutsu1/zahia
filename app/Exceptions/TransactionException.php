<?php

namespace App\Exceptions;

class TransactionException extends CustomException
{
    public static function notFound(): self
    {
        return new self('Transaction not found!');
    }
}
