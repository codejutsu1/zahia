<?php

namespace App\Contracts;

interface InteractWithChatbot
{
    public function sendMessage(string $message);
}
