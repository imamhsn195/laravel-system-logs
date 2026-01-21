# GitHub Setup Guide

This guide will help you publish this package to GitHub and install it in other projects.

## Step 1: Create GitHub Repository

1. Go to [GitHub](https://github.com) and sign in
2. Click the "+" icon in the top right corner
3. Select "New repository"
4. Repository name: `laravel-system-logs`
5. Description: "A comprehensive Laravel package for viewing and managing system logs with an intuitive web interface"
6. Choose **Public** or **Private**
7. **DO NOT** initialize with README, .gitignore, or license (we already have these)
8. Click "Create repository"

## Step 2: Push to GitHub

After creating the repository, GitHub will show you commands. Use these commands in your terminal:

```bash
cd C:\xampp\htdocs\laravel-packages\laravel-system-logs

# Add the remote repository (replace YOUR_USERNAME with your GitHub username)
git remote add origin https://github.com/YOUR_USERNAME/laravel-system-logs.git

# Rename branch to main (if needed)
git branch -M main

# Push to GitHub
git push -u origin main
```

If you're using SSH instead of HTTPS:

```bash
git remote add origin git@github.com:YOUR_USERNAME/laravel-system-logs.git
git branch -M main
git push -u origin main
```

## Step 3: Install in Another Project

### Option 1: Using Composer with GitHub Repository

Add this to your project's `composer.json`:

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

Then run:

```bash
composer require imamhsn195/laravel-system-logs:dev-main
```

### Option 2: Using Path Repository (for local development)

If you want to use the package locally for development:

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

Then run:

```bash
composer require imamhsn195/laravel-system-logs --dev
```

## Step 4: Configure the Package

After installation, publish the configuration:

```bash
php artisan vendor:publish --tag=system-logs-config
php artisan vendor:publish --tag=system-logs-assets
```

## Step 5: (Optional) Publish to Packagist

If you want to make the package available via Packagist:

1. Go to [Packagist](https://packagist.org)
2. Sign in with your GitHub account
3. Click "Submit"
4. Enter your repository URL: `https://github.com/YOUR_USERNAME/laravel-system-logs`
5. Click "Check"
6. Once approved, your package will be available at: `composer require imamhsn195/laravel-system-logs`

## Troubleshooting

### Authentication Issues

If you get authentication errors when pushing:

1. Use a Personal Access Token instead of password
2. Generate token: GitHub → Settings → Developer settings → Personal access tokens → Generate new token
3. Use the token as your password when pushing

### Branch Name

If your default branch is `master` instead of `main`:

```bash
git branch -M main
git push -u origin main
```

### Updating the Package

After making changes:

```bash
git add .
git commit -m "Your commit message"
git push origin main
```

Then in your other project:

```bash
composer update imamhsn195/laravel-system-logs
```
