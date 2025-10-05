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
   $ php artisan php artisan key:generate
