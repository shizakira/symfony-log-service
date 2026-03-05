# Log Service

API для сбора логов от агентов с асинхронной отправкой в RabbitMQ через Symfony Messenger.

## Запуск

1. Создать `.env` из `.env.example`
2. `make init`
3. `make up`

## API

**POST** `/api/logs/ingest`

**Request**
```json
{
  "logs": [{
    "timestamp": "2026-02-26T10:30:45Z",
    "level": "error",
    "service": "auth-service", 
    "message": "User authentication failed",
    "context": {"user_id": 123},
    "trace_id": "abc123def456"
  }]
}
```

**Response** `202`
```json
{
  "status": "accepted", 
  "batch_id": "batch_550e8400e29b41d4a716446655440000",
  "logs_count": 1
}
```

## Примеры запросов
### 1. Один лог error
```bash
curl -X POST http://localhost:8337/api/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [{
      "timestamp": "2026-02-26T10:30:45Z",
      "level": "error",
      "service": "auth-service",
      "message": "User authentication failed",
      "context": {"user_id": 123},
      "trace_id": "abc123def456"
    }]
  }'
```
### 2. Batch из 3 логов (разные уровни)
```bash
curl -X POST http://localhost:8337/api/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [
      {
        "timestamp": "2026-02-26T10:30:45Z",
        "level": "error",
        "service": "api-gateway",
        "message": "Request timeout",
        "context": {"endpoint": "/api/users"},
        "trace_id": "trace-001"
      },
      {
        "timestamp": "2026-02-26T10:30:45Z", 
        "level": "info",
        "service": "auth-service",
        "message": "User logged in",
        "context": {"user_id": 456},
        "trace_id": "trace-002"
      },
      {
        "timestamp": "2026-02-26T10:30:45Z",
        "level": "warning",
        "service": "db-service",
        "message": "Slow query detected",
        "context": {"query_time": "2.3s"},
        "trace_id": "trace-003"
      }
    ]
  }'
```
### 3. Минимальный лог (только обязательные поля)
```bash
curl -X POST http://localhost:8337/api/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [{
      "timestamp": "2026-02-26T10:30:45Z",
      "level": "info",
      "service": "test-service",
      "message": "test message"
    }]
  }'
```