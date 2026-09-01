// sbfp_beneficiaries.js – Server‑side pagination with DataTables
$(document).ready(function() {
    // ------------------------------------------------------------
    // DataTable initialization 
    // ------------------------------------------------------------
    var table = $('#beneficiariesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.SbfpBeneficiariesConfig.urls.datatable,
            type: 'POST',
            cache: false,
            data: function(d) {
                d.section_id = $('#sectionFilter').val() || '';
            }
        },
        columns: [
            { data: 'no', orderable: false },
            { data: 'name' },
            { data: 'sex' },
            { data: 'grade_section', orderable: false },
            { data: 'birthday' },
            { data: 'date_of_weighing' },
            { data: 'age' },
            { data: 'weight' },
            { data: 'height' },
            { data: 'bmi' },
            { data: 'nutritional_status', orderable: false },
            { data: 'height_for_age', orderable: false },
            { data: 'classification', orderable: false },
            { data: 'pregnant', orderable: false },
            { data: 'child_0_1', orderable: false },
            { data: 'dewormed', orderable: false },
            { data: 'parent_consent', orderable: false },
            { data: 'participation_4ps', orderable: false },
            { data: 'previous_sbfp', orderable: false }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [],
        language: {
            search: 'Search beneficiary:',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ beneficiaries',
            paginate: {
                previous: "<i class='fas fa-chevron-left'></i>",
                next: "<i class='fas fa-chevron-right'></i>"
            },
            emptyTable: 'No ' + (window.SbfpBeneficiariesConfig.assessment_type || '') + ' data available'
        },
        drawCallback: function() {
            bindFlagButtons();
        }
    });

    // ------------------------------------------------------------
    //Flag button binding
    // ------------------------------------------------------------
    function bindFlagButtons() {
        $('.sbfp-flag-btn').off('click').on('click', function() {
            var btn = $(this);
            var group = btn.closest('.sbfp-flag-group');
            var assessmentId = group.data('assessment-id');
            var field = btn.data('field');
            var value = btn.data('value');

            if (!assessmentId || !field) return;

            var isAlreadySelected = btn.hasClass('btn-primary');
            var newValue = isAlreadySelected ? '' : value;
            var isClearing = isAlreadySelected;

            var buttons = group.find('.sbfp-flag-btn');
            buttons.prop('disabled', true);

            if (isClearing) {
                buttons.removeClass('btn-primary btn-outline-secondary d-none').addClass('btn-outline-secondary');
            } else {
                buttons.removeClass('btn-primary').addClass('btn-outline-secondary');
                buttons.not(btn).addClass('d-none');
                btn.removeClass('btn-outline-secondary').addClass('btn-primary');
            }

            $.ajax({
                url: window.SbfpBeneficiariesConfig.urls.update_flag,
                method: 'POST',
                data: { id: assessmentId, field: field, value: newValue },
                dataType: 'json',
                success: function(resp) {
                    console.log('Flag updated:', resp);
                    if (newValue !== '') {
                        try { localStorage.setItem('sbfp_flag_' + assessmentId + '_' + field, newValue); } catch(e) {}
                    } else {
                        try { localStorage.removeItem('sbfp_flag_' + assessmentId + '_' + field); } catch(e) {}
                    }
                },
                error: function(xhr, status, err) {
                    console.warn('Flag update error:', err);
                    // Revert UI on error
                    if (isClearing) {
                        buttons.removeClass('btn-outline-secondary').addClass('btn-primary');
                        buttons.not(btn).removeClass('d-none').addClass('btn-outline-secondary');
                    } else {
                        buttons.removeClass('btn-primary').addClass('btn-outline-secondary');
                        buttons.removeClass('d-none');
                    }
                    showNotification('Failed to update. Please try again.', 'danger');
                },
                complete: function() {
                    buttons.prop('disabled', false);
                }
            });
        });
    }

    // Initial binding
    bindFlagButtons();

    // ------------------------------------------------------------
    // Apply Filters 
    // ------------------------------------------------------------
    function applyFilters() {
        var gradeLevel = $('#gradeLevelFilter').val();
        var schoolName = $('#schoolNameFilter').length ? $('#schoolNameFilter').val() : '';
        var district = $('#districtFilter').length ? $('#districtFilter').val() : '';
        var sectionId = $('#sectionFilter').val();

        showLoadingOverlay();

        var userRole = window.SbfpBeneficiariesConfig.user_role;

        function setFilter(url, data, callback) {
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                dataType: 'json',
                success: callback,
                error: function() {
                    hideLoadingOverlay();
                    showNotification('Error applying filter', 'danger');
                }
            });
        }

        setFilter(window.SbfpBeneficiariesConfig.urls.set_grade_level_filter, { grade_level: gradeLevel }, function() {
            if (schoolName && $('#schoolNameFilter').length && 
                (userRole === 'district' || userRole === 'division' || userRole === 'admin')) {
                setFilter(window.SbfpBeneficiariesConfig.urls.set_school_name_filter, { school_name: schoolName }, function() {
                    if (district && $('#districtFilter').length && 
                        (userRole === 'division' || userRole === 'admin')) {
                        setFilter(window.SbfpBeneficiariesConfig.urls.set_district_filter, { district: district }, function() {
                            table.ajax.reload();
                            hideLoadingOverlay();
                            showNotification('Filters applied', 'success');
                        });
                    } else {
                        table.ajax.reload();
                        hideLoadingOverlay();
                        showNotification('Filters applied', 'success');
                    }
                });
            } else {
                table.ajax.reload();
                hideLoadingOverlay();
                showNotification('Filters applied', 'success');
            }
        });
    }

    // ------------------------------------------------------------
    // Clear Filters
    // ------------------------------------------------------------
    function clearAllFilters() {
        showLoadingOverlay();
        
        $.ajax({
            url: window.SbfpBeneficiariesConfig.urls.clear_filters,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#gradeLevelFilter').val('');
                    if ($('#schoolNameFilter').length) $('#schoolNameFilter').val('');
                    if ($('#districtFilter').length) $('#districtFilter').val('');
                    $('#sectionFilter').val('');
                    
                    $.ajax({
                        url: window.SbfpBeneficiariesConfig.urls.set_school_level,
                        method: 'POST',
                        data: { school_level: 'all' },
                        dataType: 'json',
                        success: function() {
                            window.location.reload();
                        },
                        error: function() {
                            window.location.reload();
                        }
                    });
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

    // ------------------------------------------------------------
    // Event bindings for filter controls
    // ------------------------------------------------------------
    $('#applyFiltersBtn').click(applyFilters);
    $('#clearFiltersBtn').click(clearAllFilters);
    $('#gradeLevelFilter, #sectionFilter').change(applyFilters);
    if ($('#schoolNameFilter').length) {
        $('#schoolNameFilter').change(applyFilters);
    }
    if ($('#districtFilter').length) {
        $('#districtFilter').change(applyFilters);
    }

    // ------------------------------------------------------------
    // Assessment type switch (page reload)
    // ------------------------------------------------------------
    $('#assessmentTypeSelect').on('change', function() {
        var newType = $(this).val();
        $.ajax({
            url: window.SbfpBeneficiariesConfig.urls.set_assessment_type,
            method: 'POST',
            data: { assessment_type: newType },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error switching assessment type. Please try again.');
            }
        });
    });

    // ------------------------------------------------------------
    // Export and Print 
    // ------------------------------------------------------------
    $('#exportExcelBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');

        var overrides = {};
        try {
            for (var i = 0; i < localStorage.length; i++) {
                var key = localStorage.key(i);
                if (!key) continue;
                if (key.indexOf('sbfp_flag_') === 0) {
                    var parts = key.split('_');
                    if (parts.length >= 4) {
                        var id = parts[2];
                        var field = parts.slice(3).join('_');
                        var val = localStorage.getItem(key);
                        if (!overrides[id]) overrides[id] = {};
                        overrides[id][field] = val;
                    }
                }
            }
        } catch (ex) {
            console.warn('Could not read localStorage for export overrides', ex);
        }

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.SbfpBeneficiariesConfig.urls.export_excel;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'local_flags';
        input.value = JSON.stringify(overrides);
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();

        setTimeout(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-file-excel me-1"></i> Export to Excel');
        }, 3000);
    });

    $('#printForm').on('submit', function() {
        var overrides = {};
        try {
            for (var i = 0; i < localStorage.length; i++) {
                var key = localStorage.key(i);
                if (!key) continue;
                if (key.indexOf('sbfp_flag_') === 0) {
                    var parts = key.split('_');
                    if (parts.length >= 4) {
                        var id = parts[2];
                        var field = parts.slice(3).join('_');
                        var val = localStorage.getItem(key);
                        if (!overrides[id]) overrides[id] = {};
                        overrides[id][field] = val;
                    }
                }
            }
        } catch (ex) {
            console.warn('Could not read localStorage for print overrides', ex);
        }
        $('#printLocalFlags').val(JSON.stringify(overrides));
    });

    // ------------------------------------------------------------
    //Utility functions (loading overlay, notifications)
    // ------------------------------------------------------------
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

    // ------------------------------------------------------------
    //Remove filter 
    // ------------------------------------------------------------
    function removeFilter(filterType) {
        switch(filterType) {
            case 'grade':
                $('#gradeLevelFilter').val('');
                break;
            case 'school':
                if ($('#schoolNameFilter').length) $('#schoolNameFilter').val('');
                break;
            case 'district':
                if ($('#districtFilter').length) $('#districtFilter').val('');
                break;
            case 'section':
                if ($('#sectionFilter').length) $('#sectionFilter').val('');
                break;
        }
        applyFilters();
    }
    window.removeFilter = removeFilter;

    // ------------------------------------------------------------
    // Dynamic Section filter based on Grade Level
    // ------------------------------------------------------------
    $('#gradeLevelFilter').on('change', function() {
        var grade = $(this).val();
        if (grade) {
            $('#sectionFilter').prop('disabled', true).html('<option value="">Loading...</option>');

            $.ajax({
                url: window.SbfpBeneficiariesConfig.urls.get_sections_by_grade,
                method: 'POST',
                data: { grade_level: grade },
                dataType: 'json',
                success: function(response) {
                    var options = '<option value="">All Sections</option>';
                    if (response.sections && response.sections.length > 0) {
                        $.each(response.sections, function(index, sec) {
                            var selected = (sec.id == response.selected) ? 'selected' : '';
                            options += '<option value="' + sec.id + '" ' + selected + '>' + sec.section + '</option>';
                        });
                    } else {
                        options = '<option value="">No sections found for this grade</option>';
                    }
                    $('#sectionFilter').html(options).prop('disabled', false);
                },
                error: function() {
                    $('#sectionFilter').html('<option value="">Error loading sections</option>').prop('disabled', false);
                }
            });
        } else {
            window.location.reload();
        }
    });
});