<?php

declare(strict_types=1);

namespace App\Message;

interface MessageInterface
{
    /** @return mixed[] */
    public function getBody(): array;

    /** @param mixed[] $body */
    public function setBody(array $body): self;
}