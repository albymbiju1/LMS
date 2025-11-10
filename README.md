# Learning Management System (LMS)

A comprehensive web-based Learning Management System built with PHP and MySQL, designed to facilitate online education and course management with extensive features for students, instructors, and administrators.

## 🚀 Core Features

### 🎓 Multi-Role User System
- **Students**: Enroll in courses, submit assignments, take quizzes, participate in discussions
- **Instructors**: Create and manage courses, assignments, quizzes, and grade students
- **Administrators**: System oversight, user management, and configuration

### 📚 Course Management
- Course creation and management with instructor assignment
- Student enrollment/unenrollment with status tracking
- Course descriptions and metadata management
- Role-based course access control

### 📝 Assignment System
- Create assignments with titles, descriptions, and due dates
- File upload support for student submissions (10MB limit)
- Multiple submission prevention
- Grading system with percentage scores (0-100)
- Detailed feedback system for instructors
- Submission status tracking and history
- Assignment archiving for completed courses

### 🎯 Comprehensive Quiz System
- Quiz creation with multiple choice questions (4 options)
- Automatic scoring and grade calculation
- Question builder with correct answer specification
- Quiz attempt tracking and time monitoring
- Detailed quiz results with performance analysis
- Question review showing correct/incorrect answers
- Transaction-based quiz submission for data integrity

### 💬 Discussion & Communication
- Course-specific discussion forums
- Threaded discussions with reply system
- Author identification and timestamp tracking
- Discussion creation and management
- Reply system with chronological ordering

### 📊 Grading & Assessment
- Comprehensive grade book by course and assessment type
- Assignment grading with percentage scores
- Automatic quiz grading
- Grade categorization (A/B/C/D/F)
- Detailed feedback and comment system
- Grade history and progress tracking
- Grade analytics and reporting

### 📁 Content & Resource Management
- Module creation with file attachments
- Resource upload with file type validation
- Support for multiple file formats (PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, XLSX, ZIP, RAR)
- Secure file download with access control
- Content organization by course and module
- File metadata tracking and management

### 🔐 Security & Authentication
- Secure user registration with role selection
- Password hashing using PHP's PASSWORD_DEFAULT
- Session-based authentication with regeneration
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars
- Role-based access control throughout the system
- Password reset system with token generation (1-hour expiry)

## 🛠️ Technology Stack

### Backend
- **PHP 8.x** - Server-side scripting with modern features
- **MySQL/MariaDB** - Robust database management
- **mysqli** - Secure database connectivity
- **Transactions** - Data integrity for critical operations

### Frontend
- **Bootstrap 4.5.2** - Responsive UI framework
- **Font Awesome 6.0.0** - Comprehensive icon library
- **jQuery 3.5.1** - JavaScript interactions
- **Custom CSS** - Modern design with CSS variables and gradients

### Server Requirements
- Apache/Nginx web server
- PHP 8.0 or higher
- MySQL 5.7+ / MariaDB 10.2+
- File upload permissions
- Session support

## 📁 Project Structure

```
LMS/
├── config/                    # Configuration files
│   ├── database.php          # Database connection and setup
│   └── setup_database.php    # Complete database schema creation
├── uploads/                   # File storage directory
│   ├── assignments/          # Student assignment submissions
│   └── modules/              # Course materials and resources
├── Core Application Files:
│   ├── Authentication:
│   │   ├── login.php         # User login with session management
│   │   ├── register.php      # User registration with role selection
│   │   ├── logout.php        # Session destruction
│   │   └── forgot-password.php # Password reset system
│   ├── Dashboard & Navigation:
│   │   └── index.php         # Role-based dashboard
│   ├── Course Management:
│   │   ├── course.php        # Course details and content
│   │   ├── create_course.php # Course creation for instructors
│   │   ├── enroll.php        # Student enrollment
│   │   ├── unenroll.php      # Course removal
│   │   └── enrollment_success.php # Confirmation page
│   ├── Assignment System:
│   │   ├── assignments.php   # Assignment listing
│   │   ├── create_assignment.php # Assignment creation
│   │   ├── view_assignment.php # Assignment details
│   │   ├── submit_assignment.php # File submission
│   │   ├── view_submissions.php # Instructor submission view
│   │   ├── grade_submission.php # Individual grading
│   │   └── grade_assignment.php # Alternative grading interface
│   ├── Quiz System:
│   │   ├── quizzes.php       # Quiz listing
│   │   ├── create_quiz.php   # Quiz creation
│   │   ├── add_questions.php # Question builder
│   │   ├── view_quiz.php     # Quiz taking interface
│   │   ├── take_quiz.php     # Quiz submission
│   │   ├── quiz_results.php  # Individual results
│   │   └── view_quiz_results.php # Instructor results view
│   ├── Content Management:
│   │   ├── modules.php       # Module listing
│   │   ├── create_module.php # Module creation
│   │   ├── view_module.php   # Module content display
│   │   ├── upload_resource.php # Resource upload
│   │   └── download_resource.php # Secure download
│   ├── Communication:
│   │   ├── discussions.php   # Discussion forums
│   │   └── view_discussion.php # Threaded discussions
│   ├── User Management:
│   │   ├── profile.php       # User profile management
│   │   └── grades.php        # Grade viewing
│   └── Setup:
│       ├── create_tables.php # Database setup placeholder
│       └── sample_data.php   # Sample data generation
├── team_lms.sql              # Complete database schema with sample data
└── README.md                 # This documentation
```

## 🗄️ Complete Database Schema

The system uses a comprehensive MySQL database with the following tables:

### Core Tables
- **users** - User authentication and role management (student/instructor/admin)
- **courses** - Course information with instructor assignment
- **enrollments** - Student course enrollments with status tracking

### Assignment System
- **assignments** - Assignment creation with due dates
- **submissions** - Student assignment submissions with file paths
- **archived_submissions** - Historical submission data

### Quiz System
- **quizzes** - Quiz creation and metadata
- **quiz_questions** - Questions with multiple choice options
- **quiz_attempts** - Student quiz attempts with scoring
- **quiz_responses** - Individual question responses
- **choices** - Multiple choice options with correct answers

### Content Management
- **modules** - Course content organization
- **lessons** - Individual lesson content
- **content_items** - Various content types (video, document, link, text)

### Communication
- **discussions** - Forum-style discussions
- **discussion_replies** - Threaded replies
- **forum_threads** - Alternative forum structure
- **forum_posts** - Forum post management

### Assessment & Grading
- **grades** - Comprehensive grade tracking
- **messages** - User messaging system (ready for implementation)

### System Features
- **notifications** - User notification system
- **resources** - File resource management

### Database Views
- **assignment_submissions** - Combined view for submission data

## 🚀 Installation Guide

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache/Nginx)
- File upload permissions
- Session support enabled

### Step-by-Step Installation

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd LMS
   ```

2. **Database Setup**
   - Create a MySQL database (optional - setup script can create it)
   - Import the complete database schema:
     ```bash
     mysql -u username -p team_lms < team_lms.sql
     ```
   - Alternatively, run the setup script:
     ```bash
     http://your-domain/config/setup_database.php
     ```

3. **Configuration**
   - Update database credentials in `config/database.php`:
     ```php
     define('DB_SERVER', 'localhost');
     define('DB_USERNAME', 'your_username');
     define('DB_PASSWORD', 'your_password');
     define('DB_NAME', 'team_lms');
     ```

4. **Set File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/assignments/
   chmod 755 uploads/modules/
   ```

5. **Web Server Configuration**
   - Point document root to the LMS directory
   - Ensure .htaccess is properly configured for clean URLs
   - Verify PHP extensions: mysqli, session, fileinfo

6. **Access the Application**
   - Navigate to `http://your-domain/`
   - Register as the first user (automatically assigned as administrator)
   - Create courses and enroll students

## 🔐 Security Features

### Authentication & Authorization
- Modern password hashing using PHP's PASSWORD_DEFAULT
- Session regeneration for security
- Role-based access control throughout the system
- Password reset with secure tokens and expiration

### Data Protection
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- CSRF protection ready structure
- Input validation and sanitization

### File Security
- File type validation and restrictions
- Secure file upload handling
- Access control for downloads
- Unique filename generation to prevent conflicts

### Database Security
- Foreign key constraints for data integrity
- Transaction support for critical operations
- Error handling and logging
- Connection security with UTF-8 encoding

## 📖 User Guides

### For Students
1. **Registration & Login**: Create account with email verification
2. **Course Enrollment**: Browse available courses and enroll
3. **Access Content**: View modules, download resources
4. **Submit Assignments**: Upload files before due dates
5. **Take Quizzes**: Complete assessments with immediate feedback
6. **View Grades**: Monitor progress and feedback
7. **Participate**: Join course discussions and forums

### For Instructors
1. **Course Creation**: Design courses with descriptions and objectives
2. **Content Management**: Upload modules and organize resources
3. **Assignment Creation**: Set assignments with due dates and requirements
4. **Quiz Development**: Build assessments with multiple choice questions
5. **Grading**: Review submissions and provide detailed feedback
6. **Communication**: Manage discussions and student interactions
7. **Progress Monitoring**: Track student performance and engagement

### For Administrators
1. **User Management**: Oversee all user accounts and roles
2. **System Configuration**: Manage database and server settings
3. **Content Oversight**: Monitor all courses and activities
4. **Analytics**: Generate reports on system usage and performance
5. **Maintenance**: Perform database backups and updates
6. **Security**: Ensure system integrity and data protection

## 🎯 Advanced Features

### Assessment Capabilities
- **Multiple Question Types**: Currently supports multiple choice with extensibility for others
- **Automatic Grading**: Instant scoring for quizzes
- **Grade Analytics**: Comprehensive performance tracking
- **Feedback System**: Detailed instructor comments
- **Progress Tracking**: Student advancement monitoring

### Content Delivery
- **Multimedia Support**: Various file formats for rich content
- **Modular Structure**: Organized course content delivery
- **Resource Management**: Centralized file storage and access
- **Content Types**: Video, document, link, and text content support

### Communication Tools
- **Discussion Forums**: Course-specific conversations
- **Messaging System**: User-to-user communication (ready)
- **Notification System**: Updates and alerts (ready)
- **Collaborative Learning**: Peer interaction features

## 🔧 Technical Specifications

### Performance Features
- **Database Indexing**: Optimized queries for large datasets
- **Transaction Support**: Data integrity for critical operations
- **Error Handling**: Comprehensive logging and user feedback
- **Session Management**: Secure and efficient user sessions

### Scalability
- **Modular Design**: Easy feature addition and modification
- **Database Optimization**: Indexed tables and efficient queries
- **File Management**: Scalable upload and storage system
- **Code Organization**: Clean, maintainable PHP structure

### Integration Ready
- **Email Integration**: Prepared for notification system
- **API Structure**: Ready for REST API development
- **Plugin Architecture**: Extensible for additional features
- **Third-party Integration**: Points for external services

## 🐛 Troubleshooting

### Common Issues and Solutions

**Database Connection Errors**
- Verify MySQL server is running
- Check database credentials in config files
- Ensure database exists and user has proper permissions
- Review PHP error logs for specific error messages

**File Upload Issues**
- Verify directory permissions (755 recommended)
- Check PHP upload limits in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  max_file_uploads = 20
  ```
- Ensure sufficient disk space
- Verify file type restrictions

**Session Problems**
- Check session save path permissions
- Verify session configuration in `php.ini`
- Clear browser cookies and cache
- Check for session conflicts

**Permission Issues**
- Verify file ownership (web server user)
- Check directory permissions recursively
- Review .htaccess configuration
- Ensure proper Apache/Nginx configuration

### Performance Optimization
- Enable PHP OPcache for better performance
- Use MySQL query caching
- Implement file compression for uploads
- Regular database maintenance and optimization

## 🔄 Updates and Maintenance

### Recent Updates
- Enhanced security measures with modern PHP practices
- Improved user interface with responsive design
- Comprehensive quiz system with automatic grading
- Advanced file management with validation
- Role-based access control implementation
- Database optimization with proper indexing

### Maintenance Tasks
- Regular database backups
- Log file monitoring and cleanup
- File storage management
- Security updates and patches
- Performance monitoring and optimization

## 📝 Development Notes

### Code Standards
- Clean, readable PHP code with proper commenting
- Consistent naming conventions
- Error handling and logging
- Security-first development approach

### Database Design
- Normalized database structure
- Proper foreign key relationships
- Efficient indexing strategy
- Transaction support for data integrity

### Future Enhancements
- REST API development for mobile app integration
- Advanced analytics and reporting
- Email notification system
- Real-time chat functionality
- Video conferencing integration
- Plagiarism detection system

## 🤝 Contributing Guidelines

1. **Fork the Repository**
2. **Create Feature Branch**: `git checkout -b feature/amazing-feature`
3. **Commit Changes**: `git commit -m 'Add amazing feature'`
4. **Push to Branch**: `git push origin feature/amazing-feature`
5. **Open Pull Request** with detailed description

### Code Review Process
- Ensure code follows project standards
- Test all functionality thoroughly
- Update documentation as needed
- Verify security implications

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For technical support:
- Create an issue in the repository
- Review existing documentation
- Check system logs for error details
- Contact development team for critical issues

---

**Note**: This is a production-ready Learning Management System with comprehensive features for educational institutions. Regular maintenance and security updates are recommended for optimal performance and security.