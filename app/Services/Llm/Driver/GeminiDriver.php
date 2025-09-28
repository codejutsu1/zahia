<?php

namespace App\Services\Llm\Driver;

use App\Contracts\InteractWithLlm;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

class GeminiDriver implements InteractWithLlm
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function prompt(string $prompt): string
    {
        return Prism::text()
            ->using(Provider::Gemini, 'gemini-2.5-flash')
            ->withPrompt($prompt)
            ->asText()->text;
    }
}
