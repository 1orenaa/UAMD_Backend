# 🎓 UniManagement — Backend (Laravel API)

This is the **backend** of the UniManagement university management system, built with **Laravel 12** and connected to **SQL Server 2025**.

> 🔗 Frontend repository: [UniManagement Frontend](https://github.com/1orenaa/UAMD_Backend)

---

## 📋 Tech Stack

| Layer          | Technology                        |
| -------------- | --------------------------------- |
| Framework      | Laravel 12                        |
| Language       | PHP 8.2                           |
| Authentication | Laravel Sanctum                   |
| Database       | SQL Server 2025 Developer Edition |
| Local Server   | XAMPP (PHP 8.2)                   |
| Tools          | VS Code, SSMS, Composer           |

---

## ⚙️ Prerequisites

Make sure you have the following installed:

- [XAMPP 8.2.x](https://www.apachefriends.org/download.html) — with PHP 8.2
- [SQL Server 2025 Developer Edition](https://www.microsoft.com/en-us/sql-server/sql-server-downloads)
- [SQL Server Management Studio (SSMS)](https://aka.ms/ssmsfullsetup)
- [Composer](https://getcomposer.org/Composer-Setup.exe)
- [VS Code](https://code.visualstudio.com/)

---

## 🗄️ SQL Server Setup

### 1. Connect via SSMS

- **Server name:** `YOUR-COMPUTER-NAME` (e.g. `HARIS`)
- **Authentication:** Windows Authentication
- ✅ Check **Trust server certificate**

### 2. Enable SQL Server Authentication

1. Right-click the server → **Properties → Security**
2. Select **"SQL Server and Windows Authentication mode"**
3. Click **OK**

### 3. Enable the `sa` user

1. Go to **Security → Logins → sa**
2. Set a password under **General**
3. Under **Status** → set Login to **Enabled**
4. Click **OK**

### 4. Enable TCP/IP

1. Open **SQL Server Configuration Manager** (`SQLServerManager17.msc`)
2. Go to **SQL Server Network Configuration → Protocols for MSSQLSERVER**
3. Right-click **TCP/IP** → **Enable**
4. Go to **SQL Server Services** → Right-click **SQL Server (MSSQLSERVER)** → **Restart**

### 5. Create the Database

1. In SSMS, right-click **Databases** → **New Database**
2. Name: `uni_management`
3. Click **OK**

### 6. Install PHP Driver for SQL Server

1. Go to: [msphpsql releases v5.12.0](https://github.com/microsoft/msphpsql/releases/tag/v5.12.0)
2. Download `SQLSRV56.exe` and extract it
3. Copy these two files to `C:\xampp\php\ext\`:
    - `php_sqlsrv_82_ts_x64.dll`
    - `php_pdo_sqlsrv_82_ts_x64.dll`
4. Open `C:\xampp\php\php.ini` and add at the bottom:

```ini
extension=php_sqlsrv_82_ts_x64
extension=php_pdo_sqlsrv_82_ts_x64
extension=openssl
```

---

## 🔧 Installation

### 1. Clone the repository

```bash
git clone https://github.com/username/backend-repo.git
cd backend-repo
```

### 2. Install dependencies

```bash
composer install
```

### 3. Create the .env file

```bash
copy .env.example .env
```

### 4. Configure .env

Open `.env` and update the following values:

```env
APP_NAME=UniManagement
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlsrv
DB_HOST=YOUR-COMPUTER-NAME
DB_PORT=1433
DB_DATABASE=uni_management
DB_USERNAME=sa
DB_PASSWORD=YOUR-SA-PASSWORD

SESSION_DRIVER=database
```

> ⚠️ **IMPORTANT — Every team member must do this individually:**
>
> - `DB_HOST` → set it to **your own computer name**.
>   Find it by searching **"About your PC"** in Windows Settings.
> - `DB_PASSWORD` → set it to the password **you chose for the `sa` user** in SSMS.
>
> Do NOT copy these values from a teammate — they are unique to each machine.

### 5. Generate APP_KEY

```bash
php artisan key:generate
```

### 6. Install Sanctum

```bash
php artisan install:api
```

### 7. Run Migrations

```bash
php artisan migrate
```

### 8. Clear cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 9. Start the server

```bash
php artisan serve
```

Backend will be running at: **http://127.0.0.1:8000**

---

## 📁 Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/
│   │           └── AuthController.php
│   └── Models/
│       └── User.php
├── config/
│   └── cors.php
├── database/
│   └── migrations/
├── routes/
│   ├── api.php
│   └── web.php
├── bootstrap/
│   └── app.php
└── .env
```

---

## 🌐 API Endpoints

| Method | URL                  | Description             | Auth Required |
| ------ | -------------------- | ----------------------- | ------------- |
| POST   | `/api/auth/register` | Register a new user     | ❌            |
| POST   | `/api/auth/login`    | Login and receive token | ❌            |
| POST   | `/api/auth/logout`   | Logout current session  | ✅            |
| GET    | `/api/me`            | Get current user info   | ✅            |

---

## 👥 User Roles

| Role      | Description        |
| --------- | ------------------ |
| `student` | University student |
| `pedagog` | Lecturer / Teacher |

---

## ❗ Common Issues & Fixes

### "Login failed for user 'sa'"

→ Make sure SQL Server Authentication is enabled and the `sa` user is set to **Enabled** in SSMS.

### "TCP Provider: The wait operation timed out"

→ Enable TCP/IP from SQL Server Configuration Manager and restart the SQL Server service.

### "Call to undefined function openssl_cipher_iv_length()"

→ Open `C:\xampp\php\php.ini` and make sure `extension=openssl` does **not** have a `;` at the beginning.

### "Cannot use laravel/laravel's latest version"

→ Your PHP version is too old. Install XAMPP 8.2.x or newer.

### "The route api/auth/register could not be found"

→ Check `bootstrap/app.php` and make sure the `api` route file is registered. Then run:

```bash
php artisan route:clear
php artisan config:clear
```

---
