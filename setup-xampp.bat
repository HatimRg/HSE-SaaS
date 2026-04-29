@echo off
chcp 65001 >nul
echo ========================================
echo HSE SaaS - XAMPP Setup Script
echo ========================================
echo.

REM Check if running from correct directory
if not exist "artisan" (
    echo ERROR: Please run this script from the project root directory
    echo Current directory: %CD%
    pause
    exit /b 1
)

REM Check PHP
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: PHP not found at C:\xampp\php\php.exe
    echo Please ensure XAMPP is installed
    pause
    exit /b 1
)

REM Check MySQL
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ERROR: MySQL not found at C:\xampp\mysql\bin\mysql.exe
    echo Please ensure XAMPP is installed
    pause
    exit /b 1
)

echo [1/5] Checking MySQL connection...
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 1;" 2>nul
if errorlevel 1 (
    echo ERROR: Cannot connect to MySQL
    echo Please start MySQL from XAMPP Control Panel
    pause
    exit /b 1
)
echo      ✓ MySQL is running

echo.
echo [2/5] Creating database...
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS hse_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo ERROR: Failed to create database
    pause
    exit /b 1
)
echo      ✓ Database 'hse_saas' created

echo.
echo [3/5] Running migrations...
C:\xampp\php\php.exe artisan migrate --force
if errorlevel 1 (
    echo ERROR: Migrations failed
    pause
    exit /b 1
)
echo      ✓ Migrations completed

echo.
echo [4/5] Creating Super Admin account...
C:\xampp\php\php.exe artisan db:seed --class=SuperAdminSeeder --force
if errorlevel 1 (
    echo ERROR: Seeder failed
    pause
    exit /b 1
)
echo      ✓ Super Admin created

echo.
echo [5/5] Clearing cache...
C:\xampp\php\php.exe artisan cache:clear
C:\xampp\php\php.exe artisan config:clear
echo      ✓ Cache cleared

echo.
echo ========================================
echo Setup Complete! 🎉
echo ========================================
echo.
echo Access URLs:
echo   Landing Page: http://localhost/New Sys Files/
echo   Login Page:   http://localhost/New Sys Files/login
echo.
echo Super Admin Credentials:
echo   Email:    superadmin@hse-saas.com
echo   Password: SuperAdmin123!
echo.
echo Admin Credentials:
echo   Email:    admin@hse-saas.com
echo   Password: Admin123!
echo.
echo ========================================
pause
