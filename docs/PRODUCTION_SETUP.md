# Production Deployment Guide / Guia de Implantação em Produção

## 🚀 Production Setup / Configuração de Produção

### Prerequisites / Pré-requisitos
- Ubuntu 20.04 LTS or newer / Ubuntu 20.04 LTS ou mais recente
- Apache 2.4+ or Nginx 1.18+ / Apache 2.4+ ou Nginx 1.18+
- PHP 7.4+ with extensions / PHP 7.4+ com extensões
- MySQL 8.0+ / MySQL 8.0+
- SSL Certificate / Certificado SSL

### System Setup / Configuração do Sistema

1) **Update system / Atualizar sistema**
```bash
sudo apt update && sudo apt upgrade -y
```

2) **Install PHP and extensions / Instalar PHP e extensões**
```bash
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip
```

3) **Install MySQL / Instalar MySQL**
```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

### Apache Configuration / Configuração do Apache

1) **Install Apache / Instalar Apache**
```bash
sudo apt install -y apache2
sudo a2enmod rewrite ssl headers
```

2) **Create virtual host / Criar host virtual**
```bash
sudo nano /etc/apache2/sites-available/vantracing.conf
```

**Configuration file / Arquivo de configuração:**
```apache
<VirtualHost *:80>
    ServerName vantracing.yourdomain.com
    DocumentRoot /var/www/vantracing
    
    # Redirect HTTP to HTTPS / Redirecionar HTTP para HTTPS
    Redirect permanent / https://vantracing.yourdomain.com/
</VirtualHost>

<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName vantracing.yourdomain.com
    DocumentRoot /var/www/vantracing
    
    # SSL Configuration / Configuração SSL
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/vantracing.yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/vantracing.yourdomain.com/privkey.pem
    
    # Security Headers / Cabeçalhos de Segurança
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self'"
    
    <Directory /var/www/vantracing>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP Security / Segurança PHP
        php_flag display_errors Off
        php_flag log_errors On
        php_value error_log /var/log/apache2/vantracing_php_errors.log
    </Directory>
    
    # Protect sensitive files / Proteger arquivos sensíveis
    <FilesMatch "^\.env$">
        Require all denied
    </FilesMatch>
    
    <DirectoryMatch "^/.*/\.git/">
        Require all denied
    </DirectoryMatch>
    
    <DirectoryMatch "^/.*/database/migrations/">
        Require all denied
    </DirectoryMatch>
    
    # Enable compression / Habilitar compressão
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
        AddOutputFilterByType DEFLATE application/json
    </IfModule>
    
    # Cache static files / Cache de arquivos estáticos
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
        ExpiresByType image/png "access plus 1 month"
        ExpiresByType image/jpg "access plus 1 month"
        ExpiresByType image/jpeg "access plus 1 month"
        ExpiresByType image/gif "access plus 1 month"
        ExpiresByType image/svg+xml "access plus 1 month"
    </IfModule>
    
    ErrorLog ${APACHE_LOG_DIR}/vantracing_error.log
    CustomLog ${APACHE_LOG_DIR}/vantracing_access.log combined
</VirtualHost>
</IfModule>
```

3) **Enable site / Habilitar site**
```bash
sudo a2ensite vantracing.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

### Nginx Configuration / Configuração do Nginx

1) **Install Nginx / Instalar Nginx**
```bash
sudo apt install -y nginx
```

2) **Create server block / Criar bloco de servidor**
```bash
sudo nano /etc/nginx/sites-available/vantracing
```

**Configuration file / Arquivo de configuração:**
```nginx
# HTTP redirect to HTTPS / Redirecionamento HTTP para HTTPS
server {
    listen 80;
    server_name vantracing.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name vantracing.yourdomain.com;
    root /var/www/vantracing;
    index index.html index.php;

    # SSL Configuration / Configuração SSL
    ssl_certificate /etc/letsencrypt/live/vantracing.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vantracing.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers / Cabeçalhos de Segurança
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self'" always;

    # Main location / Localização principal
    location / {
        try_files $uri $uri/ /index.html;
    }

    # PHP processing / Processamento PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Security / Segurança
        fastcgi_hide_header X-Powered-By;
    }

    # Protect sensitive files / Proteger arquivos sensíveis
    location ~ /\.env {
        deny all;
        return 404;
    }

    location ~ /\.git {
        deny all;
        return 404;
    }

    location ~ ^/database/migrations/ {
        deny all;
        return 404;
    }

    location ~ ^/logs/ {
        deny all;
        return 404;
    }

    # Static file caching / Cache de arquivos estáticos
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }

    # Gzip compression / Compressão Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/javascript
        application/xml+rss
        application/json;

    # Logging / Logs
    access_log /var/log/nginx/vantracing_access.log;
    error_log /var/log/nginx/vantracing_error.log;
}
```

3) **Enable site / Habilitar site**
```bash
sudo ln -s /etc/nginx/sites-available/vantracing /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

### SSL Certificate with Let's Encrypt / Certificado SSL com Let's Encrypt

```bash
# Install Certbot / Instalar Certbot
sudo apt install -y certbot python3-certbot-apache  # For Apache / Para Apache
# OR / OU
sudo apt install -y certbot python3-certbot-nginx   # For Nginx / Para Nginx

# Get certificate / Obter certificado
sudo certbot --apache -d vantracing.yourdomain.com  # Apache
# OR / OU
sudo certbot --nginx -d vantracing.yourdomain.com   # Nginx

# Auto-renewal / Renovação automática
sudo crontab -e
# Add line / Adicionar linha:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

### Application Deployment / Implantação da Aplicação

1) **Clone repository / Clonar repositório**
```bash
cd /var/www
sudo git clone https://github.com/Kevynz/Vantracing.git vantracing
sudo chown -R www-data:www-data vantracing/
```

2) **Setup environment / Configurar ambiente**
```bash
cd /var/www/vantracing
sudo cp .env.example .env
sudo nano .env
```

**Production .env settings / Configurações .env de produção:**
```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=localhost
DB_NAME=vantracing_db
DB_USER=vantracing_user
DB_PASSWORD=secure_random_password_here
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

3) **Setup database / Configurar banco de dados**
```bash
# Create database and user / Criar banco de dados e usuário
sudo mysql -u root -p
```
```sql
CREATE DATABASE vantracing_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'vantracing_user'@'localhost' IDENTIFIED BY 'secure_random_password_here';
GRANT ALL PRIVILEGES ON vantracing_db.* TO 'vantracing_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Apply migrations / Aplicar migrações
mysql -u vantracing_user -p vantracing_db < database/migrations/001_init.sql
mysql -u vantracing_user -p vantracing_db < database/migrations/002_profile_split.sql
```

4) **Set permissions / Definir permissões**
```bash
sudo chown -R www-data:www-data /var/www/vantracing/
sudo chmod -R 755 /var/www/vantracing/
sudo chmod -R 775 /var/www/vantracing/logs/
sudo chmod -R 775 /var/www/vantracing/uploads/
sudo chmod 600 /var/www/vantracing/.env
```

### Monitoring and Backup / Monitoramento e Backup

1) **Log rotation / Rotação de logs**
```bash
sudo nano /etc/logrotate.d/vantracing
```
```
/var/www/vantracing/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    notifempty
    create 0664 www-data www-data
    postrotate
        systemctl reload apache2  # or nginx
    endscript
}
```

2) **Database backup script / Script de backup do banco**
```bash
sudo nano /usr/local/bin/vantracing-backup.sh
```
```bash
#!/bin/bash
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/vantracing"
mkdir -p $BACKUP_DIR

# Database backup / Backup do banco de dados
mysqldump -u vantracing_user -p'secure_random_password_here' vantracing_db > $BACKUP_DIR/vantracing_db_$DATE.sql

# Compress / Comprimir
gzip $BACKUP_DIR/vantracing_db_$DATE.sql

# Remove old backups (keep 30 days) / Remover backups antigos (manter 30 dias)
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/vantracing_db_$DATE.sql.gz"
```

```bash
sudo chmod +x /usr/local/bin/vantracing-backup.sh

# Add to crontab for daily backup / Adicionar ao crontab para backup diário
sudo crontab -e
# Add line / Adicionar linha:
# 0 2 * * * /usr/local/bin/vantracing-backup.sh
```

### Performance Optimization / Otimização de Performance

1) **PHP-FPM tuning / Ajuste do PHP-FPM**
```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

2) **MySQL tuning / Ajuste do MySQL**
```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```
```ini
[mysqld]
innodb_buffer_pool_size = 256M
query_cache_type = 1
query_cache_size = 64M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Security Checklist / Lista de Verificação de Segurança

- [ ] SSL/TLS certificate installed and configured / Certificado SSL/TLS instalado e configurado
- [ ] Security headers implemented / Cabeçalhos de segurança implementados
- [ ] Database user with minimal privileges / Usuário de banco com privilégios mínimos
- [ ] .env file protected (600 permissions) / Arquivo .env protegido (permissões 600)
- [ ] Sensitive directories blocked / Diretórios sensíveis bloqueados
- [ ] PHP display_errors disabled / display_errors do PHP desabilitado
- [ ] Regular backups scheduled / Backups regulares agendados
- [ ] Firewall configured / Firewall configurado
- [ ] System updates automated / Atualizações do sistema automatizadas
- [ ] Fail2ban installed for brute force protection / Fail2ban instalado para proteção contra força bruta

### Troubleshooting / Solução de Problemas

**Common issues / Problemas comuns:**

1. **Permission errors / Erros de permissão**
   ```bash
   sudo chown -R www-data:www-data /var/www/vantracing/
   sudo chmod -R 755 /var/www/vantracing/
   ```

2. **PHP errors not showing / Erros PHP não aparecem**
   - Check error logs: `sudo tail -f /var/log/apache2/vantracing_php_errors.log`
   - Verificar logs de erro: `sudo tail -f /var/log/apache2/vantracing_php_errors.log`

3. **Database connection issues / Problemas de conexão com banco**
   - Verify .env credentials / Verificar credenciais no .env
   - Test connection: `mysql -u vantracing_user -p vantracing_db`
   - Testar conexão: `mysql -u vantracing_user -p vantracing_db`

4. **SSL certificate issues / Problemas com certificado SSL**
   ```bash
   sudo certbot certificates
   sudo certbot renew --dry-run
   ```

For more help, check the logs and contact support.
Para mais ajuda, verifique os logs e entre em contato com o suporte.