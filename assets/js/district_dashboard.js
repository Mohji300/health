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
                    /* Preserve background colors/images when possible */
                    *, *::before, *::after { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                    @page{size:Legal landscape;margin:8mm;}
                    body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:4px;color:#000;font-size:7px;line-height:1.05;}
                    table{width:100%;border-collapse:collapse;table-layout:auto;font-size:7px;margin:0;}
                    th,td{border:0.5px solid #dee2e6;padding:2px;word-wrap:break-word;line-height:1.05;vertical-align:top;}
                    th:first-child, td:first-child{width:80px;min-width:80px;max-width:80px;}
                    th:nth-child(2), td:nth-child(2){width:30px;min-width:30px;max-width:30px;}
                    thead{display:table-header-group;} tfoot{display:table-footer-group;} tbody{display:table-row-group;}
                    tr, td, th {page-break-inside: avoid; page-break-after: auto;}
                    .no-print{display:none!important;}
                    h3{font-size:10px;margin:0 0 2px 0;font-weight:bold;}
                    p{font-size:7px;margin:0 0 4px 0;}
                    .print-header{text-align:center;margin-bottom:10px;}
                </style>
            `;
            
            try {
                function pruneEmptyColumns(tableEl) {
                    try {
                        const tbl = tableEl.cloneNode(true);
                        const rows = Array.from(tbl.querySelectorAll('tr'));
                        if (!rows.length) return tableEl;
                        const colCount = rows[0].children.length;
                        let lastNonEmpty = -1;

                        for (let c = 0; c < colCount; c++) {
                            let hasContent = false;
                            for (let r = 0; r < rows.length; r++) {
                                const cell = rows[r].children[c];
                                if (!cell) continue;
                                const txt = cell.textContent.replace(/\s+/g, '').trim();
                                if (txt !== '') { hasContent = true; break; }
                            }
                            if (hasContent) lastNonEmpty = c;
                        }

                        if (lastNonEmpty < 0 || lastNonEmpty === colCount - 1) return tableEl;

                        for (let r = 0; r < rows.length; r++) {
                            for (let c = colCount - 1; c > lastNonEmpty; c--) {
                                if (rows[r].children[c]) rows[r].removeChild(rows[r].children[c]);
                            }
                        }
                        return tbl;
                    } catch (e) { return tableEl; }
                }

                // set document title using user or school name when available (match user dashboard layout)
                const _docName = (window.DistrictDashboardConfig && (window.DistrictDashboardConfig.user_name || window.DistrictDashboardConfig.school_name || window.DistrictDashboardConfig.user)) || '';
                const _docTitle = _docName ? (_docName + ' Nutritional Status Report') : 'Nutritional Status Report';
                const _docTitleEsc = String(_docTitle).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + _docTitleEsc + '</title>' + printCss + '</head><body>');
                function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

                function getQueryParam(name) { const params = new URLSearchParams(window.location.search); return params.get(name) || ''; }

                function findSchoolName() {
                    if (window.DistrictDashboardConfig && window.DistrictDashboardConfig.school_name) return window.DistrictDashboardConfig.school_name;
                    const ps = document.querySelectorAll('p');
                    for (let p of ps) {
                        const txt = (p.textContent || '').trim();
                        if (txt.startsWith('Showing nutritional data for')) {
                            const parts = txt.split('Showing nutritional data for');
                            if (parts[1]) return parts[1].trim();
                        }
                    }
                    const sidebarName = document.querySelector('.main-sidebar-text h6');
                    if (sidebarName && sidebarName.textContent.trim()) return sidebarName.textContent.trim();
                    return getQueryParam('school_name') || getQueryParam('school') || districtName || '';
                }

                function findSchoolId() {
                    if (window.DistrictDashboardConfig && window.DistrictDashboardConfig.school_id) return window.DistrictDashboardConfig.school_id;
                    const input = document.querySelector('input[name="school_id"]');
                    if (input && input.value) return input.value;
                    return getQueryParam('school_id') || getQueryParam('school') || '';
                }

                const uName = (window.DistrictDashboardConfig && (window.DistrictDashboardConfig.user_name || window.DistrictDashboardConfig.user)) || '';
                const sName = findSchoolName();
                const sId = findSchoolId();
                const titlePrefix = uName || sName ? (escHtml(uName || '') + (uName && sName ? '/' + escHtml(sName) : (!uName ? escHtml(sName) : ''))) : '';
                const headerTitle = titlePrefix ? (titlePrefix + ' Nutritional Status Report - ' + escHtml(assessmentType)) : ('Nutritional Status Report - ' + escHtml(sName || ''));
                const idSuffix = sId ? (' | ID: ' + escHtml(sId)) : '';
                win.document.write('<div class="print-header"><h3>' + headerTitle + idSuffix + '</h3><p><strong>Assessment Type:</strong> ' + escHtml(assessmentType) + ' | <strong>School Level:</strong> ' + escHtml(schoolLevelDisplay) + ' | <strong>School ID:</strong> ' + escHtml(sId || '') + '</p></div>');
                try {
                    const clone = (new DOMParser()).parseFromString(tableHtml, 'text/html').body.firstChild.cloneNode(true);
                    const hidden = document.createElement('div');
                    hidden.style.position = 'fixed'; hidden.style.left = '-9999px'; hidden.style.top = '-9999px'; hidden.style.visibility = 'hidden';
                    document.body.appendChild(hidden);
                    hidden.appendChild(clone);

                    try {
                        const elems = clone.querySelectorAll('*');
                        elems.forEach(el => {
                            try {
                                const cs = window.getComputedStyle(el);
                                if (!cs) return;
                                const bg = cs.backgroundColor;
                                const bgImg = cs.backgroundImage;
                                const isBgVisible = bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent';
                                if (isBgVisible) el.style.backgroundColor = bg;
                                if (bgImg && bgImg !== 'none') {
                                    el.style.backgroundImage = bgImg;
                                    el.style.backgroundRepeat = cs.backgroundRepeat || '';
                                    el.style.backgroundSize = cs.backgroundSize || '';
                                    el.style.backgroundPosition = cs.backgroundPosition || '';
                                    el.style.backgroundClip = cs.backgroundClip || '';
                                }
                                if (cs.color) el.style.color = cs.color;
                                if (cs.borderColor) el.style.borderColor = cs.borderColor;
                                el.style.webkitPrintColorAdjust = 'exact';
                                el.style.printColorAdjust = 'exact';
                            } catch (inner) { /* ignore per-element failures */ }
                        });
                    } catch (ie) { /* ignore */ }

                    const pruned = pruneEmptyColumns(clone);
                    win.document.write(pruned.outerHTML);
                    document.body.removeChild(hidden);
                } catch (e) {
                    win.document.write(tableHtml);
                }
                win.document.write('</body></html>');
                win.document.close();

                var link = win.document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css';
                link.onload = function() {
                    try { win.focus(); win.print(); } catch (e) { console.error(e); }
                    win.onafterprint = function() { try { win.close(); } catch (e) {} };
                };
                link.onerror = function() {
                    try { win.focus(); win.print(); } catch (e) { console.error(e); }
                    win.onafterprint = function() { try { win.close(); } catch (e) {} };
                };
                win.document.head.appendChild(link);
            } catch (err) {
                console.error('Print window error, falling back to inline print', err);
                try { window.print(); } catch (e) { console.error(e); }
            }
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