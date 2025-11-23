<?php

namespace App\Services\Llm;

use App\Contracts\InteractWithLlm;
use App\Models\User;
use App\Services\Llm\Driver\GeminiDriver;
use App\Services\Llm\Driver\OpenAIDriver;
use Illuminate\Support\Manager;

/**
 * @method string prompt(array $prismMessages, User $user)
 *
 * @mixin InteractWithLlm
 */
class LlmManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('app.services.llm.driver', 'gemini');
    }

    public function createOpenAIDriver(): InteractWithLlm
    {
        return new OpenAIDriver;
    }

    public function createGeminiDriver(): InteractWithLlm
    {
        return new GeminiDriver;
    }
}
