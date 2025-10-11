<?php

namespace App\Enums;

enum MessageChannel: string
{
    case WHATSAPP = 'whatsapp';

    case TELEGRAM = 'telegram';
}
