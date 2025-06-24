#!/bin/bash
# install.sh - Smart Notification System Auto Installer

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="smart-notification-system"
INSTALL_DIR="/var/www/$PROJECT_NAME"
DB_NAME="smart_notification"
DB_USER="smart_notification"

# Functions
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Generate random password
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

# Detect OS
detect_os() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    else
        print_error "Cannot detect operating system"
        exit 1
    fi
}

# Check if script is run as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "This script must be run as root"
        exit 1
    fi
}

# Install dependencies for Ubuntu/Debian
install_ubuntu_dependencies() {
    print_status "Installing dependencies for Ubuntu/Debian..."
    
    apt update
    apt install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt update
    
    # Install PHP and extensions
    apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common php8.2-zip \
        php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath \
        php8.2-ldap php8.2-pgsql php8.2-redis php8.2-intl php8.2-opcache
    
    # Install PostgreSQL
    apt install -y postgresql postgresql-contrib
    
    # Install RabbitMQ
    apt install -y rabbitmq-server
    
    # Install Redis
    apt install -y redis-server
    
    # Install Nginx
    apt install -y nginx
    
    # Install additional tools
    apt install -y curl wget unzip git htop
    
    print_success "Dependencies installed successfully"
}

# Install dependencies for CentOS/RHEL/Rocky
install_centos_dependencies() {
    print_status "Installing dependencies for CentOS/RHEL/Rocky..."
    
    # Install EPEL and Remi repositories
    dnf install -y epel-release
    dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
    dnf module enable php:remi-8.2 -y
    
    # Install PHP and extensions
    dnf install -y php php-fpm php-cli php-common php-zip php-gd \
        php-mbstring php-curl php-xml php-bcmath php-ldap php-pgsql \
        php-redis php-intl php-opcache
    
    # Install PostgreSQL
    dnf install -y postgresql15-server postgresql15
    postgresql-setup --initdb
    
    # Install RabbitMQ
    dnf install -y rabbitmq-server
    
    # Install Redis
    dnf install -y redis
    
    # Install Nginx
    dnf install -y nginx
    
    # Install additional tools
    dnf install -y curl wget unzip git htop
    
    print_success "Dependencies installed successfully"
}

# Install Composer
install_composer() {
    print_status "Installing Composer..."
    
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    
    print_success "Composer installed successfully"
}

# Install Node.js
install_nodejs() {
    print_status "Installing Node.js..."
    
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt install -y nodejs
    
    print_success "Node.js installed successfully"
}

# Setup PostgreSQL
setup_postgresql() {
    print_status "Setting up PostgreSQL..."
    
    # Generate passwords
    DB_PASSWORD=$(generate_password)
    
    # Start PostgreSQL service
    systemctl enable postgresql
    systemctl start postgresql
    
    # Create database and user
    sudo -u postgres psql << EOF
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';
CREATE DATABASE $DB_NAME OWNER $DB_USER;
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\q
EOF
    
    # Update pg_hba.conf for local connections
    PG_VERSION=$(sudo -u postgres psql -t -c "SELECT version();" | grep -oP '(?<=PostgreSQL )\d+')
    PG_CONFIG_DIR="/etc/postgresql/$PG_VERSION/main"
    
    if [[ -d $PG_CONFIG_DIR ]]; then
        sed -i "s/#local_replication/local   $DB_NAME   $DB_USER   md5\n#local_replication/" $PG_CONFIG_DIR/pg_hba.conf
    fi
    
    systemctl restart postgresql
    
    print_success "PostgreSQL setup completed"
    echo "Database Password: $DB_PASSWORD" >> /root/smart-notification-passwords.txt
}

# Setup RabbitMQ
setup_rabbitmq() {
    print_status "Setting up RabbitMQ..."
    
    # Generate password
    RABBITMQ_PASSWORD=$(generate_password)
    
    # Start RabbitMQ service
    systemctl enable rabbitmq-server
    systemctl start rabbitmq-server
    
    # Enable management plugin
    rabbitmq-plugins enable rabbitmq_management
    
    # Create user
    rabbitmqctl add_user $DB_USER $RABBITMQ_PASSWORD
    rabbitmqctl set_user_tags $DB_USER administrator
    rabbitmqctl set_permissions -p / $DB_USER ".*" ".*" ".*"
    
    systemctl restart rabbitmq-server
    
    print_success "RabbitMQ setup completed"
    echo "RabbitMQ Password: $RABBITMQ_PASSWORD" >> /root/smart-notification-passwords.txt
}

# Setup Redis
setup_redis() {
    print_status "Setting up Redis..."
    
    # Generate password
    REDIS_PASSWORD=$(generate_password)
    
    # Configure Redis
    sed -i "s/# requirepass foobared/requirepass $REDIS_PASSWORD/" /etc/redis/redis.conf
    
    # Start Redis service
    systemctl enable redis
    systemctl start redis
    
    print_success "Redis setup completed"
    echo "Redis Password: $REDIS_PASSWORD" >> /root/smart-notification-passwords.txt
}

# Setup application directory
setup_application() {
    print_status "Setting up application..."
    
    # Create directory
    mkdir -p $INSTALL_DIR
    cd $INSTALL_DIR
    
    # Clone or create Laravel project
    if [[ -n "$REPO_URL" ]]; then
        git clone $REPO_URL .
    else
        composer create-project laravel/laravel . --prefer-dist
    fi
    
    # Install additional packages
    composer require adldap2/adldap2-laravel
    composer require vladimir-yuldashev/laravel-queue-rabbitmq
    composer require microsoft/microsoft-graph
    composer require tymon/jwt-auth
    composer require spatie/laravel-permission
    composer require spatie/laravel-activitylog
    composer require maatwebsite/excel
    composer require barryvdh/laravel-dompdf
    composer require predis/predis
    
    # Set permissions
    chown -R www-data:www-data $INSTALL_DIR
    chmod -R 755 $INSTALL_DIR
    chmod -R 775 $INSTALL_DIR/storage
    chmod -R 775 $INSTALL_DIR/bootstrap/cache
    
    print_success "Application setup completed"
}

# Create environment file
create_env_file() {
    print_status "Creating environment file..."
    
    # Read passwords from file
    DB_PASSWORD=$(grep "Database Password:" /root/smart-notification-passwords.txt | cut -d: -f2 | tr -d ' ')
    RABBITMQ_PASSWORD=$(grep "RabbitMQ Password:" /root/smart-notification-passwords.txt | cut -d: -f2 | tr -d ' ')
    REDIS_PASSWORD=$(grep "Redis Password:" /root/smart-notification-passwords.txt | cut -d: -f2 | tr -d ' ')
    
    # Get server IP
    SERVER_IP=$(curl -s ifconfig.me)
    
    cat > $INSTALL_DIR/.env << EOF
APP_NAME="Smart Notification System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://$SERVER_IP

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER