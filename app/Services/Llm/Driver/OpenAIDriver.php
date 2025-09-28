<?php

namespace App\Services\Llm\Driver;

use App\Contracts\InteractWithLlm;

class OpenAIDriver implements InteractWithLlm
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function prompt(string $prompt): string
    {
        return '';
    }
}
