<?php

declare(strict_types=1);

namespace App\Message;

/** added an interface for future messages, and to ensure we work with getters and setters later */
class SendMessage implements MessageInterface
{
    /** @param array{ text: string } $body */
    public function __construct(
        protected array $body
    ) {
    }

    /** @return array{ text: string } */
    public function getBody(): array
    {
        return $this->body;
    }

    /** @param array{ text: string } $body */
    public function setBody(array $body): MessageInterface
    {
        $this->body = $body;

        return $this;
    }
}