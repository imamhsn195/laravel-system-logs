@php
    $layout = config('system-logs.ui.layout', 'layouts.app');
    $title = config('system-logs.ui.title', 'System Logs');
@endphp

@extends($layout)

@section('title', $title)

@push('styles')
    @php
        $cssPath = app('system-logs.assets')->css();
        $cssFile = public_path('vendor/system-logs/css/system-logs.css');
        $cssExists = file_exists($cssFile);
    @endphp
    
    @if($cssExists)
        <link rel="stylesheet" href="{{ $cssPath }}?v={{ time() }}">
    @else
        <script>
            console.error('SystemLogs: CSS file not found at: {{ $cssFile }}');
            console.error('Please run: php artisan vendor:publish --tag=system-logs-assets --force');
        </script>
    @endif
@endpush

@section('content')
{{-- Loading Overlay --}}
<div class="system-logs-loader" id="system-logs-loader">
    <div class="system-logs-loader-content">
        <div class="system-logs-spinner"></div>
        <div class="system-logs-loader-text">Loading logs...</div>
    </div>
</div>

<div class="system-logs-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $title }}</h3>
                    </div>
                    <div class="card-body">
                        {{-- Table Header with Filter Panel --}}
                        @php
                            $filterConfig = [
                                [
                                    'name' => 'channel',
                                    'type' => 'select',
                                    'label' => __('system-logs::system-logs.channel'),
                                    'id' => 'log-channel',
                                    'defaultOption' => ['value' => '', 'label' => __('system-logs::system-logs.all_channels')],
                                    'options' => collect($channels ?? [])->mapWithKeys(function($channel) {
                                        return [$channel => \Illuminate\Support\Str::title(str_replace('_', ' ', $channel))];
                                    })->toArray(),
                                    'value' => request('channel')
                                ],
                                [
                                    'name' => 'file',
                                    'type' => 'select',
                                    'label' => __('system-logs::system-logs.file'),
                                    'id' => 'log-file',
                                    'defaultOption' => ['value' => '', 'label' => __('system-logs::system-logs.all_files')],
                                    'options' => collect($files ?? [])->mapWithKeys(function($file) {
                                        return [$file['relative_path'] => $file['relative_path'] . ' (' . $file['size_human'] . ')'];
                                    })->toArray(),
                                    'value' => request('file')
                                ],
                                [
                                    'name' => 'level',
                                    'type' => 'select',
                                    'label' => __('system-logs::system-logs.level'),
                                    'id' => 'log-level',
                                    'defaultOption' => ['value' => '', 'label' => __('system-logs::system-logs.all_levels')],
                                    'options' => collect($levels ?? [])->mapWithKeys(function($level) {
                                        return [$level => strtoupper($level)];
                                    })->toArray(),
                                    'value' => request('level')
                                ],
                                [
                                    'name' => 'environment',
                                    'type' => 'text',
                                    'label' => __('system-logs::system-logs.environment'),
                                    'id' => 'log-environment',
                                    'placeholder' => 'e.g. local, production',
                                    'value' => request('environment')
                                ],
                                [
                                    'name' => 'date',
                                    'type' => 'date',
                                    'label' => __('system-logs::system-logs.date'),
                                    'id' => 'log-date',
                                    'value' => request('date')
                                ],
                                [
                                    'name' => 'search',
                                    'type' => 'text',
                                    'label' => __('system-logs::system-logs.search'),
                                    'id' => 'log-search',
                                    'placeholder' => __('system-logs::system-logs.search_placeholder'),
                                    'value' => request('search')
                                ],
                                [
                                    'name' => 'per_page',
                                    'type' => 'select',
                                    'label' => __('system-logs::system-logs.per_page'),
                                    'id' => 'log-limit',
                                    'defaultOption' => ['value' => '', 'label' => __('system-logs::system-logs.per_page')],
                                    'options' => [
                                        10 => '10',
                                        25 => '25',
                                        50 => '50',
                                        100 => '100',
                                        300 => '300',
                                    ],
                                    'value' => request('per_page', config('system-logs.filters.default_per_page', 50))
                                ],
                                [
                                    'name' => 'max_files',
                                    'type' => 'hidden',
                                    'value' => request('max_files', config('system-logs.filters.default_max_files', 3))
                                ]
                            ];
                            
                            $permission = config('system-logs.permissions.delete');
                            $canDelete = !$permission || (auth()->check() && auth()->user()?->can($permission));
                        @endphp
                        
                        <x-system-logs::table-header
                            :buttons="[
                                'custom' => [
                                    [
                                        'type' => 'button',
                                        'id' => 'refresh-logs',
                                        'class' => 'btn btn-sm btn-outline-primary',
                                        'label' => __('system-logs::system-logs.refresh'),
                                        'icon' => 'fa-sync-alt'
                                    ]
                                ]
                            ]"
                            :filters="$filterConfig"
                            :filterUrl="route(config('system-logs.route.name_prefix') . 'index')"
                            :panelId="'system-logs-filter-panel'"
                            :formId="'log-filter-form'"
                            :formAttributes="['data-endpoint' => route(config('system-logs.route.name_prefix') . 'index'), 'class' => 'row']"
                            :canDelete="$canDelete"
                        />
                        
                        {{-- Success/Error Messages --}}
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        {{-- Log Entries Table --}}
                        <div id="log-entries-container">
                            @include('system-logs::partials.entries', ['entries' => $entries, 'canDelete' => $canDelete])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    @php
        $jsPath = app('system-logs.assets')->js();
        $jsFile = public_path('vendor/system-logs/js/system-logs.js');
        $jsExists = file_exists($jsFile);
    @endphp
    
    @if($jsExists)
        <script src="{{ $jsPath }}?v={{ time() }}"></script>
    @else
        <script>
            console.error('SystemLogs: JavaScript file not found at: {{ $jsFile }}');
            console.error('Please run: php artisan vendor:publish --tag=system-logs-assets');
        </script>
    @endif
    
    <script>
        (function() {
            function initSystemLogs() {
                if (typeof SystemLogs === 'undefined') {
                    console.error('SystemLogs: JavaScript file not loaded! Please run: php artisan vendor:publish --tag=system-logs-assets --force');
                    return;
                }
                
                try {
                    SystemLogs.init({
                        baseUrl: '{{ route(config("system-logs.route.name_prefix") . "index") }}',
                        deleteUrl: '{{ route(config("system-logs.route.name_prefix") . "destroy") }}',
                        bulkDeleteUrl: '{{ route(config("system-logs.route.name_prefix") . "bulk-delete") }}',
                        csrfToken: '{{ csrf_token() }}',
                    });
                } catch (error) {
                    console.error('SystemLogs: Initialization error:', error);
                }
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initSystemLogs);
            } else {
                setTimeout(initSystemLogs, 100);
            }
        })();
    </script>
@endpush
