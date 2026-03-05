<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * RFC 5424 log levels
 */
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';

    /**
     * Severity (0=Emergency, 7=Debug) инвертируем для RabbitMQ,
     * где большее число = выший приоритет
     */
    public function priority(): int
    {
        return match ($this) {
            self::EMERGENCY => 7,
            self::ALERT => 6,
            self::CRITICAL => 5,
            self::ERROR => 4,
            self::WARNING => 3,
            self::NOTICE => 2,
            self::INFO => 1,
            self::DEBUG => 0,
        };
    }
}
