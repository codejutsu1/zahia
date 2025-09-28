<?php

namespace App\Services\Chatbot\Drivers;

use App\Contracts\InteractWithChatbot;
use App\Http\Integrations\Twilio\Requests\SendMessageRequest;
use App\Http\Integrations\Twilio\TwilioConnector;

class TwilioDriver implements InteractWithChatbot
{
    protected TwilioConnector $connector;

    public function __construct()
    {
        $this->connector = new TwilioConnector;
    }

    public function sendMessage(string $message)
    {
        $request = new SendMessageRequest($message);

        $response = $this->connector->send($request);

        if ($response->failed()) {
            // throw new BotException(
            //     message: 'Failed to send message!',
            //     provider: 'Twilio',
            //     response_data: $response->json()
            // );
        }

        return $response->json();
    }
}
