<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Message\LogIngestMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class LogIngestionControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?InMemoryTransport $transport = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient(['environment' => 'test']);
        $this->transport = $this->getContainer()->get('messenger.transport.logs_ingest');
    }

    /**
     * @throws \JsonException
     */
    private function post(array $payload): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/logs/ingest',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT'  => 'application/json',
            ],
            content: \json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    private function validLog(array $override = []): array
    {
        return \array_merge([
            'timestamp' => '2026-02-26T10:30:45+00:00',
            'level'     => 'error',
            'service'   => 'auth-service',
            'message'   => 'User authentication failed',
        ], $override);
    }

    public function testValidPayloadReturns202(): void
    {
        $this->post(['logs' => [$this->validLog()]]);

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }

    public function testResponseStructure(): void
    {
        $this->post([
            'logs' => [
                $this->validLog(),
                $this->validLog(['level' => 'info']),
            ],
        ]);

        $response = $this->client->getResponse();
        $body = \json_decode($response->getContent(), true);

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertIsArray($body);
        self::assertSame('accepted', $body['status']);
        self::assertSame(2, $body['logs_count']);
        self::assertStringStartsWith('batch_', $body['batch_id']);
    }

    public function testEachLogDispatchedAsSeparateMessage(): void
    {
        $this->post([
            'logs' => [
                $this->validLog(),
                $this->validLog(['level' => 'info']),
                $this->validLog(['level' => 'critical']),
            ],
        ]);

        $sent = $this->transport->getSent();
        self::assertCount(3, $sent);
    }

    public function testDispatchedMessageType(): void
    {
        $this->post(['logs' => [$this->validLog()]]);

        $sent = $this->transport->getSent();

        self::assertCount(1, $sent);
        self::assertInstanceOf(LogIngestMessage::class, $sent[0]->getMessage());
    }

    public function testDispatchedMessageMetadata(): void
    {
        $this->post(['logs' => [$this->validLog()]]);

        $response = $this->client->getResponse();
        $body = \json_decode($response->getContent(), true);

        $sent = $this->transport->getSent();
        self::assertCount(1, $sent);

        $message = $sent[0]->getMessage();
        self::assertInstanceOf(LogIngestMessage::class, $message);
        self::assertSame($body['batch_id'], $message->batchId);
        self::assertSame(0, $message->retryCount);
        self::assertNotEmpty($message->publishedAt);
    }

    public function testAllMessagesShareSameBatchId(): void
    {
        $this->post([
            'logs' => [
                $this->validLog(),
                $this->validLog(['level' => 'info']),
            ],
        ]);

        $sent = $this->transport->getSent();
        self::assertCount(2, $sent);

        self::assertSame(
            $sent[0]->getMessage()->batchId,
            $sent[1]->getMessage()->batchId,
        );
    }

    public function testOptionalFieldsAreAccepted(): void
    {
        $this->post([
            'logs' => [
                $this->validLog([
                    'context'  => ['user_id' => 123],
                    'trace_id' => 'abc123def456',
                ]),
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        self::assertCount(1, $this->transport->getSent());
    }

    #[DataProvider('invalidPayloadProvider')]
    public function testInvalidPayloadReturns400(array $payload): void
    {
        $this->post($payload);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertCount(0, $this->transport->getSent());
    }

    public static function invalidPayloadProvider(): iterable
    {
        yield 'empty logs array' => [
            ['logs' => []],
        ];

        yield 'missing logs key' => [
            [],
        ];

        yield 'missing timestamp' => [
            [
                'logs' => [
                    [
                        'level'   => 'error',
                        'service' => 'auth-service',
                        'message' => 'msg',
                    ],
                ],
            ],
        ];

        yield 'missing level' => [
            [
                'logs' => [
                    [
                        'timestamp' => '2026-02-26T10:30:45+00:00',
                        'service'   => 'auth-service',
                        'message'   => 'msg',
                    ],
                ],
            ],
        ];

        yield 'missing service' => [
            [
                'logs' => [
                    [
                        'timestamp' => '2026-02-26T10:30:45+00:00',
                        'level'     => 'error',
                        'message'   => 'msg',
                    ],
                ],
            ],
        ];

        yield 'missing message' => [
            [
                'logs' => [
                    [
                        'timestamp' => '2026-02-26T10:30:45+00:00',
                        'level'     => 'error',
                        'service'   => 'auth-service',
                    ],
                ],
            ],
        ];

        yield 'invalid level' => [
            [
                'logs' => [
                    [
                        'timestamp' => '2026-02-26T10:30:45+00:00',
                        'level'     => 'invalid_level',
                        'service'   => 'auth-service',
                        'message'   => 'msg',
                    ],
                ],
            ],
        ];

        yield 'invalid timestamp format' => [
            [
                'logs' => [
                    [
                        'timestamp' => '2026-02-26 10:30:45',
                        'level'     => 'error',
                        'service'   => 'auth-service',
                        'message'   => 'msg',
                    ],
                ],
            ],
        ];

        yield 'over 1000 logs' => [
            [
                'logs' => \array_fill(0, 1001, [
                    'timestamp' => '2026-02-26T10:30:45+00:00',
                    'level'     => 'error',
                    'service'   => 'auth-service',
                    'message'   => 'msg',
                ]),
            ],
        ];
    }
}
