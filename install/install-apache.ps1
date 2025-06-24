# install-apache.ps1 - Smart Notification System Windows Server (Apache) Installer

param(
    [string]$InstallPath = "C:\Apache24\htdocs\smart-notification-system",
    [string]$PHPVersion = "8.2.13",
    [string]$ApacheVersion = "2.4.58",
    [string]$ComposerVersion = "latest",
    [switch]$SkipApache = $false,
    [switch]$SkipPHP = $false,
    [switch]$SkipServices = $false
)

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Error "This script requires Administrator privileges. Please run PowerShell as Administrator."
    exit 1
}

# Enable TLS 1.2 for downloads
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Smart Notification System - Windows Apache Installer" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# Functions
function Write-Status {
    param($Message)
    Write-Host "[INFO] $Message" -ForegroundColor Blue
}

function Write-Success {
    param($Message)
    Write-Host "[SUCCESS] $Message" -ForegroundColor Green
}

function Write-Warning {
    param($Message)
    Write-Host "[WARNING] $Message" -ForegroundColor Yellow
}

function Write-Error {
    param($Message)
    Write-Host "[ERROR] $Message" -ForegroundColor Red
}

function Install-Chocolatey {
    Write-Status "Installing Chocolatey package manager..."
    
    if (Get-Command choco -ErrorAction SilentlyContinue) {
        Write-Success "Chocolatey is already installed"
        return
    }
    
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))
    
    # Refresh environment variables
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    
    Write-Success "Chocolatey installed successfully"
}

function Install-Apache {
    if ($SkipApache) {
        Write-Warning "Skipping Apache installation"
        return
    }
    
    Write-Status "Installing Apache HTTP Server..."
    
    # Check if Apache is already installed
    if (Test-Path "C:\Apache24\bin\httpd.exe") {
        Write-Success "Apache is already installed"
        return
    }
    
    # Download Apache
    $apacheUrl = "https://www.apachelounge.com/download/VS17/binaries/httpd-$ApacheVersion-win64-VS17.zip"
    $apacheZip = "$env:TEMP\apache.zip"
    
    Write-Status "Downloading Apache from $apacheUrl..."
    Invoke-WebRequest -Uri $apacheUrl -OutFile $apacheZip
    
    # Extract Apache
    Write-Status "Extracting Apache..."
    Expand-Archive -Path $apacheZip -DestinationPath "C:\" -Force
    Remove-Item $apacheZip
    
    # Configure Apache
    Write-Status "Configuring Apache..."
    $httpdConf = "C:\Apache24\conf\httpd.conf"
    $httpdContent = Get-Content $httpdConf
    
    # Update Apache configuration
    $httpdContent = $httpdContent -replace '#ServerName www.example.com:80', 'ServerName localhost:80'
    $httpdContent = $httpdContent -replace 'DocumentRoot "c:/Apache24/htdocs"', 'DocumentRoot "c:/Apache24/htdocs"'
    $httpdContent = $httpdContent -replace 'DirectoryIndex index.html', 'DirectoryIndex index.php index.html index.htm'
    
    # Enable mod_rewrite
    $httpdContent = $httpdContent -replace '#LoadModule rewrite_module modules/mod_rewrite.so', 'LoadModule rewrite_module modules/mod_rewrite.so'
    
    # Add PHP module configuration
    $phpConfig = @"

# PHP Configuration
LoadModule php_module "C:/PHP/php8apache2_4.dll"
PHPIniDir "C:/PHP"
AddType application/x-httpd-php .php

# Virtual Host for Smart Notification System
<VirtualHost *:80>
    DocumentRoot "C:/Apache24/htdocs/smart-notification-system/public"
    ServerName localhost
    
    <Directory "C:/Apache24/htdocs/smart-notification-system/public">
        AllowOverride All
        Require all granted
        
        # Laravel Pretty URLs
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [L]
    </Directory>
    
    # Security settings
    <Files ".env">
        Require all denied
    </Files>
    
    <DirectoryMatch "/(storage|bootstrap/cache)">
        Require all denied
    </DirectoryMatch>
    
    # Logging
    ErrorLog "logs/smart-notification-error.log"
    CustomLog "logs/smart-notification-access.log" combined
</VirtualHost>
"@
    
    $httpdContent += $phpConfig
    $httpdContent | Set-Content $httpdConf
    
    # Install Apache as Windows Service
    Write-Status "Installing Apache as Windows Service..."
    & "C:\Apache24\bin\httpd.exe" -k install -n "Apache2.4"
    
    Write-Success "Apache installed and configured successfully"
}

function Install-PHP {
    if ($SkipPHP) {
        Write-Warning "Skipping PHP installation"
        return
    }
    
    Write-Status "Installing PHP $PHPVersion for Apache..."
    
    # Check if PHP is already installed
    if (Get-Command php -ErrorAction SilentlyContinue) {
        $currentVersion = php -v
        Write-Success "PHP is already installed: $currentVersion"
        return
    }
    
    $phpPath = "C:\PHP"
    $phpZip = "$env:TEMP\php.zip"
    
    # Use Thread Safe version for Apache
    $phpUrl = "https://windows.php.net/downloads/releases/php-$PHPVersion-Win32-vs16-x64.zip"
    
    # Create PHP directory
    if (!(Test-Path $phpPath)) {
        New-Item -ItemType Directory -Path $phpPath -Force
    }
    
    # Download PHP
    Write-Status "Downloading PHP Thread Safe version from $phpUrl..."
    Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip
    
    # Extract PHP
    Write-Status "Extracting PHP to $phpPath..."
    Expand-Archive -Path $phpZip -DestinationPath $phpPath -Force
    Remove-Item $phpZip
    
    # Add PHP to PATH
    $currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
    if ($currentPath -notlike "*$phpPath*") {
        [Environment]::SetEnvironmentVariable("Path", "$currentPath;$phpPath", "Machine")
        $env:Path += ";$phpPath"
    }
    
    # Create php.ini from template
    $phpIniDev = "$phpPath\php.ini-development"
    $phpIni = "$phpPath\php.ini"
    
    if (Test-Path $phpIniDev) {
        Copy-Item $phpIniDev $phpIni
    }
    
    # Configure PHP.ini for Apache
    Write-Status "Configuring PHP.ini for Apache..."
    $phpIniContent = Get-Content $phpIni
    
    # Update PHP settings
    $phpIniContent = $phpIniContent -replace ';extension=openssl', 'extension=openssl'
    $phpIniContent = $phpIniContent -replace ';extension=pdo_pgsql', 'extension=pdo_pgsql'
    $phpIniContent = $phpIniContent -replace ';extension=pgsql', 'extension=pgsql'
    $phpIniContent = $phpIniContent -replace ';extension=mbstring', 'extension=mbstring'
    $phpIniContent = $phpIniContent -replace ';extension=curl', 'extension=curl'
    $phpIniContent = $phpIniContent -replace ';extension=fileinfo', 'extension=fileinfo'
    $phpIniContent = $phpIniContent -replace ';extension=zip', 'extension=zip'
    $phpIniContent = $phpIniContent -replace ';extension=gd', 'extension=gd'
    $phpIniContent = $phpIniContent -replace ';extension=ldap', 'extension=ldap'
    $phpIniContent = $phpIniContent -replace 'memory_limit = 128M', 'memory_limit = 512M'
    $phpIniContent = $phpIniContent -replace 'max_execution_time = 30', 'max_execution_time = 300'
    $phpIniContent = $phpIniContent -replace 'upload_max_filesize = 2M', 'upload_max_filesize = 64M'
    $phpIniContent = $phpIniContent -replace 'post_max_size = 8M', 'post_max_size = 64M'
    
    # Add additional settings for Apache
    $phpIniContent += @"

; Custom settings for Smart Notification System with Apache
date.timezone = Asia/Bangkok
expose_php = Off
display_errors = Off
log_errors = On
error_log = C:\PHP\logs\php_errors.log

; Session settings
session.cookie_httponly = 1
session.use_strict_mode = 1
session.cookie_secure = 0

; Security settings
allow_url_fopen = Off
allow_url_include = Off

; Apache specific settings
auto_prepend_file =
auto_append_file =
"@
    
    $phpIniContent | Set-Content $phpIni
    
    # Create PHP logs directory
    New-Item -ItemType Directory -Path "C:\PHP\logs" -Force
    
    Write-Success "PHP installed and configured for Apache successfully"
}

function Install-Composer {
    Write-Status "Installing Composer..."
    
    # Check if Composer is already installed
    if (Get-Command composer -ErrorAction SilentlyContinue) {
        Write-Success "Composer is already installed"
        return
    }
    
    # Download and install Composer
    $composerSetup = "$env:TEMP\composer-setup.php"
    $composerInstaller = "https://getcomposer.org/installer"
    
    Invoke-WebRequest -Uri $composerInstaller -OutFile $composerSetup
    
    # Create composer directory
    if (!(Test-Path "C:\composer")) {
        New-Item -ItemType Directory -Path "C:\composer" -Force
    }
    
    # Run composer installer
    php $composerSetup --install-dir=C:\composer --filename=composer.phar
    
    # Create batch file for global access
    $composerBat = @"
@echo off
php "C:\composer\composer.phar" %*
"@
    $composerBat | Set-Content "C:\composer\composer.bat"
    
    # Add to PATH
    $currentPath = [Environment]::GetEnvironmentVariable("Path", "Machine")
    if ($currentPath -notlike "*C:\composer*") {
        [Environment]::SetEnvironmentVariable("Path", "$currentPath;C:\composer", "Machine")
        $env:Path += ";C:\composer"
    }
    
    Remove-Item $composerSetup
    Write-Success "Composer installed successfully"
}

function Install-PostgreSQL {
    Write-Status "Installing PostgreSQL..."
    
    # Check if PostgreSQL is already installed
    if (Get-Service -Name postgresql* -ErrorAction SilentlyContinue) {
        Write-Success "PostgreSQL is already installed"
        return
    }
    
    choco install postgresql15 -y --params '/Password:SmartNotification2024!'
    
    # Wait for service to start
    Start-Sleep -Seconds 15
    
    # Create database and user
    Write-Status "Creating database and user..."
    $env:PGPASSWORD = "SmartNotification2024!"
    
    # Create user and database
    & "C:\Program Files\PostgreSQL\15\bin\psql.exe" -U postgres -c "CREATE USER smart_notification WITH PASSWORD 'SmartNotification2024!';"
    & "C:\Program Files\PostgreSQL\15\bin\psql.exe" -U postgres -c "CREATE DATABASE smart_notification OWNER smart_notification;"
    & "C:\Program Files\PostgreSQL\15\bin\psql.exe" -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE smart_notification TO smart_notification;"
    
    Write-Success "PostgreSQL installed and configured"
}

function Install-RabbitMQ {
    Write-Status "Installing RabbitMQ..."
    
    # Install Erlang first (required for RabbitMQ)
    if (!(Get-Command erl -ErrorAction SilentlyContinue)) {
        choco install erlang -y
    }
    
    # Install RabbitMQ
    if (!(Get-Service -Name RabbitMQ -ErrorAction SilentlyContinue)) {
        choco install rabbitmq -y
    }
    
    # Wait for service to start
    Start-Sleep -Seconds 10
    
    # Enable management plugin
    & "C:\Program Files\RabbitMQ Server\rabbitmq_server-*\sbin\rabbitmq-plugins.bat" enable rabbitmq_management
    
    # Create user
    & "C:\Program Files\RabbitMQ Server\rabbitmq_server-*\sbin\rabbitmqctl.bat" add_user smart_notification "RabbitMQ2024!"
    & "C:\Program Files\RabbitMQ Server\rabbitmq_server-*\sbin\rabbitmqctl.bat" set_user_tags smart_notification administrator
    & "C:\Program Files\RabbitMQ Server\rabbitmq_server-*\sbin\rabbitmqctl.bat" set_permissions -p / smart_notification ".*" ".*" ".*"
    
    Write-Success "RabbitMQ installed and configured"
}

function Install-Redis {
    Write-Status "Installing Redis..."
    
    # Redis for Windows is not officially supported, using Memurai as alternative
    choco install memurai-developer -y
    
    Write-Success "Redis (Memurai) installed successfully"
}

function Install-NodeJS {
    Write-Status "Installing Node.js..."
    
    if (Get-Command node -ErrorAction SilentlyContinue) {
        Write-Success "Node.js is already installed"
        return
    }
    
    choco install nodejs -y
    
    # Refresh environment
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
    
    Write-Success "Node.js installed successfully"
}

function Setup-Application {
    Write-Status "Setting up Laravel application..."
    
    # Create application directory
    if (!(Test-Path $InstallPath)) {
        New-Item -ItemType Directory -Path $InstallPath -Force
    }
    
    Set-Location $InstallPath
    
    # Create Laravel project
    Write-Status "Creating Laravel project..."
    composer create-project laravel/laravel . --prefer-dist --no-dev
    
    # Install required packages
    Write-Status "Installing required packages..."
    composer require adldap2/adldap2-laravel
    composer require vladimir-yuldashev/laravel-queue-rabbitmq
    composer require microsoft/microsoft-graph
    composer require tymon/jwt-auth
    composer require spatie/laravel-permission
    composer require spatie/laravel-activitylog
    composer require maatwebsite/excel
    composer require barryvdh/laravel-dompdf
    composer require predis/predis
    
    Write-Success "Laravel application setup completed"
}

function Create-EnvironmentFile {
    Write-Status "Creating environment file..."
    
    $envContent = @"
APP_NAME="Smart Notification System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smart_notification
DB_USERNAME=smart_notification
DB_PASSWORD=SmartNotification2024!

RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USER=smart_notification
RABBITMQ_PASSWORD=RabbitMQ2024!
RABBITMQ_VHOST=/
QUEUE_CONNECTION=rabbitmq

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@company.com"
MAIL_FROM_NAME="${APP_NAME}"

LDAP_LOGGING=true
LDAP_CONNECTION=default
LDAP_HOST=
LDAP_USERNAME=
LDAP_PASSWORD=
LDAP_PORT=389
LDAP_BASE_DN=
LDAP_TIMEOUT=5
LDAP_SSL=false
LDAP_TLS=false

TEAMS_CLIENT_ID=
TEAMS_CLIENT_SECRET=
TEAMS_TENANT_ID=
TEAMS_REDIRECT_URI="${APP_URL}/auth/teams/callback"

JWT_SECRET=
JWT_TTL=480

API_RATE_LIMIT=5000
API_KEY_EXPIRE_DAYS=365
API_VERSION=v1

SESSION_LIFETIME=480
SESSION_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local

NOTIFICATION_RETRY_ATTEMPTS=3
NOTIFICATION_RETRY_DELAY=60
MAX_RECIPIENTS_PER_NOTIFICATION=500
MAX_NOTIFICATIONS_PER_MINUTE=1000
"@
    
    $envContent | Set-Content "$InstallPath\.env"
    Write-Success "Environment file created"
}

function Create-HtaccessFile {
    Write-Status "Creating .htaccess file for Apache..."
    
    $htaccessContent = @"
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Hide sensitive files
<FilesMatch "^\.env">
    Require all denied
</FilesMatch>

<DirectoryMatch "/(storage|bootstrap/cache)">
    Require all denied
</DirectoryMatch>

# Prevent access to PHP files in storage
<DirectoryMatch "/storage/">
    <FilesMatch "\.php$">
        Require all denied
    </FilesMatch>
</DirectoryMatch>

# Enable compression
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
</IfModule>

# Set cache headers for static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType text/js "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/x-javascript "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresDefault "access plus 2 days"
</IfModule>
"@
    
    $htaccessContent | Set-Content "$InstallPath\public\.htaccess"
    Write-Success ".htaccess file created"
}

function Initialize-Laravel {
    Write-Status "Initializing Laravel application..."
    
    Set-Location $InstallPath
    
    # Generate application key
    php artisan key:generate --force
    
    # Generate JWT secret
    php artisan jwt:secret --force
    
    # Publish vendor configs
    php artisan vendor:publish --provider="Adldap\Laravel\AdldapServiceProvider" --force --quiet
    php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" --force --quiet
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force --quiet
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --force --quiet
    
    # Set proper permissions for storage and cache
    $storagePath = "$InstallPath\storage"
    $cachePath = "$InstallPath\bootstrap\cache"
    
    # Give full control to Users group (Apache runs under this context)
    icacls $storagePath /grant "Users:(OI)(CI)F" /T
    icacls $cachePath /grant "Users:(OI)(CI)F" /T
    
    # Run migrations and seeders
    php artisan migrate --force
    php artisan db:seed --force
    
    # Create storage link
    php artisan storage:link
    
    # Cache configurations
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    Write-Success "Laravel application initialized"
}

function Start-ApacheService {
    Write-Status "Starting Apache service..."
    
    try {
        Start-Service "Apache2.4"
        Write-Success "Apache service started successfully"
        
        # Test Apache configuration
        $response = Invoke-WebRequest -Uri "http://localhost" -UseBasicParsing -ErrorAction SilentlyContinue
        if ($response.StatusCode -eq 200) {
            Write-Success "Apache is responding on http://localhost"
        }
    }
    catch {
        Write-Error "Failed to start Apache service: $($_.Exception.Message)"
        Write-Status "Checking Apache configuration..."
        & "C:\Apache24\bin\httpd.exe" -t
    }
}

function Create-WindowsServices {
    if ($SkipServices) {
        Write-Warning "Skipping Windows Services creation"
        return
    }
    
    Write-Status "Creating Windows Services for Queue and Scheduler..."
    
    # Install NSSM (Non-Sucking Service Manager)
    choco install nssm -y
    
    # Create Queue Worker Service
    $queueCommand = "php"
    $queueArgs = "artisan queue:work rabbitmq --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 --max-time=3600"
    
    & nssm install "SmartNotificationWorker" $queueCommand $queueArgs
    & nssm set "SmartNotificationWorker" AppDirectory $InstallPath
    & nssm set "SmartNotificationWorker" DisplayName "Smart Notification Queue Worker"
    & nssm set "SmartNotificationWorker" Description "Processes background jobs for Smart Notification System"
    & nssm set "SmartNotificationWorker" Start SERVICE_AUTO_START
    
    # Create Scheduler Service
    $schedulerScript = "$InstallPath\scheduler.bat"
    $schedulerContent = @"
@echo off
cd /d "$InstallPath"
:loop
php artisan schedule:run
timeout /t 60 /nobreak > nul
goto loop
"@
    $schedulerContent | Set-Content $schedulerScript
    
    & nssm install "SmartNotificationScheduler" $schedulerScript
    & nssm set "SmartNotificationScheduler" AppDirectory $InstallPath
    & nssm set "SmartNotificationScheduler" DisplayName "Smart Notification Scheduler"
    & nssm set "SmartNotificationScheduler" Description "Runs scheduled tasks for Smart Notification System"
    & nssm set "SmartNotificationScheduler" Start SERVICE_AUTO_START
    
    # Start services
    Start-Service "SmartNotificationWorker"
    Start-Service "SmartNotificationScheduler"
    
    Write-Success "Windows Services created and started"
}

function Create-AdminUser {
    Write-Status "Creating admin user..."
    
    Set-Location $InstallPath
    
    $tinkerScript = @"
`$user = App\Models\User::firstOrCreate([
    'email' => 'admin@company.local'
], [
    'ldap_guid' => 'admin-local-guid',
    'username' => 'admin',
    'first_name' => 'System',
    'last_name' => 'Administrator',
    'display_name' => 'System Administrator',
    'department' => 'IT',
    'title' => 'System Administrator',
    'is_active' => true,
    'ldap_synced_at' => now(),
]);

`$user->assignRole('admin');

App\Models\UserPreference::updateOrCreate([
    'user_id' => `$user->id
], [
    'email_preferences' => ['all'],
    'teams_preferences' => ['all'],
    'timezone' => 'Asia/Bangkok',
    'weekend_notifications' => true,
]);

echo "Admin user created: admin@company.local\n";
exit;
"@
    
    $tinkerScript | php artisan tinker
    
    Write-Success "Admin user created"
}

function Create-BackupScript {
    Write-Status "Creating backup script..."
    
    if (!(Test-Path "C:\Scripts")) {
        New-Item -ItemType Directory -Path "C:\Scripts" -Force
    }
    
    $backupScript = @"
@echo off
set BACKUP_DIR=C:\Backups\SmartNotification
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set DATE=%DATE: =0%

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM Database backup
set PGPASSWORD=SmartNotification2024!
"C:\Program Files\PostgreSQL\15\bin\pg_dump.exe" -h localhost -U smart_notification smart_notification > "%BACKUP_DIR%\database_%DATE%.sql"

REM Files backup
"C:\Program Files\7-Zip\7z.exe" a "%BACKUP_DIR%\files_%DATE%.7z" "$InstallPath" -xr!storage\logs\*

REM Clean old backups (keep 7 days)
forfiles /p "%BACKUP_DIR%" /s /c "cmd /c Del @path" /d -7 2>nul

echo Backup completed: %DATE%
"@
    
    $backupScript | Set-Content "C:\Scripts\smart-notification-backup.bat"
    
    # Create scheduled task for daily backup
    $action = New-ScheduledTaskAction -Execute "C:\Scripts\smart-notification-backup.bat"
    $trigger = New-ScheduledTaskTrigger -Daily -At 2:00AM
    $principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    
    Register-ScheduledTask -TaskName "SmartNotificationBackup" -Action $action -Trigger $trigger -Principal $principal -Description "Daily backup of Smart Notification System"
    
    Write-Success "Backup script and scheduled task created"
}

function Setup-Firewall {
    Write-Status "Configuring Windows Firewall..."
    
    # Allow HTTP traffic
    New-NetFirewallRule -DisplayName "Smart Notification HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
    
    # Allow HTTPS traffic
    New-NetFirewallRule -DisplayName "Smart Notification HTTPS" -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow
    
    # Ask about management ports
    $allowRabbitMQ = Read-Host "Allow RabbitMQ Management UI (port 15672)? [y/N]"
    if ($allowRabbitMQ -eq "y" -or $allowRabbitMQ -eq "Y") {
        New-NetFirewallRule -DisplayName "RabbitMQ Management" -Direction Inbound -Protocol TCP -LocalPort 15672 -Action Allow
    }
    
    Write-Success "Firewall configured"
}

function Save-Credentials {
    Write-Status "Saving credentials to file..."
    
    if (!(Test-Path "C:\Scripts")) {
        New-Item -ItemType Directory -Path "C:\Scripts" -Force
    }
    
    $credentialsFile = "C:\Scripts\smart-notification-credentials.txt"
    $credentials = @"
Smart Notification System - Credentials (Apache Setup)
======================================================

Apache Web Server:
- Service: Apache2.4
- Config: C:\Apache24\conf\httpd.conf
- Document Root: C:\Apache24\htdocs\smart-notification-system\public
- Error Log: C:\Apache24\logs\smart-notification-error.log
- Access Log: C:\Apache24\logs\smart-notification-access.log

Database (PostgreSQL):
- Host: localhost:5432
- Database: smart_notification
- Username: smart_notification
- Password: SmartNotification2024!

RabbitMQ:
- Host: localhost:5672
- Management UI: http://localhost:15672
- Username: smart_notification
- Password: RabbitMQ2024!

Application:
- Path: $InstallPath
- URL: http://localhost
- Admin Login: admin@company.local (use LDAP password when configured)

PHP:
- Version: $PHPVersion
- Path: C:\PHP
- Config: C:\PHP\php.ini
- Error Log: C:\PHP\logs\php_errors.log

Important Files:
- Environment: $InstallPath\.env
- Apache VHost Config: C:\Apache24\conf\httpd.conf
- Laravel Logs: $InstallPath\storage\logs\
- Backup Script: C:\Scripts\smart-notification-backup.bat

Services:
- Apache2.4 (Web Server)
- SmartNotificationWorker (Queue Worker)
- SmartNotificationScheduler (Task Scheduler)
- postgresql-x64-15 (Database)
- RabbitMQ (Message Queue)

Generated on: $(Get-Date)
"@
    
    $credentials | Set-Content $credentialsFile
    Write-Success "Credentials saved to $credentialsFile"
}

function Show-Summary {
    Clear-Host
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host "Smart Notification System Installation Complete!" -ForegroundColor Green
    Write-Host "Apache HTTP Server Setup" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host ""
    
    Write-Host "Installation Summary:" -ForegroundColor Yellow
    Write-Host "- Web Server: Apache HTTP Server 2.4" -ForegroundColor White
    Write-Host "- Installation Path: $InstallPath" -ForegroundColor White
    Write-Host "- Web URL: http://localhost" -ForegroundColor White
    Write-Host "- RabbitMQ Management: http://localhost:15672" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Default Credentials:" -ForegroundColor Yellow
    Write-Host "- Web Login: admin@company.local" -ForegroundColor White
    Write-Host "- Database: smart_notification / SmartNotification2024!" -ForegroundColor White
    Write-Host "- RabbitMQ: smart_notification / RabbitMQ2024!" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Services Installed:" -ForegroundColor Yellow
    Write-Host "- Apache2.4 (Web Server)" -ForegroundColor White
    Write-Host "- SmartNotificationWorker (Queue Processing)" -ForegroundColor White
    Write-Host "- SmartNotificationScheduler (Task Scheduling)" -ForegroundColor White
    Write-Host "- PostgreSQL Database Server" -ForegroundColor White
    Write-Host "- RabbitMQ Message Broker" -ForegroundColor White
    Write-Host "- Redis/Memurai Cache Server" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Important Files:" -ForegroundColor Yellow
    Write-Host "- Credentials: C:\Scripts\smart-notification-credentials.txt" -ForegroundColor White
    Write-Host "- Apache Config: C:\Apache24\conf\httpd.conf" -ForegroundColor White
    Write-Host "- Environment Config: $InstallPath\.env" -ForegroundColor White
    Write-Host "- Application Logs: $InstallPath\storage\logs\" -ForegroundColor White
    Write-Host "- Apache Logs: C:\Apache24\logs\" -ForegroundColor White
    Write-Host "- Backup Script: C:\Scripts\smart-notification-backup.bat" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Configure LDAP settings in .env file" -ForegroundColor White
    Write-Host "2. Configure Microsoft Teams integration" -ForegroundColor White
    Write-Host "3. Configure email SMTP settings" -ForegroundColor White
    Write-Host "4. Test the system with sample notifications" -ForegroundColor White
    Write-Host "5. Consider setting up SSL certificate" -ForegroundColor White
    Write-Host "6. Review Apache virtual host configuration" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Useful Commands:" -ForegroundColor Yellow
    Write-Host "- Restart Apache: Restart-Service Apache2.4" -ForegroundColor White
    Write-Host "- Check Apache Config: C:\Apache24\bin\httpd.exe -t" -ForegroundColor White
    Write-Host "- View Apache Logs: Get-Content C:\Apache24\logs\error.log -Tail 20" -ForegroundColor White
    Write-Host "- View Laravel Logs: Get-Content $InstallPath\storage\logs\laravel.log -Tail 20" -ForegroundColor White
    Write-Host ""
    
    Write-Warning "Please secure the credentials file and update default passwords!"
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Cyan
}

# Main installation process
function Start-Installation {
    Write-Host "Starting Smart Notification System installation with Apache..." -ForegroundColor Green
    Write-Host "This may take 20-30 minutes depending on your internet connection." -ForegroundColor Yellow
    Write-Host ""
    
    try {
        Install-Chocolatey
        Install-Apache
        Install-PHP
        Install-Composer
        Install-PostgreSQL
        Install-RabbitMQ
        Install-Redis
        Install-NodeJS
        Setup-Application
        Create-EnvironmentFile
        Create-HtaccessFile
        Initialize-Laravel
        Start-ApacheService
        Create-WindowsServices
        Create-AdminUser
        Create-BackupScript
        Setup-Firewall
        Save-Credentials
        Show-Summary
        
        Write-Success "Installation completed successfully!"
        
    } catch {
        Write-Error "Installation failed: $($_.Exception.Message)"
        Write-Host "Please check the error above and try again." -ForegroundColor Red
        
        # Show Apache error log if available
        if (Test-Path "C:\Apache24\logs\error.log") {
            Write-Host ""
            Write-Host "Recent Apache errors:" -ForegroundColor Yellow
            Get-Content "C:\Apache24\logs\error.log" -Tail 10
        }
        
        exit 1
    }
}

# Start installation
Start-Installation

# End of script