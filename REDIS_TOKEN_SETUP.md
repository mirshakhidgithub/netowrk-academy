# Redis Token Storage Implementation

## Overview

This implementation uses Redis to cache and validate authentication tokens, improving performance compared to purely database-driven token storage.

## Architecture

### Components

1. **RedisTokenRepository** (`app/Auth/RedisTokenRepository.php`)
   - Manages token storage and validation in Redis
   - Handles token verification and expiration
   - Provides user lookup from tokens

2. **AuthController** (`app/Http/Controllers/Auth/AuthController.php`)
   - Uses `RedisTokenRepository` to cache tokens
   - Maintains Sanctum for database token storage
   - Dual storage: tokens exist in both DB (source of truth) and Redis (cache)

3. **ChangePasswordController** (`app/Http/Controllers/Auth/ChangePasswordController.php`)
   - Clears Redis cache when password changes
   - Invalidates all other sessions

4. **PruneExpiredTokens Command** (`app/Console/Commands/PruneExpiredTokens.php`)
   - Scheduled daily at 2 AM to clean up expired tokens
   - Removes expired entries from database

## Configuration

### .env Setup

```dotenv
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Cache Configuration

Default cache is now Redis in `config/cache.php`:
```php
'default' => env('CACHE_STORE', 'redis'),
```

### Session Configuration

Sessions now use Redis in `config/session.php`:
```php
'driver' => env('SESSION_DRIVER', 'redis'),
```

## Token Flow

### Registration
1. User registers with email/password
2. Sanctum creates token in database
3. Token is cached in Redis for fast validation
4. Token returned to frontend

### Login
1. User authenticates with credentials
2. Previous tokens revoked from database
3. Redis cache cleared for user
4. New token created and cached in Redis
5. Token returned to frontend

### Logout
1. Token removed from Redis cache immediately
2. Token deleted from database
3. Session terminated

### Password Change
1. All other tokens revoked from database
2. Redis cache cleared for user
3. Current session invalidated

## Performance Benefits

- **Faster token validation**: Redis lookups are faster than database queries
- **Reduced database load**: Cache layer reduces database hits
- **Better scalability**: Redis can handle millions of tokens efficiently
- **Session performance**: Redis sessions faster than database sessions

## Maintenance

### Manual Token Pruning

```bash
php artisan tokens:prune
```

### Scheduled Pruning

Configured in `app/Console/Kernel.php` to run daily at 2 AM.

### Clear All Tokens (Emergency)

```bash
php artisan cache:clear
```

## Fallback & Reliability

- Tokens exist in database as source of truth
- If Redis is unavailable, tokens still work (slower, from DB)
- No single point of failure

## Token Expiration

Default TTL: 30 days (43200 minutes)

Configure in `config/sanctum.php`:
```php
'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 43200),
```

## Security Considerations

1. **Token Hashing**: Tokens are SHA-256 hashed before storage
2. **Single Session**: Login revokes previous tokens
3. **Password Change**: Session invalidation on password change
4. **Rate Limiting**: Login endpoint limited to 5 attempts/minute
5. **CSRF Protection**: Enabled for session-based endpoints

## Docker Setup

Redis is configured in `docker/compose.yaml`. To start:

```bash
docker-compose up -d redis
```

## Troubleshooting

### Redis Connection Issues

Check Redis is running:
```bash
redis-cli ping
```

Should return: `PONG`

### Tokens Not Being Cached

1. Verify Redis connection in `.env`
2. Check Redis is accepting connections
3. Review logs: `storage/logs/laravel.log`

### Sessions Not Working

1. Restart Redis: `docker-compose restart redis`
2. Clear cache: `php artisan cache:clear`
3. Check logs for connection errors
