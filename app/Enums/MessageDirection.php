<?php

namespace App\Enums;

enum MessageDirection: string
{
    case INCOMING = 'incoming';

    case OUTGOING = 'outgoing';
}
