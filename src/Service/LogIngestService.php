<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\LogIngestInput;
use App\Message\LogIngestMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final readonly class LogIngestService
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {}

    /**
     * @throws ExceptionInterface
     * @return array{status: string, batch_id: string, logs_count: int}
     */
    public function processIngest(LogIngestInput $input): array
    {
        $batchId = 'batch_' . \substr(Uuid::v4()->toHex(), 2);
        $publishedAt = new \DateTimeImmutable()->format(\DateTimeInterface::ATOM);

        foreach ($input->logs as $log) {
            $this->bus->dispatch(
                new LogIngestMessage($log),
                [
                    new AmqpStamp(
                        'logs.ingest',
                        \AMQP_NOPARAM,
                        [
                            'delivery_mode' => 2,
                            'priority'      => $log->level->priority(),
                            'headers'       => [
                                'batch_id'     => $batchId,
                                'published_at' => $publishedAt,
                                'retry_count'  => 0,
                            ],
                        ],
                    ),
                ],
            );
        }

        return [
            'status'     => 'accepted',
            'batch_id'   => $batchId,
            'logs_count' => \count($input->logs),
        ];
    }
}
