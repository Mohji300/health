/* Externalized JS for sbfp_dashboard.php — reads runtime config from window.sbfp_dashboard_controllerConfig */
$(document).ready(function() {
    console.log('SBFP Dashboard JS loaded');
    console.log('Current assessment type:', window.sbfp_dashboard_controllerConfig.assessment_type || '');

    $('#switchToBaseline').click(function() { console.log('Switching to baseline...'); switchAssessmentType('baseline'); });
    $('#switchToMidline').click(function() { console.log('Switching to midline...'); switchAssessmentType('midline'); });
    $('#switchToEndline').click(function() { console.log('Switching to endline...'); switchAssessmentType('endline'); });

    function switchAssessmentType(type) {
        $.ajax({
            url: window.sbfp_dashboard_controllerConfig.urls.set_assessment_type,
            method: 'POST',
            data: { assessment_type: type },
            dataType: 'json',
            success: function(response) {
                console.log('Switch response:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Switch AJAX error:', error);
                console.error('Response:', xhr.responseText);
                alert('AJAX error. Check console for details.');
            }
        });
    }

    $('.delete-assessment, .delete-assessment-list').click(function() {
        var grade = $(this).data('grade');
        var section = $(this).data('section');
        var school_year = $(this).data('school_year');
        var type = $(this).data('type');

        $('#deleteGrade').text(grade);
        $('#deleteSection').text(section);
        $('#deleteSchoolYear').text(school_year);

        $('#deleteAssessmentModal').data('grade', grade);
        $('#deleteAssessmentModal').data('section', section);
        $('#deleteAssessmentModal').data('school_year', school_year);
        $('#deleteAssessmentModal').data('type', type);

        var deleteModal = new bootstrap.Modal(document.getElementById('deleteAssessmentModal'));
        deleteModal.show();
    });

    // Handle remove section button clicks
    $('.remove-section-btn').click(function() {
        var sectionId = $(this).data('section-id');
        var grade = $(this).data('grade');
        var section = $(this).data('section');
        var schoolYear = $(this).data('school_year');

        $('#removeGrade').text(grade);
        $('#removeSection').text(section);
        $('#removeSchoolYear').text(schoolYear);
        $('#removeSectionId').val(sectionId);

        var removeModal = new bootstrap.Modal(document.getElementById('removeSectionModal'));
        removeModal.show();
    });

    // Handle delete confirmation
    $('#confirmDeleteBtn').click(function() {
        var modal = $('#deleteAssessmentModal');
        var grade = modal.data('grade');
        var section = modal.data('section');
        var school_year = modal.data('school_year');
        var type = modal.data('type');

        if (!grade || !section || !type) {
            alert('Error: Missing assessment data');
            return;
        }

        var button = $(this);
        var originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
        button.prop('disabled', true);

        $.ajax({
            url: window.sbfp_dashboard_controllerConfig.urls.delete_assessment,
            method: 'POST',
            data: {
                grade: grade,
                section: section,
                school_year: school_year,
                assessment_type: type
            },
            dataType: 'json',
            success: function(response) {
                // Removed console.log for delete response
                if (response.success) {
                    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteAssessmentModal'));
                    deleteModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    button.html(originalText);
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete AJAX error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error communicating with server. Check console for details.');
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });

    // Toggle Lock functionality
    $('.toggle-lock').click(function() {
        var grade = $(this).data('grade');
        var section = $(this).data('section');
        var school_year = $(this).data('school_year');
        var type = $(this).data('type');
        var button = $(this);

        var originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i>');
        button.prop('disabled', true);

        $.ajax({
            url: window.sbfp_dashboard_controllerConfig.urls.toggle_lock,
            method: 'POST',
            data: {
                grade: grade,
                section: section,
                school_year: school_year,
                assessment_type: type
            },
            dataType: 'json',
            success: function(response) {
                console.log('Lock response:', response);
                if (response.success) {
                    if (button.find('i').hasClass('fa-lock')) {
                        button.html('<i class="fas fa-unlock"></i>');
                        button.removeClass('btn-warning').addClass('btn-success');
                    } else {
                        button.html('<i class="fas fa-lock"></i>');
                        button.removeClass('btn-success').addClass('btn-warning');
                    }
                    alert('Assessment ' + response.message.toLowerCase());
                } else {
                    alert('Error: ' + response.message);
                    button.html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                console.error('Lock AJAX error:', error);
                alert('Error communicating with server');
                button.html(originalHtml);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});