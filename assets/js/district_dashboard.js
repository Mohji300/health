/* JS for district_dashboard — reads runtime config from window.DistrictDashboardConfig */
$(document).ready(function() {
    console.log('District dashboard JS loaded');
    
    $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
    
    function switchAssessmentType(type) {
        var activeBtn = type === 'baseline' ? $('#switchToBaseline') : (type === 'midline' ? $('#switchToMidline') : $('#switchToEndline'));
        var originalHtml = activeBtn.html();
        activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
        $('.assessment-switcher .btn').prop('disabled', true);
        var currentSchoolLevel = window.DistrictDashboardConfig.school_level || 'all';
        var url = window.DistrictDashboardConfig.urls.base + '?assessment_type=' + type;
        if (currentSchoolLevel && currentSchoolLevel !== 'all') url += '&school_level=' + encodeURIComponent(currentSchoolLevel);
        window.location.href = url;
    }
    
    // School level filtering
    $('#btnElementary').click(function() { $('#btnIntegrated').removeClass('active'); $('#integratedSubMenu').addClass('d-none'); enableTableSwitching(); setSchoolLevelFilter('elementary'); });
    $('#btnSecondary').click(function() { $('#btnIntegrated').removeClass('active'); $('#integratedSubMenu').addClass('d-none'); enableTableSwitching(); setSchoolLevelFilter('secondary'); });
    $('#btnIntegrated').click(function(e) { e.preventDefault(); $(this).toggleClass('active'); if ($(this).hasClass('active')) { disableTableSwitching(); $('#integratedSubMenu').removeClass('d-none'); setSchoolLevelFilter('integrated'); } else { enableTableSwitching(); $('#integratedSubMenu').addClass('d-none'); setSchoolLevelFilter('all'); } });
    $('#btnIntegratedElementary').click(function() { setSchoolLevelFilter('integrated_elementary'); });
    $('#btnIntegratedSecondary').click(function() { setSchoolLevelFilter('integrated_secondary'); });

    function enableTableSwitching() { $('#btnElementary, #btnSecondary').css({'pointerEvents': 'auto','opacity': '1'}); }
    function disableTableSwitching() { $('#btnElementary, #btnSecondary').css({'pointerEvents': 'none','opacity': '0.5'}); }

    function setSchoolLevelFilter(level) {
        $('#btnElementary, #btnSecondary, #btnIntegrated').html('<i class="fas fa-spinner fa-spin"></i>');
        $('#btnElementary, #btnSecondary, #btnIntegrated').prop('disabled', true);
        $.ajax({ url: window.DistrictDashboardConfig.urls.set_school_level, method: 'POST', data: { school_level: level, assessment_type: window.DistrictDashboardConfig.assessment_type }, dataType: 'json', success: function(response) {
            if (response.success) {
                var url = window.DistrictDashboardConfig.urls.base + '?assessment_type=' + encodeURIComponent(window.DistrictDashboardConfig.assessment_type);
                if (level !== 'all') url += '&school_level=' + encodeURIComponent(level);
                window.location.href = url;
            } else { alert('Error: ' + response.message); resetButtonStates(); }
        }, error: function() { alert('Error applying filter. Please try again.'); resetButtonStates(); } });
    }

    function resetButtonStates() { $('#btnElementary').html('<i class="fas fa-child me-1"></i> Elementary <span class="badge bg-info ms-1">K-6</span>'); $('#btnSecondary').html('<i class="fas fa-graduation-cap me-1"></i> Secondary <span class="badge bg-info ms-1">7-12</span>'); $('#btnIntegrated').html('<i class="fas fa-university me-1"></i> Integrated <span class="badge bg-info ms-1">K-12</span>'); $('#btnElementary, #btnSecondary, #btnIntegrated').prop('disabled', false); }

    // Table switching and print
    const btnElem = document.getElementById('btnElementary');
    const btnSec = document.getElementById('btnSecondary');
    const elemTable = document.getElementById('elementaryTable');
    const secTable = document.getElementById('secondaryTable');
    const btnPrint = document.getElementById('btnPrint');

    if (btnElem && btnSec && elemTable && secTable) {
        btnElem.addEventListener('click', function() { btnElem.classList.add('active'); btnSec.classList.remove('active'); elemTable.classList.remove('d-none'); secTable.classList.add('d-none'); });
        btnSec.addEventListener('click', function() { btnSec.classList.add('active'); btnElem.classList.remove('active'); secTable.classList.remove('d-none'); elemTable.classList.add('d-none'); });
    }

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
            switch(schoolLevel) { case 'all': schoolLevelDisplay = 'All Schools (Elementary View)'; break; case 'elementary': schoolLevelDisplay = 'Elementary Schools'; break; case 'secondary': schoolLevelDisplay = 'Secondary Schools'; break; case 'integrated': schoolLevelDisplay = 'Integrated Schools (Elementary View)'; break; case 'integrated_elementary': schoolLevelDisplay = 'Integrated Schools (Elementary Only)'; break; case 'integrated_secondary': schoolLevelDisplay = 'Integrated Schools (Secondary Only)'; break; }
            const printCss = '<style>@page{size:A4 landscape;margin:8mm;} body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:4px;color:#000;font-size:8px;line-height:1.2;}table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:8px;margin:0;} th,td{border:0.5px solid #dee2e6;padding:2px;word-wrap:break-word;line-height:1.1;}.no-print{display:none!important;} h3{font-size:10px;margin:0 0 2px 0;font-weight:bold;} p{font-size:7px;margin:0 0 4px 0;}.print-header{ text-align:center; margin-bottom:10px; }</style>';
            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>District Nutritional Report - ' + districtName + '</title>' + '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' + printCss + '</head><body>');
            win.document.write('<div class="print-header"><h3>Nutritional Status Report - ' + districtName + ' District</h3><p><strong>Assessment Type:</strong> ' + assessmentType + ' | <strong>School Level:</strong> ' + schoolLevelDisplay + ' | <strong>Report Date:</strong> ' + reportDate + '</p></div>');
            win.document.write(tableHtml); win.document.write('<script>window.onload=function(){ setTimeout(function(){ window.print(); window.onafterprint=function(){ window.close(); } },200); }<\/script>'); win.document.write('</body></html>'); win.document.close();
        });
    }

    // Schools box toggle and modal handling
    $('#overallSummaryCard').click(function(e) { e.stopPropagation(); $('#schoolsBox').toggleClass('d-none'); const icon = $(this).find('.fa-chevron-up'); const text = $(this).find('.small'); if ($('#schoolsBox').hasClass('d-none')) { icon.removeClass('fa-chevron-down').addClass('fa-chevron-up'); text.html('<i class="fas fa-chevron-up me-1"></i> Click to view schools'); } else { icon.removeClass('fa-chevron-up').addClass('fa-chevron-down'); text.html('<i class="fas fa-chevron-down me-1"></i> Hide schools'); } });
    $('#closeSchoolsBox').click(function(e) { e.preventDefault(); e.stopPropagation(); $('#schoolsBox').addClass('d-none'); $('#overallSummaryCard').find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up'); $('#overallSummaryCard').find('.small').html('<i class="fas fa-chevron-up me-1"></i> Click to view schools'); });

    $(document).on('click', '.school-item', function(e) { e.preventDefault(); const schoolName = $(this).data('school'); showSchoolDetails(schoolName); });

    function showSchoolDetails(schoolName) {
        $('#schoolModalBody').html(`<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading school details...</p></div>`);
        const modal = new bootstrap.Modal(document.getElementById('schoolModal'));
        modal.show();
        $.ajax({ url: window.DistrictDashboardConfig.urls.get_school_details + encodeURIComponent(schoolName), method: 'GET', dataType: 'json', success: function(response) {
            if (response.success && response.data) {
                const details = response.data;
                $('#schoolModalBody').html(`...`);
            } else {
                $('#schoolModalBody').html(`<div class="alert alert-danger">Unable to load school details. Please try again.</div>`);
            }
        }, error: function() { $('#schoolModalBody').html(`<div class="alert alert-danger">Error loading school details. Please try again.</div>`); } });
    }
});
