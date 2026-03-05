<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

readonly class LogIngestInput
{
    /**
     * @param LogIngestEntry[] $logs
     */
    public function __construct(
        #[Assert\Count(min: 1, max: 1000)]
        #[Assert\Valid]
        public array $logs,
    ) {}
}
