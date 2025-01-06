<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\MessageDispatchService;
use App\Message\SendMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageDispatchServiceTest extends WebTestCase
{
    private MessageDispatchService $service;
    private MockObject&MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->service = new MessageDispatchService($this->messageBus);
    }

    public function testHandleMessage(): void
    {
        $message = new SendMessage([
            'text' => 'Test message'
        ]);

        $this->messageBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                return $message instanceof SendMessage
                    && $message->getBody()['text'] === 'Test message';
            }))
            ->willReturn(new Envelope($message));

        $this->service->handleMessage('Test message');
    }
}