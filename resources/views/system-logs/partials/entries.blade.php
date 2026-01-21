@if($entries->isEmpty())
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> {{ __('system-logs::system-logs.no_entries_found') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th width="50">
                        <input type="checkbox" id="select-all-entries">
                    </th>
                    <th width="180">{{ __('system-logs::system-logs.timestamp') }}</th>
                    <th width="100">{{ __('system-logs::system-logs.level') }}</th>
                    <th width="100">{{ __('system-logs::system-logs.environment') }}</th>
                    <th width="150">{{ __('system-logs::system-logs.channel') }}</th>
                    <th width="200">{{ __('system-logs::system-logs.file') }}</th>
                    <th>{{ __('system-logs::system-logs.message') }}</th>
                    <th width="100">{{ __('system-logs::system-logs.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    <tr data-entry-id="{{ $entry['timestamp']->timestamp }}" data-file="{{ $entry['file'] }}">
                        <td>
                            <input type="checkbox" class="entry-checkbox" 
                                   value="{{ json_encode(['file' => $entry['file'], 'timestamp' => $entry['timestamp']->toIso8601String()]) }}">
                        </td>
                        <td>
                            <small>{{ $entry['timestamp']->format('Y-m-d H:i:s') }}</small>
                        </td>
                        <td>
                            <span class="badge level-badge level-{{ $entry['level'] }}">
                                {{ strtoupper($entry['level']) }}
                            </span>
                        </td>
                        <td>
                            <small>{{ $entry['environment'] }}</small>
                        </td>
                        <td>
                            <small>{{ $entry['file'] }}</small>
                        </td>
                        <td>
                            <small class="text-muted">{{ basename($entry['file']) }}</small>
                        </td>
                        <td>
                            <div class="log-message">
                                {{ Str::limit($entry['message'], 100) }}
                            </div>
                            @if(strlen($entry['message']) > 100 || !empty($entry['context']))
                                <button class="btn btn-sm btn-link p-0 mt-1" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#entry-{{ $entry['timestamp']->timestamp }}">
                                    <small>{{ __('system-logs::system-logs.view_details') }}</small>
                                </button>
                                <div class="collapse mt-2" id="entry-{{ $entry['timestamp']->timestamp }}">
                                    <div class="card card-body p-2">
                                        <strong>{{ __('system-logs::system-logs.full_message') }}:</strong>
                                        <pre class="mb-2">{{ $entry['message'] }}</pre>
                                        @if(!empty($entry['context']))
                                            <strong>{{ __('system-logs::system-logs.context') }}:</strong>
                                            <pre>{{ json_encode($entry['context'], JSON_PRETTY_PRINT) }}</pre>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td>
                            @if(auth()->user()?->can(config('system-logs.permissions.delete')))
                                <button class="btn btn-sm btn-danger delete-entry-btn" 
                                        data-file="{{ $entry['file'] }}" 
                                        data-timestamp="{{ $entry['timestamp']->toIso8601String() }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        <button class="btn btn-danger" id="bulk-delete-selected" disabled>
            <i class="fas fa-trash-alt"></i> {{ __('system-logs::system-logs.delete_selected') }}
        </button>
        <span class="ms-2 text-muted">
            {{ __('system-logs::system-logs.showing') }} {{ $entries->count() }} {{ __('system-logs::system-logs.entries') }}
        </span>
    </div>
@endif
