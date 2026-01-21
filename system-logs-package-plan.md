# System Logs Laravel Package - Development Plan

## Executive Summary

This document outlines the plan to convert the existing system logs module into a generic, reusable Laravel package that can be installed in any Laravel project. The package will provide comprehensive log viewing, filtering, searching, and management capabilities with an intuitive web interface.

---

## 1. Current System Analysis

### 1.1 Existing Features

#### Core Functionality
- **Log File Reading**: Reads Laravel log files from `storage/logs` directory
- **Multi-File Support**: Can scan multiple log files simultaneously (configurable limit)
- **Channel Detection**: Automatically infers log channels from file names
- **Entry Parsing**: Parses Laravel's standard log format: `[YYYY-MM-DD HH:MM:SS] ENV.LEVEL: message {context}`
- **Context Extraction**: Separates log messages from JSON context data
- **⚠️ Current Limitation**: Only scans root directory, does NOT support subdirectories (to be added in package)

#### Filtering & Search
- **Channel Filter**: Filter by log channel (e.g., `single`, `daily`, `stack`)
- **File Filter**: Filter by specific log file name
- **Level Filter**: Filter by log level (debug, info, notice, warning, error, critical, alert, emergency)
- **Environment Filter**: Filter by environment (local, production, staging, etc.)
- **Date Filter**: Filter entries by specific date
- **Text Search**: Full-text search across messages and context
- **Pagination**: Configurable entries per page (10-300, default: 50)
- **Max Files Limit**: Configurable number of files to scan (1-20, default: 3)

#### User Interface
- **Real-time Filtering**: AJAX-based filtering without page reload
- **Dynamic Table**: Updates log entries dynamically based on filters
- **Expandable Details**: Collapsible sections for full message and context
- **Level Badges**: Color-coded badges for different log levels
- **Responsive Design**: Mobile-friendly table layout
- **Loading States**: Visual feedback during data fetching
- **Error Handling**: User-friendly error messages

#### Deletion Features
- **Single Entry Delete**: Delete individual log entries by timestamp and file
- **Bulk Delete**: Delete multiple entries at once (currently requires explicit entry selection)
- **Security**: Path traversal protection and file validation
- **Confirmation Dialogs**: Prevents accidental deletions

#### API Support
- **JSON API**: Returns log data in JSON format for API consumers
- **AJAX Support**: Handles both regular and AJAX requests
- **RESTful Routes**: Standard RESTful route structure

### 1.2 Current Architecture

#### Components
1. **Controller**: `SystemLogController`
   - Handles HTTP requests
   - Validates input
   - Returns views or JSON responses
   - Manages redirects with filter preservation

2. **Service**: `SystemLogService`
   - Core business logic
   - File system operations
   - Log parsing and filtering
   - Entry deletion

3. **View**: `resources/views/admin/system-logs/index.blade.php`
   - Blade template with Vue-like JavaScript
   - Dynamic filtering UI
   - Table rendering

4. **Routes**: Defined in `routes/web.php`
   - GET `/admin/system-logs` - Index page
   - DELETE `/admin/system-logs` - Delete single entry
   - DELETE `/admin/system-logs/bulk` - Bulk delete

5. **Permissions**: 
   - `system-log.view` - View logs
   - `system-log.delete` - Delete logs

### 1.3 Dependencies

- **Laravel Framework**: Core framework
- **AdminLTE**: UI theme (optional, can be made configurable)
- **Font Awesome**: Icons (optional)
- **Bootstrap**: CSS framework (optional)

---

## 2. Package Architecture

### 2.1 Package Structure

```
laravel-system-logs/
├── config/
│   └── system-logs.php          # Package configuration
├── database/
│   └── migrations/               # (Future: if database logging is added)
├── resources/
│   ├── views/
│   │   └── system-logs/
│   │       └── index.blade.php  # Main view
│   ├── lang/
│   │   ├── en/
│   │   │   └── system-logs.php  # English translations
│   │   └── bn/
│   │       └── system-logs.php  # Bengali translations (example)
│   └── assets/
│       ├── css/
│       │   └── system-logs.css  # Package-specific styles
│       └── js/
│           └── system-logs.js   # Package-specific JavaScript
├── routes/
│   └── web.php                   # Package routes
├── src/
│   ├── Http/
│   │   └── Controllers/
│   │       └── SystemLogController.php
│   ├── Services/
│   │   └── SystemLogService.php
│   ├── Middleware/
│   │   └── SystemLogMiddleware.php  # Optional permission middleware
│   ├── Facades/
│   │   └── SystemLog.php        # Facade for easy access
│   └── SystemLogServiceProvider.php
├── tests/
│   ├── Unit/
│   │   └── SystemLogServiceTest.php
│   └── Feature/
│       └── SystemLogControllerTest.php
├── composer.json
├── README.md
└── LICENSE
```

### 2.2 Configuration File

**`config/system-logs.php`**

```php
return [
    // Log directory path
    'log_directory' => storage_path('logs'),
    
    // Route configuration
    'route' => [
        'prefix' => 'admin/system-logs',
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'system-logs.',
    ],
    
    // Permission configuration
    'permissions' => [
        'view' => 'system-log.view',
        'delete' => 'system-log.delete',
    ],
    
    // UI Configuration
    'ui' => [
        'theme' => 'adminlte', // 'adminlte', 'bootstrap', 'custom'
        'layout' => 'adminlte::page', // Blade layout to extend
        'title' => 'System Logs',
    ],
    
    // Filter defaults
    'filters' => [
        'default_per_page' => 50,
        'min_per_page' => 10,
        'max_per_page' => 300,
        'default_max_files' => 3,
        'min_max_files' => 1,
        'max_max_files' => 20,
    ],
    
    // Parsing configuration
    'parsing' => [
        'date_format' => 'Y-m-d H:i:s',
        'entry_pattern' => '/^\[(?<datetime>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<environment>[\w\-.]+)\.(?<level>[A-Z]+):\s(?<body>.*)$/',
    ],
    
    // Directory scanning
    'scanning' => [
        'recursive' => true, // Scan subdirectories recursively
        'max_depth' => 10, // Maximum directory depth to scan (0 = unlimited)
        'exclude_directories' => ['.git', 'node_modules', '.cache'], // Directories to exclude
    ],
    
    // Security
    'security' => [
        'allowed_file_extensions' => ['.log'],
        'max_file_size' => 100 * 1024 * 1024, // 100MB
    ],
];
```

### 2.3 Service Provider

**`src/SystemLogServiceProvider.php`**

```php
<?php

namespace Vendor\SystemLogs;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SystemLogServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/system-logs.php' => config_path('system-logs.php'),
        ], 'system-logs-config');
        
        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/system-logs'),
        ], 'system-logs-views');
        
        // Publish translations
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/system-logs'),
        ], 'system-logs-lang');
        
        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/system-logs'),
        ], 'system-logs-assets');
        
        // Load routes
        $this->loadRoutes();
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'system-logs');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'system-logs');
    }
    
    public function register()
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/system-logs.php',
            'system-logs'
        );
        
        // Register service as singleton
        $this->app->singleton(SystemLogService::class, function ($app) {
            return new SystemLogService(
                config('system-logs.log_directory')
            );
        });
    }
    
    protected function loadRoutes()
    {
        Route::group([
            'prefix' => config('system-logs.route.prefix'),
            'middleware' => config('system-logs.route.middleware'),
            'as' => config('system-logs.route.name_prefix'),
        ], function () {
            require __DIR__.'/../routes/web.php';
        });
    }
}
```

---

## 3. Feature Specifications

### 3.1 Existing Features (To Be Preserved)

#### 3.1.1 Log Viewing
- ✅ List log entries from multiple files
- ✅ Parse Laravel log format
- ✅ Display timestamp, level, channel, environment, message, context
- ✅ Support pagination
- ✅ Real-time filtering via AJAX
- ⚠️ **NEW**: Recursive directory scanning (currently only root directory)

#### 3.1.2 Filtering
- ✅ Filter by channel
- ✅ Filter by file
- ✅ Filter by level
- ✅ Filter by environment
- ✅ Filter by date
- ✅ Full-text search
- ✅ Configurable entries per page
- ✅ Configurable max files to scan

#### 3.1.3 Deletion
- ✅ Delete single entry
- ✅ Bulk delete selected entries
- ✅ Security validation
- ✅ Confirmation dialogs

#### 3.1.4 UI/UX
- ✅ Responsive design
- ✅ Loading states
- ✅ Error handling
- ✅ Expandable details
- ✅ Color-coded level badges

### 3.2 New Feature: Bulk Delete by Filters

#### 3.2.1 Feature Description
Allow users to delete all log entries that match the current filter criteria with a single action. This is more efficient than selecting individual entries when dealing with large numbers of matching logs.

#### 3.2.2 Use Cases
- Delete all error logs from a specific date
- Delete all logs from a specific channel
- Delete all logs matching a search term
- Delete all logs from a specific environment
- Delete logs older than a certain date (when combined with date filter)

#### 3.2.3 Implementation Details

**New Route:**
```php
Route::delete('system-logs/bulk-by-filters', [SystemLogController::class, 'bulkDeleteByFilters'])
    ->middleware('permission:system-log.delete')
    ->name('bulk-delete-by-filters');
```

**Controller Method:**
```php
public function bulkDeleteByFilters(Request $request): RedirectResponse|JsonResponse
{
    $request->validate([
        'channel' => 'nullable|string',
        'file' => 'nullable|string',
        'level' => 'nullable|string|in:debug,info,notice,warning,error,critical,alert,emergency',
        'environment' => 'nullable|string',
        'date' => 'nullable|date',
        'search' => 'nullable|string|max:255',
        'confirm' => 'required|accepted', // Require explicit confirmation
    ]);
    
    $filters = $request->only(['channel', 'file', 'level', 'environment', 'date', 'search']);
    $filters = array_filter($filters, fn($value) => !empty($value));
    
    // Require at least one filter to prevent accidental deletion of all logs
    if (empty($filters)) {
        return $this->errorResponse(
            'At least one filter must be specified for bulk deletion.',
            400
        );
    }
    
    $result = $this->logService->bulkDeleteByFilters($filters);
    
    return $this->successResponse(
        "Successfully deleted {$result['deleted']} log entries.",
        ['deleted' => $result['deleted'], 'failed' => $result['failed']]
    );
}
```

**Service Method:**
```php
public function bulkDeleteByFilters(array $filters): array
{
    $deletedCount = 0;
    $failedCount = 0;
    
    // Get all matching entries
    $entries = $this->getEntries($filters, PHP_INT_MAX);
    
    // Group entries by file for efficient deletion
    $entriesByFile = $entries['entries']->groupBy('file');
    
    foreach ($entriesByFile as $fileName => $fileEntries) {
        // Delete entries in reverse order to maintain file integrity
        $fileEntries = $fileEntries->sortByDesc(fn($entry) => $entry['timestamp']->timestamp);
        
        foreach ($fileEntries as $entry) {
            $deleted = $this->deleteEntry($fileName, $entry['timestamp']->toIso8601String());
            if ($deleted) {
                $deletedCount++;
            } else {
                $failedCount++;
            }
        }
    }
    
    return [
        'deleted' => $deletedCount,
        'failed' => $failedCount,
        'total_matched' => $entries['entries']->count(),
    ];
}
```

**UI Implementation:**
- Add a "Delete All Filtered" button in the filter panel
- Show confirmation modal with:
  - Number of entries that will be deleted
  - Active filter criteria
  - Warning message
- Require typing "DELETE" or similar confirmation
- Show progress indicator during deletion
- Display success/error message after completion

**Security Considerations:**
- Require explicit confirmation (checkbox + text input)
- Require at least one filter to be active
- Add rate limiting to prevent abuse
- Log the bulk delete action for audit purposes
- Consider adding a "dry run" mode to preview what will be deleted

---

## 4. Package Features

### 4.1 Core Features

1. **Log File Management**
   - Read Laravel log files
   - Support multiple log channels
   - Handle rotated log files
   - File size and date information

2. **Log Entry Parsing**
   - Parse standard Laravel log format
   - Extract timestamp, level, environment, message, context
   - Handle multi-line messages
   - Support JSON context extraction

3. **Filtering & Search**
   - Multiple filter criteria
   - Full-text search
   - Real-time filtering
   - Filter persistence across requests

4. **Entry Management**
   - View log entries
   - Delete single entries
   - Bulk delete selected entries
   - **NEW: Bulk delete by filters**

5. **User Interface**
   - Responsive table layout
   - Dynamic updates via AJAX
   - Expandable details
   - Loading and error states
   - Color-coded level indicators

### 4.2 Advanced Features (Future)

1. **Export Functionality**
   - Export filtered logs to CSV
   - Export to JSON
   - Export to PDF report

2. **Log Statistics**
   - Count by level
   - Count by channel
   - Timeline visualization
   - Error rate trends

3. **Real-time Monitoring**
   - WebSocket support for live log streaming
   - Auto-refresh option
   - Desktop notifications

4. **Database Logging Integration**
   - View database-stored logs
   - Unified interface for file and database logs
   - Migration support

5. **Log Archiving**
   - Archive old logs
   - Compress archived logs
   - Restore archived logs

6. **Advanced Search**
   - Regex support
   - Date range filtering
   - Multiple search terms
   - Saved search queries

7. **User Preferences**
   - Save default filters
   - Customizable columns
   - Theme preferences
   - Per-user settings

---

## 5. Implementation Plan

### 5.1 Phase 1: Package Foundation (Week 1-2)

**Tasks:**
1. Create package structure
2. Set up Composer package configuration
3. Create service provider
4. Create configuration file
5. Move controller to package
6. Move service to package
7. Set up basic routes
8. Create facade (optional)

**Deliverables:**
- Working package skeleton
- Basic installation and configuration
- Service provider registration

### 5.2 Phase 2: Core Functionality (Week 2-3)

**Tasks:**
1. Refactor service to use configuration
2. Make log directory configurable
3. Implement dependency injection
4. **Implement recursive directory scanning**
5. **Add subdirectory support with depth limits**
6. **Add directory exclusion patterns**
7. Create base view structure
8. Implement basic UI
9. Add translation support
10. Set up asset publishing

**Deliverables:**
- Functional log viewing
- Recursive directory scanning
- Configurable package
- Basic UI working

### 5.3 Phase 3: Filtering & Search (Week 3-4)

**Tasks:**
1. Implement all filter types
2. Add AJAX filtering
3. Add filter persistence
4. Implement search functionality
5. Add pagination
6. Optimize performance for large files

**Deliverables:**
- Complete filtering system
- Real-time search
- Pagination working

### 5.4 Phase 4: Deletion Features (Week 4-5)

**Tasks:**
1. Implement single entry deletion
2. Implement bulk delete (selected entries)
3. **Implement bulk delete by filters (NEW)**
4. Add security validations
5. Add confirmation dialogs
6. Add error handling

**Deliverables:**
- All deletion features working
- Security measures in place
- User-friendly confirmations

### 5.5 Phase 5: UI/UX Polish (Week 5-6)

**Tasks:**
1. Improve responsive design
2. Add loading states
3. Add error messages
4. Improve accessibility
5. Add keyboard shortcuts
6. Optimize JavaScript performance

**Deliverables:**
- Polished user interface
- Excellent user experience
- Accessible design

### 5.6 Phase 6: Testing & Documentation (Week 6-7)

**Tasks:**
1. Write unit tests for service
2. Write feature tests for controller
3. Test edge cases
4. Write package documentation
5. Create installation guide
6. Create usage examples
7. Write API documentation

**Deliverables:**
- Comprehensive test coverage
- Complete documentation
- Usage examples

### 5.7 Phase 7: Package Publishing (Week 7-8)

**Tasks:**
1. Prepare for Packagist
2. Create README with badges
3. Add license file
4. Create changelog
5. Version tagging
6. Publish to Packagist
7. Create demo project

**Deliverables:**
- Published package on Packagist
- Publicly available
- Ready for use

---

## 6. Technical Specifications

### 6.1 Requirements

**PHP:**
- PHP 8.1 or higher

**Laravel:**
- Laravel 10.x or 11.x

**Dependencies:**
- `illuminate/support`
- `illuminate/http`
- `illuminate/view`
- `illuminate/routing`

**Optional Dependencies:**
- AdminLTE (for UI theme)
- Bootstrap (for styling)
- Font Awesome (for icons)

### 6.2 Performance Considerations

1. **File Reading:**
   - Use generators for large files
   - Limit number of files scanned
   - Cache file metadata
   - Implement file size limits

2. **Memory Management:**
   - Stream file reading
   - Limit entries loaded in memory
   - Use pagination effectively
   - Clear unused data

3. **Query Optimization:**
   - Efficient regex patterns
   - Early filter application
   - Sort only when necessary
   - Limit result sets

### 6.3 Security Considerations

1. **File Access:**
   - Validate file paths
   - Prevent directory traversal
   - Check file extensions
   - Verify file permissions

2. **Input Validation:**
   - Validate all user inputs
   - Sanitize search queries
   - Validate filter values
   - Rate limit requests

3. **Permission Checks:**
   - Middleware-based permissions
   - Controller-level checks
   - Service-level validation

4. **Bulk Delete Security:**
   - Require explicit confirmation
   - Require active filters
   - Log all bulk operations
   - Rate limit bulk operations

### 6.4 Error Handling

1. **File Errors:**
   - Handle missing files gracefully
   - Handle permission errors
   - Handle corrupted files
   - Provide user-friendly messages

2. **Parsing Errors:**
   - Handle malformed log entries
   - Skip invalid entries
   - Log parsing errors
   - Continue processing

3. **Deletion Errors:**
   - Handle file write errors
   - Track failed deletions
   - Provide detailed error messages
   - Rollback on critical errors

---

## 7. API Documentation

### 7.1 Routes

#### GET `/admin/system-logs`
View log entries with optional filters.

**Query Parameters:**
- `channel` (string, optional): Filter by channel
- `file` (string, optional): Filter by file name
- `level` (string, optional): Filter by level
- `environment` (string, optional): Filter by environment
- `date` (date, optional): Filter by date (Y-m-d)
- `search` (string, optional): Search text
- `per_page` (integer, optional): Entries per page (10-300, default: 50)
- `max_files` (integer, optional): Max files to scan (1-20, default: 3)

**Response:**
- HTML: Rendered view
- JSON: `{ data: [...], meta: {...} }`

#### DELETE `/admin/system-logs`
Delete a single log entry.

**Request Body:**
```json
{
    "file": "laravel-2024-01-15.log",
    "timestamp": "2024-01-15T10:30:00Z"
}
```

**Response:**
- Success: Redirect with success message
- Error: Redirect with error message

#### DELETE `/admin/system-logs/bulk`
Delete multiple selected log entries.

**Request Body:**
```json
{
    "entries": [
        {
            "file": "laravel-2024-01-15.log",
            "timestamp": "2024-01-15T10:30:00Z"
        },
        {
            "file": "laravel-2024-01-15.log",
            "timestamp": "2024-01-15T10:31:00Z"
        }
    ]
}
```

**Response:**
- Success: JSON with deleted count
- Partial: JSON with deleted and failed counts
- Error: JSON with error message

#### DELETE `/admin/system-logs/bulk-by-filters` (NEW)
Delete all log entries matching current filters.

**Request Body:**
```json
{
    "channel": "single",
    "level": "error",
    "date": "2024-01-15",
    "confirm": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Successfully deleted 42 log entries.",
    "deleted": 42,
    "failed": 0,
    "total_matched": 42
}
```

### 7.2 Service Methods

#### `getEntries(array $filters, int $limit): array`
Get log entries matching filters.

**Parameters:**
- `$filters`: Array of filter criteria
- `$limit`: Maximum number of entries to return

**Returns:**
```php
[
    'entries' => Collection,
    'files' => Collection,
    'meta' => [
        'files_scanned' => int,
        'limit' => int,
        'filters_applied' => array,
    ],
]
```

#### `listFiles(?string $channel, ?string $date, bool $recursive = true): Collection`
List available log files.

**Parameters:**
- `$channel`: Optional channel filter
- `$date`: Optional date filter
- `$recursive`: Whether to scan subdirectories (default: true)

**Returns:** Collection of file information arrays

**Note:** When recursive is enabled, file paths include relative path from log directory (e.g., `subfolder/laravel.log`)

#### `deleteEntry(string $fileName, string $timestamp): bool`
Delete a single log entry.

**Parameters:**
- `$fileName`: Name of the log file
- `$timestamp`: ISO 8601 timestamp string

**Returns:** `true` on success, `false` on failure

#### `bulkDeleteByFilters(array $filters): array` (NEW)
Delete all entries matching filters.

**Parameters:**
- `$filters`: Array of filter criteria

**Returns:**
```php
[
    'deleted' => int,
    'failed' => int,
    'total_matched' => int,
]
```

---

## 8. Configuration Options

### 8.1 Log Directory
Configure the directory where log files are stored.

```php
'log_directory' => storage_path('logs'),
```

### 8.2 Route Configuration
Customize route prefix, middleware, and name prefix.

```php
'route' => [
    'prefix' => 'admin/system-logs',
    'middleware' => ['web', 'auth'],
    'name_prefix' => 'system-logs.',
],
```

### 8.3 Permission Configuration
Customize permission names for your permission system.

```php
'permissions' => [
    'view' => 'system-log.view',
    'delete' => 'system-log.delete',
],
```

### 8.4 UI Configuration
Customize theme and layout.

```php
'ui' => [
    'theme' => 'adminlte',
    'layout' => 'adminlte::page',
    'title' => 'System Logs',
],
```

### 8.5 Filter Defaults
Configure default filter values.

```php
'filters' => [
    'default_per_page' => 50,
    'min_per_page' => 10,
    'max_per_page' => 300,
    'default_max_files' => 3,
    'min_max_files' => 1,
    'max_max_files' => 20,
],
```

---

## 9. Installation & Usage

### 9.1 Installation

```bash
composer require vendor/laravel-system-logs
```

### 9.2 Publish Configuration

```bash
php artisan vendor:publish --tag=system-logs-config
```

### 9.3 Publish Views (Optional)

```bash
php artisan vendor:publish --tag=system-logs-views
```

### 9.4 Publish Translations (Optional)

```bash
php artisan vendor:publish --tag=system-logs-lang
```

### 9.5 Configure Permissions

Add permissions to your permission system:
- `system-log.view`
- `system-log.delete`

### 9.6 Access Logs

Navigate to `/admin/system-logs` (or your configured prefix).

---

## 10. Testing Strategy

### 10.1 Unit Tests

1. **SystemLogService Tests:**
   - File listing
   - Entry parsing
   - Filtering logic
   - Entry deletion
   - Bulk deletion by filters

2. **Helper Method Tests:**
   - Date parsing
   - Context extraction
   - Channel inference
   - File validation

### 10.2 Feature Tests

1. **Controller Tests:**
   - Index page rendering
   - Filter application
   - Single entry deletion
   - Bulk deletion
   - Bulk deletion by filters
   - Permission checks
   - Error handling

2. **Integration Tests:**
   - Full workflow tests
   - AJAX requests
   - File operations
   - Security validations

### 10.3 Test Coverage Goals

- **Unit Tests:** 90%+ coverage
- **Feature Tests:** All routes and actions
- **Integration Tests:** Critical workflows

---

## 11. Future Enhancements

### 11.1 Short-term (v1.1)
- Export to CSV/JSON
- Log statistics dashboard
- Improved error messages
- Performance optimizations

### 11.2 Medium-term (v1.2)
- Database logging integration
- Real-time log streaming
- Advanced search (regex)
- Saved search queries

### 11.3 Long-term (v2.0)
- Log archiving system
- Multi-tenant support
- API-only mode
- GraphQL support
- Desktop application

---

## 12. Conclusion

This package will provide a comprehensive, reusable solution for viewing and managing Laravel system logs. The addition of bulk delete by filters will significantly improve the user experience when dealing with large numbers of log entries.

The modular architecture ensures easy customization and extension, making it suitable for a wide range of Laravel applications.

---

## Appendix C: Recursive Directory Scanning

### C.1 Current Limitation

The current implementation uses `File::files()` which only scans the root directory:
```php
return collect(File::files($this->logDirectory))
    ->filter(fn (SplFileInfo $file) => Str::endsWith($file->getFilename(), '.log'))
```

**This does NOT support:**
- Log files in subdirectories (e.g., `storage/logs/api/laravel.log`)
- Organized log structures (e.g., `storage/logs/2024/01/laravel.log`)
- Channel-specific folders (e.g., `storage/logs/channels/single/laravel.log`)

### C.2 Implementation Solution

**Updated `listFiles()` Method:**
```php
public function listFiles(?string $channel = null, ?string $date = null, bool $recursive = null): Collection
{
    if (!File::isDirectory($this->logDirectory)) {
        return collect();
    }
    
    $recursive = $recursive ?? config('system-logs.scanning.recursive', true);
    $maxDepth = config('system-logs.scanning.max_depth', 10);
    $excludeDirs = config('system-logs.scanning.exclude_directories', []);
    
    if ($recursive) {
        $files = $this->scanRecursively($maxDepth, $excludeDirs);
    } else {
        $files = collect(File::files($this->logDirectory));
    }
    
    return $files
        ->filter(fn (SplFileInfo $file) => Str::endsWith($file->getFilename(), '.log'))
        ->map(fn (SplFileInfo $file) => $this->mapFileInfo($file, $recursive))
        ->when($channel, fn (Collection $collection) => $collection->where('channel', $channel))
        ->when($date, fn (Collection $collection) => $collection->filter(
            fn (array $file) => Str::contains($file['name'], $date)
        ))
        ->sortByDesc('updated_at')
        ->values();
}

private function scanRecursively(int $maxDepth, array $excludeDirs): Collection
{
    $files = collect();
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(
            $this->logDirectory,
            \RecursiveDirectoryIterator::SKIP_DOTS
        ),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    
    $currentDepth = 0;
    foreach ($iterator as $file) {
        $depth = $iterator->getDepth();
        
        // Check max depth
        if ($maxDepth > 0 && $depth >= $maxDepth) {
            continue;
        }
        
        // Check excluded directories
        if ($file->isDir()) {
            $dirName = $file->getFilename();
            if (in_array($dirName, $excludeDirs)) {
                $iterator->next();
                continue;
            }
        }
        
        // Only add .log files
        if ($file->isFile() && Str::endsWith($file->getFilename(), '.log')) {
            $files->push($file);
        }
    }
    
    return $files;
}

private function mapFileInfo(SplFileInfo $file, bool $recursive): array
{
    $name = $file->getFilename();
    $channel = $this->inferChannel($name);
    $updatedAt = Carbon::createFromTimestamp($file->getMTime());
    
    // Get relative path for display
    $relativePath = $recursive 
        ? str_replace($this->logDirectory . DIRECTORY_SEPARATOR, '', $file->getPathname())
        : $name;
    
    return [
        'name' => $name,
        'relative_path' => $relativePath, // e.g., "api/laravel.log" or "2024/01/laravel.log"
        'full_path' => $file->getRealPath(),
        'channel' => $channel,
        'size' => (int) $file->getSize(),
        'size_human' => $this->formatBytes((int) $file->getSize()),
        'updated_at' => $updatedAt,
        'updated_for_humans' => $updatedAt->diffForHumans(),
    ];
}
```

### C.3 Updated `deleteEntry()` Method

When files are in subdirectories, the deletion method needs to handle relative paths:

```php
public function deleteEntry(string $fileName, string $timestamp): bool
{
    // Handle both relative paths (subfolder/file.log) and simple filenames
    $filePath = $this->logDirectory . DIRECTORY_SEPARATOR . $fileName;
    
    // Security: Ensure the file is within the log directory
    $realPath = realpath($filePath);
    $realLogDir = realpath($this->logDirectory);
    
    if (!$realPath || !$realLogDir || !str_starts_with($realPath, $realLogDir)) {
        return false;
    }
    
    // Rest of the deletion logic remains the same...
}
```

### C.4 UI Updates

**File Selector Display:**
- Show relative path in file selector: `api/laravel-2024-01-15.log`
- Group by directory in dropdown (optional)
- Show directory structure in file list

**Example UI Enhancement:**
```blade
<select name="file" id="log-file">
    <option value="">All Files</option>
    <optgroup label="Root">
        <option value="laravel.log">laravel.log</option>
    </optgroup>
    <optgroup label="API Logs">
        <option value="api/laravel.log">api/laravel.log</option>
        <option value="api/errors.log">api/errors.log</option>
    </optgroup>
    <optgroup label="2024/01">
        <option value="2024/01/laravel.log">2024/01/laravel.log</option>
    </optgroup>
</select>
```

### C.5 Configuration Options

```php
'scanning' => [
    'recursive' => true, // Enable recursive scanning
    'max_depth' => 10, // Maximum directory depth (0 = unlimited)
    'exclude_directories' => [
        '.git',
        'node_modules',
        '.cache',
        'archived', // Example: exclude archived logs
    ],
],
```

### C.6 Performance Considerations

1. **Caching File List:**
   - Cache file list for 5-10 minutes
   - Invalidate cache on file changes
   - Use Laravel cache system

2. **Lazy Loading:**
   - Only scan directories when needed
   - Load file metadata on demand
   - Use generators for large directory trees

3. **Depth Limits:**
   - Default max depth prevents infinite recursion
   - Configurable per installation
   - Warning when depth limit reached

### C.7 Security Considerations

1. **Path Traversal Protection:**
   - Always validate paths are within log directory
   - Use `realpath()` to resolve symlinks
   - Prevent `../` in file paths

2. **Directory Exclusion:**
   - Exclude sensitive directories by default
   - Allow configuration of exclusions
   - Log access attempts to excluded directories

3. **File Size Limits:**
   - Skip files larger than configured limit
   - Warn about large files
   - Prevent memory exhaustion

---

## Appendix A: Bulk Delete by Filters - Detailed Specification

### A.1 User Interface

**Button Placement:**
- Located in the filter panel header
- Styled as a danger button (red)
- Icon: `fa-trash-alt` or `fa-broom`
- Label: "Delete All Filtered" or "Bulk Delete"

**Confirmation Modal:**
```
┌─────────────────────────────────────────┐
│  Delete All Filtered Log Entries       │
├─────────────────────────────────────────┤
│                                         │
│  ⚠️  Warning: This action cannot be     │
│     undone!                             │
│                                         │
│  You are about to delete all log        │
│  entries matching the following         │
│  criteria:                              │
│                                         │
│  • Channel: single                       │
│  • Level: error                         │
│  • Date: 2024-01-15                    │
│                                         │
│  Estimated entries to delete: 42        │
│                                         │
│  [ ] I understand this action cannot    │
│      be undone                          │
│                                         │
│  Type "DELETE" to confirm:              │
│  [________________]                     │
│                                         │
│  [Cancel]  [Delete All]                │
└─────────────────────────────────────────┘
```

**Validation:**
- Checkbox must be checked
- Text input must match "DELETE" (case-insensitive)
- At least one filter must be active
- Show count of entries that will be deleted

### A.2 Implementation Flow

1. User applies filters
2. User clicks "Delete All Filtered" button
3. System fetches count of matching entries
4. Confirmation modal displays with:
   - Active filter criteria
   - Estimated count
   - Warning message
5. User confirms (checkbox + text input)
6. System validates confirmation
7. System performs bulk deletion
8. Progress indicator shows during deletion
9. Success/error message displays
10. Logs refresh automatically

### A.3 Error Handling

**No Filters Active:**
- Disable button or show tooltip
- Error message: "Please apply at least one filter before bulk deleting"

**No Matching Entries:**
- Show message: "No entries match the current filters"

**Deletion Failures:**
- Show partial success message
- Display count of deleted vs failed
- Log failures for debugging

**Permission Denied:**
- Hide button if user lacks permission
- Show error if attempted via API

### A.4 Security Measures

1. **Confirmation Required:**
   - Checkbox confirmation
   - Text input confirmation ("DELETE")
   - Both must be completed

2. **Filter Requirement:**
   - At least one filter must be active
   - Prevents accidental deletion of all logs

3. **Rate Limiting:**
   - Limit bulk deletions per user per hour
   - Prevent abuse

4. **Audit Logging:**
   - Log all bulk delete operations
   - Include user, timestamp, filters, count

5. **Transaction Safety:**
   - Process deletions file by file
   - Rollback on critical errors
   - Maintain file integrity

---

## Appendix B: Migration Checklist

### B.1 From Current Module to Package

- [ ] Extract controller to package
- [ ] Extract service to package
- [ ] Extract views to package
- [ ] Extract translations to package
- [ ] Create service provider
- [ ] Create configuration file
- [ ] Update routes to use package routes
- [ ] Update permissions (if needed)
- [ ] Test installation process
- [ ] Test all features
- [ ] Update documentation
- [ ] Remove old module code

### B.2 Package Installation

- [ ] Create Composer package
- [ ] Set up autoloading
- [ ] Create service provider
- [ ] Create configuration
- [ ] Create routes
- [ ] Create views
- [ ] Create translations
- [ ] Create assets
- [ ] Write tests
- [ ] Write documentation
- [ ] Publish to Packagist

---

**Document Version:** 1.0  
**Last Updated:** 2024-01-21  
**Author:** System Logs Package Development Team
