<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\DTO\LogIngestEntry;
use App\DTO\LogIngestInput;
use App\Enum\LogLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LogIngestInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidEntryProducesNoViolations(): void
    {
        $entry = new LogIngestEntry(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: LogLevel::ERROR,
            service: 'auth-service',
            message: 'User authentication failed',
            context: ['user_id' => 123],
            traceId: 'abc123def456',
        );

        self::assertCount(0, $this->validator->validate($entry));
    }

    public function testNullOptionalFieldsAreValid(): void
    {
        $entry = new LogIngestEntry(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: LogLevel::ERROR,
            service: 'auth-service',
            message: 'User authentication failed',
            context: null,
            traceId: null,
        );

        self::assertCount(0, $this->validator->validate($entry));
    }

    /**
     * @return iterable<string, array{LogIngestEntry, string}>
     */
    public static function invalidEntryProvider(): iterable
    {
        yield 'blank timestamp' => [
            new LogIngestEntry(
                timestamp: '',
                level: LogLevel::ERROR,
                service: 'auth-service',
                message: 'msg',
            ),
            'timestamp',
        ];

        yield 'invalid timestamp format' => [
            new LogIngestEntry(
                timestamp: '2026/02/26 10:30:45',
                level: LogLevel::ERROR,
                service: 'auth-service',
                message: 'msg',
            ),
            'timestamp',
        ];

        yield 'blank service' => [
            new LogIngestEntry(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: LogLevel::ERROR,
                service: '',
                message: 'msg',
            ),
            'service',
        ];

        yield 'blank message' => [
            new LogIngestEntry(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: LogLevel::ERROR,
                service: 'auth-service',
                message: '',
            ),
            'message',
        ];
    }

    #[DataProvider('invalidEntryProvider')]
    public function testInvalidEntryProducesViolationOnExpectedPath(
        LogIngestEntry $entry,
        string $expectedPropertyPath,
    ): void {
        $violations = $this->validator->validate($entry);

        self::assertGreaterThan(0, \count($violations));
        self::assertSame($expectedPropertyPath, $violations->get(0)->getPropertyPath());
    }

    public function testValidInputProducesNoViolations(): void
    {
        $input = new LogIngestInput(logs: [
            new LogIngestEntry(
                timestamp: '2026-02-26T10:30:45+00:00',
                level: LogLevel::ERROR,
                service: 'auth-service',
                message: 'User authentication failed',
            ),
        ]);

        self::assertCount(0, $this->validator->validate($input));
    }

    public function testExactly1000LogsIsValid(): void
    {
        $entry = new LogIngestEntry(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: LogLevel::INFO,
            service: 'svc',
            message: 'msg',
        );

        self::assertCount(
            0,
            $this->validator->validate(
                new LogIngestInput(logs: \array_fill(0, 1000, $entry)),
            ),
        );
    }

    public function testEmptyLogsArrayProducesViolation(): void
    {
        $violations = $this->validator->validate(new LogIngestInput(logs: []));

        self::assertGreaterThan(0, \count($violations));
        self::assertSame('logs', $violations->get(0)->getPropertyPath());
    }

    public function testOver1000LogsProducesViolation(): void
    {
        $entry = new LogIngestEntry(
            timestamp: '2026-02-26T10:30:45+00:00',
            level: LogLevel::INFO,
            service: 'svc',
            message: 'msg',
        );

        $violations = $this->validator->validate(
            new LogIngestInput(logs: \array_fill(0, 1001, $entry)),
        );

        self::assertGreaterThan(0, \count($violations));
    }

    public function testAssertValidCascadesIntoEntries(): void
    {
        $input = new LogIngestInput(logs: [
            new LogIngestEntry(
                timestamp: '',
                level: LogLevel::ERROR,
                service: '',
                message: '',
            ),
        ]);

        $violations = $this->validator->validate($input);

        self::assertGreaterThan(0, \count($violations));
        self::assertStringStartsWith('logs[0].', $violations->get(0)->getPropertyPath());
    }
}
