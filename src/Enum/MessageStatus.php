<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * @method bool isDraft()
 * @method bool isSent()
 * @method bool isRead()
 */
enum MessageStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case READ = 'read';
}
