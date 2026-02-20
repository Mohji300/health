/* JS for division_dashboard — reads runtime config from window.DivisionDashboardConfig */
$(document).ready(function() {
    let currentView = 'districts';
    let selectedDistrict = null;
    let allSchools = [];
    
    // Store all schools data from config
    const allSchoolsData = window.DivisionDashboardConfig.all_schools_by_district || {};
    
    $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
    
    function switchAssessmentType(type) {
        var activeBtn = $('#switchTo' + type.charAt(0).toUpperCase() + type.slice(1));
        var originalHtml = activeBtn.html();
        $('.assessment-switcher .btn').prop('disabled', true);
        activeBtn.html('<i class="fas fa-spinner fa-spin"></i> Switching...');
        
        $.ajax({
            url: window.DivisionDashboardConfig.urls.set_assessment_type,
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var url = window.DivisionDashboardConfig.urls.base;
                    var schoolLevel = window.DivisionDashboardConfig.school_level || 'all';
                    if (schoolLevel && schoolLevel !== 'all') {
                        url += '?assessment_type=' + encodeURIComponent(type) + '&school_level=' + encodeURIComponent(schoolLevel);
                    } else {
                        url += '?assessment_type=' + encodeURIComponent(type);
                    }
                    window.location.href = url;
                } else {
                    alert('Error: ' + response.message);
                    activeBtn.html(originalHtml);
                    $('.assessment-switcher .btn').prop('disabled', false);
                }
            },
            error: function() {
                alert('Error switching assessment type. Please try again.');
                activeBtn.html(originalHtml);
                $('.assessment-switcher .btn').prop('disabled', false);
            }
        });
    }

    // Filter buttons
    $('#btnElementaryFilter').click(function() { 
        $('#btnIntegratedFilter').removeClass('active');
        $('#integratedSubMenu').addClass('d-none');
        setSchoolLevelFilter('elementary'); 
    });
    $('#btnSecondaryFilter').click(function() { 
        $('#btnIntegratedFilter').removeClass('active');
        $('#integratedSubMenu').addClass('d-none');
        setSchoolLevelFilter('secondary'); 
    });
    $('#btnIntegratedFilter').click(function(e) {
        e.preventDefault();
        $(this).toggleClass('active');
        if ($(this).hasClass('active')) {
            $('#integratedSubMenu').removeClass('d-none');
            setSchoolLevelFilter('integrated');
        } else {
            $('#integratedSubMenu').addClass('d-none');
            setSchoolLevelFilter('all');
        }
    });
    $('#btnIntegratedElementary').click(function() { setSchoolLevelFilter('integrated_elementary'); });
    $('#btnIntegratedSecondary').click(function() { setSchoolLevelFilter('integrated_secondary'); });

    function setSchoolLevelFilter(level) {
        $('.table-switcher .btn, #integratedSubMenu .btn').prop('disabled', true);
        $.ajax({
            url: window.DivisionDashboardConfig.urls.set_school_level,
            method: 'POST',
            data: { school_level: level },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var url = window.DivisionDashboardConfig.urls.base;
                    var assessmentType = window.DivisionDashboardConfig.assessment_type || '';
                    url += '?assessment_type=' + encodeURIComponent(assessmentType) + '&school_level=' + encodeURIComponent(level);
                    window.location.href = url;
                } else {
                    alert('Error: ' + response.message);
                    $('.table-switcher .btn, #integratedSubMenu .btn').prop('disabled', false);
                }
            },
            error: function() {
                alert('Error applying filter. Please try again.');
                $('.table-switcher .btn, #integratedSubMenu .btn').prop('disabled', false);
            }
        });
    }

    // Table visibility and print
    var currentLevel = window.DivisionDashboardConfig.school_level || 'all';
    const elemTable = document.getElementById('elementaryTable');
    const secTable = document.getElementById('secondaryTable');
    const btnPrint = document.getElementById('btnPrint');

    if (elemTable && secTable) {
        if (currentLevel === 'secondary' || currentLevel === 'integrated_secondary') {
            secTable.classList.remove('d-none');
            elemTable.classList.add('d-none');
        } else {
            elemTable.classList.remove('d-none');
            secTable.classList.add('d-none');
        }
    }

    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            const win = window.open('', '_blank');
            const isElemVisible = !elemTable.classList.contains('d-none');
            const tableHtml = (isElemVisible ? elemTable : secTable).outerHTML;
            const assessmentType = window.DivisionDashboardConfig.assessment_type_display || '';
            const schoolLevelDisplay = window.DivisionDashboardConfig.school_level_display || '';
            const reportDate = new Date().toLocaleDateString();

            win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title>' +
                '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' +
                '<style>body{padding:20px;} table{width:100%; border-collapse:collapse;} th, td{border:1px solid #dee2e6;}</style>' +
                '</head><body>');
            win.document.write('<h3>Division Nutritional Status Report - ' + assessmentType + ' Assessment</h3>');
            win.document.write('<p>School Level: ' + schoolLevelDisplay + ' | Report Date: ' + reportDate + '</p>');
            win.document.write(tableHtml);
            win.document.write('<script>window.onload=function(){ window.print(); window.onafterprint=function(){ window.close(); } }<\/script>');
            win.document.write('</body></html>');
            win.document.close();
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
        
        // Clear search input when switching districts
        $('#schoolSearch').val('');
        
        if (allSchoolsData && allSchoolsData[districtName]) {
            allSchools = allSchoolsData[districtName];
            
            // Debug: Log the first school to see its structure
            if (allSchools.length > 0) {
                console.log('Sample school data structure:', allSchools[0]);
                console.log('Current assessment type:', window.DivisionDashboardConfig.assessment_type);
            }
            
            displaySchools(allSchools);
            updateSubmissionStats(allSchools);
        } else {
            allSchools = [];
            displaySchools([]);
            updateSubmissionStats([]);
        }
    }

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
        
        const allSchoolsInDistrict = allSchoolsData[selectedDistrict] || [];
        
        if (!searchTerm || searchTerm.trim() === '') {
            displaySchools(allSchoolsInDistrict);
            updateSubmissionStats(allSchoolsInDistrict);
            $('#noSearchResults').addClass('d-none');
            $('#submissionText').text('Submission Progress:');
            return;
        }
        
        const searchLower = searchTerm.toLowerCase().trim();
        const filtered = allSchoolsInDistrict.filter(school => {
            const nameMatch = school.name && school.name.toLowerCase().includes(searchLower);
            const codeMatch = school.code && school.code.toLowerCase().includes(searchLower);
            const idMatch = school.id && school.id.toString().includes(searchLower);
            
            return nameMatch || codeMatch || idMatch;
        });
        
        displaySchools(filtered);
        
        if (filtered.length === 0) {
            $('#noSearchResults').removeClass('d-none');
            $('#noSchoolsMessage').addClass('d-none');
        } else {
            $('#noSearchResults').addClass('d-none');
            $('#noSchoolsMessage').addClass('d-none');
        }
        
        const total = allSchoolsInDistrict.length;
        const filteredCount = filtered.length;
        if (filteredCount < total) {
            $('#submissionText').text(`Showing ${filteredCount} of ${total} schools:`);
        } else {
            $('#submissionText').text('Submission Progress:');
        }
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

    // IMPROVED: Helper function to check if a school has submitted for the current assessment type
    function checkSchoolSubmissionStatus(school) {
        if (!school) return false;
        
        const assessmentType = window.DivisionDashboardConfig.assessment_type || 'baseline';
        
        // Log the school data for debugging (uncomment if needed)
        console.log(`Checking school ${school.name} for ${assessmentType}:`, {
            has_baseline: school.has_baseline,
            has_midline: school.has_midline,
            has_endline: school.has_endline,
            has_submitted: school.has_submitted,
            raw_data: school
        });
        
        // CASE 1: Check if the school object has specific assessment flags
        if (assessmentType === 'baseline' && school.has_baseline !== undefined) {
            return isTruthy(school.has_baseline);
        }
        if (assessmentType === 'midline' && school.has_midline !== undefined) {
            return isTruthy(school.has_midline);
        }
        if (assessmentType === 'endline' && school.has_endline !== undefined) {
            return isTruthy(school.has_endline);
        }
        
        // CASE 2: If the school has an assessments object with specific flags
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
        
        // CASE 3: Check if there's a submitted_assessments array/object
        if (school.submitted_assessments) {
            if (Array.isArray(school.submitted_assessments)) {
                return school.submitted_assessments.includes(assessmentType);
            }
            if (typeof school.submitted_assessments === 'object') {
                return isTruthy(school.submitted_assessments[assessmentType]);
            }
        }
        
        // CASE 4: Fallback to generic has_submitted (only for baseline since that's what your model returns)
        if (assessmentType === 'baseline' && school.has_submitted !== undefined) {
            return isTruthy(school.has_submitted);
        }
        
        // CASE 5: If we're on midline/endline and no specific flags, check if there are ANY assessments
        // This is a fallback but might not be accurate
        if ((assessmentType === 'midline' || assessmentType === 'endline') && school.has_submitted !== undefined) {
            // Don't use has_submitted for midline/endline as it might be from baseline
            return false;
        }
        
        return false;
    }
    
    // Helper function to check truthy values (handles boolean, 1, '1', 'true')
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
            const allSchoolsInDistrict = allSchoolsData[selectedDistrict] || [];
            if (schools.length < allSchoolsInDistrict.length) {
                $('#submissionText').text(`Showing ${schools.length} of ${allSchoolsInDistrict.length} schools:`);
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