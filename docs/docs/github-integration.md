---
layout: docs
title: GitHub Integration
description: Install and update modules from GitHub
---

## Overview

Install modules directly from GitHub repositories, including private repos with authentication.

## Installing from GitHub

```bash
php artisan modular:install vendor/repo
```

### Examples

```bash
# Public repo
php artisan modular:install E-Segments/blog-module

# With specific version/tag
php artisan modular:install E-Segments/blog-module --ver=1.2.0

# With specific branch
php artisan modular:install E-Segments/blog-module --branch=develop

# Force reinstall
php artisan modular:install E-Segments/blog-module --force
```

## Authentication

For private repositories, configure a GitHub Personal Access Token:

### Environment Variable

```env
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Creating a Token

1. Go to GitHub → Settings → Developer settings → Personal access tokens
2. Generate new token (classic)
3. Select scopes: `repo` (for private repos)
4. Copy the token to your `.env` file

## Configuration

```php
// config/modular.php
'github' => [
    'token' => env('GITHUB_TOKEN'),
    'default_branch' => 'main',
    'timeout' => 30,
    'verify_ssl' => true,
    'temp_path' => storage_path('modular/temp'),
    'backup_path' => storage_path('modular/backups'),
],
```

## Checking for Updates

```bash
php artisan modular:outdated
```

Output:
```
┌─────────┬─────────┬─────────┬────────────────────────┐
│ Module  │ Current │ Latest  │ Repository             │
├─────────┼─────────┼─────────┼────────────────────────┤
│ Blog    │ 1.0.0   │ 1.2.0   │ E-Segments/blog-module │
│ Media   │ 2.1.0   │ 2.1.0   │ (up to date)           │
└─────────┴─────────┴─────────┴────────────────────────┘
```

## Updating Modules

```bash
php artisan modular:update Blog

# Update to specific version
php artisan modular:update Blog --ver=1.2.0

# Update all outdated modules
php artisan modular:update --all
```

## What Happens During Install

1. **Download** - Archive downloaded from GitHub releases
2. **Extract** - ZIP extracted to temp directory
3. **Validate** - Module manifest is validated
4. **Move** - Files moved to Modules directory
5. **Enable** - Module is optionally auto-enabled
6. **Cleanup** - Temp files removed

## What Happens During Update

1. **Backup** - Current module backed up
2. **Download** - New version downloaded
3. **Extract** - New files extracted
4. **Replace** - Old files replaced with new
5. **Rollback** - If failed, backup restored
6. **Cleanup** - Temp files and old backups cleaned

## Backup & Restore

Backups are stored at:
```
storage/modular/backups/
├── Blog_1.0.0_20240115_143022.zip
├── Blog_1.1.0_20240120_091545.zip
└── Media_2.0.0_20240118_162033.zip
```

### Manual Restore

```bash
# If update fails, restore from backup
php artisan modular:restore Blog --backup=Blog_1.0.0_20240115_143022.zip
```

## Repository Requirements

For a repo to be installable:

1. Must have a valid `module.json` in root
2. Should use GitHub releases for versioning
3. Release assets or source archives must be available

### Recommended Structure

```
your-module-repo/
├── app/
├── config/
├── database/
├── resources/
├── routes/
├── module.json
└── composer.json
```

## Programmatic Usage

```php
use Esegments\ModularArchitecture\GitHub\ModuleInstaller;
use Esegments\ModularArchitecture\GitHub\GitHubClient;

$client = app(GitHubClient::class);
$installer = app(ModuleInstaller::class);

// Check if repo exists and get info
$info = $client->getRepository('E-Segments/blog-module');

// Get available releases
$releases = $client->getReleases('E-Segments/blog-module');

// Install
$result = $installer->install('E-Segments/blog-module', [
    'version' => '1.2.0',
]);

if ($result->successful()) {
    echo "Installed: {$result->module->getName()}";
} else {
    echo "Failed: {$result->error}";
}
```

## Troubleshooting

### Rate Limiting

GitHub has API rate limits. If you hit them:

```
Error: GitHub API rate limit exceeded
```

Solution: Use a personal access token (higher limits for authenticated requests).

### SSL Certificate Issues

If you have SSL issues in development:

```php
'github' => [
    'verify_ssl' => env('GITHUB_VERIFY_SSL', true),
],
```

```env
GITHUB_VERIFY_SSL=false  # Only in development!
```

### Timeout Issues

For large modules:

```php
'github' => [
    'timeout' => 60,  // Increase from default 30
],
```
