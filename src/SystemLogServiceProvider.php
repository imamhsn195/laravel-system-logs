<?php

namespace ImamHasan\SystemLogs;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;

class SystemLogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/system-logs.php',
            'system-logs'
        );
        
        // Register service as singleton
        $this->app->singleton(Services\SystemLogService::class, function ($app) {
            return new Services\SystemLogService(
                config('system-logs.log_directory')
            );
        });
        
        // Register asset helper
        $this->registerAssetHelper();
    }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
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
        $this->loadViewsFrom(__DIR__.'/../resources/views/system-logs', 'system-logs');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'system-logs');
        
        // Register Blade directives (optional)
        $this->registerBladeDirectives();
    }
    
    /**
     * Load package routes.
     */
    protected function loadRoutes(): void
    {
        Route::group([
            'prefix' => config('system-logs.route.prefix'),
            'middleware' => config('system-logs.route.middleware'),
            'as' => config('system-logs.route.name_prefix'),
        ], function () {
            require __DIR__.'/../routes/web.php';
        });
    }
    
    /**
     * Register asset helper.
     */
    protected function registerAssetHelper(): void
    {
        $this->app->singleton('system-logs.assets', function () {
            return new class {
                public function css($file = 'system-logs.css')
                {
                    return $this->url("css/{$file}");
                }
                
                public function js($file = 'system-logs.js')
                {
                    return $this->url("js/{$file}");
                }
                
                public function url($path)
                {
                    $config = config('system-logs.assets', []);
                    $version = $config['version'] ?? '1.0.0';
                    
                    if ($config['use_cdn'] ?? false) {
                        $cdnUrl = rtrim($config['cdn_url'] ?? '', '/');
                        return "{$cdnUrl}/{$path}?v={$version}";
                    }
                    
                    return asset("vendor/system-logs/{$path}") . "?v={$version}";
                }
            };
        });
    }
    
    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('systemLogsAsset', function ($expression) {
            return "<?php echo app('system-logs.assets')->url({$expression}); ?>";
        });
    }
}
