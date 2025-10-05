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
 
2. Build and start containers
   ```bash
   Before make .env file from .env.example
   docker compose up -d --build 
   ```
3. Access the application
   - Web Application: http://localhost:8008
   - phpMyAdmin: http://localhost:7272
   > phpMyAdmin credentials:
   > - Host: `db`
   >   - Username: `root`
   >   - Password: `root`
4. Run php comment:
   ```bash
   docker exec -it javadApp bash
   $ composer install
   $ php artisan php artisan key:generate
   $ php artisan php artisan migrate
   $ php artisan php artisan test 
   ```

### ✅ Implemented Features
- Laravel 12 project initialized with Docker
- Users table with `name`, `last_name`, `mobile`, `password`, `status`, ...
- OTP table and model for login verification
- Mobile normalization and validation
- Login API with OTP generation and registration flow
- Standardized JSON response (`success`, `statusType`, `message`, `errors`, `data`, `notify`)
- Feature tests with PHPUnit
- 
### 📝 Notes
- `.env` file must exist before starting containers
- OTP codes expire in 3 minutes
- Tests require a fresh database
