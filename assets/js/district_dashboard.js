/* district_dashboard.js - Complete version with search functionality */

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
    
    // School level filtering
    $('#btnElementary').click(function() {
        $('#btnIntegrated').removeClass('active');
        $('#integratedSubMenu').addClass('d-none');
        enableTableSwitching();
        setSchoolLevelFilter('elementary');
    });
    
    $('#btnSecondary').click(function() {
        $('#btnIntegrated').removeClass('active');
        $('#integratedSubMenu').addClass('d-none');
        enableTableSwitching();
        setSchoolLevelFilter('secondary');
    });
    
    $('#btnIntegrated').click(function(e) {
        e.preventDefault();
        $(this).toggleClass('active');
        if ($(this).hasClass('active')) {
            disableTableSwitching();
            $('#integratedSubMenu').removeClass('d-none');
            setSchoolLevelFilter('integrated');
        } else {
            enableTableSwitching();
            $('#integratedSubMenu').addClass('d-none');
            setSchoolLevelFilter('all');
        }
    });
    
    $('#btnIntegratedElementary').click(function() {
        setSchoolLevelFilter('integrated_elementary');
    });
    
    $('#btnIntegratedSecondary').click(function() {
        setSchoolLevelFilter('integrated_secondary');
    });

    function enableTableSwitching() {
        $('#btnElementary, #btnSecondary').css({
            'pointerEvents': 'auto',
            'opacity': '1'
        });
    }
    
    function disableTableSwitching() {
        $('#btnElementary, #btnSecondary').css({
            'pointerEvents': 'none',
            'opacity': '0.5'
        });
    }

    function setSchoolLevelFilter(level) {
        // Show loading state
        $('#btnElementary, #btnSecondary, #btnIntegrated').html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        $('#btnElementary, #btnSecondary, #btnIntegrated').prop('disabled', true);
        
        $.ajax({
            url: window.DistrictDashboardConfig.urls.set_school_level,
            method: 'POST',
            data: { 
                school_level: level, 
                assessment_type: window.DistrictDashboardConfig.assessment_type 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var url = window.DistrictDashboardConfig.urls.base + 
                             '?assessment_type=' + encodeURIComponent(window.DistrictDashboardConfig.assessment_type);
                    if (level !== 'all') {
                        url += '&school_level=' + encodeURIComponent(level);
                    }
                    window.location.href = url;
                } else {
                    alert('Error: ' + response.message);
                    resetButtonStates();
                }
            },
            error: function() {
                alert('Error applying filter. Please try again.');
                resetButtonStates();
            }
        });
    }

    function resetButtonStates() {
        $('#btnElementary').html('<i class="fas fa-child me-1"></i> Elementary <span class="badge bg-info ms-1">K-6</span>');
        $('#btnSecondary').html('<i class="fas fa-graduation-cap me-1"></i> Secondary <span class="badge bg-info ms-1">7-12</span>');
        $('#btnIntegrated').html('<i class="fas fa-university me-1"></i> Integrated <span class="badge bg-info ms-1">K-12</span>');
        $('#btnElementary, #btnSecondary, #btnIntegrated').prop('disabled', false);
    }

    // Table switching
    const btnElem = document.getElementById('btnElementary');
    const btnSec = document.getElementById('btnSecondary');
    const elemTable = document.getElementById('elementaryTable');
    const secTable = document.getElementById('secondaryTable');
    const btnPrint = document.getElementById('btnPrint');

    if (btnElem && btnSec && elemTable && secTable) {
        btnElem.addEventListener('click', function() {
            btnElem.classList.add('active');
            btnSec.classList.remove('active');
            elemTable.classList.remove('d-none');
            secTable.classList.add('d-none');
        });
        
        btnSec.addEventListener('click', function() {
            btnSec.classList.add('active');
            btnElem.classList.remove('active');
            secTable.classList.remove('d-none');
            elemTable.classList.add('d-none');
        });
    }

    // Print functionality
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
                case 'elementary': schoolLevelDisplay = 'Elementary Schools'; break;
                case 'secondary': schoolLevelDisplay = 'Secondary Schools'; break;
                case 'integrated': schoolLevelDisplay = 'Integrated Schools (Elementary View)'; break;
                case 'integrated_elementary': schoolLevelDisplay = 'Integrated Schools (Elementary Only)'; break;
                case 'integrated_secondary': schoolLevelDisplay = 'Integrated Schools (Secondary Only)'; break;
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

    // ==================== SCHOOL SEARCH FUNCTIONALITY ====================
    
    // Cache DOM elements
    const $schoolSearch = $('#schoolSearch');
    const $clearSearch = $('#clearSearch');
    const $schoolsList = $('#schoolsList');
    const $schoolItems = $('.school-item');
    const $noSearchResults = $('#noSearchResults');
    const $noSchoolsMessage = $('#noSchoolsMessage');
    
    // Initialize search - hide no results message initially
    if ($noSearchResults.length) {
        $noSearchResults.addClass('d-none');
    }
    
    // Live search as user types
    $schoolSearch.on('keyup', function() {
        performSearch();
    });
    
    // Clear search button
    $clearSearch.on('click', function() {
        $schoolSearch.val('');
        performSearch();
        $schoolSearch.focus();
    });
    
    // Search function
    function performSearch() {
        const searchTerm = $schoolSearch.val().toLowerCase().trim();
        
        // If search is empty, show all schools
        if (searchTerm === '') {
            $schoolItems.each(function() {
                $(this).show();
            });
            $noSearchResults.addClass('d-none');
            
            // Show "no schools" message if there are no schools at all
            if ($schoolItems.length === 0 && $noSchoolsMessage.length) {
                $noSchoolsMessage.removeClass('d-none');
            }
            return;
        }
        
        // Perform search
        let visibleCount = 0;
        
        $schoolItems.each(function() {
            const $item = $(this);
            const schoolName = $item.data('school')?.toLowerCase() || '';
            const schoolCode = $item.data('code')?.toLowerCase() || '';
            const itemText = $item.text().toLowerCase();
            
            // Check if search term matches name, code, or any text in the item
            const matches = schoolName.includes(searchTerm) || 
                           schoolCode.includes(searchTerm) || 
                           itemText.includes(searchTerm);
            
            if (matches) {
                $item.show();
                visibleCount++;
                
                // Highlight matching text (optional)
                highlightText($item, searchTerm);
            } else {
                $item.hide();
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0) {
            $noSearchResults.removeClass('d-none');
            if ($noSchoolsMessage.length) {
                $noSchoolsMessage.addClass('d-none');
            }
        } else {
            $noSearchResults.addClass('d-none');
        }
    }
    
    // Optional: Highlight matching text
    function highlightText($element, searchTerm) {
        if (searchTerm.length < 2) return; // Don't highlight very short terms
        
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
    
    // Reset highlighting when search is cleared
    $clearSearch.on('click', function() {
        $schoolItems.each(function() {
            const $nameElement = $(this).find('.fw-medium');
            if ($nameElement.length) {
                $nameElement.html($nameElement.text()); // Remove highlights
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
});