/**
 * System Logs Package JavaScript
 */
(function() {
    'use strict';
    
    const SystemLogs = {
        config: {},
        init: function(config) {
            this.config = config;
            
            if (!this.config.baseUrl) {
                console.error('SystemLogs: baseUrl is missing!');
                return;
            }
            
            this.bindEvents();
        },
        
        bindEvents: function() {
            const wrapper = document.querySelector('.system-logs-wrapper') || document.body;
            
            if (!wrapper) {
                console.error('SystemLogs: .system-logs-wrapper not found!');
                return;
            }
            
            // Initialize filter panel
            this.initFilterPanel();
            
            // Filter form submission
            const filterForm = document.getElementById('log-filter-form') || document.getElementById('log-filters-form');
            if (filterForm) {
                filterForm.addEventListener('submit', async (e) => {
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
            
            // Reset filters button - use event delegation
            wrapper.addEventListener('click', (e) => {
                const resetBtn = e.target.closest('#reset-filters');
                if (resetBtn) {
                    e.preventDefault();
                    // Clear all filter inputs
                    const form = document.getElementById('log-filter-form') || document.getElementById('log-filters-form');
                    if (form) {
                        form.querySelectorAll('select, input[type="text"], input[type="date"], input[type="search"], input[type="checkbox"]').forEach(input => {
                            if (input.type === 'checkbox') {
                                input.checked = false;
                            } else if (input.tagName === 'SELECT') {
                                input.selectedIndex = 0;
                            } else {
                                input.value = '';
                            }
                        });
                        // Navigate directly to base URL without any query parameters
                        this.showLoader();
                        const baseUrl = this.config.baseUrl || window.location.pathname;
                        fetch(baseUrl, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            // Parse the HTML response
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // Update entries container
                            const entriesContainer = document.getElementById('log-entries-container');
                            const newEntries = doc.querySelector('.system-logs-entries');
                            if (entriesContainer && newEntries) {
                                entriesContainer.innerHTML = newEntries.outerHTML;
                            }
                            
                            // Update buttons row - extract from response and replace current
                            const currentButtonsRow = document.querySelector('.d-flex.align-items-center.gap-2.mb-3');
                            const newButtonsRow = doc.querySelector('.d-flex.align-items-center.gap-2.mb-3');
                            if (currentButtonsRow && newButtonsRow) {
                                currentButtonsRow.innerHTML = newButtonsRow.innerHTML;
                            }
                            
                            // Update filter chips - remove if exists
                            const filterChipsContainer = document.querySelector('.filter-chips-container');
                            if (filterChipsContainer) {
                                filterChipsContainer.remove();
                            }
                            
                            // Update filter toggle button badge - remove if exists
                            const filterToggleBtn = document.querySelector('.filter-toggle-btn');
                            if (filterToggleBtn) {
                                const badge = filterToggleBtn.querySelector('.badge');
                                if (badge) {
                                    badge.remove();
                                }
                            }
                            
                            // Update URL without reload
                            window.history.pushState({}, '', baseUrl);
                            
                            // Re-initialize only the necessary event listeners for new content
                            this.updateSelectAllCheckbox();
                            this.updateBulkDeleteButton();
                            
                            this.hideLoader();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.hideLoader();
                            // Fallback to page reload
                            window.location.href = baseUrl;
                        });
                    } else {
                        window.location.href = this.config.baseUrl;
                    }
                }
            });
            
            // Delete single entry - use event delegation
            wrapper.addEventListener('click', (e) => {
                const deleteBtn = e.target.closest('.delete-entry-btn');
                if (deleteBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const file = deleteBtn.dataset.file;
                    const timestamp = deleteBtn.dataset.timestamp;
                    if (file && timestamp) {
                        this.deleteEntry(file, timestamp);
                    }
                }
            });
            
            // Select all checkbox - use event delegation
            wrapper.addEventListener('change', (e) => {
                if (e.target.id === 'select-all-entries') {
                    document.querySelectorAll('.entry-checkbox').forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                    this.updateBulkDeleteButton();
                }
                
                // Individual checkbox change
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
        },
        
        applyFilters: function() {
            this.showLoader();
            
            const form = document.getElementById('log-filter-form') || document.getElementById('log-filters-form');
            if (!form) {
                console.error('SystemLogs: Filter form not found!');
                return;
            }
            
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // AJAX request to get filtered results
            const endpoint = form.getAttribute('data-endpoint') || this.config.baseUrl;
            const url = endpoint + '?' + params.toString();
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                }
            })
            .then(response => response.text())
            .then(html => {
                // Parse the HTML response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update entries container
                const entriesContainer = document.getElementById('log-entries-container');
                const newEntries = doc.querySelector('.system-logs-entries');
                if (entriesContainer && newEntries) {
                    entriesContainer.innerHTML = newEntries.outerHTML;
                }
                
                // Update buttons row - extract from response and replace current
                const currentButtonsRow = document.querySelector('.d-flex.align-items-center.gap-2.mb-3');
                const newButtonsRow = doc.querySelector('.d-flex.align-items-center.gap-2.mb-3');
                if (currentButtonsRow && newButtonsRow) {
                    currentButtonsRow.innerHTML = newButtonsRow.innerHTML;
                }
                
                // Update filter chips - find the container in current page and in response
                const filterChipsContainer = document.querySelector('.filter-chips-container');
                const newChips = doc.querySelector('.filter-chips-container');
                
                if (filterChipsContainer) {
                    if (newChips) {
                        // Update existing chips container
                        filterChipsContainer.innerHTML = newChips.innerHTML;
                    } else {
                        // No active filters, remove chips container
                        filterChipsContainer.remove();
                    }
                } else if (newChips) {
                    // Chips container doesn't exist but we have new chips, add them after buttons row
                    const buttonsRow = document.querySelector('.d-flex.align-items-center.gap-2.mb-3');
                    if (buttonsRow) {
                        buttonsRow.insertAdjacentHTML('afterend', newChips.outerHTML);
                    }
                }
                
                // Update filter toggle button badge count
                const activeFilterCount = newChips ? newChips.querySelectorAll('.filter-chip').length : 0;
                const filterToggleBtn = document.querySelector('.filter-toggle-btn');
                if (filterToggleBtn) {
                    const badge = filterToggleBtn.querySelector('.badge');
                    if (activeFilterCount > 0) {
                        if (badge) {
                            badge.textContent = activeFilterCount;
                        } else {
                            filterToggleBtn.insertAdjacentHTML('beforeend', 
                                '<span class="badge bg-danger">' + activeFilterCount + '</span>'
                            );
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
                
                // Update URL without reload
                window.history.pushState({}, '', url);
                
                // Re-initialize only the necessary event listeners for new content
                // Most handlers use event delegation, so they work automatically
                // Only need to update select-all checkbox state
                this.updateSelectAllCheckbox();
                this.updateBulkDeleteButton();
                
                this.hideLoader();
            })
            .catch(error => {
                console.error('Error:', error);
                this.hideLoader();
                // Fallback to page reload
                window.location.href = url;
            });
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
        },
        
        initFilterPanel: function() {
            // Filter panel toggle
            document.addEventListener('click', (e) => {
                const toggleBtn = e.target.closest('[data-toggle-panel]');
                if (toggleBtn) {
                    e.preventDefault();
                    const panelId = toggleBtn.getAttribute('data-toggle-panel');
                    this.openFilterPanel(panelId);
                }
                
                const closeBtn = e.target.closest('[data-close-panel]');
                if (closeBtn) {
                    e.preventDefault();
                    const panelId = closeBtn.getAttribute('data-close-panel');
                    this.closeFilterPanel(panelId);
                }
                
                const overlay = e.target.closest('.filter-panel-overlay');
                if (overlay && e.target === overlay) {
                    const panelId = overlay.id.replace('-overlay', '');
                    this.closeFilterPanel(panelId);
                }
                
                // Refresh button
                const refreshBtn = e.target.closest('#refresh-logs');
                if (refreshBtn) {
                    e.preventDefault();
                    this.refreshLogs();
                }
            });
            
            // Apply filters button
            document.addEventListener('click', (e) => {
                const applyBtn = e.target.closest('[data-apply-filters]');
                if (applyBtn) {
                    e.preventDefault();
                    const formId = applyBtn.getAttribute('data-apply-filters');
                    const form = document.getElementById(formId);
                    if (form) {
                        this.applyFilters();
                    }
                }
            });
            
            // Clear filters button
            document.addEventListener('click', (e) => {
                const clearBtn = e.target.closest('[data-clear-filters]');
                if (clearBtn) {
                    e.preventDefault();
                    const formId = clearBtn.getAttribute('data-clear-filters');
                    const filterUrl = clearBtn.getAttribute('data-filter-url');
                    if (filterUrl) {
                        window.location.href = filterUrl;
                    }
                }
            });
            
            // Remove filter chip
            document.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('[data-remove-filter]');
                if (removeBtn) {
                    e.preventDefault();
                    const filterName = removeBtn.getAttribute('data-remove-filter');
                    const filterUrl = removeBtn.getAttribute('data-filter-url');
                    this.removeFilterChip(filterName, filterUrl);
                }
            });
            
            // ESC key to close panel
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    const activePanel = document.querySelector('.filter-panel.active');
                    if (activePanel) {
                        const panelId = activePanel.id;
                        this.closeFilterPanel(panelId);
                    }
                }
            });
        },
        
        openFilterPanel: function(panelId) {
            const overlay = document.getElementById(panelId + '-overlay');
            const panel = document.getElementById(panelId);
            
            if (overlay && panel) {
                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    panel.classList.add('active');
                }, 10);
            }
        },
        
        closeFilterPanel: function(panelId) {
            const overlay = document.getElementById(panelId + '-overlay');
            const panel = document.getElementById(panelId);
            
            if (overlay && panel) {
                panel.classList.remove('active');
                setTimeout(() => {
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }, 300);
            }
        },
        
        removeFilterChip: function(filterName, filterUrl) {
            // Get the form to update the specific filter field
            const form = document.getElementById('log-filter-form') || document.getElementById('log-filters-form');
            if (form) {
                // Clear the specific filter input
                const input = form.querySelector(`[name="${filterName}"]`);
                if (input) {
                    if (input.type === 'checkbox') {
                        input.checked = false;
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                }
                
                // Apply filters with AJAX (which will update the page without reload)
                this.applyFilters();
            } else {
                // Fallback to page reload if form not found
                const url = new URL(filterUrl || window.location.href);
                url.searchParams.delete(filterName);
                window.location.href = url.toString();
            }
        },
        
        refreshLogs: function() {
            // Reload the page with current filters preserved
            this.showLoader();
            window.location.reload();
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
