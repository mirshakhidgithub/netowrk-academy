# Email Verification & Registration Flow

## Overview

Новая система регистрации включает обязательную верификацию email адреса перед логином. Это предотвращает дубликаты и обеспечивает валидность email адресов.

## Процесс Регистрации

### 1. Frontend - Register Page
- Пользователь заполняет форму регистрации
- **Валидация на фронте:**
  - Имя: 2-255 символов, только буквы/пробелы/дефисы/апострофы
  - Username: опционально, макс 100 символов, только буквы/цифры/дефисы/подчеркивания
  - Email: валидный email формат
  - Password: минимум 8 символов, **обязательно** содержит:
    - Минимум одну прописную букву
    - Минимум одну строчную букву
    - Минимум одну цифру
    - Минимум одного спецсимвола (@$!%*?&)
  - Password Confirmation: должна совпадать с password

### 2. Backend - Registration
```
POST /api/auth/register
```

**Валидация на бэке:**
- Имя: regex проверка
- Username: уникальность (Unique constraint с soft deletes)
- Email: уникальность (Unique constraint с soft deletes) + DNS verification
- Password: regex проверка + confirmed

**Ответ:**
```json
{
  "message": "Registration successful. Please verify your email.",
  "email": "user@example.com"
}
```

**Ошибки:**
```json
{
  "errors": {
    "email": ["This email is already registered."],
    "username": ["This username is already taken."],
    "password": ["Password must contain..."]
  }
}
```

### 3. Frontend - Redirect to Verify Email
После успешной регистрации пользователь редиректится на `/verify-email?email=user@example.com`

## Процесс Верификации Email

### 1. Send Verification Code
```
POST /api/auth/verify-email/send
```

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "message": "Verification code sent successfully",
  "code": "ABC123"  // Only in development
}
```

**Rates:**
- Max 3 resends per 15 minutes
- Code valid 60 minutes
- Max 5 попыток ввода кода

**Offences:**
```json
{
  "message": "Too many verification requests. Please try again later."
}
```

### 2. Verify Code
```
POST /api/auth/verify-email/verify
```

**Request:**
```json
{
  "email": "user@example.com",
  "code": "ABC123"
}
```

**Response:**
```json
{
  "message": "Email verified successfully"
}
```

**Errors:**
```json
{
  "errors": {
    "code": ["Invalid or expired verification code"]
  }
}
```

**Backend действия:**
- Проста code из Redis cache
- Проверяет TTL
- Проверяет количество попыток
- Обновляет `email_verified_at` для пользователя
- Удаляет code из кэша

## Login Flow

### 1. User Attempts Login
```
POST /api/auth/login
```

**Request:**
```json
{
  "email": "user@example.com",
  "password": "Password123!"
}
```

### 2. Backend Checks
1. Фонетические credentials (email + password)
2. Проверяет что `email_verified_at` is NOT NULL
3. Если не верифицирован:
   ```json
   {
     "errors": {
       "email": ["Please verify your email address before logging in"]
     },
     "email": "user@example.com"
   }
   ```

### 3. Frontend Shows Verification Prompt
- Если login вернул ошибку о неверифицированном email
- Shows dialog с кнопкой "Verify Email"
- User кликает → redirect на `/verify-email` page

## Database Changes

### New Migrations

1. **2026_02_27_000001_add_role_to_users_table.php**
   - Добавляет enum `role` (user/client/admin)

2. **2026_02_27_000002_add_soft_deletes_to_users_table.php**
   - Добавляет `deleted_at` для soft deletes
   - Защищает от дубликатов при удалении пользователей

### User Model Changes

- `use SoftDeletes` trait
- `email_verified_at` остается как было (уже в базе)

## Storage (Redis)

Коды верификации хранятся в Redis с TTL:

```
email_verification:{email} → {
  "code": "ABC123",
  "attempts": 0,
  "created_at": "2026-02-27T10:00:00"
}

email_verification_rate:{email} → 1  // Rate limit counter
```

## Security Features

1. **Password Requirements:**
   - Minimum 8 characters
   - Requires uppercase, lowercase, numbers, special chars
   - Confirmed match

2. **Rate Limiting:**
   - Login: 5 attempts per minute per IP
   - Verification code send: 3 per 15 minutes per email
   - Verification attempts: 5 per code

3. **Database Constraints:**
   - `email` UNIQUE (with soft delete support)
   - `username` UNIQUE (with soft delete support)

4. **Email Verification:**
   - Token expires after 60 minutes
   - Code is 6 random characters
   - Invalidated immediately after use

5. **Session Security:**
   - Single session per user (old tokens revoked on new login)
   - Token caching in Redis for fast validation
   - Tokens stored in database as source of truth

## Testing

### Register New User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "username": "johndoe",
    "email": "john@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }'
```

### Send Verification Code
```bash
curl -X POST http://localhost:8000/api/auth/verify-email/send \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com"}'
```

### Verify Email
```bash
curl -X POST http://localhost:8000/api/auth/verify-email/verify \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com", "code": "ABC123"}'
```

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com", "password": "Password123!"}'
```

## Demo Users

After `php artisan db:seed`:

1. **Admin User**
   - Email: `admin@example.com`
   - Password: `Admin123!`
   - Role: admin
   - Email verified: ✅

2. **Admin Demo**
   - Email: `admin@demo.com`
   - Password: `admin`
   - Role: admin
   - Email verified: ✅

3. **Client Demo**
   - Email: `client@demo.com`
   - Password: `client`
   - Role: client
   - Email verified: ✅

## Troubleshooting

### Code Not Sending
1. Check Redis connection
2. Check logs: `storage/logs/laravel.log`
3. In development, code is logged to console

### User Can't Login After Verification
1. Ensure `email_verified_at` is set in database
2. Check user has a role assigned
3. Clear Redis cache: `php artisan cache:clear`

### Duplicate Email Errors
1. Check database for soft-deleted users
2. Run migration: `php artisan migrate`
3. Clear cache if needed

## Environment Variables

No new environment variables needed. Uses existing:
- `CACHE_STORE=redis` (for verification codes)
- `REDIS_HOST`, `REDIS_PORT` (connection)
