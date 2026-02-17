// District Reports JavaScript

$(document).ready(function() {
    'use strict';
    
    /**
     * Initialize DataTable
     */
    function initializeDataTable() {
        const table = $('#reportsTable');
        
        if (table.length) {
            try {
                table.DataTable({
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
                    "fixedHeader": true,
                    "drawCallback": function(settings) {
                        // Reinitialize tooltips after table redraw
                        initializeTooltips();
                    }
                });
                
                console.log('DataTable initialized successfully');
            } catch (error) {
                console.error('Error initializing DataTable:', error);
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
                    trigger: 'hover'
                });
            });
        }
    }
    
    /**
     * Handle filter form submission
     */
    function initializeFilterForm() {
        const filterForm = $('form[action*="district/reports"]');
        
        if (filterForm.length) {
            filterForm.on('submit', function() {
                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();
                
                btn.prop('disabled', true)
                   .html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...');
                
                // Store original text in case we need to restore it
                btn.data('original-text', originalText);
            });
        }
    }
    
    /**
     * Handle reset filters button
     */
    function initializeResetButton() {
        const resetBtn = $('a[href*="district/reports"]:not([href*="export"])');
        
        if (resetBtn.length) {
            resetBtn.on('click', function(e) {
                // Optional: Add confirmation dialog
                // const confirmReset = confirm('Reset all filters?');
                // if (!confirmReset) {
                //     e.preventDefault();
                // }
            });
        }
    }
    
    /**
     * Handle export buttons
     */
    function initializeExportButtons() {
        $('.btn-success, .btn-info').on('click', function(e) {
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
     * Handle table row click for details
     */
    function initializeTableRowClick() {
        $('#reportsTable tbody').on('click', 'tr', function(e) {
            // Prevent click on export buttons
            if ($(e.target).closest('a').length) {
                return;
            }
            
            // Get school name from the row
            const schoolName = $(this).find('td:first-child').text().trim();
            console.log('Row clicked:', schoolName);
            
            // Optional: Navigate to school details page
            // const schoolId = $(this).find('.badge.bg-dark').text().replace('ID:', '').trim();
            // window.location.href = '/district/school-details/' + schoolId;
        });
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
                 role="alert" style="z-index: 9999;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alertDiv);
        
        setTimeout(function() {
            alertDiv.alert('close');
        }, 5000);
    }
    
    /**
     * Initialize date pickers with validation
     */
    function initializeDatePickers() {
        const dateFrom = $('input[name="date_from"]');
        const dateTo = $('input[name="date_to"]');
        
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
     * Export table data to CSV
     */
    function exportTableToCSV(filename = 'reports.csv') {
        const table = $('#reportsTable');
        const rows = table.find('tr');
        const csv = [];
        
        rows.each(function() {
            const row = [];
            $(this).find('td, th').each(function() {
                let text = $(this).text().trim();
                // Remove extra spaces and special characters
                text = text.replace(/\s+/g, ' ').replace(/[^\w\s,-]/gi, '');
                
                // Wrap in quotes if contains comma
                if (text.includes(',')) {
                    text = `"${text}"`;
                }
                
                row.push(text);
            });
            csv.push(row.join(','));
        });
        
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
    
    // Initialize all components
    initializeDataTable();
    initializeFilterForm();
    initializeResetButton();
    initializeExportButtons();
    initializeTableRowClick();
    handleWindowResize();
    initializeDatePickers();
    
    // Additional event listeners
    $(document).on('shown.bs.tooltip', function() {
        console.log('Tooltip shown');
    });
    
    // Handle AJAX errors
    $(document).ajaxError(function(event, jqxhr, settings, error) {
        console.error('AJAX Error:', error);
        showNotification('An error occurred. Please try again.', 'danger');
    });
    
    console.log('Reports page JavaScript initialized successfully');
});

/**
 * Utility functions for reports page
 */
const ReportsUtils = {
    /**
     * Format date to YYYY-MM-DD
     */
    formatDate: function(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },
    
    /**
     * Get current filter parameters
     */
    getCurrentFilters: function() {
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
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(filters[key])}`)
            .join('&');
    },
    
    /**
     * Clear all filters and reset page
     */
    clearFilters: function() {
        window.location.href = window.location.pathname;
    }
};

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReportsUtils;
}