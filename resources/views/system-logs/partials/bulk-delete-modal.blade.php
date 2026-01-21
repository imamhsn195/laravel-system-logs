<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkDeleteModalLabel">
                    {{ __('system-logs::system-logs.delete_all_filtered') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>{{ __('system-logs::system-logs.warning') }}:</strong>
                    {{ __('system-logs::system-logs.bulk_delete_warning') }}
                </div>
                
                <div id="bulk-delete-filters-summary" class="mb-3">
                    <strong>{{ __('system-logs::system-logs.active_filters') }}:</strong>
                    <ul id="filters-list" class="list-unstyled mt-2"></ul>
                    <p class="mb-0">
                        <strong>{{ __('system-logs::system-logs.estimated_entries') }}:</strong>
                        <span id="estimated-count">-</span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm-checkbox">
                        <label class="form-check-label" for="confirm-checkbox">
                            {{ __('system-logs::system-logs.confirm_understanding') }}
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm-text" class="form-label">
                        {{ __('system-logs::system-logs.type_delete') }}
                    </label>
                    <input type="text" class="form-control" id="confirm-text" placeholder="DELETE">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('system-logs::system-logs.cancel') }}
                </button>
                <button type="button" class="btn btn-danger" id="confirm-bulk-delete" disabled>
                    <i class="fas fa-trash-alt"></i> {{ __('system-logs::system-logs.delete_all') }}
                </button>
            </div>
        </div>
    </div>
</div>
