# Task Management System

A modern web-based task management application built using Native PHP and MySQL.  
This application helps users organize daily activities, manage tasks efficiently, track productivity, handle notes, and monitor schedules through an integrated management system.

---

## Features

### Task Management
- Create, update, and delete tasks
- Task status management
- Priority level tracking
- Due date and reminder system
- Task completion tracking

### Category Management
- Custom task categories
- Category colors and icons
- Organized task grouping

### Calendar Integration
- Calendar-based task overview
- Upcoming task monitoring
- Schedule management

### Notes Management
- Personal and task-related notes
- Pin important notes
- Quick note organization

### Productivity & Reporting
- Task progress monitoring
- Productivity reports
- Export report feature

### User Management
- User registration and authentication
- Profile management
- Session handling

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP Native |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| Styling | Custom CSS |
| Server | Apache / XAMPP |

---

## Project Structure

```bash
task_management/
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── dashboard.php
│
├── assets/
│   ├── css/
│   └── images/
│
├── classes/
│   ├── Database.php
│   ├── User.php
│   ├── Task.php
│   ├── Category.php
│   └── Note.php
│
├── config/
│   ├── config.php
│   ├── database.php
│   └── session.php
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── sidebar.php
│   └── functions.php
│
└── pages/
    ├── tasks/
    ├── calendar/
    ├── categories/
    ├── notes/
    └── reports/
```

---

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache / Nginx / XAMPP

### Steps

#### 1. Clone Repository

```bash
git clone https://github.com/HilmiCy/task-management.git
```

#### 2. Move to Project Directory

```bash
cd task-management
```

#### 3. Configure Database

Create a new MySQL database and configure database credentials inside:

```bash
config/database.php
```

#### 4. Import Database Schema

Import the SQL database schema into MySQL.

#### 5. Run Application

Using PHP built-in server:

```bash
php -S localhost:8000
```

Open browser:

```bash
http://localhost:8000
```

---

## Main Modules

| Module | Description |
|--------|-------------|
| Dashboard | Task overview and productivity summary |
| Tasks | Task management and tracking |
| Categories | Task categorization |
| Calendar | Schedule and upcoming tasks |
| Notes | Personal notes management |
| Reports | Productivity reporting |
| Authentication | User login and registration |

---

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- SQL injection prevention using prepared statements
- XSS protection
- CSRF protection

---

## Future Enhancements

- Real-time notifications
- Drag and drop task management
- Mobile responsive interface
- Dark mode support
- REST API integration
- Email reminders
- Team collaboration system
- Real-time task synchronization

---

## Author

**Fadhil Cahya Hilmi**

GitHub: `@HilmiCy`

---

## License

This project is licensed under the MIT License.  
You are free to use, modify, and distribute this software in accordance with the license terms.

See the [LICENSE](LICENSE) file for more information.
