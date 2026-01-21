# Development Guide

This guide will help you develop and test the Laravel System Logs package.

## Setup for Development

### 1. Install Dependencies

```bash
composer install
```

### 2. Create Test Laravel Application

In a parent directory, create a test Laravel application:

```bash
cd ..
composer create-project laravel/laravel test-laravel-app
cd test-laravel-app
```

### 3. Link Package to Test App

Edit `test-laravel-app/composer.json` and add:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-system-logs",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "imamhsn195/laravel-system-logs": "*"
    }
}
```

### 4. Install Package in Test App

```bash
cd test-laravel-app
composer require imamhsn195/laravel-system-logs --dev
```

### 5. Publish Configuration

```bash
php artisan vendor:publish --tag=system-logs-config
php artisan vendor:publish --tag=system-logs-assets
```

### 6. Configure Routes and Permissions

Make sure your test app has:
- Authentication middleware configured
- Permission system set up (or remove permission checks in config)

## Development Workflow

### Making Changes

1. Edit files in `laravel-system-logs/` package
2. Changes are immediately available in test app (thanks to symlink)
3. Test in browser: `http://localhost:8000/admin/system-logs`
4. Run tests: `./vendor/bin/phpunit` (from package directory)

### Running Tests

From the package root:

```bash
./vendor/bin/phpunit
```

Or run specific test suite:

```bash
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature
```

### Testing in Browser

1. Start Laravel development server in test app:
   ```bash
   cd test-laravel-app
   php artisan serve
   ```

2. Visit: `http://localhost:8000/admin/system-logs`

3. Create test log entries:
   ```bash
   # In test-laravel-app
   php artisan tinker
   >>> Log::info('Test message');
   >>> Log::error('Test error');
   ```

## Package Structure

```
laravel-system-logs/
├── config/              # Configuration files
├── resources/           # Views, translations, assets
│   ├── views/          # Blade templates
│   ├── lang/           # Translation files
│   └── assets/         # CSS and JavaScript
├── routes/             # Route definitions
├── src/                # Source code
│   ├── Http/
│   │   └── Controllers/
│   ├── Services/
│   └── SystemLogServiceProvider.php
├── tests/              # Test files
├── composer.json       # Package definition
└── README.md           # Documentation
```

## Key Files

- `src/SystemLogServiceProvider.php` - Service provider
- `src/Services/SystemLogService.php` - Core business logic
- `src/Http/Controllers/SystemLogController.php` - HTTP controller
- `config/system-logs.php` - Configuration
- `resources/views/system-logs/index.blade.php` - Main view

## Common Tasks

### Adding a New Feature

1. Add feature to service (`SystemLogService.php`)
2. Add controller method if needed
3. Add route in `routes/web.php`
4. Update view if needed
5. Write tests
6. Update documentation

### Debugging

1. Check Laravel logs: `storage/logs/laravel.log`
2. Use `dd()` or `dump()` in code
3. Check browser console for JavaScript errors
4. Verify routes: `php artisan route:list | grep system-logs`

### Testing Different Layouts

Edit `config/system-logs.php` in test app:

```php
'ui' => [
    'layout' => 'layouts.your-layout',
],
```

## Before Committing

1. Run tests: `./vendor/bin/phpunit`
2. Check code style (if using PHP CS Fixer)
3. Update CHANGELOG.md
4. Update README.md if needed
5. Test in test Laravel app

## Publishing to Packagist

1. Tag a version:
   ```bash
   git tag -a v1.0.0 -m "Initial release"
   git push origin v1.0.0
   ```

2. Submit to Packagist:
   - Go to https://packagist.org
   - Submit your GitHub repository URL
   - Package will be available as: `composer require imamhsn195/laravel-system-logs`

## Troubleshooting

### Changes not reflecting in test app

```bash
cd test-laravel-app
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Symlink not working

```bash
cd test-laravel-app
composer remove imamhsn195/laravel-system-logs
composer require imamhsn195/laravel-system-logs --dev
```

### Tests failing

1. Check PHP version: `php -v` (needs 8.1+)
2. Install dependencies: `composer install`
3. Check test database configuration
