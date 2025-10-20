
## Docker

Run the stack with Postgres, Redis, PHP-FPM, and Nginx.

1) Build and start

```bash
docker compose up -d --build
```

2) App is available at http://localhost:8080

3) First run initializes:

- Composer install
- php artisan key:generate
- php artisan jwt:secret
- Migrations

4) Useful commands

```bash
# Tail logs
docker compose logs -f app
docker compose logs -f web

# Run tests
docker compose exec app php artisan test

# Run migrations
docker compose exec app php artisan migrate --force
```

## Architecture and Rationale

- **Goal**: Provide a secure OTP flow for login/registration, then issue JWTs and guide users to complete profile verification.

- **API surface** (`routes/api/v1.php`):
  - `POST /api/v1/otp/send` → `OtpController@send`
  - `POST /api/v1/otp/verify` → `OtpController@verify`
  - `POST /api/v1/verification` (auth: `api`) → `VerificationController@verification`
  - `GET /api/v1/panel` (auth: `api` + `user.verified`) → `PanelController@index`

- **Layering**:
  - Thin controllers in `app/Http/Controllers/**` keep HTTP concerns separate from business logic.
  - Core logic lives in services:
    - `app/Services/Otp/SendOtpService.php`: generate/persist OTP, cache, dispatch SMS.
    - `app/Services/Otp/VerifyOtpService.php`: load latest active OTP, validate, consume, attempt tracking.
    - `app/Services/UserService.php`: find-or-create user by phone and issue JWT.
  - Validation via `FormRequest`s and a custom rule:
    - `SendRequest`, `VerifyRequest`, and `Rules/Otp/ValidateOtpCode` (enforces IP/device binding, attempts, hash match).

- **Data model** (`app/Models/OtpToken.php` + migration):
  - Stores `phone`, `code_hash`, `salt`, `purpose`, `attempts_count`, `max_attempts`, `expires_at`, `consumed_at`, `request_ip`, `device_id`.
  - Mutator writes `code_hash` as `sha256(pepper + salt + code)` and generates a per-record random `salt`.
  - Reasoning: never store plaintext codes; per-record salts defeat rainbow tables; app-level pepper adds defense-in-depth.

- **Security choices**:
  - **Attempt limiting**: `attempts_count <= max_attempts` (configurable via `config/otp.php`).
  - **Binding**: Verification requires the same `request_ip` and `X-Device-Id` used at issuance to reduce relay abuse.
  - **Rate limiting**: `RouteServiceProvider` defines `otp-send` limits (per-hour IP and resend cooldown per IP/phone) returning standardized JSON via `CustomResponse`.
  - **Cache**: Latest OTP cached until `expires_at` via `OtpCache` (Redis) for fast lookups and to ensure only the newest OTP is valid.

- **Authentication**:
  - JWT via `tymon/jwt-auth` on the `api` guard (`config/auth.php`, `config/jwt.php`).
  - Reasoning: stateless mobile-friendly API, clear TTLs, blacklist support, subject locking enabled.

- **Middleware**:
  - `AcceptJsonMiddleware` forces `Accept: application/json` for consistent API responses.
  - `EnsureUserVerified` prevents panel access until profile verification is completed.

- **Configuration**:
  - `config/otp.php` exposes length, TTL, attempts, cooldowns, hourly limits, pepper, and cleanup days; all overrideable via env.

- **Operations**:
  - Console task `otp:cleanup` removes old OTPs; scheduled daily at 02:30 in `bootstrap/app.php`.
  - Dockerized stack: PHP-FPM app, Nginx, Postgres, Redis; a one-shot `migrate` service ensures schema readiness.
  - Healthchecks ensure Nginx waits for PHP-FPM; entrypoint sets app key and JWT secret.

- **Testing**:
  - Feature tests cover send/verify flows, limits, and edge cases (`tests/Feature/Otp/*`).
  - Unit tests cover services, rule behavior, and the OTP model mutator.

- **Trade-offs and extensions**:
  - SMS dispatch is logged via `SmsService` for local/dev; swap with a real provider without changing business logic.
  - Default OTP length (6) and short TTL balance UX and security; adjust via env for your threat model.
  - Add additional verification fields or steps in `VerificationController` without altering OTP core.

For deeper details, see `README-DECISIONS.md` and the inline docblocks in the referenced classes.
