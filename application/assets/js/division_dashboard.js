/* JS for division_dashboard */
$(document).ready(function() {
    let currentView = 'districts';
    let selectedDistrict = null;
    let allSchools = [];
    
    // Store all schools data from config
    // const allSchoolsData = window.DivisionDashboardConfig.all_schools_by_district || {};
    
    function buildDashboardUrl(type, level) {
        const params = new URLSearchParams();
        params.set('assessment_type', type);

        if (level && level !== 'all') {
            params.set('school_level', level);
        }

        const districtId = window.DivisionDashboardConfig.selected_legislative_district_id;
        if (districtId) {
            params.set('legislative_district_id', districtId);
        }

        return window.DivisionDashboardConfig.urls.base + '?' + params.toString();
    }

    $(document).on('change', '#districtFilter', function() {
        $('#districtFilterForm').submit();
    });

    // Assessment dropdown click handler (AJAX then redirect)
    $(document).on('click', 'a.dropdown-item[data-type]', function(e) {
        e.preventDefault();
        var $item = $(this);
        var type = $item.data('type');
        if ($item.hasClass('active')) return;
        var $btn = $('#assessmentDropdown');
        if ($btn.length) $btn.prop('disabled', true);

        $.ajax({
            url: window.DivisionDashboardConfig.urls.set_assessment_type,
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var schoolLevel = window.DivisionDashboardConfig.school_level || 'all';
                    window.location.href = buildDashboardUrl(type, schoolLevel);
                } else {
                    hideLoading();
                    showNotification('Error updating filter: ' + response.message, 'error');
                    if ($btn.length) $btn.prop('disabled', false);
                }
            },
            error: function() {
                hideLoading();
                showNotification('Error connecting to server. Please try again.', 'error');
                if ($btn.length) $btn.prop('disabled', false);
            }
        });
    });

    // Search functionality
    let searchTimeout;
    $('#schoolSearch').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        searchTimeout = setTimeout(function() {
            filterSchools(searchTerm);
        }, 300);
    });

    $('#clearSearch').click(function() {
        $('#schoolSearch').val('');
        filterSchools('');
    });

    function filterSchools(searchTerm) {
        if (!selectedDistrict) return;
        
        if (!searchTerm || searchTerm.trim() === '') {
            displaySchools(allSchools);
            updateSubmissionStats(allSchools);
            $('#noSearchResults').addClass('d-none');
            $('#submissionText').text('Submission Progress:');
            return;
        }
        
        const searchLower = searchTerm.toLowerCase().trim();
        const filtered = allSchools.filter(school => {
            const nameMatch = school.name && school.name.toLowerCase().includes(searchLower);
            const codeMatch = school.code && school.code.toLowerCase().includes(searchLower);
            const idMatch = school.id && school.id.toString().includes(searchLower);
            return nameMatch || codeMatch || idMatch;
        });
        
        displaySchools(filtered);
        updateSubmissionStats(filtered);
        
        if (filtered.length === 0) {
            $('#noSearchResults').removeClass('d-none');
            $('#noSchoolsMessage').addClass('d-none');
        } else {
            $('#noSearchResults').addClass('d-none');
            $('#noSchoolsMessage').addClass('d-none');
        }
        
        const total = allSchools.length;
        const filteredCount = filtered.length;
        if (filteredCount < total) {
            $('#submissionText').text(`Showing ${filteredCount} of ${total} schools:`);
        } else {
            $('#submissionText').text('Submission Progress:');
        }
    }
    
    // Handle school level dropdown selection
    $('.dropdown-item[data-level]').on('click', function(e) {
        e.preventDefault();
        
        const level = $(this).data('level');
        const levelText = $(this).text().trim();
        const assessmentType = window.DivisionDashboardConfig.assessment_type;
        
        if ($(this).hasClass('active')) {
            return;
        }
        
        // Update dropdown button text with icon and selected option
        const selectedIcon = $(this).find('i').clone();
        const button = $('#schoolLevelDropdown');
        button.html('').append(selectedIcon).append(' ' + levelText);
        
        showLoading();
        
        $('.btn').prop('disabled', true);
        
        // Make AJAX request to update filter
        $.ajax({
            url: window.DivisionDashboardConfig.urls.set_school_level,
            method: 'POST',
            data: { 
                school_level: level,
                assessment_type: assessmentType 
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = buildDashboardUrl(assessmentType, level);
                } else {
                    hideLoading();
                    showNotification('Error updating filter: ' + response.message, 'error');
                    $('.btn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showNotification('Error connecting to server. Please try again.', 'error');
                console.error('School level update error:', error);
                $('.btn').prop('disabled', false);
            }
        });
    });
    
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
    
    // Table visibility function
    function updateTableVisibility(schoolLevel) {
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        const shsTable = document.getElementById('shsTable');
        
        if (!elemTable || !secTable) return;
        
        // Determine which table to show based on school level
        switch(schoolLevel) {
            case 'secondary':
            case 'integrated_secondary':
                elemTable.classList.add('d-none');
                secTable.classList.remove('d-none');
                if (shsTable) shsTable.classList.add('d-none');
                break;

            case 'shs_only':
                // If a dedicated SHS table exists in the view, show it; otherwise fall back to secondary table
                if (shsTable) {
                    if (elemTable) elemTable.classList.add('d-none');
                    if (secTable) secTable.classList.add('d-none');
                    shsTable.classList.remove('d-none');
                } else {
                    elemTable.classList.add('d-none');
                    secTable.classList.remove('d-none');
                }
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

    // Update table visibility on page load
    var currentLevel = window.DivisionDashboardConfig.school_level || 'all';
    updateTableVisibility(currentLevel);

    
    const elemTable = document.getElementById('elementaryTable');
    const secTable = document.getElementById('secondaryTable');
    const shsTable = document.getElementById('shsTable');
    const btnPrint = document.getElementById('btnPrint');

    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            const win = window.open('', '_blank');
            const isElemVisible = elemTable && !elemTable.classList.contains('d-none');
            const isShsVisible = shsTable && !shsTable.classList.contains('d-none');
            const tableHtml = isElemVisible ? elemTable.outerHTML : (isShsVisible ? shsTable.outerHTML : secTable.outerHTML);
            const assessmentType = window.DivisionDashboardConfig.assessment_type_display || '';
            let schoolLevelDisplay = window.DivisionDashboardConfig.school_level_display || '';
            const reportDate = new Date().toLocaleDateString();

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
                const _docName = (window.DivisionDashboardConfig && (window.DivisionDashboardConfig.user_name || window.DivisionDashboardConfig.school_name || window.DivisionDashboardConfig.user)) || '';
                const _docTitle = _docName ? (_docName + ' Nutritional Status Report') : 'Nutritional Status Report';
                const _docTitleEsc = String(_docTitle).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + _docTitleEsc + '</title>' +
                    `
                    <style>
                        /* Ask browsers to preserve background colors when printing */
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
                    ` +
                    '</head><body>');
            function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

            function getQueryParam(name) { const params = new URLSearchParams(window.location.search); return params.get(name) || ''; }
            function findSchoolName() {
                if (window.DivisionDashboardConfig && window.DivisionDashboardConfig.school_name) return window.DivisionDashboardConfig.school_name;
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
                return getQueryParam('school_name') || getQueryParam('school') || '';
            }
            function findSchoolId() {
                if (window.DivisionDashboardConfig && window.DivisionDashboardConfig.school_id) return window.DivisionDashboardConfig.school_id;
                const input = document.querySelector('input[name="school_id"]');
                if (input && input.value) return input.value;
                return getQueryParam('school_id') || getQueryParam('school') || '';
            }

            const uName = (window.DivisionDashboardConfig && (window.DivisionDashboardConfig.user_name || window.DivisionDashboardConfig.user)) || '';
            const sName = findSchoolName();
            const sId = findSchoolId();
            const titlePrefix = uName || sName ? (escHtml(uName || '') + (uName && sName ? '/' + escHtml(sName) : (!uName ? escHtml(sName) : ''))) : '';
            const headerTitle = titlePrefix ? (titlePrefix + ' Nutritional Status Report - ' + escHtml(assessmentType)) : ('Nutritional Status Report - ' + escHtml(sName || ''));
            const idSuffix = sId ? (' | ID: ' + escHtml(sId)) : '';
                // Provide a default display label for SHS if not already set
                if (!schoolLevelDisplay) {
                    switch(window.DivisionDashboardConfig.school_level) {
                        case 'shs_only': schoolLevelDisplay = 'Senior High School (11-12)'; break;
                        case 'secondary': schoolLevelDisplay = 'Secondary Schools Only'; break;
                        case 'elementary': schoolLevelDisplay = 'Elementary Schools Only'; break;
                        default: schoolLevelDisplay = 'All Schools';
                    }
                }

                win.document.write('<div class="print-header"><h3>' + headerTitle + idSuffix + '</h3><p><strong>Assessment Type:</strong> ' + escHtml(assessmentType) + ' | <strong>School Level:</strong> ' + escHtml(schoolLevelDisplay) + ' | <strong>School ID:</strong> ' + escHtml(sId || '') + '</p></div>');
            try {
                const clone = (new DOMParser()).parseFromString(tableHtml, 'text/html').body.firstChild.cloneNode(true);
                const hidden = document.createElement('div');
                hidden.style.position = 'fixed'; hidden.style.left = '-9999px'; hidden.style.top = '-9999px'; hidden.style.visibility = 'hidden';
                document.body.appendChild(hidden);
                hidden.appendChild(clone);

                try {
                    // Inline computed background (including images/gradients), color and border for all elements
                    const elems = clone.querySelectorAll('*');
                    elems.forEach(el => {
                        try {
                            const cs = window.getComputedStyle(el);
                            if (!cs) return;
                            const bg = cs.backgroundColor;
                            const bgImg = cs.backgroundImage;
                            const isBgVisible = bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent';
                            if (isBgVisible) {
                                el.style.backgroundColor = bg;
                            }
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
    
    // District/schools UI
    $('#overallSummaryCard').click(function() { $('#schoolsBox').toggleClass('d-none'); });
    $('#closeSchoolsBox').click(function() { $('#schoolsBox').addClass('d-none'); });
    $(document).on('click', '.district-card', function() { const districtName = $(this).data('district'); selectDistrict(districtName); });

    // Back button handler
    $('#backToDistricts').off('click').on('click', function() {
        currentView = 'districts';
        selectedDistrict = null;
        $('#boxTitle').text('All Districts');
        $('#districtsView').removeClass('d-none');
        $('#schoolsView').addClass('d-none');
        $('#schoolSearch').val('');
        allSchools = [];
    });

    function selectDistrict(districtName) {
        selectedDistrict = districtName;
        currentView = 'schools';
        $('#boxTitle').text('Schools in ' + districtName);
        $('#districtsView').addClass('d-none');
        $('#schoolsView').removeClass('d-none');
        $('#districtSchoolsTitle').text('Schools in ' + districtName);
        $('#schoolSearch').val('');
        allSchools = [];

        // Show loading state
        const list = $('#schoolsList');
        list.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        // Build the URL explicitly
        const url = window.DivisionDashboardConfig.urls.get_district_schools;

        // Fetch schools for this district via AJAX
        $.ajax({
            url: url,
            method: 'GET',
            data: { district: districtName },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (response.success) {
                    allSchools = response.schools || [];
                    displaySchools(allSchools);
                    updateSubmissionStats(allSchools);
                } else {
                    list.html('<div class="text-center py-4 text-danger">Error loading schools: ' + (response.message || 'Unknown error') + '</div>');
                }
            },
            error: function(xhr, status, error) {
            list.html('<div class="text-center py-4 text-danger">Error loading schools. Please try again.</div>');
            showNotification('Failed to load schools', 'error');
            }
        });
    }
        

    function displaySchools(schools) {
        const list = $('#schoolsList');
        list.empty();
        
        $('#noSchoolsMessage').addClass('d-none');
        $('#noSearchResults').addClass('d-none');
        
        if (!schools || !Array.isArray(schools) || schools.length === 0) {
            $('#noSchoolsMessage').removeClass('d-none');
            return;
        }
        
        schools.forEach(function(school) {
            const hasSubmitted = checkSchoolSubmissionStatus(school);
            const statusClass = hasSubmitted ? 'submission-submitted' : 'submission-pending';
            const statusText = hasSubmitted ? 'Submitted' : 'Pending';
            const icon = hasSubmitted ? 'check' : 'circle';
            const iconColor = hasSubmitted ? 'text-success' : 'text-muted';
            const schoolName = school && school.name ? school.name : 'Unknown School';
            const schoolCode = school && school.code ? school.code : '';
            const schoolId = school && school.id ? school.id : '';
            
            const item = `
                <a href="#" class="list-group-item list-group-item-action school-item" 
                   data-school-id="${schoolId}" 
                   data-school-name="${schoolName}"
                   data-school-code="${schoolCode}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <div class="rounded-circle ${statusClass} p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fas fa-${icon} fa-xs ${iconColor}"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium">${schoolName}</div>
                            <div>
                                ${schoolCode ? `<small class="text-muted">School ID: ${schoolCode}</small>` : ''}
                                ${schoolId ? `<small class="text-muted ms-2">(Ref: ${schoolId})</small>` : ''}
                            </div>
                        </div>
                        <div>
                            <span class="badge ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                </a>
            `;
            list.append(item);
        });
        
        $('.school-item').off('click').on('click', function(e) { 
            e.preventDefault(); 
            const schoolId = $(this).data('school-id');
            const schoolName = $(this).data('school-name');
            if (schoolId) {
                showSchoolDetails(schoolId, schoolName);
            } else {
                alert('Invalid school ID. Cannot load details.');
            }
        });
    }
    
    function checkSchoolSubmissionStatus(school) {
        if (!school) return false;
        
        const assessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';

        if (assessmentType === 'baseline' && school.has_baseline !== undefined) {
            return isTruthy(school.has_baseline);
        }
        if (assessmentType === 'midline' && school.has_midline !== undefined) {
            return isTruthy(school.has_midline);
        }
        if (assessmentType === 'endline' && school.has_endline !== undefined) {
            return isTruthy(school.has_endline);
        }

        if (school.assessments) {
            if (assessmentType === 'baseline' && school.assessments.has_baseline !== undefined) {
                return isTruthy(school.assessments.has_baseline);
            }
            if (assessmentType === 'midline' && school.assessments.has_midline !== undefined) {
                return isTruthy(school.assessments.has_midline);
            }
            if (assessmentType === 'endline' && school.assessments.has_endline !== undefined) {
                return isTruthy(school.assessments.has_endline);
            }
        }

        if (school.submitted_assessments) {
            if (Array.isArray(school.submitted_assessments)) {
                return school.submitted_assessments.includes(assessmentType);
            }
            if (typeof school.submitted_assessments === 'object') {
                return isTruthy(school.submitted_assessments[assessmentType]);
            }
        }

        if (assessmentType === 'baseline' && school.has_submitted !== undefined) {
            return isTruthy(school.has_submitted);
        }

        if ((assessmentType === 'midline' || assessmentType === 'endline') && school.has_submitted !== undefined) {
            return false;
        }
        
        return false;
    }

    function isTruthy(value) {
        if (value === true || value === 1 || value === '1' || value === 'true') {
            return true;
        }
        return false;
    }

    function updateSubmissionStats(schools) {
        if (!schools || !Array.isArray(schools)) {
            $('#submissionCount').text('0/0 schools submitted');
            $('#submissionProgressBar').css('width', '0%');
            return;
        }
        
        const submitted = schools.filter(s => checkSchoolSubmissionStatus(s)).length; 
        const total = schools.length; 
        const percentage = total > 0 ? Math.round((submitted / total) * 100) : 0; 
        
        $('#submissionCount').text(submitted + '/' + total + ' schools submitted'); 
        $('#submissionProgressBar').css('width', percentage + '%'); 
        
        const searchTerm = $('#schoolSearch').val();
        if (searchTerm && searchTerm.trim() !== '' && selectedDistrict) {
            if (schools.length < allSchools.length) {
                $('#submissionText').text(`Showing ${schools.length} of ${allSchools.length} schools:`);
                return;
            }
        }
        $('#submissionText').text('Submission Progress:');
    }
    
    function showSchoolDetails(schoolId, schoolName) {
        if (!schoolId) {
            $('#schoolModalBody').html(`
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Invalid school ID. Please try again.
                </div>
            `);
            const modal = new bootstrap.Modal(document.getElementById('schoolModal'));
            modal.show();
            return;
        }
        
        $('#schoolModalBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading details for ${schoolName}...</p>
            </div>
        `);
        
        const modal = new bootstrap.Modal(document.getElementById('schoolModal'));
        modal.show();
        
        $.ajax({
            url: window.DivisionDashboardConfig.urls.get_school_details + encodeURIComponent(schoolId),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const details = response.data;
                    
                    const assessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';
                    let assessmentStatus = 'Not Submitted';
                    let statusClass = 'bg-secondary';
                    
                    if (details.assessments) {
                        if (assessmentType === 'baseline' && details.assessments.has_baseline) {
                            assessmentStatus = 'Submitted';
                            statusClass = 'bg-success';
                        } else if (assessmentType === 'midline' && details.assessments.has_midline) {
                            assessmentStatus = 'Submitted';
                            statusClass = 'bg-success';
                        } else if (assessmentType === 'endline' && details.assessments.has_endline) {
                            assessmentStatus = 'Submitted';
                            statusClass = 'bg-success';
                        }
                    }
                    
                    let detailsHtml = `
                        <div class="school-details">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="fw-bold mb-0">${details.name || schoolName}</h6>
                                <span class="badge ${statusClass}">${assessmentStatus}</span>
                            </div>
                            
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>School ID:</strong></td>
                                    <td>${details.code || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>DB Record ID:</strong></td>
                                    <td>${details.id || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>District:</strong></td>
                                    <td>${details.district || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>School Level:</strong></td>
                                    <td>${details.level || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>School Type:</strong></td>
                                    <td>${details.type || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Region:</strong></td>
                                    <td>${details.region || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Division:</strong></td>
                                    <td>${details.division || 'N/A'}</td>
                                </tr>
                            </table>
                            
                            <hr>
                            
                            <h6 class="fw-bold mb-3">Contact Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Contact Person:</strong></td>
                                    <td>${details.contact_person || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Contact Number:</strong></td>
                                    <td>${details.contact_number || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>${details.email || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Address:</strong></td>
                                    <td>${details.address || 'N/A'}</td>
                                </tr>
                            </table>
                            
                            <hr>
                            
                            <h6 class="fw-bold mb-3">Assessment Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Baseline Assessment:</strong></td>
                                    <td>${details.assessments && details.assessments.has_baseline ? 
                                        '<span class="badge bg-success">Submitted</span>' : 
                                        '<span class="badge bg-secondary">Not Submitted</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Midline Assessment:</strong></td>
                                    <td>${details.assessments && details.assessments.has_midline ? 
                                        '<span class="badge bg-success">Submitted</span>' : 
                                        '<span class="badge bg-secondary">Not Submitted</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Endline Assessment:</strong></td>
                                    <td>${details.assessments && details.assessments.has_endline ? 
                                        '<span class="badge bg-success">Submitted</span>' : 
                                        '<span class="badge bg-secondary">Not Submitted</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Assessment Date:</strong></td>
                                    <td>${details.assessments && details.assessments.last_assessment_date ? 
                                        details.assessments.last_assessment_date : 'N/A'}</td>
                                </tr>
                            </table>
                        </div>
                    `;
                    $('#schoolModalBody').html(detailsHtml);
                } else {
                    $('#schoolModalBody').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Unable to load school details. Please try again.
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#schoolModalBody').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading school details. Please try again.
                        <br><small class="text-muted">${error}</small>
                    </div>
                `);
            }
        });
    }
});