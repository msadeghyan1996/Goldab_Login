# Goldab Login — API Only (Laravel 12, PHP 8.3)

Mobile-first authentication flow with phone number and OTP, built on **Laravel 12**, **Sanctum**, **Redis**, and **Horizon**. This README explains **why** the architecture looks the way it does and documents key **security** and **data** decisions.

---

## TL;DR – Why this architecture?

* **Domain-centric layers (Ports & Adapters):** Business logic (use-cases, services, contracts) lives under `app/Domain/Auth`. HTTP controllers are thin and only orchestrate. This keeps the core testable and reusable across **API** and a future **web UI** without duplication.
* **Ephemeral OTP => Redis-only store:** OTPs are short-lived, high-churn, and require atomic counters and TTLs. **Redis** is a perfect fit (native TTL, atomic increment/locks, fast access). We intentionally **avoid persisting OTPs in SQL**.
* **Security built-in:** OTPs are never stored in plaintext; we store `HMAC(otp, APP_KEY)` and compare using constant-time. Rate limiting, attempt counting, and lockout protect against brute force.
* **Queues + Horizon:** OTP issuing/sending is queued to decouple hot paths and absorb spikes. Horizon provides visibility and safe scaling knobs.
* **Sanctum tokens + abilities:** API-only login issues personal access tokens. When profile is incomplete, the token carries the `pending-profile` ability; once completed, it’s upgraded.
* **Versioned API (`/api/v1`):** Backward-compatible changes later won’t break clients.

---

## Architecture

**Major components**

* `app/Domain/Auth/Actions` – Use-cases: `RequestOtp`, `VerifyOtp`, `PasswordLogin`, `CompleteProfile`.
* `app/Domain/Auth/Contracts` – Ports: `OtpStore` (Redis-backed), `OtpSender` (pluggable senders).
* `app/Domain/Auth/DTO` – Data shuttles like `OtpContext` (ip, userAgent, channel).
* `app/Domain/Auth/Enums` – `OtpChannel` = `WEB | API`, `AttemptMethod`, `AttemptResult`.
* `app/Domain/Auth/Events` – `OtpRequested`, `OtpVerified`, `LoginFailed`, `ProfileCompleted`.
* `app/Domain/Auth/Services` – `OtpManager`, `AttemptLogger`, `DigitNormalizer`, `NationalIdValidator`.
* `app/Domain/Auth/Stores` – **Redis** implementation of `OtpStore` (the only runtime store).
* `app/Domain/Auth/Senders` – `LogOtpSender` (dev), `NullOtpSender` (tests). Vendor senders can be added (e.g., Kavenegar/Twilio) without touching domain logic.
* `app/Http/Controllers/Api/V1/Auth` – HTTP endpoints; validate → call actions → JSON.
* `app/Jobs/IssueOtpJob` – Generates code + stores HMAC via `OtpManager` then calls the sender.
* `app/Models/LoginAttempt` – Auditing table for security analytics.
* `app/Providers/AuthFlowServiceProvider` – Binds interfaces to concrete drivers by config.

**Flow overview**

1. Start (`POST /api/v1/auth/request`) → client sends mobile. Server decides the next step:
    - If a user exists **and** has a password → do **not** send OTP; respond `{ next: "password" }`.
    - Otherwise (user missing or exists without password) → **issue OTP** (queued) and respond `{ next: "otp" }`.
2. If `next = "password"` → client calls `POST /api/v1/auth/login` with `{ mobile, password }` → returns full-access token.
3. If `next = "otp"` → client calls `POST /api/v1/auth/verify-otp` with `{ mobile, code }`:
    - On success: if profile incomplete → issue token with ability `pending-profile`.
4. Complete Profile (`POST /api/v1/auth/complete-profile`) → set `first_name`, `last_name`, `national_id` (validated) and optional password → **upgrade** token to full access.

---

## Security decisions

**OTP protection**

* **Never store raw OTP.** We store only `HMAC(OTP, APP_KEY)` using SHA-256. This prevents disclosure at rest.
* **Constant-time compare**: `hash_equals` mitigates timing-based leaks during verification.
* **Short TTL**: Defaults ~5 minutes (configurable). OTP is single-use; on success we delete it.
* **Attempt counting + lockout**: After N failed attempts (default 5), lock the mobile for L seconds (default 900s). Redis atomic increments ensure accuracy under concurrency.
* **Rate limiting**: Named limiters per `ip+mobile` for `/request-otp` and `/verify-otp` (e.g., 5/min, 20/hour; 10/min, 50/hour). This throttles abuse prior to hitting verification code.
* **No user enumeration (measured disclosure)**: The product requirement mandates branching on user existence/password. To limit enumeration risk, `/auth/request` returns only a generic **next-step hint** (`password` or `otp`) without any user details. Combined with rate limiting, attempt counters/lockout, and auditing, exposure is minimized while preserving the required UX.

**Transport vs channel**

* **OtpChannel** indicates **origin** (`WEB | API`), not the transport (SMS/Email). Transport belongs to **sender drivers**. This separation avoids semantic drift and keeps the domain crisp.

**PII minimization & audit**

* **Audit log**: We persist `user_id?`, `mobile?`, `ip`, `user_agent`, `channel`, `method`, `result`, `occurred_at`, `context?`. This enables anomaly detection without storing OTPs or excessive PII.
* **Sensitive defaults**: No OTP logging in production. Secrets in `.env`. Token abilities are least-privilege by default (`pending-profile`).

---

## Data decisions

**Users table**

* `mobile` (**unique**, indexed) – primary credential for this flow.
* `first_name`, `last_name` (nullable) – collected post-OTP for new users.
* `national_id` (**unique**, indexed, nullable) – validated via check-digit algorithm.
* `email` (nullable) – not required for OTP or password login.
* `password` (nullable) – users may remain OTP-only or set a password later.

**Why no SQL table for OTP?**

* OTPs are **ephemeral** and security-sensitive. Redis provides TTL, atomic counters, and low latency. Persisting OTP hashes in SQL increases operational footprint and potential retention risks without tangible benefit.

**Indexes**

* `users.mobile`, `users.national_id` for lookups.
* `login_attempts.occurred_at` for time-series queries.

---

## API surface (v1)

Primary flow

- POST /api/v1/auth/request
    - Body: { mobile }
    - Response:
        - 200 { next: "password" } if user exists and has a password (no OTP issued), or
        - 200 { next: "otp" } if user is new or has no password (OTP issued asynchronously).
    - Notes: Rate-limited; does not return any user details.

- POST /api/v1/auth/login (password path)
    - Body: { mobile, password }
    - Response: 200 { token } or 422.

- POST /api/v1/auth/verify-otp (otp path)
    - Body: { mobile, code }
    - Response:
        - 200 { token, status: "ok" } if profile complete,
        - 200 { token, status: "pending_profile" } if profile incomplete (token ability: pending-profile),
        - 422 for invalid/expired codes.

- POST /api/v1/auth/complete-profile (auth required, ability pending-profile)
    - Body: { first_name, last_name, national_id, password? }
    - Response: 200 { token, status: "ok" } (upgraded abilities).

- GET /api/v1/me – return current user.
- POST /api/v1/auth/logout – revoke current token.

Validation rules
- mobile: strict regex for IR format (e.g., ^09\d{9}$).
- code: numeric, exact digits (configurable length).
- national_id: valid check-digit; reject trivial repeats (e.g., all zeros).

Error shape
- Consistent JSON envelopes (HTTP 422 for validation). Messages avoid leaking whether a user exists beyond the **next-step hint** required by the product flow.

---

## Configuration

`.env` keys (suggested defaults):

```env
# Core
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# OTP
OTP_LENGTH=6
OTP_TTL=300
OTP_ATTEMPTS=5
OTP_LOCK=900
OTP_SENDER=log   # production must NOT log OTP values
```

`config/otp.php` centralizes OTP length/ttl/attempts/lock seconds and sender selection. `AuthFlowServiceProvider` binds `OtpStore` to **Redis** and `OtpSender` to the chosen sender.

---

## Operations & scaling

* **Horizon**: Monitor queues, tag OTP jobs (`auth`, `otp`, `mobile:<number>`), and scale workers safely. Restrict dashboard to local/dev.
* **Stateless API**: Horizontal scale is straightforward; Redis centralizes OTP state.
* **Resilience**: OTP issuing is idempotent; a new request invalidates the previous code for the same mobile.
* **Observability**: Audit logs form a lightweight security trail without storing OTP data.

---

## Local development

   ```bash
   composer install
   cp .env.example .env && php artisan key:generate
   # Configure DB + Redis
   php artisan migrate
   
   php artisan horizon
   php artisan serve
   ```

---

## Testing

```bash
php artisan test
```

* **Unit**: `NationalIdValidator`, `DigitNormalizer`, and `OtpStore` (with an in-memory fake for speed).
* **Feature**: API happy path (request→verify), invalid/expired OTP, lockout, rate limits, `pending-profile` upgrade flow, user creation.

---

## Trade-offs & alternatives

* **Redis-only OTP store**: Simpler, safer, and faster than SQL for ephemeral secrets. If a compliance regime demands row-level visibility, consider logging **events**, not storing OTPs in SQL.
* **Sender drivers vs channels**: Channels describe origin (`WEB|API`). Transport is a pluggable **sender** (log/null/vendor). This separation keeps the domain clean.

---

## Transparency on tooling

Parts of the scaffolding and documentation were accelerated using AI-assisted tooling. Architectural choices (Redis-only OTP, domain layering, token abilities, security controls) are deliberate and documented above. The codebase is structured so future contributors can reason about and extend it without depending on AI tools.
