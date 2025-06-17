# Docker Configuration Files

This directory contains Docker-related configuration files for the HRIS backend application.

## Structure

```
.docker/
├── supervisord/
│   ├── supervisord.conf       # Main supervisord configuration
│   └── conf.d/
│       └── laravel.conf       # Laravel application process configuration
└── README.md                  # This file
```

## Supervisord Configuration

### supervisord.conf

Main supervisord configuration file that:

-   Runs in foreground mode (nodaemon=true)
-   Sets up Unix socket for supervisorctl
-   Includes all configuration files from conf.d/

### conf.d/laravel.conf

Laravel application process configuration that:

-   Runs `php artisan serve` command
-   Manages auto-restart on failure
-   Handles logging to storage/logs/
-   Runs as www-data user for security

## Usage

These configuration files are copied into the Docker container during build and used by supervisord to manage the Laravel application process.

To add more processes (like queue workers), create additional .conf files in the conf.d/ directory.

Example for queue worker:

```ini
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php artisan queue:work --sleep=3 --tries=3
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue.log
```
