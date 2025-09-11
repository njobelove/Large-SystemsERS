# Academic System Setup Guide

## Prerequisites
- MySQL Server installed and running
- PHP 8.0+ installed

## Step 1: Install PHP (if not already installed)

1. **Download PHP:**
   - Go to: https://windows.php.net/download/
   - Download the latest thread-safe version (e.g., `php-8.2.x-nts-Win32-vs16-x64.zip`)

2. **Install PHP:**
   - Extract the ZIP file to `C:\php`
   - Add `C:\php` to your system PATH:
     - Right-click "This PC" → Properties → Advanced system settings
     - Click "Environment Variables"
     - Under "System variables", find "Path" and click "Edit"
     - Add `C:\php` to the list

3. **Configure PHP:**
   - Copy `C:\php\php.ini-production` to `C:\php\php.ini`
   - Open `C:\php\php.ini` in a text editor
   - Find and uncomment these lines (remove the semicolon):
     ```
     extension=mysqli
     extension=pdo_mysql
     ```

4. **Verify PHP installation:**
   - Open Command Prompt and run: `php --version`
   - You should see PHP version information

## Step 2: Configure Database Connection

1. **Update config.php:**
   - Open `config.php` in your project
   - Update the database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_mysql_username');
     define('DB_PASS', 'your_mysql_password');
     define('DB_NAME', 'academic_system');
     ```

## Step 3: Run Database Setup

1. **Double-click `run_setup.bat`** in your project folder
   - This will automatically check for PHP and run the database setup
   - Or run manually: `php setup_database.php`

## Step 4: Test the System

### Admin Access:
- URL: `admin/courses.php`
- Login: `admin@system.edu` / `password`

### Instructor Access:
- URL: `instructor/courses.php`
- Login: `john@instructor.edu` / `password`

### Student Access:
- URL: `student/dashboard.php`
- Login: `alice@student.edu` / `password`

## Features Implemented

✅ **Admin Section (Manage Courses):**
- Create new courses with instructor assignment
- View all courses with enrollment counts
- Assign instructors to courses

✅ **Student Section (Register Courses):**
- View available courses for registration
- Register for courses not already enrolled in
- Filter out full courses

✅ **Instructor Section (Courses):**
- View courses assigned to the instructor
- See enrolled student counts
- Access course management features

✅ **Database Integration:**
- All data saved to MySQL database
- Proper relationships between users, courses, and enrollments
- Sample data included for testing

## File Structure
```
academic_system/
├── admin/
│   ├── courses.php          # Admin course management
│   ├── student-info.php     # View all students
│   └── ...
├── student/
│   ├── register-courses.php # Student course registration
│   ├── dashboard.php        # Student dashboard
│   └── ...
├── instructor/
│   ├── courses.php          # Instructor's courses
│   ├── dashboard.php        # Instructor dashboard
│   └── ...
├── config.php               # Database configuration
├── functions.php            # Database functions
├── database_setup.sql       # Database schema
├── setup_database.php       # Setup script
└── run_setup.bat           # Windows setup batch file
```

## Troubleshooting

**PHP not found error:**
- Make sure PHP is installed and added to PATH
- Restart Command Prompt after adding to PATH

**Database connection error:**
- Ensure MySQL is running
- Check database credentials in config.php
- Create database manually if needed: `CREATE DATABASE academic_system;`

**Permission errors:**
- Make sure the web server has write permissions for uploads/ directory
- Check MySQL user permissions

## Next Steps

1. **Test Course Creation:**
   - Login as admin
   - Go to admin/courses.php
   - Create a new course with an instructor

2. **Test Student Registration:**
   - Login as student (alice@student.edu)
   - Go to student/register-courses.php
   - Register for available courses

3. **Test Instructor View:**
   - Login as instructor (john@instructor.edu)
   - Go to instructor/courses.php
   - View assigned courses

The system is now fully functional with proper database integration!
