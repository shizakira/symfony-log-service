<?php

declare(strict_types=1);

namespace App\Message;

use App\DTO\LogIngestEntry;

final readonly class LogIngestMessage
{
    public function __construct(
        public string $batchId,
        public string $publishedAt,
        public int $retryCount,
        public LogIngestEntry $log,
    ) {}
}
