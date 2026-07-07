// sbfp_beneficiaries.js – Server‑side pagination with DataTables
$(document).ready(function() {
    // ------------------------------------------------------------
    // 1. DataTable initialization (server-side)
    // ------------------------------------------------------------
    var table = $('#beneficiariesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.SbfpBeneficiariesConfig.urls.datatable,
            type: 'POST',
            cache: false,
            data: function(d) {
                // Pass section filter (and any other GET parameters)
                d.section_id = $('#sectionFilter').val() || '';
                // The rest (grade, school, district) are stored in session
                // and will be applied on the server side.
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
        order: [[1, 'asc']], // default sort by name
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
            // Re‑bind flag button events after each redraw
            bindFlagButtons();
        }
    });

    // ------------------------------------------------------------
    // 2. Flag button binding (works with dynamic rows)
    // ------------------------------------------------------------
    function bindFlagButtons() {
        $('.sbfp-flag-btn').off('click').on('click', function() {
            var btn = $(this);
            var group = btn.closest('.sbfp-flag-group');
            var assessmentId = group.data('assessment-id');
            var field = btn.data('field');
            var value = btn.data('value');

            if (!assessmentId || !field) return;

            // Store in localStorage (optional, for export/print overrides)
            try {
                localStorage.setItem('sbfp_flag_' + assessmentId + '_' + field, value);
            } catch (e) {}

            // UI update
            var buttons = group.find('.sbfp-flag-btn');
            buttons.prop('disabled', true);
            buttons.removeClass('btn-primary').addClass('btn-outline-secondary');
            buttons.not(btn).addClass('d-none');
            btn.removeClass('btn-outline-secondary').removeClass('d-none').addClass('btn-primary');

            // AJAX update
            $.ajax({
                url: window.SbfpBeneficiariesConfig.urls.update_flag,
                method: 'POST',
                data: { id: assessmentId, field: field, value: value },
                dataType: 'json',
                success: function(resp) {
                    console.log('Flag updated:', resp);
                },
                error: function(xhr, status, err) {
                    console.warn('Flag update error:', err);
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
    // 3. Apply Filters (AJAX to session + reload table)
    // ------------------------------------------------------------
    function applyFilters() {
        var gradeLevel = $('#gradeLevelFilter').val();
        var schoolName = $('#schoolNameFilter').length ? $('#schoolNameFilter').val() : '';
        var district = $('#districtFilter').length ? $('#districtFilter').val() : '';
        var sectionId = $('#sectionFilter').val();

        showLoadingOverlay();

        var userRole = window.SbfpBeneficiariesConfig.user_role;

        // Helper to set a filter via AJAX
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

        // Chain filter settings
        setFilter(window.SbfpBeneficiariesConfig.urls.set_grade_level_filter, { grade_level: gradeLevel }, function() {
            if (schoolName && $('#schoolNameFilter').length && 
                (userRole === 'district' || userRole === 'division' || userRole === 'admin')) {
                setFilter(window.SbfpBeneficiariesConfig.urls.set_school_name_filter, { school_name: schoolName }, function() {
                    if (district && $('#districtFilter').length && 
                        (userRole === 'division' || userRole === 'admin')) {
                        setFilter(window.SbfpBeneficiariesConfig.urls.set_district_filter, { district: district }, function() {
                            // All filters saved, reload table
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
    // 4. Clear Filters
    // ------------------------------------------------------------
    function clearAllFilters() {
        showLoadingOverlay();
        
        // First, clear the regular filters
        $.ajax({
            url: window.SbfpBeneficiariesConfig.urls.clear_filters,
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reset dropdowns
                    $('#gradeLevelFilter').val('');
                    if ($('#schoolNameFilter').length) $('#schoolNameFilter').val('');
                    if ($('#districtFilter').length) $('#districtFilter').val('');
                    $('#sectionFilter').val('');
                    
                    // Explicitly set school level to 'all' via AJAX
                    $.ajax({
                        url: window.SbfpBeneficiariesConfig.urls.set_school_level,
                        method: 'POST',
                        data: { school_level: 'all' },
                        dataType: 'json',
                        success: function(levelResponse) {
                            // Reload the page after school level is set
                            window.location.reload();
                        },
                        error: function() {
                            // Still reload even if school level set fails
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
    // 5. Event bindings for filter controls
    // ------------------------------------------------------------
    $('#applyFiltersBtn').click(applyFilters);
    $('#clearFiltersBtn').click(clearAllFilters);

    // Auto‑apply on change (optional – you may keep or remove)
    $('#gradeLevelFilter, #sectionFilter').change(applyFilters);
    if ($('#schoolNameFilter').length) {
        $('#schoolNameFilter').change(applyFilters);
    }
    if ($('#districtFilter').length) {
        $('#districtFilter').change(applyFilters);
    }

    // ------------------------------------------------------------
    // 6. Assessment type switch (page reload)
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
                    window.location.reload(); // Reload page to reset DataTable with new type
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
    // 7. Export and Print (keep existing logic)
    // ------------------------------------------------------------
    $('#exportExcelBtn').on('click', function(e) {
        e.preventDefault();
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
    // 8. Utility functions (loading overlay, notifications)
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
    // 9. Remove filter (via active filter badge X buttons)
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
        applyFilters(); // will reload table
    }
    // Make removeFilter globally accessible for inline onclick
    window.removeFilter = removeFilter;

    // ------------------------------------------------------------
    // 10. Initialize local flags from localStorage (for UI consistency)
    // ------------------------------------------------------------
    (function initializeLocalFlags() {
        $('.sbfp-flag-group').each(function() {
            var group = $(this);
            var assessmentId = group.data('assessment-id');
            if (!assessmentId) return;
            group.find('.sbfp-flag-btn').each(function() {
                var b = $(this);
                var field = b.data('field');
                if (!field) return;
                try {
                    var stored = localStorage.getItem('sbfp_flag_' + assessmentId + '_' + field);
                    if (stored && stored !== '') {
                        var normalizedStored = String(stored).toLowerCase();
                        group.find('.sbfp-flag-btn').each(function() {
                            var btn = $(this);
                            var btnVal = String(btn.data('value') || '').toLowerCase();
                            if (btnVal === normalizedStored) {
                                btn.removeClass('btn-outline-secondary').addClass('btn-primary').removeClass('d-none');
                            } else {
                                btn.addClass('d-none').removeClass('btn-primary').addClass('btn-outline-secondary');
                            }
                        });
                    }
                } catch (e) {
                    // ignore
                }
            });
        });
    })();

    // ------------------------------------------------------------
    // 11. Dynamic Section filter based on Grade Level
    // ------------------------------------------------------------
    $('#gradeLevelFilter').on('change', function() {
        var grade = $(this).val();
        if (grade) {
            // Show loading state if desired
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
            // If grade is cleared, reset section dropdown to all sections (or reload page)
            // Optionally, you could reload the original sections list via another AJAX call or simply reload the page.
            // Simpler: reload the page with current filters
            window.location.reload();
        }
    });
});