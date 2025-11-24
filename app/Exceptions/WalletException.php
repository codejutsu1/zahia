<?php

namespace App\Exceptions;

class WalletException extends CustomException
{
    public static function invalidAmount(): self
    {
        return new self('Invalid amount provided!');
    }

    public static function insufficientBalance(): self
    {
        return new self('Insufficient balance!');
    }
}
