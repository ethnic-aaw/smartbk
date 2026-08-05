# Smart BK - Deployment Guide

## 🚀 Deployment ke Production

### Prerequisites
- Server dengan PHP 7.4+ (recommended PHP 8.0+)
- MySQL 5.7+ atau MariaDB 10.3+
- Apache dengan mod_rewrite atau Nginx
- SSL Certificate (untuk HTTPS)
- Minimal 512MB RAM, 1GB storage

---

## 📋 Checklist Pre-Deployment

### 1. Code Review
- [x] Semua error sudah diperbaiki
- [x] Testing manual sudah dilakukan
- [x] API endpoints berfungsi normal
- [x] Upload folder sudah ada dan writable
- [x] Database schema final sudah divalidasi

### 2. Security Checklist
- [ ] Ganti password default admin
- [ ] Set strong password database
- [ ] Disable error display (`display_errors = Off`)
- [ ] Enable HTTPS
- [ ] Set proper file permissions
- [ ] Review .gitignore (jangan commit .env atau credentials)
- [ ] Aktifkan PHP security headers

### 3. Performance
- [ ] Enable OPcache di php.ini
- [ ] Set proper memory_limit (minimal 256M)
- [ ] Configure max_execution_time
- [ ] Enable Gzip compression

---

## 🔧 Deployment Steps

### Step 1: Backup Current System (Jika Update)
```bash
# Backup database
mysqldump -u root -p smart_bk > backup_smart_bk_$(date +%Y%m%d).sql

# Backup files
tar -czf smartbk_backup_$(date +%Y%m%d).tar.gz /path/to/smartbk/
```

### Step 2: Upload Files
**Via FTP/SFTP:**
```
Upload semua file ke server:
/var/www/html/smartbk/
atau
/public_html/smartbk/
```

**Via Git (Recommended):**
```bash
cd /var/www/html
git clone <repository-url> smartbk
cd smartbk
```

### Step 3: Set Permissions
```bash
# Set owner (ganti 'www-data' sesuai user web server)
sudo chown -R www-data:www-data /var/www/html/smartbk

# Set directory permissions
sudo find /var/www/html/smartbk -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/smartbk -type f -exec chmod 644 {} \;

# Set upload folder writable
sudo chmod -R 777 /var/www/html/smartbk/assets/uploads
```

### Step 4: Database Setup
```bash
# Login ke MySQL
mysql -u root -p

# Create database
CREATE DATABASE smart_bk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'smartbk_user'@'localhost' IDENTIFIED BY 'PASSWORD_KUAT_DISINI';

# Grant privileges
GRANT ALL PRIVILEGES ON smart_bk.* TO 'smartbk_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import database
mysql -u smartbk_user -p smart_bk < sql/smart_bk.sql
```

### Step 5: Environment Configuration

**Opsi A: Edit config/db.php langsung**
```php
<?php
require_once __DIR__ . '/app.php';

$dbHost = '127.0.0.1';
$dbName = 'smart_bk';
$dbUser = 'smartbk_user';
$dbPass = 'PASSWORD_KUAT_DISINI';

// ... rest of the file
```

**Opsi B: Environment Variables (Recommended)**

Buat file `.env` di root (jangan commit ke Git):
```
DB_HOST=127.0.0.1
DB_NAME=smart_bk
DB_USER=smartbk_user
DB_PASS=password_kuat_disini
```

Atau set via Apache/Nginx config (lihat step 6).

### Step 6: Web Server Configuration

#### Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName smartbk.sekolah.sch.id
    DocumentRoot /var/www/html/smartbk
    
    <Directory /var/www/html/smartbk>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Security headers
        Header set X-Content-Type-Options "nosniff"
        Header set X-Frame-Options "SAMEORIGIN"
        Header set X-XSS-Protection "1; mode=block"
    </Directory>
    
    # Environment variables
    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_NAME smart_bk
    SetEnv DB_USER smartbk_user
    SetEnv DB_PASS password_kuat_disini
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/smartbk_error.log
    CustomLog ${APACHE_LOG_DIR}/smartbk_access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS (setelah SSL aktif)
<VirtualHost *:80>
    ServerName smartbk.sekolah.sch.id
    Redirect permanent / https://smartbk.sekolah.sch.id/
</VirtualHost>

<VirtualHost *:443>
    ServerName smartbk.sekolah.sch.id
    DocumentRoot /var/www/html/smartbk
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    
    # Same configuration as port 80
    <Directory /var/www/html/smartbk>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    SetEnv DB_HOST 127.0.0.1
    SetEnv DB_NAME smart_bk
    SetEnv DB_USER smartbk_user
    SetEnv DB_PASS password_kuat_disini
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name smartbk.sekolah.sch.id;
    root /var/www/html/smartbk;
    index index.php;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name smartbk.sekolah.sch.id;
    root /var/www/html/smartbk;
    index index.php;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Environment variables
        fastcgi_param DB_HOST 127.0.0.1;
        fastcgi_param DB_NAME smart_bk;
        fastcgi_param DB_USER smartbk_user;
        fastcgi_param DB_PASS password_kuat_disini;
    }
    
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Step 7: PHP Configuration (php.ini)

```ini
# Production settings
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

# Upload settings
upload_max_filesize = 2M
post_max_size = 3M
max_file_uploads = 20

# Performance
memory_limit = 256M
max_execution_time = 60

# OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60

# Security
expose_php = Off
allow_url_fopen = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
```

### Step 8: SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Untuk Apache
sudo certbot --apache -d smartbk.sekolah.sch.id

# Untuk Nginx
sudo certbot --nginx -d smartbk.sekolah.sch.id

# Auto-renew
sudo certbot renew --dry-run
```

### Step 9: Ganti Password Default

```bash
# Login ke MySQL
mysql -u smartbk_user -p smart_bk

# Generate password hash baru (gunakan PHP)
php -r "echo password_hash('password_baru_super_kuat', PASSWORD_BCRYPT);"

# Update password admin
UPDATE users SET password_hash = '$2y$10$HASH_RESULT_DISINI' WHERE username = 'admin';
```

### Step 10: Test Deployment

```bash
# Test Apache config
sudo apache2ctl configtest

# Test Nginx config
sudo nginx -t

# Restart web server
sudo systemctl restart apache2
# atau
sudo systemctl restart nginx

# Test database connection
mysql -u smartbk_user -p smart_bk -e "SELECT COUNT(*) FROM siswa;"
```

---

## 🔒 Security Hardening

### 1. Disable Directory Listing
```apache
# .htaccess di root
Options -Indexes
```

### 2. Protect Sensitive Files
```apache
# .htaccess
<FilesMatch "^(\.env|\.git|composer\.(json|lock)|package(-lock)?\.json)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. Enable HSTS (HTTP Strict Transport Security)
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

### 4. Disable PHP Execution in Upload Folder
```apache
# assets/uploads/.htaccess
<Files *.php>
    deny from all
</Files>
```

### 5. Enable Rate Limiting (ModSecurity atau Fail2Ban)
```bash
sudo apt install fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

## 📊 Monitoring & Logs

### Log Files Location
```
Apache:
- Error: /var/log/apache2/smartbk_error.log
- Access: /var/log/apache2/smartbk_access.log

Nginx:
- Error: /var/log/nginx/error.log
- Access: /var/log/nginx/access.log

PHP:
- /var/log/php_errors.log

MySQL:
- /var/log/mysql/error.log
```

### Monitoring Commands
```bash
# Tail error log
sudo tail -f /var/log/apache2/smartbk_error.log

# Check disk space
df -h

# Check MySQL status
sudo systemctl status mysql

# Check web server status
sudo systemctl status apache2
```

---

## 🔄 Backup Strategy

### Daily Backup Script (`/etc/cron.daily/smartbk-backup`)
```bash
#!/bin/bash

# Variables
BACKUP_DIR="/backup/smartbk"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="smart_bk"
DB_USER="smartbk_user"
DB_PASS="password"
APP_DIR="/var/www/html/smartbk"

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz $APP_DIR/assets/uploads

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
find $BACKUP_DIR -name "files_*.tar.gz" -mtime +7 -delete

# Log
echo "Backup completed at $(date)" >> $BACKUP_DIR/backup.log
```

```bash
# Make executable
sudo chmod +x /etc/cron.daily/smartbk-backup
```

---

## 🚨 Troubleshooting

### Problem: "Koneksi database gagal"
**Solution:**
```bash
# Check MySQL running
sudo systemctl status mysql

# Check credentials
mysql -u smartbk_user -p smart_bk

# Check config/db.php
cat /var/www/html/smartbk/config/db.php
```

### Problem: "Permission denied" saat upload foto
**Solution:**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/html/smartbk/assets/uploads
sudo chmod -R 777 /var/www/html/smartbk/assets/uploads
```

### Problem: "500 Internal Server Error"
**Solution:**
```bash
# Check error log
sudo tail -100 /var/log/apache2/smartbk_error.log

# Check PHP errors
sudo tail -100 /var/log/php_errors.log

# Enable display_errors temporarily (jangan di production!)
# php.ini: display_errors = On
```

### Problem: Session tidak persist
**Solution:**
```bash
# Check session path writable
ls -la /var/lib/php/sessions

# Fix permissions
sudo chmod 1733 /var/lib/php/sessions
```

---

## 📱 Post-Deployment Checklist

- [ ] Website accessible via HTTPS
- [ ] Login berfungsi normal
- [ ] Upload foto siswa berfungsi
- [ ] Database connection OK
- [ ] API endpoints respond correctly
- [ ] Email notification setup (future)
- [ ] Backup script running
- [ ] Monitoring active
- [ ] DNS pointing ke server
- [ ] SSL certificate valid
- [ ] Performance test passed
- [ ] Security audit passed
- [ ] Documentation updated
- [ ] Team training completed

---

## 📞 Support & Maintenance

### Weekly Tasks
- [ ] Check error logs
- [ ] Review backup logs
- [ ] Monitor disk space
- [ ] Check SSL certificate expiry

### Monthly Tasks
- [ ] Update dependencies (jika ada)
- [ ] Review user accounts
- [ ] Database optimization
- [ ] Security audit
- [ ] Performance review

### Quarterly Tasks
- [ ] Full backup test (restore)
- [ ] Disaster recovery drill
- [ ] Security penetration test
- [ ] User feedback review

---

## 🎯 Success Metrics

- **Uptime:** Target 99.9%
- **Response Time:** < 2 seconds
- **Database Size:** Monitor growth
- **Active Users:** Track daily/monthly
- **Error Rate:** < 0.1%

---

**Deployment Version:** 1.0  
**Last Updated:** 2026-08-05  
**Next Review:** 2026-09-05  
**Status:** ✅ Ready for Production
