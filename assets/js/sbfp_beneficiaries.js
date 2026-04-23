// Externalized JS for sbfp_beneficiaries
$(document).ready(function() {
    var hasData = (window.SbfpBeneficiariesConfig && window.SbfpBeneficiariesConfig.hasData) ? true : false;

    if (hasData) {
        $('#beneficiariesTable').DataTable({
            pageLength: 25,
            lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'All']],
            order: [[0,'asc']],
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                search: 'Search beneficiary:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ beneficiaries',
                paginate: { previous: "<i class='fas fa-chevron-left'></i>", next: "<i class='fas fa-chevron-right'></i>" },
                emptyTable: 'No ' + (window.SbfpBeneficiariesConfig.assessment_type || '') + ' data available'
            }
        });
    } else {
        $('#beneficiariesTable').addClass('table-striped');
    }

    $('#switchToBaseline').click(function() { switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { switchAssessmentType('endline'); });
    
    // Apply Filters button click
    $('#applyFiltersBtn').click(function() {
        applyFilters();
    });
    
    // Clear Filters button click
    $('#clearFiltersBtn').click(function() {
        clearAllFilters();
    });
    
    // Grade level filter change
    $('#gradeLevelFilter').change(function() {
        applyFilters();
    });
    
    // School name filter change (for district, division, admin)
    if ($('#schoolNameFilter').length) {
        $('#schoolNameFilter').change(function() {
            applyFilters();
        });
    }
    
    // District filter change (for division/admin)
    if ($('#districtFilter').length) {
        $('#districtFilter').change(function() {
            applyFilters();
        });
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
            url: (window.SbfpBeneficiariesConfig && window.SbfpBeneficiariesConfig.urls ? window.SbfpBeneficiariesConfig.urls.set_assessment_type : ''),
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
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
    
    function applyFilters() {
        var gradeLevel = $('#gradeLevelFilter').val();
        var schoolName = $('#schoolNameFilter').length ? $('#schoolNameFilter').val() : '';
        var district = $('#districtFilter').length ? $('#districtFilter').val() : '';
        
        // Show loading indicator
        showLoadingOverlay();
        
        // Determine which filters to apply based on user role
        var userRole = window.SbfpBeneficiariesConfig.user_role;
        
        // Apply grade level filter (always)
        if (gradeLevel !== undefined) {
            $.ajax({
                url: window.SbfpBeneficiariesConfig.urls.set_grade_level_filter,
                method: 'POST',
                data: { grade_level: gradeLevel },
                dataType: 'json',
                success: function() {
                    // Apply school name filter for district, division, admin
                    if (schoolName !== undefined && $('#schoolNameFilter').length && 
                        (userRole === 'district' || userRole === 'division' || userRole === 'admin')) {
                        $.ajax({
                            url: window.SbfpBeneficiariesConfig.urls.set_school_name_filter,
                            method: 'POST',
                            data: { school_name: schoolName },
                            dataType: 'json',
                            success: function() {
                                // Apply district filter only for division/admin
                                if (district !== undefined && $('#districtFilter').length && 
                                    (userRole === 'division' || userRole === 'admin')) {
                                    $.ajax({
                                        url: window.SbfpBeneficiariesConfig.urls.set_district_filter,
                                        method: 'POST',
                                        data: { district: district },
                                        dataType: 'json',
                                        success: function() {
                                            window.location.reload();
                                        },
                                        error: function() {
                                            hideLoadingOverlay();
                                            showNotification('Error applying district filter', 'danger');
                                        }
                                    });
                                } else {
                                    window.location.reload();
                                }
                            },
                            error: function() {
                                hideLoadingOverlay();
                                showNotification('Error applying school filter', 'danger');
                            }
                        });
                    } else {
                        window.location.reload();
                    }
                },
                error: function() {
                    hideLoadingOverlay();
                    showNotification('Error applying grade filter', 'danger');
                }
            });
        } else {
            window.location.reload();
        }
    }
    
    function clearAllFilters() {
        showLoadingOverlay();
        
        $.ajax({
            url: window.SbfpBeneficiariesConfig.urls.clear_filters,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    hideLoadingOverlay();
                    showNotification('Error clearing filters', 'danger');
                }
            },
            error: function() {
                hideLoadingOverlay();
                showNotification('Error clearing filters', 'danger');
            }
        });
    }
    
    function removeFilter(filterType) {
        switch(filterType) {
            case 'grade':
                $('#gradeLevelFilter').val('');
                break;
            case 'school':
                if ($('#schoolNameFilter').length) {
                    $('#schoolNameFilter').val('');
                }
                break;
            case 'district':
                if ($('#districtFilter').length) {
                    $('#districtFilter').val('');
                }
                break;
        }
        applyFilters();
    }
    
    function showLoadingOverlay() {
        var overlay = $('<div id="loadingOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; flex-direction: column;">' +
            '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">' +
            '<span class="visually-hidden">Loading...</span></div>' +
            '<p class="mt-3 text-primary">Applying filters...</p></div>');
        $('body').append(overlay);
    }
    
    function hideLoadingOverlay() {
        $('#loadingOverlay').remove();
    }
    
    function showNotification(message, type) {
        var alertDiv = $('<div class="alert alert-' + type + ' alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999; min-width: 300px;">' +
            '<div class="d-flex">' +
            '<div class="flex-shrink-0">' +
            '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle') + ' me-2"></i>' +
            '</div>' +
            '<div class="flex-grow-1">' + message + '</div>' +
            '</div>' +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
        $('body').append(alertDiv);
        setTimeout(function() { alertDiv.fadeOut(500, function() { $(this).remove(); }); }, 3000);
    }

    $('#btnPrint').click(function() { window.print(); });
});