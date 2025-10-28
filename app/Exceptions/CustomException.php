<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function __construct(
        string $message,
        protected array $response_data = [],
        protected ?string $provider = null,
    ) {
        parent::__construct($message);
    }

    public function context(): array
    {
        return [
            'provider' => $this->provider,
            'response_data' => $this->response_data,
        ];
    }
}
