# Quick Start Guide

## 🚀 Push to GitHub

Run these commands (replace `YOUR_USERNAME` with your GitHub username):

```bash
cd C:\xampp\htdocs\laravel-packages\laravel-system-logs

# Add remote (if not already added)
git remote add origin https://github.com/YOUR_USERNAME/laravel-system-logs.git

# Or if you prefer SSH:
# git remote add origin git@github.com:YOUR_USERNAME/laravel-system-logs.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## 📦 Install in Another Project

### Method 1: From GitHub (Recommended)

Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/YOUR_USERNAME/laravel-system-logs"
        }
    ],
    "require": {
        "imamhsn195/laravel-system-logs": "dev-main"
    }
}
```

Then:

```bash
composer require imamhsn195/laravel-system-logs:dev-main
php artisan vendor:publish --tag=system-logs-config
php artisan vendor:publish --tag=system-logs-assets
```

### Method 2: Local Path (For Development)

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

Then:

```bash
composer require imamhsn195/laravel-system-logs --dev
```

## ✅ After Installation

1. Publish config: `php artisan vendor:publish --tag=system-logs-config`
2. Publish assets: `php artisan vendor:publish --tag=system-logs-assets`
3. Configure permissions in `config/system-logs.php` (or set to `null` to disable)
4. Access at: `/admin/system-logs` (or your configured route)

## 📝 Next Steps

- Configure your layout in `config/system-logs.php`
- Set up permissions if needed
- Customize views if needed: `php artisan vendor:publish --tag=system-logs-views`
