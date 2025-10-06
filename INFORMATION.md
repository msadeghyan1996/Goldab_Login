# Project Information

## Running Tests

Enter a shell inside the `api` service and run the Laravel test suite:

```bash
# open a shell in the api container
docker compose exec api /bin/sh

# inside the container run PHPUnit through artisan
php artisan test
```

## Architecture

- **Selected architecture:** _Service Layer_ pattern.
- **Framework default:** Laravel's built-in **MVC** remains the foundational structure; controllers coordinate requests and delegate complex business logic to services in the service layer.

## OTP Handling

- There is **no dedicated database table** for OTPs.
- OTPs are **stored and read entirely from Redis** for speed and ephemeral persistence.

## Security & Validation

- Common web security best practices are applied where possible:

  - Protections against **SQL injection** are enforced by using parameterized queries / Eloquent ORM and avoiding raw concatenated SQL.
  - **XSS** mitigations are applied by escaping output in views and using safe rendering methods for user-supplied content.
  - Standard **validation rules** are applied on incoming requests (both server-side Laravel validation and additional checks in services where needed).

- **Rate limiting:** Users cannot request (per IP or per phone number) more than **once per minute**. This prevents abuse of endpoints such as OTP sending.
- **Brute‑force protection:** Password endpoints are hardened so attackers cannot easily brute-force credentials (rate limiting, account lockout strategies, and monitoring applied).

## Frontend

- Frontend stack uses **Livewire** for reactive server-driven components.
- UI components are built with **DaisyUI** on top of Tailwind; design follows a **minimal** aesthetic.

## Internationalization

- For ordinary strings it would be preferable to use **Laravel Localization** for multi-language support, but this was deferred due to time constraints.

## Code Quality & Organization

- Core principles of **SOLID** are followed as much as practical within project constraints.
- Complex operations related to the `User` model are extracted into a dedicated **User service** to keep models and controllers thin.

## Queues & Background Processing

- **Laravel Horizon** is used to manage queues and visualize queue metrics.
- **Supervisor** is used to keep Horizon and the scheduler running reliably in production.

## Performance

- The application runs on **Laravel Octane** with **FrankenPHP**, which avoids reloading PHP files on every request and substantially improves throughput and latency.

---

> If you want, I can add small code snippets (rate limiting middleware example, Redis OTP example, or a Supervisor unit file for Horizon)."
