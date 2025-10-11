# Online Course Management System (OCMS)

A comprehensive web-based course management system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

### User Roles
- **Admin**: Manage users, courses, and view analytics
- **Instructor**: Create courses, upload content, create quizzes
- **Student**: Browse courses, enroll, view content, take quizzes

### Core Modules

1. **Authentication Module**
   - User registration and login
   - Role-based access control
   - Session management

2. **Course Management**
   - Create, edit, and delete courses
   - Course enrollment system
   - Course content organization

3. **Content Upload**
   - Video upload support
   - PDF/document upload
   - Text notes and descriptions
   - File management system

4. **Quiz Module**
   - Create quizzes with multiple question types
   - Multiple choice, true/false, short answer questions
   - Time-limited quizzes
   - Automatic scoring and results

5. **Admin Dashboard**
   - User management
   - Course analytics
   - System statistics
   - Enrollment tracking

## Installation

1. **Database Setup**
   ```sql
   -- Import the database schema
   mysql -u root -p < database/schema.sql
   ```

2. **Configuration**
   - Update `config/database.php` with your database credentials
   - Ensure PHP has file upload permissions
   - Create upload directories:
     ```bash
     mkdir uploads
     mkdir uploads/content
     chmod 777 uploads
     chmod 777 uploads/content
     ```

3. **Web Server Setup**
   - Place files in your web server directory (e.g., `htdocs` for XAMPP)
   - Ensure PHP 7.4+ is installed
   - Enable MySQLi extension

## Default Login Credentials

- **Admin**: username: `admin`, password: `password`
- **Instructor**: username: `instructor1`, password: `password`
- **Student**: username: `student1`, password: `password`

## File Structure

```
OCMS/
├── config/
│   └── database.php          # Database configuration
├── database/
│   └── schema.sql            # Database schema
├── includes/
│   ├── auth.php              # Authentication class
│   ├── course.php            # Course management class
│   ├── content.php            # Content management class
│   ├── quiz.php              # Quiz management class
│   ├── header.php            # Common header
│   └── footer.php            # Common footer
├── admin/
│   ├── users.php             # User management
│   ├── courses.php           # Course management
│   └── analytics.php         # System analytics
├── instructor/
│   ├── courses.php           # Instructor courses
│   ├── add-course.php        # Create course
│   ├── course-content.php    # Manage content
│   └── add-quiz.php          # Create quiz
├── student/
│   ├── courses.php           # Browse courses
│   ├── my-courses.php        # Enrolled courses
│   ├── enroll.php            # Course enrollment
│   ├── quizzes.php           # Available quizzes
│   └── take-quiz.php         # Quiz interface
├── assets/
│   └── css/
│       └── style.css         # Main stylesheet
├── uploads/                  # File upload directory
├── index.php                 # Main entry point
├── login.php                 # Login page
├── register.php              # Registration page
├── dashboard.php             # Main dashboard
└── logout.php                # Logout handler
```

## Usage

1. **Access the System**
   - Navigate to your web server URL
   - Login with default credentials or register new account

2. **Admin Functions**
   - Manage all users and courses
   - View system analytics and reports
   - Monitor enrollment statistics

3. **Instructor Functions**
   - Create and manage courses
   - Upload course materials (videos, documents)
   - Create and manage quizzes
   - Track student progress

4. **Student Functions**
   - Browse available courses
   - Enroll in courses
   - Access course content
   - Take quizzes and view results

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- File upload permissions
- MySQLi extension enabled

## Security Features

- Password hashing with PHP's password_hash()
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- File upload validation

## Customization

The system is designed to be easily customizable:

- Modify CSS in `assets/css/style.css` for styling
- Update database schema for additional features
- Extend classes in `includes/` directory
- Add new modules following existing patterns

## Support

For issues or questions, please refer to the code documentation or create an issue in the project repository.
