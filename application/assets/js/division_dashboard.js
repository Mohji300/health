/* JS for division_dashboard */
$(document).ready(function() {
    let currentView = 'districts';
    let selectedDistrict = null;
    let allSchools = [];
    let currentSchoolDetails = null;
    let currentSchoolAssessmentType = 'baseline';
    let currentSchoolLevel = 'all';
    let districtReportState = null;

    // -------------------------------------------------------------------
    //  UI helpers
    // -------------------------------------------------------------------
    function showLoading(message) {
        if (!$('#loadingOverlay').length) {
            $('body').append(`
                <div id="loadingOverlay">
                    <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="loading-text">${message || 'Loading, please wait…'}</div>
                </div>`);
        } else {
            $('#loadingOverlay .loading-text').text(message || 'Loading, please wait…');
        }
    }
    function hideLoading() { $('#loadingOverlay').remove(); }

    function showNotification(message, type) {
        $('.notification-toast').remove();
        const n = $(`
            <div class="alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show notification-toast"
                 role="alert"
                 style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`);
        $('body').append(n);
        setTimeout(() => n.fadeOut(300, function() { $(this).remove(); }), 5000);
    }

    function updateTableVisibility(schoolLevel) {
        const elemTable = document.getElementById('elementaryTable');
        const secTable  = document.getElementById('secondaryTable');
        const shsTable  = document.getElementById('shsTable');
        if (!elemTable || !secTable) return;

        switch (schoolLevel) {
            case 'secondary':
            case 'integrated_secondary':
                elemTable.classList.add('d-none');
                secTable.classList.remove('d-none');
                if (shsTable) shsTable.classList.add('d-none');
                break;
            case 'shs_only':
                if (shsTable) {
                    elemTable.classList.add('d-none');
                    secTable.classList.add('d-none');
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

    // -------------------------------------------------------------------
    //  AJAX loader — loads ONLY the selected assessment
    // -------------------------------------------------------------------
    function loadDashboardData(assessmentType, schoolLevel, legislativeDistrictId) {
        showLoading();  // ensure loader is visible

        return $.ajax({   // <-- add "return"
            url: window.DivisionDashboardConfig.urls.get_dashboard_data,
            method: 'GET',
            data: {
                assessment_type:         assessmentType,
                school_level:            schoolLevel || 'all',
                legislative_district_id: legislativeDistrictId || ''
            },
            dataType: 'json',
            cache: false,
            success: function(response) {
                if (!response.success) {
                    showNotification(response.message || 'Unable to load dashboard data.', 'error');
                    return;
                }
                window.DivisionDashboardConfig.assessment_type = response.assessment_type;
                window.DivisionDashboardConfig.school_level    = response.school_level;
                updateDashboard(response);
            },
            error: function(xhr, status, err) {
                console.error('Dashboard AJAX error:', err);
                showNotification('Failed to load dashboard data.', 'error');
            },
            complete: hideLoading
        });
    }

    function updateDashboard(response) {
        // --- Tables ---
        if (response.table_html !== undefined) {
            $('#tableContent').html(response.table_html);
        }

        // --- District cards ---
        if (response.district_html !== undefined) {
            $('#districtsContainer').html(response.district_html);
        }

        // --- Overall stats ---
        const stats = response.overall_stats || {};
        $('#totalSchools').text(Number(stats.total_schools || 0).toLocaleString());
        $('#totalSubmitted').text(Number(stats.total_submitted || 0).toLocaleString());
        $('#overallCompletion').text(Number(stats.overall_completion || 0) + '%');

        // --- Header badge + assessment dropdown label ---
        const type = response.assessment_type || '';
        const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);

        $('#currentAssessmentType').text(typeLabel + ' Assessment');
        $('#assessmentDropdownLabel').text(typeLabel);

        // --- Active dropdown item + count badge ---
        $('.dropdown-item[data-type="baseline"], .dropdown-item[data-type="midline"], .dropdown-item[data-type="endline"]').removeClass('active');
        $('.dropdown-item[data-type="' + type + '"]').addClass('active');
        $('#' + type + 'Count').text(Number(response.assessment_count || 0).toLocaleString());

        // --- Keep hidden form fields in sync ---
        $('#districtFilterForm input[name="assessment_type"]').val(response.assessment_type);
        $('#districtFilterForm input[name="school_level"]').val(response.school_level);

        // --- School level button label ---
        const levelMap = {
            'all': 'Elementary',
            'elementary': 'Elementary',
            'secondary': 'Secondary',
            'integrated': 'Integrated',
            'integrated_elementary': 'Integrated Elementary',
            'integrated_secondary': 'Integrated Secondary',
            'shs_only': 'Stand Alone SHS'
        };
        $('#schoolLevelDropdown').html(
            '<i class="fas fa-filter me-1"></i> School Level: ' +
            (levelMap[response.school_level] || 'Elementary')
        );

        const levelLabels = {
        'all': 'All Schools',
        'elementary': 'Elementary Schools',
        'secondary': 'Secondary Schools',
        'integrated': 'Integrated Schools',
        'integrated_elementary': 'Integrated Schools (Elementary)',
        'integrated_secondary': 'Integrated Schools (Secondary)',
        'shs_only': 'Stand Alone SHS'
        };
        window.DivisionDashboardConfig.assessment_type_display = typeLabel;
        window.DivisionDashboardConfig.school_level_display    = levelLabels[response.school_level] || 'All Schools';

        // Re-apply visibility to the freshly injected tables
        updateTableVisibility(response.school_level || 'all');
    }

    // -------------------------------------------------------------------
    //  Assessment dropdown — AJAX only, NO page reload
    // -------------------------------------------------------------------
    $(document).on('click', 'a.dropdown-item[data-type]', function(e) {
        e.preventDefault();
        const $item = $(this);
        const type  = $item.data('type');

        // "Select an Assessment" default has no data-type value → do nothing
        if (!type || !['baseline','midline','endline'].includes(type)) return;
        if ($item.hasClass('active')) return;

        const schoolLevel = window.DivisionDashboardConfig.school_level || 'all';
        const districtId  = window.DivisionDashboardConfig.selected_legislative_district_id || '';

        // Optimistic UI update
        $('.dropdown-item[data-type="baseline"], .dropdown-item[data-type="midline"], .dropdown-item[data-type="endline"]').removeClass('active');
        $item.addClass('active');
        $('#currentAssessmentType').text(
            type.charAt(0).toUpperCase() + type.slice(1) + ' Assessment'
        );

        // >>> SHOW LOADING SCREEN <<<
        showLoading('Changing assessment type…');

        $.ajax({
            url: window.DivisionDashboardConfig.urls.set_assessment_type,
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    showNotification(response.message || 'Could not change assessment type.', 'error');
                    hideLoading();
                    return;
                }
                loadDashboardData(type, schoolLevel, districtId);
            },
            error: function() {
                showNotification('Unable to change assessment type.', 'error');
                hideLoading();
            }
        });
    });

    // -------------------------------------------------------------------
    //  School level dropdown — AJAX only, NO page reload
    // -------------------------------------------------------------------
    $(document).on('click', '.dropdown-item[data-level]', function(e) {
        e.preventDefault();
        const $item = $(this);
        const level = $item.data('level');
        const levelText = $item.text().trim();
        const assessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';
        const districtId = window.DivisionDashboardConfig.selected_legislative_district_id || '';

        if ($item.hasClass('active')) return;

        // Optimistic UI update
        $('.dropdown-item[data-level]').removeClass('active');
        $item.addClass('active');
        $('#schoolLevelDropdown').html(
            '<i class="fas fa-filter me-1"></i> School Level: ' + levelText
        );

        $.ajax({
            url: window.DivisionDashboardConfig.urls.set_school_level,
            method: 'POST',
            data: { school_level: level, assessment_type: assessmentType },
            dataType: 'json',
            success: function(response) {
                if (!response.success) {
                    showNotification(response.message || 'Could not change school level.', 'error');
                    return;
                }
                loadDashboardData(assessmentType, level, districtId);
            },
            error: function() {
                showNotification('Unable to change school level.', 'error');
            }
        });
    });

    // District filter — still a GET form submit (fast shell load)
    $(document).on('change', '#districtFilter', function() {
        const districtId     = $(this).val() || '';
        const assessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';
        const schoolLevel    = window.DivisionDashboardConfig.school_level    || 'all';

        window.DivisionDashboardConfig.selected_legislative_district_id = districtId;

        // >>> SHOW LOADING SCREEN <<<
        showLoading('Changing legislative district…');
        $(this).prop('disabled', true);

        loadDashboardData(assessmentType, schoolLevel, districtId).always(function() {
            $('#districtFilter').prop('disabled', false);
        });
    });

    // -------------------------------------------------------------------
    //  Print / Export — always re-query the DOM
    // -------------------------------------------------------------------
    function getVisibleReportTable() {
        const elem = document.getElementById('elementaryTable');
        const shs  = document.getElementById('shsTable');
        const sec  = document.getElementById('secondaryTable');
        if (elem && !elem.classList.contains('d-none')) return elem;
        if (shs  && !shs.classList.contains('d-none'))  return shs;
        return sec;
    }

    $(document).on('click', '#btnExportExcel', function() {
        const table = getVisibleReportTable();
        if (!table) { alert('No report table is available to export.'); return; }
        const config = window.DivisionDashboardConfig || {};
        const assessmentType = config.assessment_type_display || 'Report';
        const divisionName   = config.user_name || config.division_name || 'Division';
        const safePart = v => String(v).replace(/[^a-z0-9_-]+/gi, '_').replace(/^_+|_+$/g, '');
        const filename = `${safePart(divisionName) || 'Division'}_${safePart(assessmentType)}_Nutritional_Report.xls`;
        const html = `<!doctype html><html><head><meta charset="utf-8"></head><body>${table.outerHTML}</body></html>`;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    });

    $(document).on('click', '#btnPrint', function() {
        const table = getVisibleReportTable();
        if (!table) return;
        const win = window.open('', '_blank');
        if (!win) return;

        const assessmentType  = window.DivisionDashboardConfig.assessment_type_display || '';
        const schoolLevelText = window.DivisionDashboardConfig.school_level_display    || '';
        const tableHtml       = table.outerHTML;

        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>Nutritional Status Report</title>' +
            '<style>@page{size:Legal landscape;margin:8mm;}' +
            'body{font-family:Arial,sans-serif;font-size:12px;}' +
            'table{width:100%;border-collapse:collapse;font-size:11px;}' +
            'th,td{border:0.5px solid #dee2e6;padding:4px;}' +
            '.no-print{display:none;}' +
            '</style></head><body>' +
            '<div style="text-align:center;margin-bottom:10px;">' +
            '<h3>Nutritional Status Report</h3>' +
            '<p><strong>Assessment Type:</strong> ' + assessmentType +
            ' | <strong>School Level:</strong> ' + schoolLevelText + '</p>' +
            '</div>' +
            tableHtml + '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function() {
            try { win.print(); } catch(e) {}
            win.onafterprint = function() { win.close(); };
        }, 250);
    });

    // -------------------------------------------------------------------
    //  District / School UI
    // -------------------------------------------------------------------
    $(document).on('click', '#overallSummaryCard', function() {
        $('#schoolsBox').toggleClass('d-none');
    });
    $(document).on('click', '#closeSchoolsBox', function() {
        $('#schoolsBox').addClass('d-none');
    });
    $(document).on('click', '.district-report-btn', function(e) {
        e.preventDefault(); e.stopPropagation();
        showDistrictReport($(this).data('district'));
    });
    $(document).on('click', '.district-card', function() {
        selectDistrict($(this).data('district'));
    });

    // School search
    let searchTimeout;
    $(document).on('input', '#schoolSearch', function() {
        clearTimeout(searchTimeout);
        const term = $(this).val();
        searchTimeout = setTimeout(() => filterSchools(term), 300);
    });
    $(document).on('click', '#clearSearch', function() {
        $('#schoolSearch').val('');
        filterSchools('');
    });

    $(document).on('click', '#backToDistricts', function() {
        currentView = 'districts';
        selectedDistrict = null;
        $('#boxTitle').text('All Districts');
        $('#districtsView').removeClass('d-none');
        $('#schoolsView').addClass('d-none');
        $('#schoolSearch').val('');
        allSchools = [];
    });

    // -------------------------------------------------------------------
    //  District / School helpers
    // -------------------------------------------------------------------
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
            const idMatch   = school.id && school.id.toString().includes(searchLower);
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

    function selectDistrict(districtName) {
        selectedDistrict = districtName;
        currentView = 'schools';
        $('#boxTitle').text('Schools in ' + districtName);
        $('#districtsView').addClass('d-none');
        $('#schoolsView').removeClass('d-none');
        $('#districtSchoolsTitle').text('Schools in ' + districtName);
        $('#schoolSearch').val('');
        allSchools = [];

        const list = $('#schoolsList');
        list.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        $.ajax({
            url: window.DivisionDashboardConfig.urls.get_district_schools,
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
            error: function() {
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
            const hasPartial   = isTruthy(school.has_partial_assessment);
            const statusClass  = hasSubmitted ? 'submission-submitted' : 'submission-pending';
            const statusText   = hasSubmitted ? 'Submitted' : 'Pending Assessment';
            const icon         = hasSubmitted ? 'check' : 'circle';
            const iconColor    = hasSubmitted ? 'text-success' : 'text-muted';
            const pendingHint  = hasPartial && !hasSubmitted
                ? '<i class="fas fa-hourglass-half text-warning ms-2" title="This school has submitted assessments but still has pending sections."></i>'
                : '';
            const schoolName = school && school.name ? school.name : 'Unknown School';
            const schoolCode = school && school.code ? school.code : '';
            const schoolId   = school && school.id   ? school.id   : '';

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
                            <div class="fw-medium">${schoolName}${pendingHint}</div>
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
            const schoolId   = $(this).data('school-id');
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
        const value = school['has_' + assessmentType] !== undefined
            ? school['has_' + assessmentType]
            : (school.assessments ? school.assessments[assessmentType] : undefined);
        return isTruthy(value);
    }

    function isTruthy(value) {
        return (value === true || value === 1 || value === '1' || value === 'true');
    }

    function updateSubmissionStats(schools) {
        if (!schools || !Array.isArray(schools)) {
            $('#submissionCount').text('0/0 schools submitted');
            $('#submissionProgressBar').css('width', '0%');
            return;
        }

        const submitted  = schools.filter(s => checkSchoolSubmissionStatus(s)).length;
        const total      = schools.length;
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

    // -------------------------------------------------------------------
    //  School details modal
    // -------------------------------------------------------------------
    function showSchoolDetails(schoolId, schoolName) {
        if (!schoolId) {
            $('#schoolModalBody').html(`
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Invalid school ID. Please try again.
                </div>
            `);
            new bootstrap.Modal(document.getElementById('schoolModal')).show();
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
        new bootstrap.Modal(document.getElementById('schoolModal')).show();

        $.ajax({
            url: window.DivisionDashboardConfig.urls.get_school_details + encodeURIComponent(schoolId),
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const details = response.data;
                    currentSchoolDetails = details;
                    currentSchoolAssessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';
                    currentSchoolLevel = 'all';

                    const assessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';
                    let assessmentStatus = 'Not Submitted';
                    let statusClass = 'bg-secondary';

                    if (details.assessments && details.assessments['has_' + assessmentType]) {
                        assessmentStatus = 'Submitted';
                        statusClass = 'bg-success';
                    } else {
                        assessmentStatus = 'Pending Assessment';
                        statusClass = 'bg-warning text-dark';
                    }

                    const detailsHtml = `
                        <div class="school-details">
                            <div id="pendingAssessmentsPanel" class="alert alert-warning mb-3">
                                ${renderPendingAssessments(details, assessmentType)}
                            </div>
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="fw-bold mb-0">${details.name || schoolName}</h6>
                                <span class="badge ${statusClass}">${assessmentStatus}</span>
                            </div>

                            <table class="table table-sm table-borderless">
                                <tr><td width="40%"><strong>School ID:</strong></td><td>${details.code || 'N/A'}</td></tr>
                                <tr><td><strong>District:</strong></td><td>${details.district || 'N/A'}</td></tr>
                                <tr><td><strong>School Level:</strong></td><td>${details.level || 'N/A'}</td></tr>
                            </table>

                            <hr>

                            <h6 class="fw-bold mb-3">Assessment Information</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Baseline Assessment:</strong></td>
                                    <td>${details.assessments && details.assessments.has_baseline ?
                                        '<span class="badge bg-success">Submitted</span>' :
                                        '<span class="badge bg-warning text-dark">Pending - Not Submitted</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Midline Assessment:</strong></td>
                                    <td>${details.assessments && details.assessments.has_midline ?
                                        '<span class="badge bg-success">Submitted</span>' :
                                        '<span class="badge bg-warning text-dark">Pending - Not Submitted</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Endline Assessment:</strong></td>
                                    <td>${details.assessments && details.assessments.has_endline ?
                                        '<span class="badge bg-success">Submitted</span>' :
                                        '<span class="badge bg-warning text-dark">Pending - Not Submitted</span>'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Assessment Date:</strong></td>
                                    <td>${details.assessments && details.assessments.last_assessment_date ?
                                        details.assessments.last_assessment_date : 'N/A'}</td>
                                </tr>
                            </table>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <h6 class="fw-bold mb-0">Nutritional Assessment Report</h6>
                                <span class="text-muted small">${(details.consolidated || []).length} record(s)</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Assessment type">
                                    <button type="button" class="btn btn-outline-primary school-report-assessment ${currentSchoolAssessmentType === 'baseline' ? 'active' : ''}" data-type="baseline">Baseline</button>
                                    <button type="button" class="btn btn-outline-primary school-report-assessment ${currentSchoolAssessmentType === 'midline' ? 'active' : ''}" data-type="midline">Midline</button>
                                    <button type="button" class="btn btn-outline-primary school-report-assessment ${currentSchoolAssessmentType === 'endline' ? 'active' : ''}" data-type="endline">Endline</button>
                                </div>
                                <select class="form-select form-select-sm school-report-level" aria-label="School level">
                                    ${schoolLevelOptions(currentSchoolLevel)}
                                </select>
                                <div class="btn-group btn-group-sm" role="group" aria-label="Report actions">
                                    <button type="button" class="btn btn-success" id="schoolReportPrint"><i class="fas fa-print me-1"></i>Print</button>
                                    <button type="button" class="btn btn-primary" id="schoolReportExport"><i class="fas fa-file-excel me-1"></i>Export Excel</button>
                                </div>
                            </div>
                            <div id="schoolReportArea">${renderConsolidatedSchoolTable(details, details.consolidated || [])}</div>
                        </div>
                    `;
                    $('#schoolModalBody').html(detailsHtml);
                    bindPendingAssessmentFilters(details);
                    bindSchoolReportControls();
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

    function getPendingSections(details, filter) {
        const records = details.consolidated || [];
        const sections = {};
        (details.sections || []).forEach(function(section) {
            sections[section.section_id] = {
                grade: section.grade || 'N/A',
                section: section.section || 'N/A',
                year: section.school_year || 'N/A',
                submitted: {}
            };
        });
        records.forEach(function(record) {
            const key = record.section_id || [record.grade_level, record.section, record.school_year].join('|');
            if (!sections[key]) {
                sections[key] = {
                    grade: record.grade_level || 'N/A',
                    section: record.section || 'N/A',
                    year: record.school_year || 'N/A',
                    submitted: {}
                };
            }
            sections[key].submitted[record.assessment_type || 'baseline'] = true;
        });
        const types = filter && filter !== 'all' ? [filter] : ['baseline', 'midline', 'endline'];
        return Object.keys(sections).map(function(key) {
            const section = sections[key];
            section.pending = types.filter(function(type) { return !section.submitted[type]; });
            return section;
        }).filter(function(section) { return section.pending.length > 0; });
    }

    function bindPendingAssessmentFilters(details) {
        $('.pending-assessment-filter').off('click').on('click', function() {
            const filter = $(this).data('type');
            $('.pending-assessment-filter').removeClass('active');
            $(this).addClass('active');
            $('#pendingAssessmentsPanel').html(renderPendingAssessments(details, filter));
            bindPendingAssessmentFilters(details);
        });
    }

    function renderPendingAssessments(details, filter) {
        filter = filter || 'baseline';
        const pendingSections = getPendingSections(details, filter);

        const filterButtons = '<div class="btn-group btn-group-sm d-flex flex-wrap mt-2 mb-2" role="group" aria-label="Pending assessment filter">' +
            '<button type="button" class="btn btn-outline-primary pending-assessment-filter ' + (filter === 'baseline' ? 'active' : '') + '" data-type="baseline">Baseline</button>' +
            '<button type="button" class="btn btn-outline-warning pending-assessment-filter ' + (filter === 'midline' ? 'active' : '') + '" data-type="midline">Midline</button>' +
            '<button type="button" class="btn btn-outline-success pending-assessment-filter ' + (filter === 'endline' ? 'active' : '') + '" data-type="endline">Endline</button>' +
            '</div>';
        if (!pendingSections.length) {
            return filterButtons + '<strong><i class="fas fa-check-circle me-1"></i>No pending ' + filter + ' assessments.</strong>';
        }
        const rows = pendingSections.map(function(section) {
            return '<tr><td>' + section.grade + '</td><td>' + section.section + '</td><td>' + section.year + '</td><td>' +
                section.pending.map(function(type) {
                    return '<span class="badge bg-warning text-dark me-1 mb-1">' +
                        type.charAt(0).toUpperCase() + type.slice(1) + ' - Not Submitted</span>';
                }).join('') + '</td></tr>';
        }).join('');
        return '<strong><i class="fas fa-hourglass-half me-1"></i>Sections with pending assessments</strong>' +
            filterButtons +
            '<div class="table-responsive mt-2"><table class="table table-sm table-bordered mb-0 bg-white">' +
            '<thead><tr><th>Grade</th><th>Section</th><th>School Year</th><th>Pending Assessment</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table></div>';
    }

    function bindSchoolReportControls() {
        $('.school-report-assessment').off('click').on('click', function() {
            currentSchoolAssessmentType = $(this).data('type');
            $('.school-report-assessment').removeClass('active');
            $(this).addClass('active');
            $('#schoolReportArea').html(renderConsolidatedSchoolTable(currentSchoolDetails, currentSchoolDetails.consolidated || []));
        });

        $('.school-report-level').off('change').on('change', function() {
            currentSchoolLevel = $(this).val();
            $('#schoolReportArea').html(renderConsolidatedSchoolTable(currentSchoolDetails, currentSchoolDetails.consolidated || []));
        });

        $('#schoolReportPrint').off('click').on('click', function() {
            const table = document.querySelector('#schoolReportArea table');
            if (!table) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            printWindow.document.write(`<!doctype html><html><head><title>Nutritional Assessment Report</title><style>
                @page { size: Legal landscape; margin: 8mm; }
                body { font-family: Arial, sans-serif; font-size: 11px; }
                h3 { text-align: center; margin: 0 0 8px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #999; padding: 4px; text-align: center; }
                th { background: #e9ecef; }
            </style></head><body><h3>Nutritional Assessment Report</h3>${table.outerHTML}</body></html>`);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.onafterprint = function() { printWindow.close(); };
        });

        $('#schoolReportExport').off('click').on('click', function() {
            const table = document.querySelector('#schoolReportArea table');
            if (!table) return;
            const schoolName = currentSchoolDetails && currentSchoolDetails.name ? currentSchoolDetails.name : 'School';
            const safeName = String(schoolName).replace(/[^a-z0-9_-]+/gi, '_').replace(/^_+|_+$/g, '') || 'School';
            const workbook = `<!doctype html><html><head><meta charset="utf-8"></head><body>${table.outerHTML}</body></html>`;
            const blob = new Blob([workbook], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${safeName}_${currentSchoolAssessmentType}_nutritional_report.xls`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        });
    }

    function renderConsolidatedSchoolTable(school, records) {
        const assessmentType = currentSchoolAssessmentType;
        const filteredRecords = records.filter(function(record) {
            const grade = String(record.grade_level || '').trim().toLowerCase();
            const isElementary = grade === 'kindergarten' || grade === 'kinder' || grade === 'sped' || /^grade [1-6]$/.test(grade);
            const isSecondary  = /^grade (7|8|9|10|11|12)$/.test(grade);
            const matchesLevel = currentSchoolLevel === 'all' ||
                (currentSchoolLevel === 'elementary' && isElementary) ||
                (currentSchoolLevel === 'secondary' && isSecondary) ||
                (currentSchoolLevel === 'integrated') ||
                (currentSchoolLevel === 'integrated_elementary' && isElementary) ||
                (currentSchoolLevel === 'integrated_secondary' && isSecondary) ||
                (currentSchoolLevel === 'shs_only' && /^(grade 11|grade 12)$/.test(grade));
            return (record.assessment_type || 'baseline') === assessmentType && matchesLevel;
        });

        if (!filteredRecords.length) {
            return '<div class="alert alert-light border mb-0">No assessment records found for this school and assessment type.</div>';
        }

        const gradeOrder = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'SPED',
            'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        const bmiFields = ['severely_wasted', 'wasted', 'normal_bmi', 'overweight', 'obese'];
        const hfaFields = ['severely_stunted', 'stunted', 'normal_hfa', 'tall', 'pupils_height'];
        const emptyStats = function() {
            const stats = { enrolment: 0, pupils_weighed: 0 };
            bmiFields.concat(hfaFields).forEach(function(field) { stats[field] = 0; });
            return stats;
        };
        const gradeLabel = function(value) {
            return String(value || '').trim().toLowerCase() === 'kindergarten' ? 'Kinder' : String(value || '').trim();
        };
        const stats = {};

        filteredRecords.forEach(function(record) {
            const grade = gradeLabel(record.grade_level);
            const sex = String(record.sex || '').trim().toUpperCase();
            if (!grade || (sex !== 'M' && sex !== 'F')) return;
            if (!stats[grade]) stats[grade] = { M: emptyStats(), F: emptyStats(), Total: emptyStats() };

            const status = String(record.nutritional_status || '').trim().toLowerCase();
            const hfa    = String(record.height_for_age || '').trim().toLowerCase();
            const values = stats[grade][sex];
            values.enrolment++;
            if (Number(record.weight) > 0) values.pupils_weighed++;
            if (Number(record.height) > 0) values.pupils_height++;
            if (status === 'severely wasted') values.severely_wasted++;
            if (status === 'wasted') values.wasted++;
            if (status === 'normal') values.normal_bmi++;
            if (status === 'overweight') values.overweight++;
            if (status === 'obese') values.obese++;
            if (hfa === 'severely stunted') values.severely_stunted++;
            if (hfa === 'stunted') values.stunted++;
            if (hfa === 'normal') values.normal_hfa++;
            if (hfa === 'tall' || hfa === 'above normal') values.tall++;
        });

        Object.keys(stats).forEach(function(grade) {
            Object.keys(stats[grade].Total).forEach(function(field) {
                stats[grade].Total[field] = stats[grade].M[field] + stats[grade].F[field];
            });
        });

        const pct = function(value, denominator) {
            return denominator ? Math.round((value / denominator) * 100) + '%' : '0%';
        };
        const row = function(grade, sex, values, totalRow, showGrade) {
            const gradeCell = showGrade ? `<td class="fw-bold text-center align-middle" rowspan="3">${grade}</td>` : '';
            let html = `<tr class="${totalRow ? 'table-primary fw-bold' : ''}">${gradeCell}<td>${sex}</td><td>${values.enrolment}</td><td>${values.pupils_weighed}</td>`;
            bmiFields.concat(hfaFields).forEach(function(field) {
                html += `<td>${values[field]}</td><td>${pct(values[field], values.enrolment)}</td>`;
            });
            return html + '</tr>';
        };
        const grand = { M: emptyStats(), F: emptyStats(), Total: emptyStats() };
        const body = [];

        gradeOrder.forEach(function(grade) {
            if (!stats[grade]) return;
            ['M', 'F', 'Total'].forEach(function(sex) {
                const values = stats[grade][sex];
                Object.keys(values).forEach(function(field) { grand[sex][field] += values[field]; });
                body.push(row(grade, sex, values, sex === 'Total', sex === 'M'));
            });
        });
        ['M', 'F', 'Total'].forEach(function(sex) {
            body.push(row('Grand Total', sex, grand[sex], true, sex === 'M'));
        });

        return `
            <div class="table-responsive border rounded">
                <table class="table table-sm table-bordered table-hover mb-0 align-middle text-center detailed-consolidated-table">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="3">Grade Level</th>
                            <th rowspan="3">Sex</th>
                            <th rowspan="3">Enrolment</th>
                            <th rowspan="3">Pupils Weighed</th>
                            <th colspan="10">BODY MASS INDEX (BMI)</th>
                            <th colspan="10">HEIGHT-FOR-AGE (HFA)</th>
                        </tr>
                        <tr class="table-secondary">
                            <th colspan="2">Severely Wasted</th><th colspan="2">Wasted</th><th colspan="2">Normal BMI</th>
                            <th colspan="2">Overweight</th><th colspan="2">Obese</th>
                            <th colspan="2">Severely Stunted</th><th colspan="2">Stunted</th><th colspan="2">Normal HFA</th>
                            <th colspan="2">Tall</th><th colspan="2">Pupils Height</th>
                        </tr>
                        <tr class="table-secondary">${'<th>Count</th><th>%</th>'.repeat(10)}</tr>
                    </thead>
                    <tbody>${body.join('')}</tbody>
                </table>
            </div>
        `;
    }

    // -------------------------------------------------------------------
    //  District report modal
    // -------------------------------------------------------------------
    function showDistrictReport(districtName) {
        const modal = new bootstrap.Modal(document.getElementById('districtReportModal'));
        $('#districtReportTitle').text(districtName + ' Nutritional Assessment Report');
        $('#districtReportBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading district report...</p></div>');
        modal.show();

        $.getJSON(window.DivisionDashboardConfig.urls.get_district_report, { district: districtName })
            .done(function(response) {
                if (!response.success) {
                    $('#districtReportBody').html('<div class="alert alert-danger">Unable to load district report.</div>');
                    return;
                }
                districtReportState = {
                    name: districtName,
                    records: response.records || [],
                    assessmentType: window.DivisionDashboardConfig.assessment_type || 'baseline',
                    schoolLevel: window.DivisionDashboardConfig.school_level || 'all'
                };
                renderDistrictReport();
            })
            .fail(function() {
                $('#districtReportBody').html('<div class="alert alert-danger">Error loading district report.</div>');
            });
    }

    function renderDistrictReport() {
        if (!districtReportState) return;
        const oldAssessment = currentSchoolAssessmentType;
        const oldLevel = currentSchoolLevel;
        currentSchoolAssessmentType = districtReportState.assessmentType;
        currentSchoolLevel = districtReportState.schoolLevel;
        const table = renderConsolidatedSchoolTable({ name: districtReportState.name }, districtReportState.records);
        currentSchoolAssessmentType = oldAssessment;
        currentSchoolLevel = oldLevel;

        $('#districtReportBody').html(`
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
                <div class="btn-group btn-group-sm" role="group" aria-label="District assessment type">
                    <button type="button" class="btn btn-outline-primary district-report-assessment ${districtReportState.assessmentType === 'baseline' ? 'active' : ''}" data-type="baseline">Baseline</button>
                    <button type="button" class="btn btn-outline-primary district-report-assessment ${districtReportState.assessmentType === 'midline' ? 'active' : ''}" data-type="midline">Midline</button>
                    <button type="button" class="btn btn-outline-primary district-report-assessment ${districtReportState.assessmentType === 'endline' ? 'active' : ''}" data-type="endline">Endline</button>
                </div>
                <select class="form-select form-select-sm district-report-level" aria-label="District school level">
                    ${schoolLevelOptions(districtReportState.schoolLevel)}
                </select>
                <div class="btn-group btn-group-sm" role="group" aria-label="District report actions">
                    <button type="button" class="btn btn-success" id="districtReportPrint"><i class="fas fa-print me-1"></i>Print</button>
                    <button type="button" class="btn btn-primary" id="districtReportExport"><i class="fas fa-file-excel me-1"></i>Export Excel</button>
                </div>
            </div>
            <div id="districtReportArea">${table}</div>
        `);
        bindDistrictReportControls();
    }

    function bindDistrictReportControls() {
        $('.district-report-assessment').off('click').on('click', function() {
            districtReportState.assessmentType = $(this).data('type');
            renderDistrictReport();
        });
        $('.district-report-level').off('change').on('change', function() {
            districtReportState.schoolLevel = $(this).val();
            renderDistrictReport();
        });
        $('#districtReportPrint').off('click').on('click', function() {
            const table = document.querySelector('#districtReportArea table');
            if (!table) return;
            const printWindow = window.open('', '_blank');
            if (!printWindow) return;
            printWindow.document.write(`<!doctype html><html><head><title>District Nutritional Assessment Report</title><style>
                @page { size: Legal landscape; margin: 8mm; }
                body { font-family: Arial, sans-serif; font-size: 11px; }
                h3 { text-align: center; margin: 0 0 8px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #999; padding: 4px; text-align: center; }
                th { background: #e9ecef; }
            </style></head><body><h3>${districtReportState.name} Nutritional Assessment Report</h3>${table.outerHTML}</body></html>`);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.onafterprint = function() { printWindow.close(); };
        });
        $('#districtReportExport').off('click').on('click', function() {
            const table = document.querySelector('#districtReportArea table');
            if (!table) return;
            const safeName = String(districtReportState.name).replace(/[^a-z0-9_-]+/gi, '_').replace(/^_+|_+$/g, '') || 'District';
            const workbook = `<!doctype html><html><head><meta charset="utf-8"></head><body>${table.outerHTML}</body></html>`;
            const blob = new Blob([workbook], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${safeName}_${districtReportState.assessmentType}_nutritional_report.xls`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        });
    }

    function schoolLevelOptions(selected) {
        return [
            ['all', 'All School Levels'],
            ['elementary', 'Elementary'],
            ['secondary', 'Secondary'],
            ['integrated', 'Integrated (K-12)'],
            ['integrated_elementary', 'Integrated Elementary (K-6)'],
            ['integrated_secondary', 'Integrated Secondary (7-12)'],
            ['shs_only', 'Stand Alone SHS (11-12)']
        ].map(function(option) {
            return '<option value="' + option[0] + '" ' + (selected === option[0] ? 'selected' : '') + '>' + option[1] + '</option>';
        }).join('');
    }

    // -------------------------------------------------------------------
    //  Initial visibility of the shell
    // -------------------------------------------------------------------
    updateTableVisibility(window.DivisionDashboardConfig.school_level || 'all');
});