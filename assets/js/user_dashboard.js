$(document).ready(function() {
    $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
    
    // Check if config exists
    if (!window.user_dashboard_controllerConfig) {
        console.error('Config object is undefined');
        return;
    }
    
    // Get display mode and school level from config
    var displayMode = window.user_dashboard_controllerConfig.display_mode || 'normal';
    var schoolLevel = window.user_dashboard_controllerConfig.school_level || 'all';
    
    console.log('Display mode:', displayMode);
    console.log('School level:', schoolLevel);
    
    // Set initial table visibility based on display mode
    setInitialTableVisibility();
    
    // ONLY initialize integrated submenu for integrated schools
    if (displayMode === 'integrated') {
        initializeIntegratedSubMenu();
    }

    function initializeIntegratedSubMenu() {
        console.log('Initializing integrated submenu');
        
        // Integrated sub-menu buttons
        $('#btnIntegratedElementary').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Update button styles
            $('#btnIntegratedElementary').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#btnIntegratedSecondary').removeClass('btn-primary').addClass('btn-outline-primary');
            
            // Show elementary table, hide secondary table
            $('#elementaryTable').removeClass('d-none');
            $('#secondaryTable').addClass('d-none');
            $('#shsTable').addClass('d-none');
            
            // Update the school level filter
            setSchoolLevelFilter('integrated_elementary');
        });
        
        $('#btnIntegratedSecondary').off('click').on('click', function(e) {
            e.preventDefault();
            
            // Update button styles
            $('#btnIntegratedSecondary').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#btnIntegratedElementary').removeClass('btn-primary').addClass('btn-outline-primary');
            
            // Show secondary table, hide elementary table
            $('#secondaryTable').removeClass('d-none');
            $('#elementaryTable').addClass('d-none');
            $('#shsTable').addClass('d-none');
            
            // Update the school level filter
            setSchoolLevelFilter('integrated_secondary');
        });
        
        // Set initial state of integrated submenu based on school level
        if (schoolLevel === 'integrated_secondary') {
            $('#btnIntegratedSecondary').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#btnIntegratedElementary').removeClass('btn-primary').addClass('btn-outline-primary');
        } else {
            // Default to elementary for integrated and integrated_elementary
            $('#btnIntegratedElementary').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#btnIntegratedSecondary').removeClass('btn-primary').addClass('btn-outline-primary');
        }
    }
    
    function setInitialTableVisibility() {
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        const shsTable = document.getElementById('shsTable');
        
        // First, hide all tables
        if (elemTable) elemTable.classList.add('d-none');
        if (secTable) secTable.classList.add('d-none');
        if (shsTable) shsTable.classList.add('d-none');
        
        console.log('Setting visibility for mode:', displayMode);
        
        // Show the appropriate table based on display_mode ONLY
        if (displayMode === 'elementary_only') {
            console.log('Elementary school - showing elementary table');
            if (elemTable) elemTable.classList.remove('d-none');
        } 
        else if (displayMode === 'secondary_only') {
            console.log('Secondary school - showing secondary table');
            if (secTable) secTable.classList.remove('d-none');
        } 
        else if (displayMode === 'shs_only') {
            console.log('SHS school - showing SHS table');
            if (shsTable) shsTable.classList.remove('d-none');
        } 
        else if (displayMode === 'integrated') {
            console.log('Integrated school - showing based on integrated filter');
            if (schoolLevel === 'integrated_secondary') {
                if (secTable) secTable.classList.remove('d-none');
            } else {
                if (elemTable) elemTable.classList.remove('d-none');
            }
        } 
        else {
            console.log('Unknown display mode - showing elementary as fallback');
            if (elemTable) elemTable.classList.remove('d-none');
        }
    }
    
    function switchAssessmentType(type) {
        var activeBtn = type === 'baseline' ? $('#switchToBaseline') : 
                    (type === 'midline' ? $('#switchToMidline') : $('#switchToEndline'));

        activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
        $('.assessment-switcher .btn').prop('disabled', true);

        var currentSchoolLevel = window.user_dashboard_controllerConfig.school_level || 'all';

        var url = window.user_dashboard_controllerConfig.urls.base + '?assessment_type=' + type;

        if (currentSchoolLevel && currentSchoolLevel !== 'all') {
            url += '&school_level=' + encodeURIComponent(currentSchoolLevel);
        }
        
        window.location.href = url;
    }

    function setSchoolLevelFilter(level) {
        // Show loading indicators
        $('#btnIntegratedElementary, #btnIntegratedSecondary').each(function() {
            var $btn = $(this);
            if ($btn.length) {
                $btn.html('<i class="fas fa-spinner fa-spin"></i>');
            }
        });

        $('#btnIntegratedElementary, #btnIntegratedSecondary').prop('disabled', true);
        
        $.ajax({
            url: window.user_dashboard_controllerConfig.urls.set_school_level,
            method: 'POST',
            data: { school_level: level },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + response.message);
                    resetIntegratedButtonStates();
                }
            },
            error: function() {
                alert('Error applying filter. Please try again.');
                resetIntegratedButtonStates();
            }
        });
    }
    
    function resetIntegratedButtonStates() {
        $('#btnIntegratedElementary').html('<i class="fas fa-child me-1"></i> Elementary <span class="badge bg-info ms-1">K-6</span>');
        $('#btnIntegratedSecondary').html('<i class="fas fa-graduation-cap me-1"></i> Secondary <span class="badge bg-info ms-1">7-12</span>');
        
        $('#btnIntegratedElementary, #btnIntegratedSecondary').prop('disabled', false);
    }
    
    // Print functionality
    const btnPrint = document.getElementById('btnPrint');
    
    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            const win = window.open('', '_blank');
            
            let tableHtml;
            
            // Determine which table to print based on display mode
            if (displayMode === 'shs_only') {
                tableHtml = document.getElementById('shsTable').outerHTML;
            } else if (displayMode === 'elementary_only') {
                tableHtml = document.getElementById('elementaryTable').outerHTML;
            } else if (displayMode === 'secondary_only') {
                tableHtml = document.getElementById('secondaryTable').outerHTML;
            } else if (displayMode === 'integrated') {
                // For integrated, print the currently visible table
                const elemTable = document.getElementById('elementaryTable');
                const secTable = document.getElementById('secondaryTable');
                
                if (elemTable && !elemTable.classList.contains('d-none')) {
                    tableHtml = elemTable.outerHTML;
                } else if (secTable && !secTable.classList.contains('d-none')) {
                    tableHtml = secTable.outerHTML;
                } else {
                    tableHtml = elemTable ? elemTable.outerHTML : secTable.outerHTML;
                }
            } else {
                // Default to elementary
                tableHtml = document.getElementById('elementaryTable').outerHTML;
            }
            
            const assessmentType = window.user_dashboard_controllerConfig.assessment_type_display || '';
            const reportDate = new Date().toLocaleDateString();
            
            let schoolTypeDisplay = 'All Schools';
            if (displayMode === 'elementary_only') schoolTypeDisplay = 'Elementary School';
            else if (displayMode === 'secondary_only') schoolTypeDisplay = 'Secondary School';
            else if (displayMode === 'shs_only') schoolTypeDisplay = 'Stand Alone SHS';
            else if (displayMode === 'integrated') schoolTypeDisplay = 'Integrated School';
            
            const printCss = `
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
            `;

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
                } catch (e) {
                    return tableEl;
                }
            }

            try {
                // determine document title using user or school name from config when possible
                const _docName = (window.user_dashboard_controllerConfig && (window.user_dashboard_controllerConfig.user_name || window.user_dashboard_controllerConfig.school_name || window.user_dashboard_controllerConfig.user)) || '';
                const _docTitle = _docName ? (_docName + ' Nutritional Status Report') : 'Nutritional Status Report';
                const _docTitleEsc = String(_docTitle).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + _docTitleEsc + '</title>' + printCss + '</head><body>');
                function escHtml(str) { return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

                function getQueryParam(name) { const params = new URLSearchParams(window.location.search); return params.get(name) || ''; }

                function findSchoolName() {
                    if (window.user_dashboard_controllerConfig && window.user_dashboard_controllerConfig.school_name) return window.user_dashboard_controllerConfig.school_name;
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
                    if (window.user_dashboard_controllerConfig && window.user_dashboard_controllerConfig.school_id) return window.user_dashboard_controllerConfig.school_id;
                    const input = document.querySelector('input[name="school_id"]');
                    if (input && input.value) return input.value;
                    return getQueryParam('school_id') || getQueryParam('school') || '';
                }

                const uName = (window.user_dashboard_controllerConfig && (window.user_dashboard_controllerConfig.user_name || window.user_dashboard_controllerConfig.user)) || '';
                const sName = findSchoolName();
                const sId = findSchoolId();
                const titlePrefix = uName || sName ? (escHtml(uName || '') + (uName && sName ? '/' + escHtml(sName) : (!uName ? escHtml(sName) : ''))) : '';
                const headerTitle = titlePrefix ? (titlePrefix + ' Nutritional Status Report - ' + escHtml(assessmentType)) : ('Nutritional Status Report - ' + escHtml(sName || ''));
                const idSuffix = sId ? (' | ID: ' + escHtml(sId)) : '';
                win.document.write('<div class="print-header"><h3>' + headerTitle + idSuffix + '</h3><p><strong>Assessment Type:</strong> ' + escHtml(assessmentType) + ' | <strong>School Level:</strong> ' + escHtml(schoolTypeDisplay) + ' | <strong>School ID:</strong> ' + escHtml(sId || '') + '</p></div>');
                try {
                    const clone = (new DOMParser()).parseFromString(tableHtml, 'text/html').body.firstChild.cloneNode(true);
                    // append clone to a hidden container in the current document so getComputedStyle works
                    const hidden = document.createElement('div');
                    hidden.style.position = 'fixed'; hidden.style.left = '-9999px'; hidden.style.top = '-9999px'; hidden.style.visibility = 'hidden';
                    document.body.appendChild(hidden);
                    hidden.appendChild(clone);

                    // inline computed background and color for header cells to improve print fidelity
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
                // Popup might be blocked or other error — fallback to printing current window
                console.error('Could not open print window, falling back to inline print', err);
                try { window.print(); } catch (e) { console.error(e); }
            }
        });
    }
});