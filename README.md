# 🎓 Capital University Attendance Management System

A comprehensive web-based attendance management system for Capital University, featuring an AI-powered chatbot assistant for student inquiries.

## 📋 Overview

This system provides a complete solution for managing student attendance, faculty information, class schedules, and academic records. It includes separate dashboards for administrators, faculty members, and students, along with an intelligent AI chatbot powered by Google's Gemini API.

## ✨ Features

### 👨‍💼 Admin Dashboard
- Manage faculties, departments, and classes
- Add and manage teachers and students
- Assign subjects to teachers
- Generate comprehensive reports
- View attendance statistics
- Export data to Excel/PDF

### 👨‍🏫 Faculty Dashboard
- View assigned classes and subjects
- Mark student attendance
- Generate attendance reports
- View student performance
- Download reports in PDF format
- Manage class schedules and timetables

### 👨‍🎓 Student Features
- View personal attendance records
- Check class schedules
- Access exam reports
- View subject-wise performance
- Search and filter attendance data

### 🤖 AI Chatbot Assistant
- Powered by Google Gemini 2.5 Flash
- Answers questions about university programs, faculties, and admissions
- Context-aware conversation history
- Smart suggestion system
- Plain text responses for better readability

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Libraries**: 
  - PHPSpreadsheet (Excel operations)
  - FPDF (PDF generation)
  - Bootstrap (UI framework)
- **AI**: Google Gemini API

## 📦 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependency management)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/capital-university-attendance.git
   cd capital-university-attendance
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   - Create a new MySQL database named `attendanceproject`
   - Import the database schema from `Database/` folder
   - Update database credentials in `connection/connect.php`:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'attendanceproject');
     ```

4. **Configure AI Chatbot** (Optional)
   - Get your Google Gemini API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
   - Update the API key in `app/ai_chatbot.php`:
     ```php
     define('GEMINI_API_KEY', 'your_api_key_here');
     ```

5. **Set Permissions**
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 assets/
   ```

6. **Start the Application**
   - Place the project in your web server's document root (e.g., `htdocs` for XAMPP)
   - Access via browser: `http://localhost/capital-university-attendance/`

## 📁 Project Structure

```
├── Account_users/          # Faculty/User dashboard and features
├── app/                    # AI chatbot application
├── assets/                 # CSS, JS, and static assets
├── connection/             # Database connection files
├── Database/               # Database schema and migrations
├── Database_users/         # User-specific database operations
├── fonts/                  # Custom fonts
├── fpdf184/                # PDF generation library
├── html/                   # Admin dashboard pages
├── img/                    # Images and logos
├── includes/               # Reusable PHP components
├── interval/               # Interval/splash screens
├── js/                     # JavaScript files
├── library/                # Third-party libraries
├── scss/                   # SCSS stylesheets
├── tasks/                  # Background tasks
├── uploads/                # User uploaded files
├── vendor/                 # Composer dependencies
├── index.php               # Main entry point
└── composer.json           # Dependency configuration
```

## 🔐 Default Login Credentials

### Admin
- Username: `admin`
- Password: `admin123`

### Faculty
- Username: `faculty@example.com`
- Password: `faculty123`

**⚠️ Important**: Change these credentials after first login!

## 🚀 Usage

### For Administrators
1. Login with admin credentials
2. Navigate to the admin dashboard
3. Add faculties, departments, and classes
4. Register teachers and students
5. Assign subjects and create timetables
6. Monitor attendance and generate reports

### For Faculty Members
1. Login with faculty credentials
2. View assigned classes
3. Mark daily attendance
4. Generate and download reports
5. Update student records

### For Students
1. Access student portal
2. View attendance records
3. Check class schedules
4. Download attendance reports

## 🤖 AI Chatbot Features

The integrated AI chatbot can answer questions about:
- University faculties and programs
- Admission requirements
- Contact information
- Faculty details and deans
- Program durations and credit hours
- University history and mission

**Example queries:**
- "What programs does Computer Science Faculty offer?"
- "What are the admission requirements?"
- "Who is the Dean of Health Sciences?"
- "Tell me about Faculty of Medicine"

## 📊 Reports Available

- Daily/Weekly/Monthly attendance reports
- Student-wise attendance summary
- Subject-wise performance reports
- Class attendance statistics
- Absent students list
- Exam reports
- Export to PDF/Excel formats

## 🔧 Configuration

### Database Configuration
Edit `connection/connect.php` to update database settings.

### AI Chatbot Training
Modify `app/ai_train.txt` to customize chatbot responses and add university-specific information.

### File Upload Settings
Configure upload limits in `php.ini` or `.htaccess` file.

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Developer

**Mohamed Bashir**
- GitHub: [@yourusername](https://github.com/yourusername)
- Email: your.email@example.com

## 🙏 Acknowledgments

- Capital University for project requirements
- Google Gemini API for AI capabilities
- PHPSpreadsheet for Excel operations
- FPDF for PDF generation
- Bootstrap for UI components

## 📞 Support

For support and queries:
- Email: support@capitaluniversity.edu
- Website: [Capital University](https://capitaluniversity.edu)

## 🔄 Version History

- **v1.0.0** (2024) - Initial release
  - Complete attendance management system
  - Admin and faculty dashboards
  - AI chatbot integration
  - PDF/Excel report generation

---

Made with ❤️ for Capital University
