<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Message;
use App\Enum\MessageStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
/**
 * TODO: Cover with a test
 */
class SendMessageHandler
{
    public function __construct(private EntityManagerInterface $manager)
    {
    }
    
    public function __invoke(SendMessage $sendMessage): void
    {
        /** removed setting of createdAt and uuid, it is handled by entity constructor
         * added message status enum
         */
        $message = new Message();
        $message->setText((string) $sendMessage->getBody()['text']);
        $message->setStatus(MessageStatus::SENT);

        $this->manager->persist($message);
        $this->manager->flush();
    }
}