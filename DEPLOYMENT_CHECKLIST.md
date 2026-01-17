# Pre-Deployment Checklist

## Files to Upload:
- [ ] All PHP files (*.php)
- [ ] Database file (result_system.sql)
- [ ] Images folder with all subfolders
- [ ] .htaccess file
- [ ] vendor folder (if using Composer)
- [ ] README.md
- [ ] favicon.ico (if exists)

## Files to EXCLUDE:
- [ ] db_config.php (replace with production version)
- [ ] Any .env files
- [ ] composer.lock (optional)
- [ ] test*.php files
- [ ] debug*.php files
- [ ] phpinfo.php

## Configuration Updates Needed:
- [ ] Update db_config.php with hosting database credentials
- [ ] Update mail_config.php with hosting email SMTP
- [ ] Enable HTTPS redirect in .htaccess
- [ ] Set error_log path in .htaccess
- [ ] Remove development comments from code

## Database Tasks:
- [ ] Create database via cPanel
- [ ] Create database user
- [ ] Grant privileges to user
- [ ] Import result_system.sql
- [ ] Verify all tables created
- [ ] Check sample data (delete if needed)

## Security Checklist:
- [ ] Change all default passwords
- [ ] Update admin credentials
- [ ] Set strong database password
- [ ] Verify .htaccess is protecting sensitive files
- [ ] Test file upload restrictions
- [ ] Enable SSL certificate
- [ ] Set secure session settings

## Testing Checklist:
- [ ] Test homepage loads
- [ ] Test student login
- [ ] Test teacher login
- [ ] Test admin login
- [ ] Test result viewing
- [ ] Test file uploads (testimonials)
- [ ] Test on mobile devices
- [ ] Test all forms submit correctly
- [ ] Test email sending (if configured)
- [ ] Check all images load

## Performance:
- [ ] Optimize images (compress)
- [ ] Enable browser caching (.htaccess)
- [ ] Enable GZIP compression (.htaccess)
- [ ] Minify CSS/JS (optional)

## Post-Deployment:
- [ ] Setup backup schedule
- [ ] Configure error logging
- [ ] Monitor error logs daily
- [ ] Create admin documentation
- [ ] Train staff on system usage
- [ ] Setup monitoring (uptime)

## Domain & Hosting Info:
```
Domain: _______________________
Hosting Provider: ______________
cPanel URL: ___________________
FTP Host: _____________________
FTP User: _____________________
Database Host: ________________
Database Name: ________________
Database User: ________________
SSL Status: [ ] Active [ ] Pending
```

## Emergency Contacts:
```
Hosting Support: ______________
Domain Registrar: _____________
Developer: ____________________
```

---

**Deployment Date**: ___/___/______  
**Deployed By**: __________________  
**Version**: 1.0.0
