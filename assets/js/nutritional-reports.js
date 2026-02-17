// Nutritional Reports JavaScript

$(document).ready(function() {
    'use strict';
    
    /**
     * Initialize DataTable
     */
    function initializeDataTable() {
        const table = $('#reportsTable');
        
        if (table.length && reportsConfig.hasReports) {
            try {
                const dataTable = table.DataTable({
                    "pageLength": 25,
                    "ordering": true,
                    "order": [[0, 'asc'], [3, 'asc'], [4, 'asc'], [5, 'asc']],
                    "columnDefs": [
                        { "orderable": false, "targets": [8, 9] }
                    ],
                    "language": {
                        "emptyTable": "No reports available",
                        "info": "Showing _START_ to _END_ of _TOTAL_ reports",
                        "infoEmpty": "Showing 0 to 0 of 0 reports",
                        "infoFiltered": "(filtered from _MAX_ total reports)",
                        "lengthMenu": "Show _MENU_ reports",
                        "search": "Search:",
                        "zeroRecords": "No matching reports found",
                        "paginate": {
                            "first": "First",
                            "last": "Last",
                            "next": "Next",
                            "previous": "Previous"
                        }
                    },
                    "responsive": true,
                    "autoWidth": false,
                    "scrollX": true,
                    "scrollCollapse": true,
                    "drawCallback": function(settings) {
                        // Reinitialize tooltips after table redraw
                        initializeTooltips();
                        updateReportCount();
                    }
                });
                
                console.log('DataTable initialized successfully');
                
                // Store DataTable instance for later use
                window.reportsDataTable = dataTable;
                
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                showNotification('Error loading table data', 'danger');
            }
        }
    }
    
    /**
     * Initialize Bootstrap Tooltips
     */
    function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        
        if (tooltipTriggerList.length) {
            // Destroy existing tooltips first
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                const tooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (tooltip) {
                    tooltip.dispose();
                }
            });
            
            // Create new tooltips
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    placement: 'top',
                    trigger: 'hover',
                    delay: { "show": 100, "hide": 100 }
                });
            });
        }
    }
    
    /**
     * Handle filter form submission
     */
    function initializeFilterForm() {
        const filterForm = $('#filterForm');
        
        if (filterForm.length) {
            filterForm.on('submit', function() {
                const btn = $('#applyFiltersBtn');
                const originalText = btn.html();
                
                btn.prop('disabled', true)
                   .html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...');
                
                // Store original text for potential recovery
                btn.data('original-text', originalText);
                
                // Optional: Add loading overlay
                showLoadingOverlay();
            });
        }
    }
    
    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        const overlay = $(`
            <div id="loadingOverlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.8);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
            ">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-primary">Loading reports...</p>
            </div>
        `);
        
        $('body').append(overlay);
    }
    
    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        $('#loadingOverlay').remove();
    }
    
    /**
     * Handle reset filters button
     */
    function initializeResetButton() {
        const resetBtn = $('#resetFiltersBtn, #clearFiltersBtn');
        
        if (resetBtn.length) {
            resetBtn.on('click', function(e) {
                // Optional: Add confirmation
                // if (!confirm('Reset all filters?')) {
                //     e.preventDefault();
                //     return false;
                // }
            });
        }
    }
    
    /**
     * Handle export buttons
     */
    function initializeExportButtons() {
        $('.export-btn, .export-detail-btn').on('click', function(e) {
            const btn = $(this);
            const originalText = btn.html();
            
            // Show loading state
            btn.prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');
            
            // Restore button after a delay (if page doesn't redirect)
            setTimeout(function() {
                btn.prop('disabled', false)
                   .html(originalText);
            }, 3000);
        });
    }
    
    /**
     * Handle statistics button
     */
    function initializeStatsButton() {
        $('.stats-btn').on('click', function(e) {
            const btn = $(this);
            const originalText = btn.html();
            
            btn.prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin me-1"></i> Loading...');
        });
    }
    
    /**
     * Update report count in header
     */
    function updateReportCount() {
        const table = $('#reportsTable').DataTable();
        if (table) {
            const count = table.rows({ search: 'applied' }).count();
            $('#reportCount').text(`${count.toLocaleString()} Reports`);
        }
    }
    
    /**
     * Handle window resize for responsive tables
     */
    function handleWindowResize() {
        let resizeTimer;
        
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            
            resizeTimer = setTimeout(function() {
                const table = $('#reportsTable').DataTable();
                if (table) {
                    table.columns.adjust().responsive.recalc();
                }
            }, 250);
        });
    }
    
    /**
     * Show notification message
     */
    function showNotification(message, type = 'info') {
        const alertDiv = $(`
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" 
                 role="alert" style="z-index: 9999; min-width: 300px;">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                          type === 'danger' ? 'exclamation-circle' : 
                                          type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                    </div>
                    <div class="flex-grow-1">
                        ${message}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        $('body').append(alertDiv);
        
        setTimeout(function() {
            alertDiv.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Validate date inputs
     */
    function validateDates() {
        const dateFrom = $('#dateFrom');
        const dateTo = $('#dateTo');
        
        if (dateFrom.length && dateTo.length) {
            dateFrom.on('change', function() {
                const fromDate = $(this).val();
                if (fromDate && dateTo.val() && dateTo.val() < fromDate) {
                    dateTo.val(fromDate);
                    showNotification('"Date To" adjusted to match "Date From"', 'warning');
                }
            });
            
            dateTo.on('change', function() {
                const toDate = $(this).val();
                const fromDate = dateFrom.val();
                
                if (fromDate && toDate && toDate < fromDate) {
                    dateFrom.val(toDate);
                    showNotification('"Date From" adjusted to match "Date To"', 'warning');
                }
            });
        }
    }
    
    /**
     * Handle table row click for quick export
     */
    function initializeTableRowClick() {
        $('#reportsTable tbody').on('click', 'tr', function(e) {
            // Prevent click on export buttons
            if ($(e.target).closest('a').length || $(e.target).closest('.btn').length) {
                return;
            }
            
            // Get school details from the row
            const schoolName = $(this).find('td:first-child').text().trim();
            const schoolId = $(this).find('.badge.bg-dark').text().replace('ID:', '').trim();
            
            console.log('Row clicked:', { schoolName, schoolId });
            
            // Highlight the row briefly
            $(this).addClass('table-active');
            setTimeout(() => {
                $(this).removeClass('table-active');
            }, 200);
        });
    }
    
    /**
     * Export current table data to CSV
     */
    function exportTableToCSV() {
        if (!reportsConfig.hasReports) {
            showNotification('No data to export', 'warning');
            return;
        }
        
        const table = $('#reportsTable').DataTable();
        const data = table.rows({ search: 'applied' }).data();
        const filename = `reports_export_${new Date().toISOString().slice(0,10)}.csv`;
        
        // Convert DataTable data to CSV
        const csv = [];
        
        // Add headers
        const headers = [];
        $('#reportsTable thead th').each(function() {
            headers.push($(this).text().trim());
        });
        csv.push(headers.join(','));
        
        // Add data
        data.each(function(row) {
            const rowData = [];
            $(row).each(function(index, cell) {
                let text = $(cell).text().trim();
                // Escape commas and quotes
                if (text.includes(',') || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                rowData.push(text);
            });
            csv.push(rowData.join(','));
        });
        
        // Download CSV
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        URL.revokeObjectURL(url);
        
        showNotification('CSV exported successfully!', 'success');
    }
    
    /**
     * Initialize all components
     */
    function initialize() {
        initializeDataTable();
        initializeTooltips();
        initializeFilterForm();
        initializeResetButton();
        initializeExportButtons();
        initializeStatsButton();
        handleWindowResize();
        validateDates();
        initializeTableRowClick();
        
        console.log('Nutritional reports page initialized successfully');
    }
    
    // Start initialization
    initialize();
    
    // Handle AJAX errors globally
    $(document).ajaxError(function(event, jqxhr, settings, error) {
        console.error('AJAX Error:', error);
        hideLoadingOverlay();
        showNotification('An error occurred. Please try again.', 'danger');
    });
    
});

/**
 * Utility functions for nutritional reports
 */
const NutritionalReportsUtils = {
    /**
     * Format date to YYYY-MM-DD
     */
    formatDate: function(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },
    
    /**
     * Get current filter parameters
     */
    getCurrentFilters: function() {
        if (typeof reportsConfig !== 'undefined' && reportsConfig.currentFilters) {
            return reportsConfig.currentFilters;
        }
        
        const urlParams = new URLSearchParams(window.location.search);
        const filters = {};
        
        for (const [key, value] of urlParams) {
            filters[key] = value;
        }
        
        return filters;
    },
    
    /**
     * Build filter string for URLs
     */
    buildFilterString: function(filters) {
        return Object.keys(filters)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(filters[key] || '')}`)
            .join('&');
    },
    
    /**
     * Clear all filters and reset page
     */
    clearFilters: function() {
        window.location.href = window.location.pathname;
    },
    
    /**
     * Get report count
     */
    getReportCount: function() {
        if (typeof reportsConfig !== 'undefined') {
            return reportsConfig.totalReports;
        }
        return 0;
    }
};

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NutritionalReportsUtils;
}