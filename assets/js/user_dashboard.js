/* JS extracted from application/views/user_dashboard.php
   Uses runtime config object provided by the view: window.UserDashboardConfig
*/
$(document).ready(function() {
    $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
    
    // Get display mode from config
    var displayMode = window.UserDashboardConfig.display_mode || 'normal';
    var schoolLevel = window.UserDashboardConfig.school_level || 'all';
    
    console.log('Display Mode:', displayMode); // For debugging
    console.log('School Level:', schoolLevel); // For debugging
    
    // Set initial table visibility based on display mode
    setInitialTableVisibility();
    
    // Initialize integrated submenu buttons
    initializeIntegratedSubMenu();
    
    // Handle filter buttons visibility based on display mode
    handleButtonVisibility();
    
    // Only initialize main filter buttons if in normal mode
    if (displayMode === 'normal') {
        initializeFilterButtons();
    }
    
    // Function to handle button visibility
    function handleButtonVisibility() {
        // Hide the main filter buttons group if not in normal mode
        if (displayMode !== 'normal') {
            $('.btn-group.me-2:not(#integratedSubMenu)').hide();
        } else {
            $('.btn-group.me-2:not(#integratedSubMenu)').show();
        }
        
        // Show integrated submenu if in integrated mode
        if (displayMode === 'integrated') {
            $('#integratedSubMenu').show();
        } else {
            $('#integratedSubMenu').hide();
        }
    }
    
    // Function to initialize integrated submenu buttons
    function initializeIntegratedSubMenu() {
        // Integrated sub-menu buttons
        $('#btnIntegratedElementary').click(function(e) {
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
        
        $('#btnIntegratedSecondary').click(function(e) {
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
        if (displayMode === 'integrated') {
            if (schoolLevel === 'integrated_elementary') {
                $('#btnIntegratedElementary').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnIntegratedSecondary').removeClass('btn-primary').addClass('btn-outline-primary');
            } else if (schoolLevel === 'integrated_secondary') {
                $('#btnIntegratedSecondary').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnIntegratedElementary').removeClass('btn-primary').addClass('btn-outline-primary');
            } else {
                // Default to elementary
                $('#btnIntegratedElementary').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnIntegratedSecondary').removeClass('btn-primary').addClass('btn-outline-primary');
            }
        }
    }
    
    // Function to initialize filter buttons
    function initializeFilterButtons() {
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
        
        // Integrated button shows sub-menu
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
        
        var currentLevel = window.UserDashboardConfig.school_level || 'all';
        if (currentLevel.startsWith('integrated')) {
            $('#integratedSubMenu').removeClass('d-none');
            $('#btnIntegrated').addClass('active');
            disableTableSwitching();
        } else {
            enableTableSwitching();
        }
        
        // Add click handlers for table switching
        addTableSwitchingHandlers();
    }
    
    function setInitialTableVisibility() {
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        const shsTable = document.getElementById('shsTable');
        
        // First, hide all tables
        if (elemTable) elemTable.classList.add('d-none');
        if (secTable) secTable.classList.add('d-none');
        if (shsTable) shsTable.classList.add('d-none');
        
        console.log('Setting initial visibility for mode:', displayMode);
        console.log('School level for visibility:', schoolLevel);
        
        // Show the appropriate table based on display mode
        if (displayMode === 'elementary_only') {
            console.log('Showing elementary table only');
            if (elemTable) {
                elemTable.classList.remove('d-none');
            }
        } else if (displayMode === 'secondary_only') {
            console.log('Showing secondary table only');
            if (secTable) {
                secTable.classList.remove('d-none');
            }
        } else if (displayMode === 'shs_only') {
            console.log('Showing SHS table only');
            if (shsTable) {
                shsTable.classList.remove('d-none');
            }
        } else if (displayMode === 'integrated') {
            // For integrated, check the school level to determine which table to show
            console.log('Integrated mode, school level:', schoolLevel);
            if (schoolLevel === 'integrated_secondary') {
                if (secTable) {
                    secTable.classList.remove('d-none');
                }
                // Update button states
                $('#btnIntegratedSecondary').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnIntegratedElementary').removeClass('btn-primary').addClass('btn-outline-primary');
            } else {
                // Default to elementary for integrated and integrated_elementary
                if (elemTable) {
                    elemTable.classList.remove('d-none');
                }
                // Update button states
                $('#btnIntegratedElementary').removeClass('btn-outline-primary').addClass('btn-primary');
                $('#btnIntegratedSecondary').removeClass('btn-primary').addClass('btn-outline-primary');
            }
        } else {
            // Normal mode - check school level to determine which table to show
            console.log('Normal mode, school level:', schoolLevel);
            
            // Handle Stand Alone SHS in normal mode (should not happen, but just in case)
            if (schoolLevel === 'Stand Alone SHS' || schoolLevel === 'standalone_shs' || schoolLevel === 'shs_only') {
                console.log('Showing SHS table (fallback)');
                if (shsTable) {
                    shsTable.classList.remove('d-none');
                }
            } else if (schoolLevel === 'secondary') {
                if (secTable) {
                    secTable.classList.remove('d-none');
                }
            } else {
                // Default to elementary for all, elementary, and other cases
                if (elemTable) {
                    elemTable.classList.remove('d-none');
                }
            }
        }
    }
    
    // Table switching functions
    function enableTableSwitching() {
        const btnElem = document.getElementById('btnElementary');
        const btnSec = document.getElementById('btnSecondary');
        
        if (btnElem && btnSec) {
            btnElem.style.pointerEvents = 'auto';
            btnElem.style.opacity = '1';
            btnSec.style.pointerEvents = 'auto';
            btnSec.style.opacity = '1';
        }
    }
    
    function disableTableSwitching() {
        const btnElem = document.getElementById('btnElementary');
        const btnSec = document.getElementById('btnSecondary');
        
        if (btnElem && btnSec) {
            btnElem.style.pointerEvents = 'none';
            btnElem.style.opacity = '0.5';
            btnSec.style.pointerEvents = 'none';
            btnSec.style.opacity = '0.5';
        }
    }
    
    function addTableSwitchingHandlers() {
        const btnElem = document.getElementById('btnElementary');
        const btnSec = document.getElementById('btnSecondary');
        const elemTable = document.getElementById('elementaryTable');
        const secTable = document.getElementById('secondaryTable');
        
        if (btnElem && btnSec && elemTable && secTable) {
            // Remove any existing event listeners by cloning and replacing
            const newBtnElem = btnElem.cloneNode(true);
            const newBtnSec = btnSec.cloneNode(true);
            btnElem.parentNode.replaceChild(newBtnElem, btnElem);
            btnSec.parentNode.replaceChild(newBtnSec, btnSec);
            
            // Add new event listeners
            newBtnElem.addEventListener('click', function(e) {
                e.preventDefault();
                newBtnElem.classList.add('active');
                newBtnSec.classList.remove('active');
                elemTable.classList.remove('d-none');
                secTable.classList.add('d-none');
                $('#shsTable').addClass('d-none');
            });

            newBtnSec.addEventListener('click', function(e) {
                e.preventDefault();
                newBtnSec.classList.add('active');
                newBtnElem.classList.remove('active');
                secTable.classList.remove('d-none');
                elemTable.classList.add('d-none');
                $('#shsTable').addClass('d-none');
            });
        }
    }
    
    function switchAssessmentType(type) {
        var activeBtn;
        if (type === 'baseline') {
            activeBtn = $('#switchToBaseline');
            $('#switchToMidline').prop('disabled', true);
            $('#switchToEndline').prop('disabled', true);
        } else if (type === 'midline') {
            activeBtn = $('#switchToMidline');
            $('#switchToBaseline').prop('disabled', true);
            $('#switchToEndline').prop('disabled', true);
        } else {
            activeBtn = $('#switchToEndline');
            $('#switchToBaseline').prop('disabled', true);
            $('#switchToMidline').prop('disabled', true);
        }
        
        var originalHtml = activeBtn.html();
        activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
        activeBtn.prop('disabled', true);
        
        $.ajax({
            url: window.UserDashboardConfig.urls.set_assessment_type,
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var url = window.UserDashboardConfig.urls.base;
                    var schoolLevel = window.UserDashboardConfig.school_level || '';
                    if (schoolLevel && schoolLevel !== 'all') {
                        url += '?school_level=' + encodeURIComponent(schoolLevel);
                    }
                    window.location.href = url;
                } else {
                    alert('Error: ' + response.message);
                    activeBtn.html(originalHtml);
                    activeBtn.prop('disabled', false);
                    $('.assessment-switcher .btn').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                alert('Error switching assessment type. Please try again.');
                activeBtn.html(originalHtml);
                activeBtn.prop('disabled', false);
                $('.assessment-switcher .btn').prop('disabled', false);
            }
        });
    }

    function setSchoolLevelFilter(level) {
        // Show loading indicators
        $('#btnElementary, #btnSecondary, #btnIntegrated, #btnIntegratedElementary, #btnIntegratedSecondary').each(function() {
            var $btn = $(this);
            if ($btn.length) {
                var originalText = $btn.html();
                $btn.data('original-text', originalText);
                $btn.html('<i class="fas fa-spinner fa-spin"></i>');
            }
        });

        $('#btnElementary, #btnSecondary, #btnIntegrated, #btnIntegratedElementary, #btnIntegratedSecondary').prop('disabled', true);
        
        $.ajax({
            url: window.UserDashboardConfig.urls.set_school_level,
            method: 'POST',
            data: { school_level: level },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
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
        $('#btnIntegratedElementary').html('<i class="fas fa-child me-1"></i> Elementary <span class="badge bg-info ms-1">K-6</span>');
        $('#btnIntegratedSecondary').html('<i class="fas fa-graduation-cap me-1"></i> Secondary <span class="badge bg-info ms-1">7-12</span>');
        
        $('#btnElementary, #btnSecondary, #btnIntegrated, #btnIntegratedElementary, #btnIntegratedSecondary').prop('disabled', false);
    }
    
    // Table view handling based on display mode
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
                // For integrated, check which table is visible
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
                // Normal mode - check which table is visible
                const elemTable = document.getElementById('elementaryTable');
                const secTable = document.getElementById('secondaryTable');
                const shsTable = document.getElementById('shsTable');
                
                if (shsTable && !shsTable.classList.contains('d-none')) {
                    tableHtml = shsTable.outerHTML;
                } else if (secTable && !secTable.classList.contains('d-none')) {
                    tableHtml = secTable.outerHTML;
                } else if (elemTable && !elemTable.classList.contains('d-none')) {
                    tableHtml = elemTable.outerHTML;
                } else {
                    tableHtml = elemTable ? elemTable.outerHTML : secTable.outerHTML;
                }
            }
            
            const assessmentType = window.UserDashboardConfig.assessment_type_display || '';
            const schoolLevel = window.UserDashboardConfig.school_level || '';
            const reportDate = new Date().toLocaleDateString();
            
            let schoolLevelDisplay = 'All Schools';
            
            // Handle different formats of school level
            const levelLower = String(schoolLevel).toLowerCase();
            
            if (levelLower === 'all') {
                schoolLevelDisplay = 'All Schools';
            } else if (levelLower === 'elementary') {
                schoolLevelDisplay = 'Elementary Schools';
            } else if (levelLower === 'secondary') {
                schoolLevelDisplay = 'Secondary Schools';
            } else if (levelLower === 'standalone_shs' || levelLower === 'stand alone shs' || levelLower === 'shs_only' || levelLower === 'shs') {
                schoolLevelDisplay = 'Stand Alone SHS';
            } else if (levelLower === 'integrated') {
                schoolLevelDisplay = 'Integrated Schools';
            } else if (levelLower === 'integrated_elementary') {
                schoolLevelDisplay = 'Integrated Schools (Elementary Only)';
            } else if (levelLower === 'integrated_secondary') {
                schoolLevelDisplay = 'Integrated Schools (Secondary Only)';
            } else {
                schoolLevelDisplay = schoolLevel; // Use original value
            }
            
            const printCss = '<style>' +
                '@page{size:A4 landscape;margin:8mm;} body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:4px;color:#000;font-size:8px;line-height:1.2;} ' +
                'table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8px;margin:0;} th,td{border:0.5px solid #dee2e6;padding:2px;word-wrap:break-word;line-height:1.1;} ' +
                '.no-print{display:none!important;} h3{font-size:10px;margin:0 0 2px 0;font-weight:bold;} p{font-size:7px;margin:0 0 4px 0;} </style>';

            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title>' +
                '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
                printCss +
                '</head><body>');
            win.document.write('<h3>Nutritional Status Report - ' + assessmentType + ' Assessment</h3>');
            win.document.write('<p>Report Date: ' + reportDate + ' | School Level: ' + schoolLevelDisplay + '</p>');
            win.document.write(tableHtml);
            win.document.write('<script>window.onload=function(){ setTimeout(function(){ window.print(); window.onafterprint=function(){ window.close(); } },200); }<\/script>');
            win.document.write('</body></html>');
            win.document.close();
        });
    }
});