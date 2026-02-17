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

    $('#btnPrint').click(function() { window.print(); });
});
