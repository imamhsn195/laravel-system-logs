<?php

namespace ImamHasan\SystemLogs\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ImamHasan\SystemLogs\SystemLogServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create logs directory for testing
        $this->app->make('files')->ensureDirectoryExists(storage_path('logs'));
    }
    
    protected function getPackageProviders($app)
    {
        return [
            SystemLogServiceProvider::class,
        ];
    }
    
    protected function getEnvironmentSetUp($app)
    {
        // Setup test environment
        $app['config']->set('system-logs.log_directory', storage_path('logs'));
    }
}
