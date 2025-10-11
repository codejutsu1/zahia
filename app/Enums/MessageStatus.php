<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Queued = 'queued';

    case Sending = 'sending';

    case Sent = 'sent';

    case Failed = 'failed';

    case Delivered = 'delivered';

    case Undelivered = 'undelivered';

    case Receiving = 'receiving';

    case Received = 'received';

    case Accepted = 'accepted';

    case Scheduled = 'scheduled';

    case Read = 'read';

    case Cancelled = 'cancelled';
}
