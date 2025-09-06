<?php

namespace App\Entity;

use DateTimeImmutable;
use DateInterval;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'symfony_demo_access_token')]
class AccessToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, unique: true)]
    private readonly string $token;

    #[ORM\Column(type: Types::INTEGER)]
    private readonly int $user_id;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private readonly DateTimeImmutable $expires_at;

    public function __construct(int $user_id)
    {
        $this->token = bin2hex(random_bytes(32));
        $this->user_id = $user_id;
        $this->expires_at = (new DateTimeImmutable())->add(new DateInterval('P7D'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function isValid(): bool
    {
        return $this->expires_at > new DateTimeImmutable();
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }
}
