<?php

namespace ImamHasan\SystemLogs\Tests\Unit;

use ImamHasan\SystemLogs\Tests\TestCase;
use ImamHasan\SystemLogs\Services\SystemLogService;
use Illuminate\Support\Facades\File;

class SystemLogServiceTest extends TestCase
{
    protected SystemLogService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SystemLogService(storage_path('logs'));
    }
    
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
    
    public function test_can_list_log_files()
    {
        // Create a test log file
        $logPath = storage_path('logs/test-laravel.log');
        File::put($logPath, '[2024-01-15 10:00:00] local.INFO: Test message');
        
        $files = $this->service->listFiles();
        
        $this->assertNotEmpty($files);
        $this->assertTrue($files->contains('name', 'test-laravel.log'));
    }
    
    public function test_can_parse_log_entries()
    {
        // Create test log content
        $logContent = "[2024-01-15 10:00:00] local.INFO: Test message\n";
        $logPath = storage_path('logs/test-laravel.log');
        File::put($logPath, $logContent);
        
        $entries = $this->service->getEntries([], 10);
        
        $this->assertNotEmpty($entries['entries']);
        $this->assertEquals('info', $entries['entries']->first()['level']);
    }
    
    public function test_can_filter_by_level()
    {
        $logContent = "[2024-01-15 10:00:00] local.ERROR: Error message\n" .
                      "[2024-01-15 10:01:00] local.INFO: Info message\n";
        $logPath = storage_path('logs/test-laravel.log');
        File::put($logPath, $logContent);
        
        $entries = $this->service->getEntries(['level' => 'error'], 10);
        
        $this->assertNotEmpty($entries['entries']);
        $this->assertTrue($entries['entries']->every(fn($entry) => $entry['level'] === 'error'));
    }
    
    public function test_can_delete_entry()
    {
        $logContent = "[2024-01-15 10:00:00] local.INFO: First message\n" .
                      "[2024-01-15 10:01:00] local.INFO: Second message\n";
        $logPath = storage_path('logs/test-laravel.log');
        File::put($logPath, $logContent);
        
        $deleted = $this->service->deleteEntry('test-laravel.log', '2024-01-15T10:00:00Z');
        
        $this->assertTrue($deleted);
        
        // Verify entry was deleted
        $remaining = File::get($logPath);
        $this->assertStringNotContainsString('First message', $remaining);
        $this->assertStringContainsString('Second message', $remaining);
    }
}
