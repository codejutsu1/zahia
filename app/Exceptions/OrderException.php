<?php

namespace App\Exceptions;

class OrderException extends CustomException
{
    public static function nullableEmail(): self
    {
        return new self('Please provide a valid email address to initialize a transaction, its a one time process');
    }
}
