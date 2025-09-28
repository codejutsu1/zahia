<?php

namespace App\Facade;

use App\Services\Chatbot\ChatbotManager;
use Illuminate\Support\Facades\Facade;

class Chatbot extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ChatbotManager::class;
    }
}
