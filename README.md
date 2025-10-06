## 🚀 Project Setup with Docker

This project includes a Dockerized environment to simplify setup and ensure consistency across different machines.

### 🧩 Requirements

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

### ⚙️ How to Start the Project

1. Clone the repository:
   ```bash
   cd Login 
   ```
2. Copy environment file:
   > cp .env.example .env
3. Build and start containers
   ```bash
   Before make .env file from .env.example
   docker compose up -d --build 
   ```
4. Access the application
    - Web Application: http://localhost:8008
    - phpMyAdmin: http://localhost:7272
   > phpMyAdmin credentials:
   > - Host: `db`
       >
    - Username: `root`
   > - Password: `root`
5. Install dependencies and set up Laravel:
   ```bash
   docker exec -it javadApp bash
   $ composer install
   $ php artisan key:generate
   $ php artisan migrate
   $ php artisan test 
   ```

### ✅ Implemented Features

- Laravel 12 project initialized with Docker
- Users table with name, last_name, mobile, password, status, etc.
- OTP model and table for login verification
- Mobile normalization and validation
- Login API with OTP generation and verification flow
- UpdateInfo API to update user profile (name, last_name, password, national_id)
- Standardized JSON responses (success, statusType, message, errors, data, notify)
- Feature tests with PHPUnit:
    - LoginTest
    - OtpVerifyTest
    - UpdateInfoTest
- Unit tests for Iranian national code validation (NationalCodeTest)
- Unit tests for Iranian mobile (MobileTest)

### 📝 Notes

- `.env` file must exist before starting containers
- OTP codes expire in 3 minutes
- National codes are validated for Iranian format
- Passwords are hashed securely using Laravel Hash::make
- Tests require a fresh database sqlite
- Rate limiting is applied to login, OTP verification, and update info endpoints
