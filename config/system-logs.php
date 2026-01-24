<?php

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
        'layout' => 'layouts.app',
        'layout_type' => 'extend',
        'section_name' => 'content',
        'title' => 'System Logs',
        'filter_panel' => [
            'enabled' => true,
            'width' => 400,
            'position' => 'right',
            'overlay_opacity' => 0.5,
        ],
        'filter_chips' => [
            'enabled' => true,
            'auto_remove' => true,
        ],
    ],
    
    // Filter defaults
    'filters' => [
        'default_per_page' => 50,
        'min_per_page' => 10,
        'max_per_page' => 300,
        'default_max_files' => 3,
        'min_max_files' => 1,
        'max_max_files' => 20,
        'max_lines_per_file' => 2000, // Limit lines read per file for performance
        'read_from_end' => true, // Read from end of file (most recent entries first)
        'levels' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
    ],
    
    // Parsing configuration
    'parsing' => [
        'date_format' => 'Y-m-d H:i:s',
        'entry_pattern' => '/^\[(?<datetime>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<environment>[\w\-.]+)\.(?<level>[A-Z]+):\s(?<body>.*)$/',
    ],
    
    // Directory scanning
    'scanning' => [
        'recursive' => true,
        'max_depth' => 10,
        'exclude_directories' => ['.git', 'node_modules', '.cache'],
    ],
    
    // Security
    'security' => [
        'allowed_file_extensions' => ['.log'],
        'max_file_size' => 100 * 1024 * 1024, // 100MB
    ],
    
    // Assets configuration
    'assets' => [
        'use_cdn' => false,
        'cdn_url' => '',
        'version' => '1.0.0',
        'publish_path' => 'vendor/system-logs',
    ],
];
