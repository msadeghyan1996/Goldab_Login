## OTP Service: Architecture, Security, and Data Decisions

### Overview
- Issues OTP codes for login/register, verifies them, then authenticates with JWT and guides the user to complete verification or access the panel.

### Architecture
- **Endpoints**:
  - POST `/api/otp/send`: issues OTP (throttled via `otp-send`).
  - POST `/api/otp/verify`: validates code, consumes OTP, returns JWT and `next_step`.
  - POST `/api/verification` (auth:jwt): stores `national_id`, `first_name`, `last_name`.
  - GET `/api/panel` (auth:jwt + `user.verified`): welcome message.
- **Flow**:
  - Issue: `SendOtpService` generates code, `OtpToken` mutator hashes it, row persisted, token cached until expiry, SMS logged.
  - Verify: `ValidateOtpCode` checks cache, device/IP, attempts, and code hash; controller consumes OTP and issues JWT via `UserService`.
- **Composition**: Controllers thin; business logic in services; validation in `FormRequest` + rule; rate limits in `RouteServiceProvider`.

### Security Decisions
- **OTP storage**: Only a hash is stored: `sha256(pepper + salt + code)` with a per-record random salt and app-level pepper.
- **Attempt limiting**: `attempts_count <= max_attempts`; attempts are incremented during validation.
- **Binding**: Verification requires the same `request_ip` and `X-Device-Id` used at issuance.
- **Rate limiting**: Hourly limit per IP and resend cooldown per IP and per phone; returns standardized JSON with `Retry-After`.
- **JWT auth**: `api` guard uses `tymon/jwt-auth`; TTL from `config/jwt.php`; subject locked; blacklist enabled.
- **Accept header**: Forced to `application/json` for consistent API responses.
- **Account enumeration resistance**: `POST /api/v1/otp/send` always returns the same 201 response shape regardless of whether the phone maps to an existing user; the computed purpose (REGISTER vs LOGIN) is not exposed. Rate-limit failures use a shared 429 JSON. `POST /api/v1/otp/verify` requires possession of a valid OTP, and its validation failures are generic (e.g., `code_not_exists`) rather than revealing account presence. Therefore, access to only the `send` and `verify` routes does not allow an attacker to determine whether a given phone number has an account.

### Data Model & Retention
- **Table `otp_tokens`**:
  - Fields: `phone`, `code_hash`, `salt`, `purpose`, `attempts_count`, `max_attempts`, `expires_at`, `consumed_at`, `request_ip`, `device_id`, timestamps.
  - Indexes on `phone`, `expires_at`, `consumed_at` for latest-active lookups and cleanup.
- **Cache**: `otp-token:{phone}` holds the latest token until `expires_at` (no extra grace in cache).
- **Cleanup**: `php artisan otp:cleanup` deletes tokens older than `config('otp.cleanup_days')` (default 2 days).
- **User model**: Minimal fields; verification considered complete when `national_id` is present.

### Configuration
- `config/otp.php`:
  - `length`: OTP digits (default 6)
  - `ttl_minutes`: code lifetime (default 2)
  - `max_attempts`: max verify attempts per code (default 5)
  - `resend_cooldown_seconds`: resend cooldown (default 120)
  - `max_sends_per_hour`: per-IP hourly send limit (default 5)
  - `cleanup_days`: data retention for OTP rows (default 2)
- Environment: set corresponding `OTP_*` variables to override defaults.


### Threat Model & Mitigations
- **Brute-force of codes**: Short TTL, per-code attempt limits, resend cooldowns, and per-IP hourly caps.
- **Relay/reuse**: OTPs are one-time; consumed tokens are invalidated; only latest active OTP is accepted; cache cleared on consumption.
- **Database compromise**: Only salted+peppered hashes are stored; plaintext codes never persist; salts are per-record.
- **Session theft**: JWT TTLs are enforced; blacklist enabled; subject lock prevents cross-model token reuse.
- **Device/IP spoofing trade-off**: Binding to `request_ip` and `X-Device-Id` reduces abuse but may block users behind changing networks; error messaging advises retry without VPN.

### OTP Generation & Validation
- **Generation**: `random_int` for uniform, cryptographically secure digits; zero-padded to fixed length from config.
- **Hashing**: `sha256(pepper + salt + code)` with 16-byte random salt (hex) via model mutator.
- **Validation**: Timing-safe compare via `hash_equals`; attempts incremented on each check; fail fast on IP/device mismatch.

### Caching Strategy
- **Why cache latest token**: Fast path for validation and a single source of truth for "latest-only" semantics.
- **Backend**: Redis (default) via Laravel cache; keys expire at `expires_at` to avoid extra cleanup.
- **On issue**: New token is written to DB and set in cache; previous code becomes effectively invalid.
- **On consume**: Cache is forgotten to prevent reuse.

### JWT & Auth Decisions
- **Guard**: `api` uses `tymon/jwt-auth` for stateless mobile/web clients.
- **TTL**: Configurable; encourages periodic re-auth; aligns with typical mobile app patterns.
- **Blacklist & subject lock**: Reduce replay and cross-model impersonation risks.

### Data Retention & Privacy
- **PII**: Minimal user fields (first/last name, phone, optional national_id). No OTP plaintext stored.
- **Retention**: OTP rows cleaned after `cleanup_days` (default 2). Consider legal/compliance needs before extending.
- **Logs**: Development `SmsService` logs code to app logs; replace in production with a real SMS provider and disable code logging.

### Rate Limiting Rationale
- **Per-IP hourly cap**: Thwarts mass enumeration from a single origin.
- **Resend cooldown (IP & phone)**: Prevents rapid code requests and SMS spam.
- **Standard responses**: JSON with `Retry-After` for client-side UX/backoff.

### Reliability & Edge Cases
- **Clock skew**: Uses server time (`now()`); tests leverage `Date::setTestNow()`.
- **Concurrent requests**: Latest-only semantics avoid older code acceptance after resend; DB remains source of truth.
- **Idempotency**: Verify endpoint consumes on success; repeated attempts with same code will fail post-consumption.

### Extensibility Points
- **SMS providers**: Swap `SmsService` implementation (Twilio, AWS SNS, etc.).
- **Purposes/flows**: Extend `OtpPurpose` enum and branch behavior per purpose.
- **Additional verification**: Add fields/steps in `VerificationController` without touching OTP core.
- **GUARD/ALGOS**: Switch JWT algorithms/claims in `config/jwt.php` as needed.

### Known Limitations
- **IP/device binding**: May affect roaming users; tune or disable per business needs.
- **Single latest OTP**: Only the most recent unexpired token is valid; coordinate UX to avoid confusion after resends.
- **SMS delivery**: Out-of-band reliability depends on provider; consider delivery receipts/backoff in production.

### Testing Strategy
- **Feature tests**: End-to-end send/verify, limits, cache semantics, and error messages.
- **Unit tests**: OTP model hashing, services, and rule behaviors.
- **Determinism**: Time and config overridden in tests for stable assertions.


