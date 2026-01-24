@props([
    'filters' => [],
    'filterUrl' => null,
])

@php
    $filterUrl = $filterUrl ?? request()->url();
    $activeFilters = [];
    
    foreach ($filters as $field) {
        $fieldName = $field['name'] ?? '';
        $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
        $value = request($fieldName);
        
        if ($value !== null && $value !== '') {
            // Skip hidden fields and certain fields from display
            if (($field['type'] ?? 'text') !== 'hidden' && !in_array($fieldName, ['per_page', 'max_files'])) {
                $activeFilters[] = [
                    'name' => $fieldName,
                    'label' => $fieldLabel,
                    'value' => $value,
                ];
            }
        }
    }
@endphp

@if(count($activeFilters) > 0 && config('system-logs.ui.filter_chips.enabled', true))
    <div class="filter-chips-container d-flex flex-wrap align-items-center gap-2 mb-3">
        @foreach($activeFilters as $filter)
            <span class="badge bg-info filter-chip d-inline-flex align-items-center gap-1">
                <span>{{ $filter['label'] }}: {{ $filter['value'] }}</span>
                @if(config('system-logs.ui.filter_chips.auto_remove', true))
                    <button type="button" 
                            class="filter-chip-remove" 
                            data-remove-filter="{{ $filter['name'] }}"
                            data-filter-url="{{ $filterUrl }}"
                            aria-label="Remove filter"
                            title="Remove filter"></button>
                @endif
            </span>
        @endforeach
    </div>
@endif
