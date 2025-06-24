# install.ps1 - Smart Notification System Windows Server Installer

# Requires PowerShell 5.1 or higher and Administrator privileges

param(
    [string]$InstallPath = "C:\inetpub\wwwroot\smart-notification-system",
    [string]$PHPVersion = "8.2.13",
    [string]$ComposerVersion = "latest",
    [switch]$SkipIIS = $false,
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
Write-Host "Smart Notification System - Windows Installer" -ForegroundColor Cyan
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

function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
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

function Install-IIS {
    if ($SkipIIS) {
        Write-Warning "Skipping IIS installation"
        return
    }
    
    Write-Status "Installing IIS and required features..."
    
    # Check if IIS is already installed
    $iisFeature = Get-WindowsFeature -Name IIS-WebServerRole
    if ($iisFeature.InstallState -eq "Installed") {
        Write-Success "IIS is already installed"
    } else {
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-WebServerRole -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-WebServer -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-CommonHttpFeatures -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-HttpErrors -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-HttpLogging -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-Security -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-RequestFiltering -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-StaticContent -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-DefaultDocument -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-DirectoryBrowsing -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-ASPNET45 -All
        Enable-WindowsOptionalFeature -Online -FeatureName IIS-CGI -All
        
        Write-Success "IIS installed successfully"
    }
    
    # Install URL Rewrite Module
    Write-Status "Installing IIS URL Rewrite Module..."
    $urlRewriteUrl = "https://download.microsoft.com/download/1/2/8/128E2E22-C1B9-44A4-BE2A-5859ED1D4592/rewrite_amd64_en-US.msi"
    $urlRewritePath = "$env:TEMP\rewrite_amd64_en-US.msi"
    
    Invoke-WebRequest -Uri $urlRewriteUrl -OutFile $urlRewritePath
    Start-Process msiexec.exe -ArgumentList "/i", $urlRewritePath, "/quiet" -Wait
    Remove-Item $urlRewritePath
    
    Write-Success "URL Rewrite Module installed"
}

function Install-PHP {
    if ($SkipPHP) {
        Write-Warning "Skipping PHP installation"
        return
    }
    
    Write-Status "Installing PHP $PHPVersion..."
    
    # Check if PHP is already installed
    if (Get-Command php -ErrorAction SilentlyContinue) {
        $currentVersion = php -v
        Write-Success "PHP is already installed: $currentVersion"
        return
    }
    
    $phpPath = "C:\PHP"
    $phpZip = "$env:TEMP\php.zip"
    $phpUrl = "https://windows.php.net/downloads/releases/php-$PHPVersion-Win32-vs16-x64.zip"
    
    # Create PHP directory
    if (!(Test-Path $phpPath)) {
        New-Item -ItemType Directory -Path $phpPath -Force
    }
    
    # Download PHP
    Write-Status "Downloading PHP from $phpUrl..."
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
    
    # Configure PHP.ini
    Write-Status "Configuring PHP.ini..."
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
    
    # Add additional settings
    $phpIniContent += @"

; Custom settings for Smart Notification System
date.timezone = Asia/Bangkok
expose_php = Off
display_errors = Off
log_errors = On
error_log = C:\PHP\logs\php_errors.log

; Session settings
session.cookie_httponly = 1
session.use_strict_mode = 1
session.cookie_secure = 1

; Security settings
allow_url_fopen = Off
allow_url_include = Off
"@
    
    $phpIniContent | Set-Content $phpIni
    
    # Create PHP logs directory
    New-Item -ItemType Directory -Path "C:\PHP\logs" -Force
    
    Write-Success "PHP installed and configured successfully"
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
    Start-Sleep -Seconds 10
    
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
    
    # Enable management plugin
    Start-Sleep -Seconds 5
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

function Initialize-Laravel {
    Write-Status "Initializing Laravel application..."
    
    Set-Location $InstallPath
    
    # Generate application key
    php artisan key:generate --force
    
    # Generate JWT secret
    php artisan jwt:secret --force
    
    # Publish vendor configs
    php artisan vendor:publish --provider="Adldap\Laravel\AdldapServiceProvider" --force
    php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider" --force
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
    php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --force
    
    # Set proper permissions for storage and cache
    $storagePath = "$InstallPath\storage"
    $cachePath = "$InstallPath\bootstrap\cache"
    
    # Give full control to IIS_IUSRS
    icacls $storagePath /grant "IIS_IUSRS:(OI)(CI)F" /T
    icacls $cachePath /grant "IIS_IUSRS:(OI)(CI)F" /T
    
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

function Setup-IISConfiguration {
    Write-Status "Configuring IIS for Laravel..."
    
    # Import WebAdministration module
    Import-Module WebAdministration
    
    # Create application pool
    $appPoolName = "SmartNotificationPool"
    if (!(Get-IISAppPool -Name $appPoolName -ErrorAction SilentlyContinue)) {
        New-WebAppPool -Name $appPoolName
        Set-ItemProperty -Path "IIS:\AppPools\$appPoolName" -Name processModel.identityType -Value ApplicationPoolIdentity
        Set-ItemProperty -Path "IIS:\AppPools\$appPoolName" -Name recycling.periodicRestart.time -Value "00:00:00"
    }
    
    # Create website
    $siteName = "SmartNotificationSystem"
    $publicPath = "$InstallPath\public"
    
    if (Get-Website -Name $siteName -ErrorAction SilentlyContinue) {
        Remove-Website -Name $siteName
    }
    
    New-Website -Name $siteName -Port 80 -PhysicalPath $publicPath -ApplicationPool $appPoolName
    
    # Create web.config for URL rewriting
    $webConfig = @"
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Laravel" stopProcessing="true">
                    <match url="^" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
        <handlers>
            <add name="PHP-FastCGI" path="*.php" verb="*" modules="FastCgiModule" scriptProcessor="C:\PHP\php-cgi.exe" resourceType="Either" />
        </handlers>
        <fastCgi>
            <application fullPath="C:\PHP\php-cgi.exe" />
        </fastCgi>
        <defaultDocument>
            <files>
                <clear />
                <add value="index.php" />
                <add value="index.html" />
            </files>
        </defaultDocument>
        <httpErrors errorMode="Detailed" />
    </system.webServer>
</configuration>
"@
    
    $webConfig | Set-Content "$publicPath\web.config"
    
    Write-Success "IIS configured successfully"
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
    $queueArgs = "artisan queue:work rabbitmq --tries=3 --timeout=90 --sleep=3"
    
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
forfiles /p "%BACKUP_DIR%" /s /c "cmd /c Del @path" /d -7

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
    
    # Allow RabbitMQ Management (optional)
    $allowRabbitMQ = Read-Host "Allow RabbitMQ Management UI (port 15672)? [y/N]"
    if ($allowRabbitMQ -eq "y" -or $allowRabbitMQ -eq "Y") {
        New-NetFirewallRule -DisplayName "RabbitMQ Management" -Direction Inbound -Protocol TCP -LocalPort 15672 -Action Allow
    }
    
    Write-Success "Firewall configured"
}

function Save-Credentials {
    Write-Status "Saving credentials to file..."
    
    $credentialsFile = "C:\Scripts\smart-notification-credentials.txt"
    $credentials = @"
Smart Notification System - Credentials
=======================================

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

Important Files:
- Environment: $InstallPath\.env
- Logs: $InstallPath\storage\logs\
- Backup Script: C:\Scripts\smart-notification-backup.bat

Services:
- SmartNotificationWorker (Queue Worker)
- SmartNotificationScheduler (Task Scheduler)
- PostgreSQL-x64-15
- RabbitMQ

Generated on: $(Get-Date)
"@
    
    if (!(Test-Path "C:\Scripts")) {
        New-Item -ItemType Directory -Path "C:\Scripts" -Force
    }
    
    $credentials | Set-Content $credentialsFile
    Write-Success "Credentials saved to $credentialsFile"
}

function Show-Summary {
    Clear-Host
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host "Smart Notification System Installation Complete!" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host ""
    
    Write-Host "Installation Summary:" -ForegroundColor Yellow
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
    Write-Host "- SmartNotificationWorker (Queue Processing)" -ForegroundColor White
    Write-Host "- SmartNotificationScheduler (Task Scheduling)" -ForegroundColor White
    Write-Host "- PostgreSQL Database Server" -ForegroundColor White
    Write-Host "- RabbitMQ Message Broker" -ForegroundColor White
    Write-Host "- Redis/Memurai Cache Server" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Important Files:" -ForegroundColor Yellow
    Write-Host "- Credentials: C:\Scripts\smart-notification-credentials.txt" -ForegroundColor White
    Write-Host "- Environment Config: $InstallPath\.env" -ForegroundColor White
    Write-Host "- Application Logs: $InstallPath\storage\logs\" -ForegroundColor White
    Write-Host "- Backup Script: C:\Scripts\smart-notification-backup.bat" -ForegroundColor White
    Write-Host ""
    
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Configure LDAP settings in .env file" -ForegroundColor White
    Write-Host "2. Configure Microsoft Teams integration" -ForegroundColor White
    Write-Host "3. Configure email SMTP settings" -ForegroundColor White
    Write-Host "4. Test the system with sample notifications" -ForegroundColor White
    Write-Host "5. Consider setting up SSL certificate" -ForegroundColor White
    Write-Host ""
    
    Write-Warning "Please secure the credentials file and update default passwords!"
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Cyan
}

# Main installation process
function Start-Installation {
    Write-Host "Starting Smart Notification System installation..." -ForegroundColor Green
    Write-Host "This may take 20-30 minutes depending on your internet connection." -ForegroundColor Yellow
    Write-Host ""
    
    try {
        Install-Chocolatey
        Install-IIS
        Install-PHP
        Install-Composer
        Install-PostgreSQL
        Install-RabbitMQ
        Install-Redis
        Install-NodeJS
        Setup-Application
        Create-EnvironmentFile
        Initialize-Laravel
        Setup-IISConfiguration
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
        exit 1
    }
}

# Start installation
Start-Installation

# End of script "