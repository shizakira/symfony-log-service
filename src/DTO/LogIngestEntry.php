<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\LogLevel;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\SerializedName;

readonly class LogIngestEntry
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
        public string $timestamp,

        #[Assert\NotBlank]
        public LogLevel $level,

        #[Assert\NotBlank]
        public string $service,

        #[Assert\NotBlank]
        public string $message,

        public ?array $context = null,

        #[SerializedName('trace_id')]
        #[Assert\Length(min: 1)]
        public ?string $traceId = null,
    ) {}
}
