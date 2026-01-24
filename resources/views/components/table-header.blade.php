@props([
    'buttons' => [],
    'filters' => [],
    'filterUrl' => null,
    'showingTrash' => false,
    'panelId' => 'filter-panel-' . uniqid(),
    'formId' => null,
    'formAttributes' => [],
    'canDelete' => false,
])

@php
    // Count active filters
    $activeFilterCount = 0;
    foreach ($filters as $field) {
        $value = request($field['name'] ?? '');
        if ($value !== null && $value !== '') {
            $activeFilterCount++;
        }
    }
    
    $formId = $formId ?? 'filter-form-' . uniqid();
    $filterUrl = $filterUrl ?? request()->url();
@endphp

{{-- Action Buttons Row: Refresh | Filters | Reset Filter --}}
<div class="d-flex align-items-center gap-2 mb-3">
    {{-- Custom buttons (Refresh, etc.) --}}
    @if(isset($buttons['custom']) && is_array($buttons['custom']))
        @foreach($buttons['custom'] as $customButton)
            @php
                $permission = $customButton['permission'] ?? null;
                $canAccess = !$permission || (auth()->check() && auth()->user()?->can($permission));
            @endphp
            @if($canAccess)
                @if(($customButton['type'] ?? 'link') === 'button')
                    <button type="button" 
                            id="{{ $customButton['id'] ?? '' }}"
                            class="{{ $customButton['class'] ?? 'btn btn-sm btn-outline-primary' }}"
                            @if(isset($customButton['onclick']))
                                onclick="{{ $customButton['onclick'] }}"
                            @endif>
                        @if(isset($customButton['icon']))
                            <i class="fas {{ $customButton['icon'] }}"></i>
                        @endif
                        {{ $customButton['label'] ?? '' }}
                    </button>
                @else
                    <a href="{{ $customButton['route'] ?? '#' }}" 
                       class="{{ $customButton['class'] ?? 'btn btn-sm btn-outline-primary' }}">
                        @if(isset($customButton['icon']))
                            <i class="fas {{ $customButton['icon'] }}"></i>
                        @endif
                        {{ $customButton['label'] ?? '' }}
                    </a>
                @endif
            @endif
        @endforeach
    @endif
    
    {{-- Filters Button --}}
    @if(config('system-logs.ui.filter_panel.enabled', true))
        <button type="button" 
                class="btn btn-sm btn-outline-info filter-toggle-btn position-relative" 
                data-toggle-panel="{{ $panelId }}">
            <i class="fas fa-filter"></i> {{ __('system-logs::system-logs.apply_filters') }}
            @if($activeFilterCount > 0)
                <span class="badge bg-danger">
                    {{ $activeFilterCount }}
                </span>
            @endif
        </button>
    @endif
    
    {{-- Reset Filter Button - Only show when filters are active --}}
    @if($activeFilterCount > 0)
        <button type="button" class="btn btn-sm btn-outline-secondary" id="reset-filters">
            <i class="fas fa-redo"></i> {{ __('system-logs::system-logs.reset_filter') }}
        </button>
    @endif
</div>

{{-- Filter Chips Below Buttons --}}
@if(config('system-logs.ui.filter_chips.enabled', true))
    <x-system-logs::filter-chips :filters="$filters" :filterUrl="$filterUrl" />
@endif

@if(config('system-logs.ui.filter_panel.enabled', true))
    <x-system-logs::filter-panel 
        :filters="$filters" 
        :filterUrl="$filterUrl"
        :panelId="$panelId"
        :formId="$formId"
        :formAttributes="$formAttributes"
    />
@endif
