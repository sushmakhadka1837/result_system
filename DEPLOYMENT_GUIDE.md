# 🚀 PEC Result System - Deployment Guide

## चरण 1: Pre-Deployment Checklist

### ✅ Files तयार गर्नुहोस्:
- [x] सबै PHP files
- [x] Database SQL file (`result_system.sql`)
- [x] Images folder (`images/`)
- [x] `.htaccess` file
- [x] `composer.json` (if using Composer)

### ✅ Code Review:
```bash
# Error reporting बन्द गर्नुहोस् production मा
# db_config.php मा पहिले नै .htaccess ले handle गरेको छ
```

---

## चरण 2: Hosting छान्नुहोस्

### विकल्प A: Shared Hosting (सजिलो र सस्तो)

**सिफारिस गरिएको Hosting Providers:**
- **HostSugar Nepal** - ₹1000/year (hostsugar.com.np)
- **WebSugar** - ₹1500/year (websugar.com.np)
- **Mercantile** - ₹2000/year (mercantile.com.np)
- **InfiniteHost Nepal** - ₹1200/year

**Features चाहिने:**
- ✅ PHP 7.4+
- ✅ MySQL 5.7+
- ✅ cPanel access
- ✅ Email accounts
- ✅ SSL certificate (Free)

### विकल्प B: Cloud VPS (Advanced)
- DigitalOcean - $6/month
- AWS EC2 - $5-10/month
- Linode - $5/month

---

## चरण 3: Shared Hosting मा Deploy (Step by Step)

### 3.1 Files Upload गर्नुहोस्

**Method 1: File Manager (cPanel)**
1. cPanel login गर्नुहोस्
2. File Manager खोल्नुहोस्
3. `public_html` folder खोल्नुहोस्
4. Project zip गरेर upload गर्नुहोस्
5. Extract गर्नुहोस्

**Method 2: FTP (FileZilla)**
1. FileZilla download गर्नुहोस्
2. FTP credentials राख्नुहोस्:
   - Host: yourdomain.com
   - Username: (hosting provider ले दिएको)
   - Password: (hosting provider ले दिएको)
   - Port: 21
3. सबै files drag & drop गर्नुहोस्

### 3.2 Database Setup

1. **cPanel > MySQL Databases**
2. **Create Database**:
   ```
   Database Name: username_result_system
   (Note: cPanel ले prefix automatically add गर्छ)
   ```

3. **Create User**:
   ```
   Username: username_result_user
   Password: [strong password - generate गर्नुहोस्]
   ```

4. **Add User to Database**:
   - User select गर्नुहोस्
   - Database select गर्नुहोस्
   - "ALL PRIVILEGES" दिनुहोस्

5. **Import SQL**:
   - cPanel > phpMyAdmin
   - Database select गर्नुहोस्
   - "Import" tab
   - `result_system.sql` file select गर्नुहोस्
   - "Go" थिच्नुहोस्

### 3.3 Configuration Update

**db_config.php edit गर्नुहोस्:**
```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'username_result_user');  // Your database username
define('DB_PASS', 'your_strong_password');  // Your database password
define('DB_NAME', 'username_result_system'); // Your database name

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Production mode - errors hidden (.htaccess handles this)
?>
```

### 3.4 Folder Permissions

cPanel File Manager मा:
```
images/              → 755
images/testimonials/ → 755
vendor/              → 755
*.php files          → 644
.htaccess            → 644
```

**Right-click > Change Permissions**

### 3.5 SSL Certificate Setup (Free)

1. cPanel > SSL/TLS Status
2. "Run AutoSSL" click गर्नुहोस्
3. Wait 5-10 minutes
4. HTTPS automatically काम गर्नेछ

**.htaccess मा HTTPS redirect enable गर्नुहोस्:**
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## चरण 4: Testing

### 4.1 Basic Tests:
1. **Homepage**: `https://yourdomain.com`
2. **Student Login**: `https://yourdomain.com/student_login.php`
3. **Teacher Login**: `https://yourdomain.com/teacher_login.php`
4. **Admin Login**: `https://yourdomain.com/admin_login.php`

### 4.2 Check:
- ✅ Database connection
- ✅ Image uploads
- ✅ Result viewing
- ✅ Mobile responsiveness
- ✅ SSL certificate (padlock icon)

### 4.3 Test on Mobile:
- Chrome DevTools > Toggle Device Toolbar
- Test on real mobile device

---

## चरण 5: Post-Deployment Tasks

### 5.1 Security Hardening

**Remove unnecessary files:**
```bash
# Delete if present:
- phpinfo.php
- test.php
- debug files
```

**Update .htaccess:**
```apache
# Already configured in your .htaccess file
```

### 5.2 Backup Setup

**Manual Backup (Weekly):**
1. cPanel > Backup Wizard
2. "Backup" > "Full Backup"
3. Email address दिनुहोस्

**Automated Backup:**
- Most hosting providers automatically backup गर्छन्

### 5.3 Email Configuration

**mail_config.php update गर्नुहोस्:**
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'mail.yourdomain.com';  // cPanel > Email Accounts मा पाइन्छ
$mail->SMTPAuth = true;
$mail->Username = 'noreply@yourdomain.com';  // Create this email in cPanel
$mail->Password = 'email_password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->setFrom('noreply@yourdomain.com', 'PEC Result System');
?>
```

### 5.4 Performance Optimization

**Enable Caching (already in .htaccess):**
- Browser caching
- GZIP compression

**Optimize Images:**
```bash
# Use tools like TinyPNG.com
# Reduce image sizes
```

---

## चरण 6: Monitoring & Maintenance

### Daily Tasks:
- Check error logs: `public_html/error_log`
- Monitor student activity

### Weekly Tasks:
- Backup database
- Update announcements
- Check testimonials

### Monthly Tasks:
- Update PHP version (cPanel > Select PHP Version)
- Review security
- Clean old data

---

## 🆘 Common Issues & Solutions

### Issue 1: "Database Connection Failed"
**Solution:**
- Check `db_config.php` credentials
- Verify database exists in phpMyAdmin
- Check user permissions

### Issue 2: "500 Internal Server Error"
**Solution:**
- Check `.htaccess` syntax
- View error_log file
- Check file permissions (644 for PHP files)

### Issue 3: "Images Not Uploading"
**Solution:**
```bash
# Set folder permissions to 755
chmod 755 images/testimonials/
```

### Issue 4: "Page Not Found (404)"
**Solution:**
- Check file names (case-sensitive on Linux servers)
- Verify `.htaccess` is uploaded

### Issue 5: "Email Not Sending"
**Solution:**
- Configure `mail_config.php`
- Create email account in cPanel
- Use cPanel SMTP settings

---

## 📞 Support Contacts

**Hosting Support:**
- Live Chat: Hosting provider website
- Email: support@yourhost.com
- Phone: Check provider website

**Domain Issues:**
- Registrar support (where you bought domain)

**Technical Help:**
- PEC IT Department
- Developer contact

---

## 💰 Cost Estimation

### Nepali Hosting (1 Year):
- Domain (.com.np): ₹500-800
- Shared Hosting: ₹1000-2000
- SSL Certificate: Free (Let's Encrypt)
- **Total**: ₹1500-2800/year

### International Hosting:
- Domain (.com): $10-15
- Hosting: $30-60/year
- SSL: Free
- **Total**: $40-75/year

---

## ✅ Final Checklist

- [ ] Files uploaded to `public_html`
- [ ] Database imported successfully
- [ ] `db_config.php` updated with correct credentials
- [ ] Folder permissions set (755 for folders, 644 for files)
- [ ] SSL certificate installed
- [ ] HTTPS redirect working
- [ ] All login portals tested
- [ ] Mobile responsive checked
- [ ] Email sending tested
- [ ] Backup configured
- [ ] Error logging enabled

---

**🎉 Congratulations! Your PEC Result System is now LIVE!**

Share URL: `https://yourdomain.com`

---

**Need Help?**
- Documentation: README.md
- Issue Tracker: Contact developer
- Community: PEC IT Team
