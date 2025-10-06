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
   >   - Password: `root`
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
- Login API with OTP generation and registration flow
- Standardized JSON response (success, statusType, message, errors, data, notify)
- Feature tests with PHPUnit
- OTPFactory for testing OTPs
- OtpVerifyTest feature tests
- OTP verification API with 4-digit codes
- Mobile verification flow
- Redirect to registration page if user info incomplete
- OTP expires in 3 minutes

### 📝 Notes

- `.env` file must exist before starting containers
- OTP codes expire in 3 minutes
- Tests require a fresh database
