# XAMPP Setup Instructions

## Prerequisites
1. XAMPP installed with Apache and MySQL
2. Project placed in `C:\xampp\htdocs\New Sys Files\`

## Setup Steps

### 1. Start XAMPP Services
1. Open **XAMPP Control Panel**
2. Start **Apache**
3. Start **MySQL**

### 2. Create Database
Open browser and go to: `http://localhost/phpmyadmin`
- Create database named: `hse_saas`
- Collation: `utf8mb4_unicode_ci`

OR run via MySQL command line:
```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS hse_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Run Migrations & Seeders
Once MySQL is running, execute:

```bash
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan db:seed --class=SuperAdminSeeder --force
```

### 4. Access the Application

**Landing Page:**
```
http://localhost/New%20Sys%20Files/
```

**Login Page:**
```
http://localhost/New%20Sys%20Files/login
```

**API Base URL:**
```
http://localhost/New%20Sys%20Files/api/
```

## Default Accounts

### Super Admin (Full Access)
- **Email:** `superadmin@hse-saas.com`
- **Password:** `SuperAdmin123!`
- **Permissions:** ALL SYSTEM PERMISSIONS

### Admin (Company Admin)
- **Email:** `admin@hse-saas.com`
- **Password:** `Admin123!`
- **Permissions:** Company management permissions

## Troubleshooting

### 403 Forbidden Error
- Ensure `.htaccess` files exist in root and public folders
- Check Apache `httpd.conf` has `AllowOverride All` for your directory

### Database Connection Error
- Verify MySQL is running in XAMPP Control Panel
- Check `.env` file has correct database credentials:
  ```
  DB_DATABASE=hse_saas
  DB_USERNAME=root
  DB_PASSWORD=
  ```

### White Screen / 500 Error
- Check `storage/logs/laravel.log` for errors
- Ensure `storage/` and `bootstrap/cache/` directories are writable
- Run: `C:\xampp\php\php.exe artisan cache:clear`

### Permission Denied
```bash
# Windows - Run as Administrator if needed
icacls "C:\xampp\htdocs\New Sys Files\storage" /grant Everyone:F /T
icacls "C:\xampp\htdocs\New Sys Files\bootstrap\cache" /grant Everyone:F /T
```

## Quick Commands Reference

```bash
# Navigate to project
cd "C:\xampp\htdocs\New Sys Files"

# Run migrations
C:\xampp\php\php.exe artisan migrate

# Run seeders
C:\xampp\php\php.exe artisan db:seed --class=SuperAdminSeeder

# Clear cache
C:\xampp\php\php.exe artisan cache:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan view:clear

# Create new user
C:\xampp\php\php.exe artisan tinker
>>> \App\Models\User::create(['email' => 'test@test.com', 'password' => bcrypt('password'), 'first_name' => 'Test', 'last_name' => 'User', 'role_id' => 1, 'company_id' => 1]);
```

## File Structure
```
C:\xampp\htdocs\New Sys Files\
├── .htaccess                 # Redirects to public folder
├── public/                   # Web root
│   ├── .htaccess            # Laravel routing
│   └── index.php            # Entry point
├── app/                      # Application code
├── database/                 # Migrations & seeders
│   └── seeders/
│       └── SuperAdminSeeder.php
├── resources/               # Views, JS, CSS
├── routes/                  # Web & API routes
├── storage/                 # Logs, cache, sessions
├── vendor/                  # Composer dependencies
└── .env                     # Environment configuration
```
