<?php

namespace App\Services\Chatbot;

use App\Contracts\InteractWithChatbot;
use App\Services\Chatbot\Drivers\TelegramDriver;
use App\Services\Chatbot\Drivers\TwilioDriver;
use App\Services\Chatbot\Drivers\WhatsappDriver;
use Illuminate\Support\Manager;

class ChatbotManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('services.bot.driver', 'twilio');
    }

    public function createWhatsappDriver(): InteractWithChatbot
    {
        return new WhatsappDriver;
    }

    public function createTelegramDriver(): InteractWithChatbot
    {
        return new TelegramDriver;
    }

    public function createTwilioDriver(): InteractWithChatbot
    {
        return new TwilioDriver;
    }
}
