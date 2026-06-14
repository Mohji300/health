<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SHD Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4 position-relative">

          
<body class="bg-light">
<div class="d-flex" id="wrapper">
    <?php $this->load->view('templates/sidebar'); ?>
    <div id="page-content-wrapper" class="w-100">
                  <?php
            // Reusable header partial
            $header_data = [
              'title' => 'SHD Reports',
              'current_filters' => isset($current_filters) ? $current_filters : [],
              'reports' => isset($reports) ? $reports : []
            ];
            $this->load->view('templates/default_header', $header_data);
          ?>
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>SHD Reports</h2>
                <button class="btn btn-success" id="btnCreateReport">
                    <i class="fas fa-plus"></i> Create School Report
                </button>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>School Name</th>
                                <th>School Year</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $i=1; foreach ($reports as $r): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($r->school_name) ?></td>
                                <td><?= htmlspecialchars($r->school_year) ?></td>
                                <td><?= date('M j, Y', strtotime($r->created_at)) ?></td>
                                <td>
                                    <a href="<?= site_url('shd_reports_controller/report_entry/'.$r->id) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Enter Data
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($reports)): ?>
                            <tr><td colspan="5" class="text-center">No reports found. Click "Create School Report".</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for creating new school report -->
<div class="modal fade" id="createReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create School Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="schoolName" class="form-label">School Name</label>
                    <input type="text" class="form-control" id="schoolName" required>
                </div>
                <div class="mb-3">
                    <label for="schoolYear" class="form-label">School Year</label>
                    <input type="text" class="form-control" id="schoolYear" placeholder="e.g., 2025-2026" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmCreate">Create</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){
    $('#btnCreateReport').click(function(){
        $('#createReportModal').modal('show');
    });

    $('#confirmCreate').click(function(){
        var schoolName = $('#schoolName').val().trim();
        var schoolYear = $('#schoolYear').val().trim();
        if(!schoolName || !schoolYear){
            alert('Please fill all fields.');
            return;
        }
        $.ajax({
            url: '<?= site_url("shd_reports_controller/create_school_report") ?>',
            type: 'POST',
            data: { school_name: schoolName, school_year: schoolYear },
            dataType: 'json',
            success: function(res){
                if(res.status === 'success'){
                    window.location.href = '<?= site_url("shd_reports_controller/report_entry/") ?>' + res.report_id;
                } else {
                    alert(res.message);
                }
            },
            error: function(){
                alert('Server error. Please try again.');
            }
        });
    });
});
</script>
</body>
</html>