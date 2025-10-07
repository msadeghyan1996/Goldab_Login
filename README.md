# OTP Authentication API

A secure mobile authentication system with OTP-based registration and JWT authentication, built with Lumen.

## Architecture

**Layered Architecture** with Repository Pattern:
- **Controllers**: Request handling and validation
- **Services**: Business logic (AuthService, OtpService)
- **Repositories**: Data access abstraction
- **Models**: Database entities (User, OtpCode, LoginAttempt)
- **Channels**: Notification delivery (SMS, Email)
- **Jobs**: Async processing (Queue workers)

**Why this architecture?**
- Clean separation of concerns
- Testable components with dependency injection
- Scalable and maintainable codebase
- Interface-based programming for flexibility

## Security Features

- ✅ **Passwords**: Bcrypt hashing, 8+ characters required
- ✅ **OTP**: SHA-256 hashed, 5-minute expiry, single-use, max 3 attempts
- ✅ **Brute Force**: Account lockout after 5 failed login attempts
- ✅ **Rate Limiting**: 10 requests/min per endpoint, 3 OTP requests/15 min
- ✅ **JWT**: Stateless authentication with 1-hour expiry
- ✅ **Validation**: Strict input validation, SQL injection & XSS protection

## Scalability

- ✅ **Stateless**: JWT authentication, no server sessions
- ✅ **Redis**: Caching and queue management
- ✅ **Async Jobs**: Non-blocking notification delivery
- ✅ **Database**: Indexed columns, optimized queries
- ✅ **Horizontal Scaling**: Load balancer ready

## Installation

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
# Edit .env: set DB credentials, JWT_SECRET, Redis, Twilio

# 3. Run migrations
php artisan migrate

# 4. Start services
redis-server                    # Terminal 1
php artisan queue:work          # Terminal 2
php -S localhost:8000 -t public # Terminal 3
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/check-mobile` | Check if mobile exists |
| POST | `/api/v1/auth/request-otp` | Request OTP code |
| POST | `/api/v1/auth/register` | Complete registration |
| POST | `/api/v1/auth/login` | Login with password |
| GET | `/api/v1/auth/me` | Get user info (protected) |

**API Documentation**: `http://localhost:8000/api/documentation`

## Testing

```bash
# Run all tests (30+ tests, ~90% coverage)
./vendor/bin/phpunit

# Run specific suites
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature
```

**Test Coverage:**
- Unit Tests: OtpService, AuthService
- Feature Tests: Registration flow, Authentication flow
- Security Tests: Validation, rate limiting, brute force protection

## Configuration

Key `.env` variables:

```env
JWT_SECRET=your_jwt_secret
JWT_TTL=3600
OTP_EXPIRY=300
OTP_MAX_ATTEMPTS=3
LOGIN_MAX_ATTEMPTS=5
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
```

## Tech Stack

- **Framework**: Lumen 10.0 (PHP 8.0+)
- **Database**: MySQL 5.7+
- **Cache/Queue**: Redis 5.0+
- **Auth**: JWT (firebase/php-jwt)
- **SMS**: Twilio
- **Testing**: PHPUnit 10.0, Mockery

---

**License**: MIT | **Support**: support@example.com

