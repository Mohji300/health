/* district_dashboard.js */

$(document).ready(function() {
    console.log('District dashboard JS loaded');
    
    // Assessment type switching
    $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
    
    function switchAssessmentType(type) {
        var activeBtn = type === 'baseline' ? $('#switchToBaseline') : 
                       (type === 'midline' ? $('#switchToMidline') : $('#switchToEndline'));
        var originalHtml = activeBtn.html();
        activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
        $('.assessment-switcher .btn').prop('disabled', true);
        
        var currentSchoolLevel = window.DistrictDashboardConfig.school_level || 'all';
        var url = window.DistrictDashboardConfig.urls.base + '?assessment_type=' + type;
        if (currentSchoolLevel && currentSchoolLevel !== 'all') {
            url += '&school_level=' + encodeURIComponent(currentSchoolLevel);
        }
        window.location.href = url;
    }

    
    //school level dropdown selection
    $('.dropdown-item[data-level]').on('click', function(e) {
        e.preventDefault();
        
        const level = $(this).data('level');
        const levelText = $(this).text().trim();
        const assessmentType = window.DistrictDashboardConfig.assessment_type;

        if ($(this).hasClass('active')) {
            return;
        }

        const selectedIcon = $(this).find('i').clone();
        const button = $('#schoolLevelDropdown');
        button.html('').append(selectedIcon).append(' ' + levelText);

        showLoading();

        $.ajax({
            url: window.DistrictDashboardConfig.urls.set_school_level,
            method: 'POST',
            data: {
                school_level: level,
                assessment_type: assessmentType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                } else {
                    hideLoading();
                    showNotification('Error updating filter: ' + response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showNotification('Error connecting to server. Please try again.', 'error');
                console.error('School level update error:', error);
            }
        });
    });
    
    function updateTableVisibility(schoolLevel) {
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        
        if (!elemTable || !secTable) return;

        switch(schoolLevel) {
            case 'secondary':
            case 'integrated_secondary':
                elemTable.classList.add('d-none');
                secTable.classList.remove('d-none');
                break;
                
            case 'elementary':
            case 'integrated_elementary':
            case 'integrated':
            case 'all':
            default:
                elemTable.classList.remove('d-none');
                secTable.classList.add('d-none');
                break;
        }
    }

    const currentSchoolLevel = window.DistrictDashboardConfig.school_level || 'all';
    updateTableVisibility(currentSchoolLevel);

    // print functionality
    const btnPrint = document.getElementById('btnPrint');
    const elemTable = document.getElementById('elementaryTable');
    const secTable = document.getElementById('secondaryTable');

    if (btnPrint) {
        btnPrint.addEventListener('click', function() {
            const win = window.open('', '_blank');
            const isElemVisible = !elemTable.classList.contains('d-none');
            const tableHtml = (isElemVisible ? elemTable : secTable).outerHTML;
            const assessmentType = window.DistrictDashboardConfig.assessment_type_display || '';
            const reportDate = new Date().toLocaleDateString();
            const districtName = window.DistrictDashboardConfig.district_name || '';
            const schoolLevel = window.DistrictDashboardConfig.school_level || 'all';
            
            let schoolLevelDisplay = 'All Schools (Elementary View)';
            switch(schoolLevel) {
                case 'all': schoolLevelDisplay = 'All Schools (Elementary View)'; break;
                case 'elementary': schoolLevelDisplay = 'Elementary Schools Only'; break;
                case 'secondary': schoolLevelDisplay = 'Secondary Schools Only'; break;
                case 'integrated': schoolLevelDisplay = 'Integrated Schools (All Grades)'; break;
                case 'integrated_elementary': schoolLevelDisplay = 'Integrated Schools (Elementary Grades Only)'; break;
                case 'integrated_secondary': schoolLevelDisplay = 'Integrated Schools (Secondary Grades Only)'; break;
            }
            
            const printCss = `
                <style>
                    @page{size:A4 landscape;margin:8mm;}
                    body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:4px;color:#000;font-size:8px;line-height:1.2;}
                    table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8px;margin:0;}
                    th,td{border:0.5px solid #dee2e6;padding:2px;word-wrap:break-word;line-height:1.1;}
                    .no-print{display:none!important;}
                    h3{font-size:10px;margin:0 0 2px 0;font-weight:bold;}
                    p{font-size:7px;margin:0 0 4px 0;}
                    .print-header{text-align:center;margin-bottom:10px;}
                </style>
            `;
            
            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>District Nutritional Report - ' + districtName + '</title>' + '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' + printCss + '</head><body>');
            win.document.write('<div class="print-header"><h3>Nutritional Status Report - ' + districtName + ' District</h3><p><strong>Assessment Type:</strong> ' + assessmentType + ' | <strong>School Level:</strong> ' + schoolLevelDisplay + ' | <strong>Report Date:</strong> ' + reportDate + '</p></div>');
            win.document.write(tableHtml);
            win.document.write('<script>window.onload=function(){ setTimeout(function(){ window.print(); window.onafterprint=function(){ window.close(); } },200); }<\/script>');
            win.document.write('</body></html>');
            win.document.close();
        });
    }

    const $schoolSearch = $('#schoolSearch');
    const $clearSearch = $('#clearSearch');
    const $schoolsList = $('#schoolsList');
    const $schoolItems = $('.school-item');
    const $noSearchResults = $('#noSearchResults');
    const $noSchoolsMessage = $('#noSchoolsMessage');

    if ($noSearchResults.length) {
        $noSearchResults.addClass('d-none');
    }

    $schoolSearch.on('keyup', function() {
        performSearch();
    });

    $clearSearch.on('click', function() {
        $schoolSearch.val('');
        performSearch();
        $schoolSearch.focus();
    });

    function performSearch() {
        const searchTerm = $schoolSearch.val().toLowerCase().trim();

        if (searchTerm === '') {
            $schoolItems.each(function() {
                $(this).show();
            });
            $noSearchResults.addClass('d-none');

            if ($schoolItems.length === 0 && $noSchoolsMessage.length) {
                $noSchoolsMessage.removeClass('d-none');
            }
            return;
        }

        let visibleCount = 0;
        
        $schoolItems.each(function() {
            const $item = $(this);
            const schoolName = $item.data('school')?.toLowerCase() || '';
            const schoolCode = $item.data('code')?.toLowerCase() || '';
            const itemText = $item.text().toLowerCase();

            const matches = schoolName.includes(searchTerm) || 
                           schoolCode.includes(searchTerm) || 
                           itemText.includes(searchTerm);
            
            if (matches) {
                $item.show();
                visibleCount++;

                highlightText($item, searchTerm);
            } else {
                $item.hide();
            }
        });

        if (visibleCount === 0) {
            $noSearchResults.removeClass('d-none');
            if ($noSchoolsMessage.length) {
                $noSchoolsMessage.addClass('d-none');
            }
        } else {
            $noSearchResults.addClass('d-none');
        }
    }

    function highlightText($element, searchTerm) {
        if (searchTerm.length < 2) return;
        
        const nameElement = $element.find('.fw-medium');
        if (nameElement.length) {
            const originalText = nameElement.text();
            const regex = new RegExp('(' + escapeRegExp(searchTerm) + ')', 'gi');
            const highlighted = originalText.replace(regex, '<mark>$1</mark>');
            nameElement.html(highlighted);
        }
    }
    
    // Helper function to escape regex special characters
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    $clearSearch.on('click', function() {
        $schoolItems.each(function() {
            const $nameElement = $(this).find('.fw-medium');
            if ($nameElement.length) {
                $nameElement.html($nameElement.text());
            }
        });
    });

    // Schools box toggle
    $('#overallSummaryCard').click(function(e) {
        e.stopPropagation();
        $('#schoolsBox').toggleClass('d-none');
        const icon = $(this).find('.fa-chevron-up, .fa-chevron-down');
        const text = $(this).find('.small');
        
        if ($('#schoolsBox').hasClass('d-none')) {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
            text.html('<i class="fas fa-chevron-up me-1"></i> Click to view schools');
        } else {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
            text.html('<i class="fas fa-chevron-down me-1"></i> Hide schools');
        }
    });
    
    $('#closeSchoolsBox').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#schoolsBox').addClass('d-none');
        $('#overallSummaryCard').find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        $('#overallSummaryCard').find('.small').html('<i class="fas fa-chevron-up me-1"></i> Click to view schools');
    });

    // School details modal
    $(document).on('click', '.school-item', function(e) {
        e.preventDefault();
        const schoolName = $(this).data('school');
        showSchoolDetails(schoolName);
    });

    function showSchoolDetails(schoolName) {
        $('#schoolModalBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading school details...</p>
            </div>
        `);
        
        const modal = new bootstrap.Modal(document.getElementById('schoolModal'));
        modal.show();
        
        $.ajax({
            url: window.DistrictDashboardConfig.urls.get_school_details + encodeURIComponent(schoolName),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const details = response.data;
                    let schoolDetailsHtml = `
                        <div class="school-details">
                            <h6 class="border-bottom pb-2 mb-3">${escapeHtml(schoolName)}</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">School ID:</dt>
                                <dd class="col-sm-8">${escapeHtml(details.school_id || 'N/A')}</dd>
                                
                                <dt class="col-sm-4">Address:</dt>
                                <dd class="col-sm-8">${escapeHtml(details.address || 'N/A')}</dd>
                                
                                <dt class="col-sm-4">School Level:</dt>
                                <dd class="col-sm-8">${escapeHtml(details.school_level || 'N/A')}</dd>
                                
                                <dt class="col-sm-4">School Head:</dt>
                                <dd class="col-sm-8">${escapeHtml(details.school_head_name || 'N/A')}</dd>
                                
                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8">${escapeHtml(details.email || 'N/A')}</dd>
                            </dl>
                        </div>
                    `;
                    $('#schoolModalBody').html(schoolDetailsHtml);
                } else {
                    $('#schoolModalBody').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Unable to load school details. Please try again.
                        </div>
                    `);
                }
            },
            error: function() {
                $('#schoolModalBody').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error loading school details. Please try again.
                    </div>
                `);
            }
        });
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return text;
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Loading overlay functions
    function showLoading() {
        if (!$('#loadingOverlay').length) {
            $('body').append(`
                <div id="loadingOverlay" class="loading-overlay">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
        }
    }
    
    function hideLoading() {
        $('#loadingOverlay').remove();
    }
    
    // Notification function
    function showNotification(message, type) {
        $('.notification-toast').remove();

        const notification = $(`
            <div class="alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show notification-toast" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(notification);

        setTimeout(() => {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
});