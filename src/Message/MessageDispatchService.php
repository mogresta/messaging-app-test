<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\MessageBusInterface;

class MessageDispatchService
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function handleMessage(string $messageContent): void {
        $message = [
         'text' => $messageContent,
        ];

        $this->messageBus->dispatch((new SendMessage($message)));
    }
}