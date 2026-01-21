<?php

namespace ImamHasan\SystemLogs\Tests\Feature;

use ImamHasan\SystemLogs\Tests\TestCase;
use Illuminate\Support\Facades\File;

class SystemLogControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up test log files
        $files = File::files(storage_path('logs'));
        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'test-')) {
                File::delete($file->getPathname());
            }
        }
        parent::tearDown();
    }
    
    public function test_can_view_logs_page()
    {
        // Create test log file
        $logPath = storage_path('logs/test-laravel.log');
        File::put($logPath, '[2024-01-15 10:00:00] local.INFO: Test');
        
        $response = $this->get('/admin/system-logs');
        
        $response->assertStatus(200);
        $response->assertSee('System Logs');
    }
    
    public function test_can_filter_logs_by_level()
    {
        $logContent = "[2024-01-15 10:00:00] local.ERROR: Error message\n" .
                      "[2024-01-15 10:01:00] local.INFO: Info message\n";
        $logPath = storage_path('logs/test-laravel.log');
        File::put($logPath, $logContent);
        
        $response = $this->get('/admin/system-logs?level=error');
        
        $response->assertStatus(200);
        $response->assertSee('ERROR');
    }
}
