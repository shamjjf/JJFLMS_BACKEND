# LeaveFlow — Laravel Backend API

Complete Laravel 10 backend for the LeaveFlow Leave Management System React frontend. Uses **MySQL** database with **Sanctum** token authentication.

---

## Requirements

- PHP 8.1+
- Composer
- MySQL 8.0+
- Node.js (for the React frontend)

---

## Quick Setup

### 1. Install Dependencies

```bash
cd leave-management-backend
composer install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL credentials:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=leave_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Create Database

```sql
CREATE DATABASE leave_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Run Migrations & Seed Data

```bash
php artisan migrate
php artisan db:seed
```

This creates all tables and seeds them with the same demo data your frontend uses (6 employees, 5 leave types, 10 holidays, 6 leave requests, and all leave balances).

### 5. Start the Server

```bash
php artisan serve
```

Backend will run at `http://localhost:8000`

---

## Connect React Frontend

### Step 1: Install axios

```bash
cd leave-management-system
npm install axios
```

### Step 2: Copy Integration Files

From the `frontend-integration/` folder in this backend project:

```bash
# Copy API service
cp frontend-integration/src/services/api.js  ../leave-management-system/src/services/api.js

# Replace the state hook with the API-connected version
cp frontend-integration/src/hooks/useAppState.js  ../leave-management-system/src/hooks/useAppState.js
```

### Step 3: Create `.env` in your React project

```
REACT_APP_API_URL=http://localhost:8000/api
```

### Step 4: Start both servers

```bash
# Terminal 1 — Backend
cd leave-management-backend
php artisan serve

# Terminal 2 — Frontend
cd leave-management-system
npm start
```

---

## API Endpoints

### Authentication

| Method | Endpoint          | Description                    | Auth Required |
|--------|-------------------|--------------------------------|---------------|
| POST   | `/api/login`      | Login with email + password    | No            |
| POST   | `/api/login-as`   | Demo quick-login by user ID   | No            |
| POST   | `/api/logout`     | Logout (revoke token)          | Yes           |
| GET    | `/api/user`       | Get current user               | Yes           |

### Dashboard

| Method | Endpoint          | Description                    |
|--------|-------------------|--------------------------------|
| GET    | `/api/dashboard`  | Dashboard stats (role-aware)   |

### Employees

| Method | Endpoint              | Description                        |
|--------|-----------------------|------------------------------------|
| GET    | `/api/employees`      | List all (supports ?department=, ?search=) |
| GET    | `/api/employees/{id}` | Get single employee                |
| GET    | `/api/departments`    | List departments                   |

### Leave Types

| Method | Endpoint               | Description                    |
|--------|------------------------|--------------------------------|
| GET    | `/api/leave-types`     | List active leave types        |
| PUT    | `/api/leave-types/{id}`| Update config (Admin only)     |

### Leave Requests

| Method | Endpoint                  | Description                           |
|--------|---------------------------|---------------------------------------|
| GET    | `/api/leaves`             | List leaves (?status=, ?employee_id=) |
| POST   | `/api/leaves`             | Apply for leave                       |
| PUT    | `/api/leaves/{id}/cancel` | Cancel pending request (owner only)   |
| PUT    | `/api/leaves/{id}/review` | Approve/reject (HR/Admin only)        |

### Leave Balances

| Method | Endpoint          | Description                           |
|--------|-------------------|---------------------------------------|
| GET    | `/api/balances`   | Get balances (?year=, ?department=)   |

### Holidays

| Method | Endpoint              | Description                    |
|--------|-----------------------|--------------------------------|
| GET    | `/api/holidays`       | List holidays (?year=)         |
| POST   | `/api/holidays`       | Add holiday (HR/Admin only)    |
| DELETE | `/api/holidays/{id}`  | Delete holiday (HR/Admin only) |

### Reports

| Method | Endpoint                  | Description                    |
|--------|---------------------------|--------------------------------|
| GET    | `/api/reports/employee`   | Employee-wise report           |
| GET    | `/api/reports/department` | Department summary             |
| GET    | `/api/reports/monthly`    | Monthly trend + stats          |

---

## Demo Accounts

All accounts use password: `password`

| Email               | Role     | Name          |
|---------------------|----------|---------------|
| admin@company.com   | Admin    | Admin User    |
| priya@company.com   | HR       | Priya Mehta   |
| arjun@company.com   | Employee | Arjun Sharma  |
| ravi@company.com    | Employee | Ravi Kumar    |
| sneha@company.com   | Employee | Sneha Patel   |
| deepak@company.com  | Employee | Deepak Nair   |

---

## Database Schema

```
users              → id, name, email, password, department, role, avatar, manager_id
leave_types        → id, code, name, color, annual_limit, carry_forward, is_active
leave_requests     → id, employee_id, leave_type_id, start_date, end_date, days, reason, status, applied_on, approved_by, comments
leave_balances     → id, employee_id, leave_type_id, balance, year
holidays           → id, name, date, type
personal_access_tokens → (Sanctum default)
```

---

## Project Structure

```
leave-management-backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php          # Login, logout, user
│   │   │   ├── DashboardController.php     # Dashboard stats
│   │   │   ├── EmployeeController.php      # Employee listing
│   │   │   ├── HolidayController.php       # Holiday CRUD
│   │   │   ├── LeaveBalanceController.php  # Balance queries
│   │   │   ├── LeaveRequestController.php  # Apply, cancel, review
│   │   │   ├── LeaveTypeController.php     # Leave type config
│   │   │   └── ReportController.php        # Analytics reports
│   │   ├── Kernel.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── Holiday.php
│   │   ├── LeaveBalance.php
│   │   ├── LeaveRequest.php
│   │   ├── LeaveType.php
│   │   └── User.php
│   └── Providers/
├── config/
├── database/
│   ├── migrations/                          # 6 migration files
│   └── seeders/DatabaseSeeder.php           # All demo data
├── frontend-integration/                    # Files to copy into React project
│   └── src/
│       ├── hooks/useAppState.js             # Updated hook (API version)
│       └── services/api.js                  # Axios API service
├── routes/api.php                           # All API routes
├── .env.example
├── composer.json
└── README.md
```
