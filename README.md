# Laravel Authentication & User Management API

<p align="center">
    <a href="https://laravel.com" target="_blank">
        <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="350">
    </a>
</p>

<p align="center">
    <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel">
    <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php">
    <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql">
    <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge">
</p>

A complete RESTful API for authentication, user management, and role-based access control built using Laravel 12 and Laravel Sanctum.

---

# ✨ Features

### 🔐 Authentication
- Laravel Sanctum Token-based login
- Registration, login, logout
- Change password
- Update profile

### 👥 User Management
- Full CRUD (Admin Only)
- Role-Based Access Control (Admin/User)
- Activity logs
- User analytics

### 📊 Advanced System Capabilities
- Search & filtering
- CSV/JSON export
- Pagination support
- Bulk operations

### 🔒 Security
- Password hashing
- Input validation
- SQL injection prevention
- CORS ready
- Rate limiting ready

---

# 🚀 Quick Start

## Prerequisites
- PHP 8.2+
- Composer
- MySQL 8+
- Laravel 12

---

## Installation

### 1. Clone
```bash
git clone <your-repo-url>
cd auth-app
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_auth
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Migrate & Seed
```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

### 4. Start the Server
```bash
php artisan serve
```

---

# 📚 API Documentation

## 🔓 Public Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/register | Register new user |
| POST | /api/login | Login user |

---

## 🔐 Authenticated User Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/user-profile | Get logged-in user |
| GET | /api/profile | View own profile |
| PUT | /api/profile | Update own profile |
| POST | /api/change-password | Change password |
| POST | /api/logout | Logout user |

---

## 👑 Admin Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/users | List users |
| GET | /api/users/{id} | Get user details |
| PUT | /api/users/{id} | Update user |
| DELETE | /api/users/{id} | Delete user |
| POST | /api/users/{id}/change-role | Update user role |
| POST | /api/users/search | Advanced search |
| GET | /api/users/statistics | User stats |
| GET | /api/users/activity | Activity log |
| PUT | /api/users/bulk-update-roles | Bulk role updates |
| GET | /api/users/export | Export users |
| POST | /api/create-admin | Create admin |

---

# 🎯 Example Requests

### Registration
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{ "first_name":"John","last_name":"Doe","email":"john@example.com","password":"Password@123","password_confirmation":"Password@123" }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{ "email":"admin@example.com","password":"AdminPassword123" }'
```

---

# 🗄️ Database Schema (Users Table)
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    first_login TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

# 🔧 Project Structure
```
auth-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   └── UserController.php
│   ├── Models/
│   └── Providers/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
└── README.md
```

---

# 🧪 Testing

### Default Seeded Accounts
| Role | Email | Password |
|------|--------|-----------|
| Admin | admin@example.com | AdminPassword123 |
| User | user@example.com | UserPassword123 |

Use Postman collection to test all endpoints.

---

# 🛡️ Security Features

✔ Bcrypt password hashing  
✔ Sanctum API tokens  
✔ Role-based permissions  
✔ Validation rules  
✔ CORS configured  
✔ SQL Injection protection  
✔ Rate limiting ready  

---

# 📊 API Response Samples

### Success
```json
{
  "message": "Operation successful",
  "data": {}
}
```

### Error
```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["Invalid email"]
  }
}
```

---

# 🔍 Search & Filter

### GET
```
/api/users?page=1&per_page=20&role=admin&search=john
```

### POST
```json
{
  "search": "john",
  "role": "admin",
  "sort_by": "name",
  "sort_order": "asc"
}
```

---

# 📤 Export

| Format | Endpoint |
|--------|----------|
| JSON | /api/users/export?format=json |
| CSV | /api/users/export?format=csv |

---

# 🚨 Error Codes
| Code | Message | Fix |
|------|---------|-----|
| 401 | Unauthorized | Add valid token |
| 403 | Forbidden | Admin required |
| 404 | Not Found | Invalid ID |
| 422 | Validation error | Fix input |
| 500 | Server error | Check logs |
