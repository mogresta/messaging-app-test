<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Entity\Message;
use App\Enum\MessageStatus;
use App\Message\SendMessage;
use App\Message\SendMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SendMessageHandlerTest extends WebTestCase
{
    private SendMessageHandler $handler;
    private MockObject&EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new SendMessageHandler($this->entityManager);
    }

    public function testInvoke(): void
    {
        $message = new SendMessage(['text' => 'Test message']);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($this->callback(function ($entity) {
                return $entity instanceof Message
                    && $entity->getText() === 'Test message'
                    && $entity->getStatus() === MessageStatus::SENT;
            }));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->handler->__invoke($message);
    }
}