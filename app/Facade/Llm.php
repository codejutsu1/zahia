<?php

namespace App\Facade;

use App\Models\User;
use App\Services\Llm\LlmManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string prompt(array $prismMessages, User $user)
 *
 * @see LlmManager
 */
class Llm extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LlmManager::class;
    }
}
