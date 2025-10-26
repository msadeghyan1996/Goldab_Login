# Authentication API

Laravel-based mobile authentication flow that supports password-based login for existing users and OTP-driven registration for new users.

## Architecture & Flow

- Controllers handle the login/registration flow, FormRequests do validation, and `OtpService` keeps the OTP logic in one place.
- `/api/auth/lookup` decides if we show the password screen or start OTP signup. I accept the small enumeration risk for better UX, but the route is throttled.
- OTP signup creates a 6-digit code, hashes it, stores it in cache with TTL, and fires `OtpCodeGenerated`.
- After OTP verification we issue a short-lived registration token. The final POST request to `api/auth/register` creates the user and returns a Sanctum token.
- Password login checks the hashed password and issues a Sanctum token. `/api/auth/me` and `/api/auth/logout` show how protected routes work.

### User Registration Flow

```
Client -> POST /api/auth/lookup (phone)
Client <- { "is_new_user": true, "authentication_type": "otp" }
Client -> POST /api/auth/register/otp/request (phone)
Server -> logs OTP via OtpCodeGenerated listener
Client -> POST /api/auth/register/otp/verify (phone, code)
Client <- { "registration_token": "..." }
Client -> POST /api/auth/register (registration_token, profile, password)
Client <- { "access_token": "...", "token_type": "Bearer" }
```

## Data & State Decisions

- `users` table stores first name, last name, national ID (unique), phone (unique), and password.
- Laravel’s `hashed` cast secures passwords automatically.
- Cache store keeps OTP payloads and pending registration tokens instead of a table.
- Pending registration cache entry stores the verified phone number so we do not recheck OTP on the final step.

## Security & Scalability Considerations

- OTP codes and passwords are always hashed; nothing sensitive is stored in plain text.
- Rate limiting and per-code attempt counters slow brute-force attempts.
- API responses go through `apiResponse()` helper so errors are consistent and easy to handle.
- Events + queued listener keep the request cycle fast and let us drop in a real SMS provider later just by listening to `OtpCodeGenerated`.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
php artisan queue:work
```

OTP codes appear in the logs while the queue worker runs.

## Tests

```bash
php artisan test
```

Feature tests cover lookup, OTP edge cases, password login, registration, rate limiting, and Sanctum token flow.

## Notes

- OTP length, TTL, and rate limits are Configurable in `config/otp.php` or via environment variables.
- SMS provider can be easily integrated by just listening to `OtpCodeGenerated` event.
- Every endpoint returns a structured JSON response using the `apiResponse()` helper.
