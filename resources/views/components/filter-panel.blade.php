@props([
    'filters' => [],
    'filterUrl' => null,
    'panelId' => 'filter-panel',
    'formId' => 'filter-form',
    'formAttributes' => [],
])

@php
    $filterUrl = $filterUrl ?? request()->url();
    $panelWidth = config('system-logs.ui.filter_panel.width', 400);
    $panelPosition = config('system-logs.ui.filter_panel.position', 'right');
    $overlayOpacity = config('system-logs.ui.filter_panel.overlay_opacity', 0.5);
    
    $formAttributes = array_merge([
        'id' => $formId,
        'method' => 'GET',
        'action' => $filterUrl,
    ], $formAttributes);
    
    $formClass = $formAttributes['class'] ?? 'row g-3';
    unset($formAttributes['class']);
    
    // Helper function to build HTML attributes
    $buildAttributes = function($attributes) {
        $html = '';
        foreach ($attributes as $key => $value) {
            if ($value !== null && $value !== '') {
                $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return $html;
    };
@endphp

<div class="filter-panel-overlay" id="{{ $panelId }}-overlay" style="display: none; background: rgba(0, 0, 0, {{ $overlayOpacity }});">
    <div class="filter-panel {{ $panelPosition === 'left' ? 'filter-panel-left' : 'filter-panel-right' }}" 
         id="{{ $panelId }}"
         style="width: {{ $panelWidth }}px;">
        <div class="filter-panel-header d-flex justify-content-between align-items-center p-3 border-bottom">
            <h5 class="mb-0">{{ __('system-logs::system-logs.advanced_filters') }}</h5>
            <button type="button" class="btn-close" data-close-panel="{{ $panelId }}" aria-label="Close"></button>
        </div>
        
        <div class="filter-panel-body p-3" style="overflow-y: auto; flex: 1;">
            <form{!! $buildAttributes($formAttributes) !!} class="{{ $formClass }}">
                @foreach($filters as $field)
                    @php
                        $fieldName = $field['name'] ?? '';
                        $fieldType = $field['type'] ?? 'text';
                        $fieldId = $field['id'] ?? $fieldName;
                        $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
                        $fieldValue = $field['value'] ?? request($fieldName);
                        $fieldPlaceholder = $field['placeholder'] ?? '';
                        $fieldClass = $field['class'] ?? 'form-control';
                        $fieldAttributes = $field['attributes'] ?? [];
                    @endphp
                    
                    @if($fieldType === 'hidden')
                        <input type="hidden" 
                               name="{{ $fieldName }}" 
                               value="{{ $fieldValue }}"{!! $buildAttributes($fieldAttributes) !!}>
                    @else
                        <div class="col-12 mb-3">
                            <label for="{{ $fieldId }}" class="form-label">{{ $fieldLabel }}</label>
                            
                            @if($fieldType === 'select')
                                <select name="{{ $fieldName }}" 
                                        id="{{ $fieldId }}" 
                                        class="{{ $fieldClass }}"{!! $buildAttributes($fieldAttributes) !!}>
                                    @if(isset($field['defaultOption']))
                                        <option value="{{ $field['defaultOption']['value'] ?? '' }}">
                                            {{ $field['defaultOption']['label'] ?? '' }}
                                        </option>
                                    @endif
                                    @if(isset($field['options']) && is_array($field['options']))
                                        @foreach($field['options'] as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}" {{ $fieldValue == $optionValue ? 'selected' : '' }}>
                                                {{ $optionLabel }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            @elseif($fieldType === 'checkbox')
                                <div class="form-check">
                                    <input type="checkbox" 
                                           name="{{ $fieldName }}" 
                                           id="{{ $fieldId }}" 
                                           class="form-check-input"
                                           value="{{ $field['checkboxValue'] ?? '1' }}"
                                           {{ $fieldValue ? 'checked' : '' }}{!! $buildAttributes($fieldAttributes) !!}>
                                    <label class="form-check-label" for="{{ $fieldId }}">
                                        {{ $fieldLabel }}
                                    </label>
                                </div>
                            @elseif($fieldType === 'number')
                                <input type="number" 
                                       name="{{ $fieldName }}" 
                                       id="{{ $fieldId }}" 
                                       class="{{ $fieldClass }}"
                                       value="{{ $fieldValue }}"
                                       placeholder="{{ $fieldPlaceholder }}"{!! $buildAttributes($fieldAttributes) !!}>
                            @elseif($fieldType === 'date')
                                <input type="date" 
                                       name="{{ $fieldName }}" 
                                       id="{{ $fieldId }}" 
                                       class="{{ $fieldClass }}"
                                       value="{{ $fieldValue }}"{!! $buildAttributes($fieldAttributes) !!}>
                            @else
                                <input type="{{ $fieldType }}" 
                                       name="{{ $fieldName }}" 
                                       id="{{ $fieldId }}" 
                                       class="{{ $fieldClass }}"
                                       value="{{ $fieldValue }}"
                                       placeholder="{{ $fieldPlaceholder }}"{!! $buildAttributes($fieldAttributes) !!}>
                            @endif
                        </div>
                    @endif
                @endforeach
            </form>
        </div>
        
        <div class="filter-panel-footer p-3 border-top">
            <div class="text-muted small">
                <i class="fas fa-info-circle"></i> {{ __('system-logs::system-logs.filters_auto_apply') ?? 'Filters apply automatically when changed' }}
            </div>
        </div>
    </div>
</div>
