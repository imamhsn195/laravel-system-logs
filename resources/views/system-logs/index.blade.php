@php
    $layout = config('system-logs.ui.layout', 'layouts.app');
    $title = config('system-logs.ui.title', 'System Logs');
@endphp

@extends($layout)

@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ app('system-logs.assets')->css() }}">
@endpush

@section('content')
<div class="system-logs-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $title }}</h3>
                    </div>
                    <div class="card-body">
                        {{-- Filter Panel --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <form id="log-filters-form" class="row g-3">
                                    <div class="col-md-2">
                                        <label for="filter-channel" class="form-label">{{ __('system-logs::system-logs.channel') }}</label>
                                        <select name="channel" id="filter-channel" class="form-select">
                                            <option value="">{{ __('system-logs::system-logs.all_channels') }}</option>
                                            @foreach($files->pluck('channel')->unique() as $channel)
                                                <option value="{{ $channel }}" {{ request('channel') == $channel ? 'selected' : '' }}>
                                                    {{ ucfirst($channel) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-file" class="form-label">{{ __('system-logs::system-logs.file') }}</label>
                                        <select name="file" id="filter-file" class="form-select">
                                            <option value="">{{ __('system-logs::system-logs.all_files') }}</option>
                                            @foreach($files as $file)
                                                <option value="{{ $file['relative_path'] }}" {{ request('file') == $file['relative_path'] ? 'selected' : '' }}>
                                                    {{ $file['relative_path'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-level" class="form-label">{{ __('system-logs::system-logs.level') }}</label>
                                        <select name="level" id="filter-level" class="form-select">
                                            <option value="">{{ __('system-logs::system-logs.all_levels') }}</option>
                                            <option value="debug" {{ request('level') == 'debug' ? 'selected' : '' }}>Debug</option>
                                            <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>Info</option>
                                            <option value="notice" {{ request('level') == 'notice' ? 'selected' : '' }}>Notice</option>
                                            <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>Warning</option>
                                            <option value="error" {{ request('level') == 'error' ? 'selected' : '' }}>Error</option>
                                            <option value="critical" {{ request('level') == 'critical' ? 'selected' : '' }}>Critical</option>
                                            <option value="alert" {{ request('level') == 'alert' ? 'selected' : '' }}>Alert</option>
                                            <option value="emergency" {{ request('level') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-environment" class="form-label">{{ __('system-logs::system-logs.environment') }}</label>
                                        <input type="text" name="environment" id="filter-environment" class="form-control" 
                                               value="{{ request('environment') }}" placeholder="{{ __('system-logs::system-logs.environment') }}">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-date" class="form-label">{{ __('system-logs::system-logs.date') }}</label>
                                        <input type="date" name="date" id="filter-date" class="form-control" 
                                               value="{{ request('date') }}">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-search" class="form-label">{{ __('system-logs::system-logs.search') }}</label>
                                        <input type="text" name="search" id="filter-search" class="form-control" 
                                               value="{{ request('search') }}" placeholder="{{ __('system-logs::system-logs.search_placeholder') }}">
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-per-page" class="form-label">{{ __('system-logs::system-logs.per_page') }}</label>
                                        <select name="per_page" id="filter-per-page" class="form-select">
                                            <option value="10" {{ request('per_page', 50) == 10 ? 'selected' : '' }}>10</option>
                                            <option value="25" {{ request('per_page', 50) == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page', 50) == 100 ? 'selected' : '' }}>100</option>
                                            <option value="300" {{ request('per_page', 50) == 300 ? 'selected' : '' }}>300</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="filter-max-files" class="form-label">{{ __('system-logs::system-logs.max_files') }}</label>
                                        <select name="max_files" id="filter-max-files" class="form-select">
                                            @for($i = 1; $i <= 20; $i++)
                                                <option value="{{ $i }}" {{ request('max_files', 3) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-12 mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter"></i> {{ __('system-logs::system-logs.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="reset-filters">
                                            <i class="fas fa-redo"></i> {{ __('system-logs::system-logs.reset') }}
                                        </button>
                                        @if(auth()->user()?->can(config('system-logs.permissions.delete')))
                                            <button type="button" class="btn btn-danger" id="bulk-delete-filtered">
                                                <i class="fas fa-trash-alt"></i> {{ __('system-logs::system-logs.delete_all_filtered') }}
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                        
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
                            @include('system-logs::partials.entries', ['entries' => $entries])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Delete Confirmation Modal --}}
@if(auth()->user()?->can(config('system-logs.permissions.delete')))
    @include('system-logs::partials.bulk-delete-modal')
@endif
@endsection

@push('scripts')
    <script src="{{ app('system-logs.assets')->js() }}"></script>
    <script>
        SystemLogs.init({
            baseUrl: '{{ route(config("system-logs.route.name_prefix") . "index") }}',
            deleteUrl: '{{ route(config("system-logs.route.name_prefix") . "destroy") }}',
            bulkDeleteUrl: '{{ route(config("system-logs.route.name_prefix") . "bulk-delete") }}',
            bulkDeleteByFiltersUrl: '{{ route(config("system-logs.route.name_prefix") . "bulk-delete-by-filters") }}',
            csrfToken: '{{ csrf_token() }}',
        });
    </script>
@endpush
