#!/bin/bash
# VanTracing System Deployment Script / Script de Deploy do Sistema VanTracing
# This script helps deploy and configure the complete VanTracing system
# Este script ajuda a implementar e configurar o sistema VanTracing completo

set -e  # Exit on any error / Sair em caso de erro

# Colors for output / Cores para saída
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration / Configuração
WEBROOT=${WEBROOT:-"/var/www/html/vantracing"}
DB_NAME=${DB_NAME:-"vantracing_db"}
DB_USER=${DB_USER:-"vantracing_user"}
DB_PASSWORD=${DB_PASSWORD:-""}
DB_HOST=${DB_HOST:-"localhost"}
PHP_USER=${PHP_USER:-"www-data"}
ENABLE_SSL=${ENABLE_SSL:-"false"}
DOMAIN=${DOMAIN:-"localhost"}

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  VanTracing Enterprise Deployment${NC}"
echo -e "${BLUE}========================================${NC}"

# Function to print status / Função para imprimir status
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root / Verificar se executando como root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

# Detect OS and package manager / Detectar SO e gerenciador de pacotes
detect_os() {
    if [ -f /etc/debian_version ]; then
        OS="debian"
        PKG_MANAGER="apt"
    elif [ -f /etc/redhat-release ]; then
        OS="redhat"
        PKG_MANAGER="yum"
    elif [ -f /etc/arch-release ]; then
        OS="arch"
        PKG_MANAGER="pacman"
    else
        print_warning "Unknown OS detected, assuming Debian/Ubuntu"
        OS="debian"
        PKG_MANAGER="apt"
    fi
    
    print_status "Detected OS: $OS"
}

# Install system dependencies / Instalar dependências do sistema
install_dependencies() {
    print_status "Installing system dependencies..."
    
    case $PKG_MANAGER in
        "apt")
            apt update
            apt install -y nginx mysql-server php8.1-fpm php8.1-mysql php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd php8.1-intl certbot python3-certbot-nginx cron logrotate
            ;;
        "yum")
            yum update -y
            yum install -y nginx mysql-server php-fpm php-mysql php-curl php-json php-mbstring php-xml php-zip php-gd php-intl certbot python3-certbot-nginx crontabs logrotate
            ;;
        "pacman")
            pacman -Syu --noconfirm
            pacman -S --noconfirm nginx mysql php php-fpm certbot certbot-nginx cronie logrotate
            ;;
    esac
}

# Configure MySQL database / Configurar banco de dados MySQL
setup_database() {
    print_status "Setting up database..."
    
    # Start MySQL service / Iniciar serviço MySQL
    systemctl start mysql
    systemctl enable mysql
    
    # Secure MySQL installation (basic) / Instalação segura do MySQL (básico)
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Create database and user / Criar banco de dados e usuário
    if [ -n "$DB_PASSWORD" ]; then
        mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'$DB_HOST' IDENTIFIED BY '$DB_PASSWORD';"
        mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'$DB_HOST';"
        mysql -e "FLUSH PRIVILEGES;"
        print_status "Database created with user: $DB_USER"
    else
        print_warning "No database password provided, skipping user creation"
    fi
}

# Create database tables / Criar tabelas do banco de dados
create_tables() {
    print_status "Creating database tables..."
    
    # Users table / Tabela de usuários
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        senha VARCHAR(255) NOT NULL,
        tipo ENUM('responsavel', 'motorista', 'admin') NOT NULL,
        telefone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_tipo (tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    # Children table / Tabela de crianças
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS children (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        idade INT NOT NULL,
        endereco TEXT NOT NULL,
        escola VARCHAR(100) NOT NULL,
        responsavel_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (responsavel_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_responsavel (responsavel_id),
        INDEX idx_escola (escola)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    # Routes table / Tabela de rotas
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS routes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        motorista_id INT NOT NULL,
        route_name VARCHAR(100) NOT NULL,
        start_location TEXT NOT NULL,
        end_location TEXT NOT NULL,
        schedule_time TIME NOT NULL,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (motorista_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_motorista (motorista_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    # System metrics table / Tabela de métricas do sistema
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS system_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_type VARCHAR(50) NOT NULL,
        metric_name VARCHAR(100) NOT NULL,
        metric_value DECIMAL(10,4) NOT NULL,
        unit VARCHAR(20),
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        additional_data JSON,
        INDEX idx_type_name (metric_type, metric_name),
        INDEX idx_timestamp (timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    # Performance snapshots table / Tabela de snapshots de performance
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS performance_snapshots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        snapshot_name VARCHAR(100) NOT NULL,
        data JSON NOT NULL,
        health_score INT DEFAULT 100,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (snapshot_name),
        INDEX idx_created (created_at),
        INDEX idx_health (health_score)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    # Metric alerts table / Tabela de alertas de métricas
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS metric_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alert_type VARCHAR(50) NOT NULL,
        severity ENUM('info', 'warning', 'error', 'critical', 'fatal') DEFAULT 'warning',
        message TEXT NOT NULL,
        metric_data JSON,
        resolved BOOLEAN DEFAULT FALSE,
        resolved_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (alert_type),
        INDEX idx_severity (severity),
        INDEX idx_resolved (resolved),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    # Password reset tokens table / Tabela de tokens de reset de senha
    mysql $DB_NAME -e "
    CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_email (email),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
    
    print_status "Database tables created successfully"
}

# Setup web directory / Configurar diretório web
setup_web_directory() {
    print_status "Setting up web directory..."
    
    # Create web directory / Criar diretório web
    mkdir -p $WEBROOT
    
    # Copy files (assuming script is in the project directory)
    # Copiar arquivos (assumindo que o script está no diretório do projeto)
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
    
    cp -r "$PROJECT_DIR"/* $WEBROOT/
    
    # Set proper permissions / Definir permissões adequadas
    chown -R $PHP_USER:$PHP_USER $WEBROOT
    find $WEBROOT -type f -exec chmod 644 {} \;
    find $WEBROOT -type d -exec chmod 755 {} \;
    
    # Create necessary directories / Criar diretórios necessários
    mkdir -p $WEBROOT/logs $WEBROOT/cache $WEBROOT/backups $WEBROOT/uploads
    chown -R $PHP_USER:$PHP_USER $WEBROOT/logs $WEBROOT/cache $WEBROOT/backups $WEBROOT/uploads
    chmod 755 $WEBROOT/logs $WEBROOT/cache $WEBROOT/backups $WEBROOT/uploads
}

# Configure Nginx / Configurar Nginx
setup_nginx() {
    print_status "Configuring Nginx..."
    
    cat > /etc/nginx/sites-available/vantracing << EOF
server {
    listen 80;
    server_name $DOMAIN www.$DOMAIN;
    root $WEBROOT;
    index index.html index.php;

    # Security headers / Cabeçalhos de segurança
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net;";

    # Gzip compression / Compressão Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;

    # Rate limiting / Limitação de taxa
    limit_req_zone \$binary_remote_addr zone=login:10m rate=5r/m;
    limit_req_zone \$binary_remote_addr zone=api:10m rate=30r/m;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api/ {
        limit_req zone=api burst=10 nodelay;
        try_files \$uri \$uri/ =404;
    }

    location ~ ^/api/(login|register|reset-senha)\.php$ {
        limit_req zone=login burst=3 nodelay;
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }

    location /logs/ {
        deny all;
    }

    location /backups/ {
        deny all;
    }

    location /cache/ {
        deny all;
    }

    # Static file caching / Cache de arquivos estáticos
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
    }
}
EOF

    # Enable site / Habilitar site
    ln -sf /etc/nginx/sites-available/vantracing /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Test nginx configuration / Testar configuração do nginx
    nginx -t
    
    # Start services / Iniciar serviços
    systemctl start nginx php8.1-fpm
    systemctl enable nginx php8.1-fpm
}

# Setup SSL with Let's Encrypt / Configurar SSL com Let's Encrypt
setup_ssl() {
    if [ "$ENABLE_SSL" == "true" ] && [ "$DOMAIN" != "localhost" ]; then
        print_status "Setting up SSL certificate..."
        
        # Stop nginx temporarily / Parar nginx temporariamente
        systemctl stop nginx
        
        # Get certificate / Obter certificado
        certbot --nginx -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN --redirect
        
        # Auto-renewal / Auto-renovação
        echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
        
        systemctl start nginx
        print_status "SSL certificate installed successfully"
    else
        print_warning "SSL setup skipped (ENABLE_SSL=$ENABLE_SSL, DOMAIN=$DOMAIN)"
    fi
}

# Setup cron jobs / Configurar tarefas cron
setup_cron() {
    print_status "Setting up cron jobs..."
    
    # Create cron job for metrics collection / Criar tarefa cron para coleta de métricas
    echo "*/5 * * * * /usr/bin/php $WEBROOT/api/metrics_collector.php >/dev/null 2>&1" | crontab -u $PHP_USER -
    
    # Create cron job for cleanup / Criar tarefa cron para limpeza
    echo "0 3 * * 0 /usr/bin/php $WEBROOT/api/metrics_collector.php --cleanup >/dev/null 2>&1" | crontab -u $PHP_USER -
    
    # Create cron job for backups / Criar tarefa cron para backups
    echo "0 2 * * * /usr/bin/php $WEBROOT/api/backup_system.php >/dev/null 2>&1" | crontab -u $PHP_USER -
    
    print_status "Cron jobs configured"
}

# Configure log rotation / Configurar rotação de logs
setup_logrotation() {
    print_status "Setting up log rotation..."
    
    cat > /etc/logrotate.d/vantracing << EOF
$WEBROOT/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    copytruncate
    su $PHP_USER $PHP_USER
}
EOF

    print_status "Log rotation configured"
}

# Create database connection file / Criar arquivo de conexão com banco de dados
create_db_config() {
    print_status "Creating database configuration..."
    
    cat > $WEBROOT/api/db_connect.php << EOF
<?php
/**
 * Database Connection Configuration / Configuração de Conexão com Banco de Dados
 * Auto-generated by deployment script / Auto-gerado pelo script de deploy
 */

\$host = '$DB_HOST';
\$dbname = '$DB_NAME';
\$username = '$DB_USER';
\$password = '$DB_PASSWORD';

try {
    \$conn = new PDO("mysql:host=\$host;dbname=\$dbname;charset=utf8mb4", \$username, \$password);
    \$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    \$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException \$e) {
    error_log("Database connection failed: " . \$e->getMessage());
    die("Erro de conexão com o banco de dados");
}
?>
EOF

    chown $PHP_USER:$PHP_USER $WEBROOT/api/db_connect.php
    chmod 600 $WEBROOT/api/db_connect.php
}

# Run system tests / Executar testes do sistema
run_tests() {
    print_status "Running system tests..."
    
    # Test database connection / Testar conexão com banco de dados
    if mysql $DB_NAME -e "SELECT 1;" >/dev/null 2>&1; then
        print_status "✓ Database connection successful"
    else
        print_error "✗ Database connection failed"
        return 1
    fi
    
    # Test web server / Testar servidor web
    if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200"; then
        print_status "✓ Web server responding"
    else
        print_warning "⚠ Web server may not be responding correctly"
    fi
    
    # Test PHP / Testar PHP
    if php -v >/dev/null 2>&1; then
        print_status "✓ PHP is working"
    else
        print_error "✗ PHP test failed"
        return 1
    fi
    
    # Test metrics collection / Testar coleta de métricas
    if php $WEBROOT/api/metrics_collector.php --dry-run >/dev/null 2>&1; then
        print_status "✓ Metrics system is ready"
    else
        print_warning "⚠ Metrics system may need attention"
    fi
    
    print_status "System tests completed"
}

# Main installation function / Função principal de instalação
main() {
    echo -e "${BLUE}Starting VanTracing deployment...${NC}"
    
    check_root
    detect_os
    
    print_status "Configuration:"
    print_status "- Web Root: $WEBROOT"
    print_status "- Database: $DB_NAME"
    print_status "- Database User: $DB_USER"
    print_status "- Domain: $DOMAIN"
    print_status "- SSL Enabled: $ENABLE_SSL"
    
    if [ -z "$DB_PASSWORD" ]; then
        print_warning "No database password set. Please set DB_PASSWORD environment variable."
        read -s -p "Enter database password: " DB_PASSWORD
        echo
    fi
    
    install_dependencies
    setup_database
    create_tables
    setup_web_directory
    create_db_config
    setup_nginx
    setup_ssl
    setup_cron
    setup_logrotation
    run_tests
    
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  VanTracing Deployment Completed!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo
    echo -e "${BLUE}Access your VanTracing system at:${NC}"
    
    if [ "$ENABLE_SSL" == "true" ] && [ "$DOMAIN" != "localhost" ]; then
        echo -e "${GREEN}https://$DOMAIN${NC}"
    else
        echo -e "${GREEN}http://$DOMAIN${NC}"
    fi
    
    echo
    echo -e "${BLUE}Next steps:${NC}"
    echo "1. Create an admin user through the registration page"
    echo "2. Review the monitoring dashboard at /monitoring_dashboard.html"
    echo "3. Check system logs in $WEBROOT/logs/"
    echo "4. Configure additional settings as needed"
    echo
    echo -e "${YELLOW}Important files:${NC}"
    echo "- Web files: $WEBROOT"
    echo "- Logs: $WEBROOT/logs"
    echo "- Database config: $WEBROOT/api/db_connect.php"
    echo "- Nginx config: /etc/nginx/sites-available/vantracing"
    echo
    echo -e "${BLUE}For support, check the documentation or logs.${NC}"
}

# Run main function / Executar função principal
main "$@"