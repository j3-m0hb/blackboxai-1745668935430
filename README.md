# Employee Management System (Sistem Kepegawaian)

A comprehensive web-based employee management system for PT. Sejahtera Bersama Express using PHP, MySQL PDO, and modern web technologies.

## Features

- Employee Data Management
- Attendance Tracking
- Payroll Management
- Document Management
- Real-time Dashboard
- Multi-level User Access
- Activity Logging
- Backup & Restore

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/kepeg_sbe.git
cd kepeg_sbe
```

2. Create a MySQL database:
```sql
CREATE DATABASE kepeg_sbe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Configure the database connection:
   - Copy `src/config/database.example.php` to `src/config/database.php`
   - Update the database credentials in `database.php`

4. Initialize the database:
```bash
cd src/database
php init.php
```

5. Set up your web server:
   - Point the document root to the `src` directory
   - Ensure the `uploads` directory is writable by the web server

6. Default login credentials:
   - Username: admin
   - Password: admin123
   - **Important**: Change these credentials immediately after first login

## Directory Structure

```
src/
├── api/            # API endpoints
├── assets/         # Static assets (CSS, JS, images)
├── config/         # Configuration files
├── database/       # Database scripts
├── includes/       # Common PHP includes
├── modules/        # Feature modules
├── uploads/        # User uploads
└── index.php       # Application entry point
```

## Security Features

- Password hashing using MD5
- Session management
- CSRF protection
- Input validation
- SQL injection prevention
- XSS protection
- Activity logging

## User Roles

1. Admin
   - Full system access
   - User management
   - System configuration
   - Backup & restore

2. HRD
   - Employee management
   - Attendance management
   - Payroll management
   - Document management

3. Employee
   - View personal data
   - View attendance
   - View salary slips
   - Upload/download documents

## Key Modules

### Dashboard
- Employee statistics
- Attendance overview
- Contract status monitoring
- Recent activities
- Birthday notifications

### Employee Management
- Personal data
- Employment details
- Document management
- Performance tracking

### Attendance System
- Daily attendance
- Monthly reports
- Leave management
- Attendance statistics

### Payroll System
- Salary components
- Deductions
- Payslip generation
- Monthly reports

### Document Management
- Multiple file formats
- Categorization
- Access control
- Version tracking

## Development Guidelines

1. Code Style
   - Follow PSR-12 coding standards
   - Use meaningful variable and function names
   - Comment complex logic
   - Keep functions small and focused

2. Database
   - Use prepared statements
   - Follow naming conventions
   - Include foreign key constraints
   - Implement soft deletes

3. Security
   - Validate all inputs
   - Escape all outputs
   - Use parameterized queries
   - Implement proper access control

4. UI/UX
   - Responsive design
   - Consistent styling
   - Clear navigation
   - Helpful error messages

## Maintenance

1. Regular Backups
   - Database backups
   - Document backups
   - Configuration backups

2. Updates
   - Security patches
   - Feature updates
   - Bug fixes

3. Monitoring
   - Error logging
   - Activity logging
   - Performance monitoring

## Support

For support and bug reports, please contact:
- Email: support@example.com
- Phone: +62 123 4567 890

## License

This project is proprietary software. All rights reserved.

## Credits

Developed by [Your Name/Company]
For PT. Sejahtera Bersama Express
