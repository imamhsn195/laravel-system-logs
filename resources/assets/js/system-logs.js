/**
 * System Logs Package JavaScript
 */
(function() {
    'use strict';
    
    const SystemLogs = {
        config: {},
        init: function(config) {
            console.log('SystemLogs: Initializing...', config);
            this.config = config;
            
            // Check if required elements exist
            if (!this.config.baseUrl) {
                console.error('SystemLogs: baseUrl is missing!');
                return;
            }
            
            this.bindEvents();
            console.log('SystemLogs: Initialized successfully');
        },
        
        bindEvents: function() {
            console.log('SystemLogs: Binding events...');
            // Use event delegation for dynamically loaded elements
            const wrapper = document.querySelector('.system-logs-wrapper') || document.body;
            
            if (!wrapper) {
                console.error('SystemLogs: .system-logs-wrapper not found!');
                return;
            }
            
            console.log('SystemLogs: Wrapper found', wrapper);
            
            // Filter form submission
            const filterForm = document.getElementById('log-filters-form');
            if (filterForm) {
                filterForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.applyFilters();
                });
                
                // Auto-apply filters on change
                const filterInputs = filterForm.querySelectorAll('select, input[type="text"], input[type="date"], input[type="search"]');
                let debounceTimer;
                
                filterInputs.forEach(input => {
                    // Skip reset button and other non-filter inputs
                    if (input.id === 'reset-filters' || input.type === 'button' || input.type === 'submit') {
                        return;
                    }
                    
                    input.addEventListener('change', () => {
                        this.applyFilters();
                    });
                    
                    // For text inputs, add debounce to avoid too many requests
                    if (input.type === 'text' || input.type === 'search') {
                        input.addEventListener('input', () => {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(() => {
                                this.applyFilters();
                            }, 500); // Wait 500ms after user stops typing
                        });
                    }
                });
            }
            
            // Reset filters
            const resetBtn = document.getElementById('reset-filters');
            if (resetBtn) {
                resetBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.href = this.config.baseUrl;
                });
            }
            
            // Delete single entry - use event delegation
            wrapper.addEventListener('click', (e) => {
                const deleteBtn = e.target.closest('.delete-entry-btn');
                if (deleteBtn) {
                    console.log('SystemLogs: Delete button clicked', deleteBtn);
                    e.preventDefault();
                    e.stopPropagation();
                    const file = deleteBtn.dataset.file;
                    const timestamp = deleteBtn.dataset.timestamp;
                    console.log('SystemLogs: Delete data', { file, timestamp });
                    if (file && timestamp) {
                        this.deleteEntry(file, timestamp);
                    } else {
                        console.error('SystemLogs: Missing file or timestamp data attributes');
                    }
                }
            });
            
            // Select all checkbox
            const selectAll = document.getElementById('select-all-entries');
            if (selectAll) {
                selectAll.addEventListener('change', (e) => {
                    document.querySelectorAll('.entry-checkbox').forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                    this.updateBulkDeleteButton();
                });
            }
            
            // Individual checkbox change - use event delegation
            wrapper.addEventListener('change', (e) => {
                if (e.target.classList.contains('entry-checkbox')) {
                    this.updateBulkDeleteButton();
                    this.updateSelectAllCheckbox();
                }
            });
            
            // Bulk delete selected
            const bulkDeleteSelected = document.getElementById('bulk-delete-selected');
            if (bulkDeleteSelected) {
                bulkDeleteSelected.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.bulkDeleteSelected();
                });
            }
            
            // Bulk delete by filters
            const bulkDeleteFiltered = document.getElementById('bulk-delete-filtered');
            if (bulkDeleteFiltered) {
                bulkDeleteFiltered.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showBulkDeleteModal();
                });
            }
            
            // Confirm bulk delete
            const confirmBulkDelete = document.getElementById('confirm-bulk-delete');
            if (confirmBulkDelete) {
                confirmBulkDelete.addEventListener('click', () => {
                    this.confirmBulkDelete();
                });
            }
            
            // Modal validation
            const confirmCheckbox = document.getElementById('confirm-checkbox');
            const confirmText = document.getElementById('confirm-text');
            if (confirmCheckbox && confirmText) {
                [confirmCheckbox, confirmText].forEach(el => {
                    el.addEventListener('change', () => {
                        this.validateBulkDeleteConfirmation();
                    });
                    el.addEventListener('input', () => {
                        this.validateBulkDeleteConfirmation();
                    });
                });
            }
        },
        
        applyFilters: function() {
            this.showLoader();
            
            const form = document.getElementById('log-filters-form');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            window.location.href = this.config.baseUrl + '?' + params.toString();
        },
        
        showLoader: function() {
            const loader = document.getElementById('system-logs-loader');
            if (loader) {
                loader.classList.add('active');
            }
        },
        
        hideLoader: function() {
            const loader = document.getElementById('system-logs-loader');
            if (loader) {
                loader.classList.remove('active');
            }
        },
        
        deleteEntry: function(file, timestamp) {
            if (!confirm('Are you sure you want to delete this log entry?')) {
                return;
            }
            
            this.showLoader();
            
            const formData = new FormData();
            formData.append('file', file);
            formData.append('timestamp', timestamp);
            formData.append('_token', this.config.csrfToken);
            formData.append('_method', 'DELETE');
            
            fetch(this.config.deleteUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    this.hideLoader();
                    alert(data.message || 'Failed to delete log entry');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.hideLoader();
                alert('An error occurred while deleting the log entry');
            });
        },
        
        bulkDeleteSelected: function() {
            const selected = Array.from(document.querySelectorAll('.entry-checkbox:checked'))
                .map(checkbox => JSON.parse(checkbox.value));
            
            if (selected.length === 0) {
                alert('Please select at least one entry to delete');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${selected.length} log entries?`)) {
                return;
            }
            
            this.showLoader();
            
            // Send as JSON with proper array format
            const formData = new FormData();
            selected.forEach((entry, index) => {
                formData.append(`entries[${index}][file]`, entry.file);
                formData.append(`entries[${index}][timestamp]`, entry.timestamp);
            });
            formData.append('_token', this.config.csrfToken);
            formData.append('_method', 'DELETE');
            
            fetch(this.config.bulkDeleteUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    this.hideLoader();
                    alert(data.message || 'Failed to delete log entries');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.hideLoader();
                alert('An error occurred while deleting log entries');
            });
        },
        
        showBulkDeleteModal: function() {
            // Get active filters
            const form = document.getElementById('log-filters-form');
            const formData = new FormData(form);
            const activeFilters = [];
            
            for (const [key, value] of formData.entries()) {
                if (value && !['per_page', 'max_files'].includes(key)) {
                    activeFilters.push({ key, value });
                }
            }
            
            if (activeFilters.length === 0) {
                alert('Please apply at least one filter before bulk deleting');
                return;
            }
            
            // Update modal content
            const filtersList = document.getElementById('filters-list');
            filtersList.innerHTML = activeFilters.map(filter => 
                `<li><strong>${filter.key}:</strong> ${filter.value}</li>`
            ).join('');
            
            // Get estimated count (you might want to make an AJAX call here)
            document.getElementById('estimated-count').textContent = 'Calculating...';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
            modal.show();
        },
        
        validateBulkDeleteConfirmation: function() {
            const checkbox = document.getElementById('confirm-checkbox');
            const textInput = document.getElementById('confirm-text');
            const confirmBtn = document.getElementById('confirm-bulk-delete');
            
            if (checkbox && textInput && confirmBtn) {
                const isValid = checkbox.checked && 
                               textInput.value.toUpperCase() === 'DELETE';
                confirmBtn.disabled = !isValid;
            }
        },
        
        confirmBulkDelete: function() {
            const form = document.getElementById('log-filters-form');
            const formData = new FormData(form);
            const params = {};
            
            for (const [key, value] of formData.entries()) {
                if (value && !['per_page', 'max_files'].includes(key)) {
                    params[key] = value;
                }
            }
            
            params.confirm = true;
            
            const requestData = new FormData();
            Object.keys(params).forEach(key => {
                requestData.append(key, params[key]);
            });
            requestData.append('_token', this.config.csrfToken);
            requestData.append('_method', 'DELETE');
            
            this.showLoader();
            
            fetch(this.config.bulkDeleteByFiltersUrl, {
                method: 'POST',
                body: requestData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'));
                    modal.hide();
                    location.reload();
                } else {
                    this.hideLoader();
                    alert(data.message || 'Failed to delete log entries');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.hideLoader();
                alert('An error occurred while deleting log entries');
            });
        },
        
        updateBulkDeleteButton: function() {
            const selected = document.querySelectorAll('.entry-checkbox:checked').length;
            const btn = document.getElementById('bulk-delete-selected');
            if (btn) {
                btn.disabled = selected === 0;
                if (selected > 0) {
                    btn.textContent = `Delete Selected (${selected})`;
                } else {
                    btn.textContent = 'Delete Selected';
                }
            }
        },
        
        updateSelectAllCheckbox: function() {
            const checkboxes = document.querySelectorAll('.entry-checkbox');
            const checked = document.querySelectorAll('.entry-checkbox:checked').length;
            const selectAll = document.getElementById('select-all-entries');
            
            if (selectAll && checkboxes.length > 0) {
                selectAll.checked = checked === checkboxes.length;
                selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
            }
        }
    };
    
    // Export to global scope
    window.SystemLogs = SystemLogs;
    
    // Hide loader when page is fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            SystemLogs.hideLoader();
        });
    } else {
        SystemLogs.hideLoader();
    }
})();
