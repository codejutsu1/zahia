<?php

namespace App\Services\Llm\Driver;

use App\Contracts\InteractWithLlm;
use App\Models\User;
use App\Prism\Tools\Product\ListProductsTool;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Prism;

class GeminiDriver implements InteractWithLlm
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function prompt(array $prismMessages, User $user): string
    {
        return Prism::text()
            ->using(Provider::Gemini, 'gemini-2.5-flash')
            ->withTools([
                ListProductsTool::make($user),
            ])
            ->withMaxSteps(2)
            ->withMessages($prismMessages)
            ->asText()->text;
    }
}
