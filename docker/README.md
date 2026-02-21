# CCNA Trainer — Infrastructure (Docker)

Запускает сервисы: **PostgreSQL 17**, **Redis 7**, **Mailpit**.

## Быстрый старт

```bash
cd d:\git\ccna\docker

# Скопировать переменные окружения
cp .env.example .env

# Запустить все сервисы
docker compose up -d

# Проверить статус
docker compose ps
```

## Сервисы

| Сервис | Порт | Описание |
|--------|------|----------|
| PostgreSQL | `5432` | Основная БД |
| Redis | `6379` | Кэш / Очереди / Сессии |
| Mailpit SMTP | `1025` | SMTP-ловушка для dev |
| Mailpit UI | `8025` | Web-интерфейс писем → http://localhost:8025 |

## Управление

```bash
# Остановить
docker compose stop

# Остановить и удалить контейнеры
docker compose down

# Удалить с данными (ОСТОРОЖНО: сотрёт БД)
docker compose down -v

# Логи конкретного сервиса
docker compose logs -f postgres
docker compose logs -f redis
```

## Подключение к PostgreSQL

```bash
docker exec -it ccna_postgres psql -U postgres -d ccna_trainer
```

## Подключение к Redis

```bash
docker exec -it ccna_redis redis-cli
```
