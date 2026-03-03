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
    
    // Function to initialize integrated submenu buttons
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
                // Default to elementary for integrated
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
            
            const printCss = '<style>' +
                '@page{size:A4 landscape;margin:8mm;} body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:4px;color:#000;font-size:8px;line-height:1.2;} ' +
                'table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8px;margin:0;} th,td{border:0.5px solid #dee2e6;padding:2px;word-wrap:break-word;line-height:1.1;} ' +
                '.no-print{display:none!important;} h3{font-size:10px;margin:0 0 2px 0;font-weight:bold;} p{font-size:7px;margin:0 0 4px 0;} </style>';

            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title>' +
                '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
                printCss +
                '</head><body>');
            win.document.write('<h3>Nutritional Status Report - ' + assessmentType + ' Assessment</h3>');
            win.document.write('<p>Report Date: ' + reportDate + ' | School Type: ' + schoolTypeDisplay + '</p>');
            win.document.write(tableHtml);
            win.document.write('<script>window.onload=function(){ setTimeout(function(){ window.print(); window.onafterprint=function(){ window.close(); } },200); }<\/script>');
            win.document.write('</body></html>');
            win.document.close();
        });
    }
});