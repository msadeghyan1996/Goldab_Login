#  Mobile Login / Registration

##  Overview
This project implements a secure **mobile-based login and registration system** using **OTP verification** and **password-based authentication**.

- If the user **already exists** → they log in using their password.
- If the user **is new** → they receive an OTP, verify it, complete their profile, and set a password.

---

## Features
-  Mobile-based registration and login
-  Password-based login for existing users
-  Secure OTP generation and hashing
-  OTP expiration, retry, and block mechanisms
-  Repository pattern for clean architecture
-  Compliance SOLID Principle
-  Authentication with Laravel Sanctum

---

##  Project Structure
```
app/
├── Http/
│ └── Controllers/
│ ├── VerificationController.php
│ └── UserController.php
├── Models/
│ ├── User.php
│ └── Verification.php
└── Src/
├── Verification/
│     ├──Contracts/
│       └── VerificationContract.php
|     ├──Repositories/
│        └── VerificationRepository.php
├── User/
│     └─Contracts/
│       └── UserContract.php
|     └──Repositories/
│        └── UserRepository.php
```
---

##  Installation

###  Install
```bash
git clone https://github.com/your-repo/mobile-login.git
cd mobile-login
composer install
cp .env.example .env
php artisan key:generate
```
---

### Configure Database

```
Update your .env file:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=otp_demo
DB_USERNAME=root
DB_PASSWORD=
```
--- 

### API Endpoints
#### 1 - Create Verification / Login
```json
POST /api/verification/create

{
  "phone": "09123456789",
  // "password": "pass123"   // for old users 
}

// if user exists add filed => "password" => [pass]
// if user not exists just use field => "phone" => [phone]
```

1.1 - Response Existing User
```json
{
  "message": "ورود با موفقیت انجام شد.",
  "status": "success",
  "token": "AUTH_TOKEN"
}
```

1.2 - Response New User

```json
{
  "message": "کد تایید ارسال گردید.",
  "phone": "09123456789",
  "status": "Created",
  //"code": "123456"  // for dev mode
}
```

#### 2 - Verify OTP
```json
POST /api/verification/verify

{
  "phone": "09123456789",
  "code": "123456"
}

```
2.1 Response Verify
```json
{
  "message": "لطفاً پروفایل خود را تکمیل کنید.",
  "status": "incomplete",
  "user_id": 3,
  "token": "TEMP_PROFILE_TOKEN"
}
```
---
### 3 - Complete Profile

```json
POST /api/user/complete-profile
Authorization: Bearer TEMP_PROFILE_TOKEN

{
  "first_name": "arash",
  "last_name": "narimani",
  "national_id": "1362960586",
  "password": "pass123"
}
```
3.1 Response Complete Profile

```json
{
  "message": "پروفایل شما با موفقیت تکمیل شد.",
  "status": "success",
  "token": "FINAL_AUTH_TOKEN"
}
```
---
## Security & Scalability
### 1 . Hashing
### 2 . Expiration
### 3 . Rate Limiting
### 4 . Token Authentication
### 5 . Scalable Architecture

### note :
In development mode, OTP codes are logged in storage/logs/laravel.log
