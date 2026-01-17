# 🎓 PEC Result Management System

A comprehensive web-based result management system for Pokhara Engineering College.

## 📋 Features

- **Student Portal**: View results, assignments, announcements, and notes
- **Teacher Dashboard**: Manage marks, publish results, post announcements
- **Admin Panel**: Complete system management, user control, testimonials
- **AI Integration**: Performance insights and predictive marks
- **Mobile Responsive**: Works seamlessly on all devices
- **Result Types**: UT Exams, Internal Assessments, Final Results
- **Department-wise**: Computer, Civil, Architecture, IT Engineering
- **Community Voices**: Testimonials from students, teachers, management

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Frontend**: Bootstrap 5.3, Tailwind CSS
- **Libraries**: PHPMailer, Chart.js, FullCalendar
- **Icons**: Font Awesome 6.4

## 📦 Installation

1. **Clone/Download** the project files
2. **Import Database**:
   ```bash
   mysql -u root -p result_system < result_system.sql
   ```
3. **Configure Database**:
   - Edit `db_config.php`
   - Update credentials
4. **Setup Email** (optional):
   - Edit `mail_config.php`
   - Add SMTP credentials
5. **Create Uploads Folder**:
   ```bash
   mkdir -p images/testimonials
   chmod 755 images/testimonials
   ```

## 🔐 Default Login Credentials

**Admin**:
- URL: `admin_login.php`
- Check database `admin` table

**Teacher**:
- URL: `teacher_login.php`
- Check database `teachers` table

**Student**:
- URL: `student_login.php`
- Check database `students` table

## 🚀 Deployment Guide

### For Shared Hosting (cPanel):

1. **Upload Files**:
   - Zip entire project
   - Upload via File Manager or FTP
   - Extract in `public_html`

2. **Create Database**:
   - Create MySQL database via cPanel
   - Import `result_system.sql`
   - Note database name, username, password

3. **Update Configuration**:
   - Edit `db_config.php` with hosting credentials
   - Turn off error display (already in .htaccess)

4. **Set Permissions**:
   ```
   chmod 755 images/
   chmod 755 images/testimonials/
   chmod 644 *.php
   ```

5. **Test**:
   - Visit `yourdomain.com`
   - Check all login portals

### For VPS/Cloud (DigitalOcean, AWS, etc.):

1. **Server Setup**:
   ```bash
   sudo apt update
   sudo apt install apache2 php mysql-server php-mysqli php-mbstring
   sudo systemctl start apache2 mysql
   ```

2. **Upload Project**:
   ```bash
   cd /var/www/html
   sudo git clone [your-repo] OR upload via SFTP
   ```

3. **Database Setup**:
   ```bash
   sudo mysql -u root -p
   CREATE DATABASE result_system;
   CREATE USER 'result_user'@'localhost' IDENTIFIED BY 'strong_password';
   GRANT ALL ON result_system.* TO 'result_user'@'localhost';
   EXIT;
   
   mysql -u root -p result_system < result_system.sql
   ```

4. **Configure Apache**:
   ```bash
   sudo nano /etc/apache2/sites-available/result-system.conf
   ```
   
5. **Enable SSL** (Let's Encrypt):
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d yourdomain.com
   ```

## 📁 Directory Structure

```
result_system/
├── admin_*.php          # Admin panel files
├── student_*.php        # Student portal files
├── teacher_*.php        # Teacher dashboard files
├── manage_*.php         # Management interfaces
├── view_*.php           # Result viewing pages
├── images/              # Image uploads
│   └── testimonials/    # Testimonial photos
├── vendor/              # Composer dependencies
├── db_config.php        # Database configuration
├── mail_config.php      # Email configuration
├── common.php           # Common functions
└── result_system.sql    # Database schema
```

## 🔧 Configuration Files

**db_config.php**: Database credentials
**mail_config.php**: SMTP email settings
**.htaccess**: Apache configuration, security

## 📊 Database Tables

- `students` - Student records
- `teachers` - Teacher accounts
- `admin` - Admin users
- `departments` - Engineering departments
- `semesters` - Semester definitions
- `subjects_master` - Subject catalog
- `results` - Student marks/grades
- `results_publish_status` - Publication control
- `notices` - Announcements
- `testimonials` - Community feedback
- `academic_events` - Calendar events

## 🛡️ Security Features

- ✅ Session-based authentication
- ✅ Prepared statements (SQL injection prevention)
- ✅ Password hashing
- ✅ File upload validation
- ✅ HTTPS ready
- ✅ XSS protection headers
- ✅ CSRF token support

## 📱 Mobile Responsive

All pages optimized for:
- Desktop (1920px+)
- Laptop (1024px - 1919px)
- Tablet (768px - 1023px)
- Mobile (320px - 767px)

## 🐛 Troubleshooting

**Database Connection Error**:
- Check `db_config.php` credentials
- Verify MySQL is running
- Check database exists

**File Upload Issues**:
- Check folder permissions (755)
- Verify `upload_max_filesize` in php.ini

**Email Not Sending**:
- Configure `mail_config.php`
- Check SMTP credentials
- Enable "Less secure apps" (Gmail)

## 📄 License

Educational Project - Pokhara Engineering College

## 👥 Contact

For support, contact PEC IT Department.

---

**Version**: 1.0.0  
**Last Updated**: January 2026
