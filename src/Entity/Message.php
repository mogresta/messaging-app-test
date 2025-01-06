<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MessageStatus;
use App\Repository\MessageRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
/**
 * Class revised with minimal modification of the existing code, assuming no new features
 * such as message metadata or attachments were intended to be added, please let me know if this is
 * also needed/wanted and I will revise the solution
 */
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** added unique and nullable false requirements */
    #[ORM\Column(type: Types::GUID, unique: true, nullable: false)]
    private ?string $uuid;

    /** added assertions for not blank and min and max length, added length messages */
    #[ORM\Column(type: Types::TEXT, length: 2000)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: 1,
        max: 2000,
        minMessage: 'Message must be at least {{ limit }} characters long',
        maxMessage: 'Message cannot be longer than {{ limit }} characters',
    )]
    private ?string $text = null;

    /** added enum for status, default draft so that null isn't used, limited length */
    #[ORM\Column(length: 32, enumType: MessageStatus::class)]
    private MessageStatus $status = MessageStatus::DRAFT;

    /** changed to datetime immutable to prevent modification, set to readonly */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private readonly DateTimeImmutable $createdAt;

    /** added updated at by lifecycle callback */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /** uuid and created at set in constructor */
    public function __construct()
    {
        $this->uuid = Uuid::v6()->toRfc4122();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getStatus(): MessageStatus
    {
        return $this->status;
    }

    public function setStatus(MessageStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
