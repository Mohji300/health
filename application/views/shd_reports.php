<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SHD Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/shd_reports.css'); ?>">
  </head>
  <body class="bg-light">
    <div class="d-flex" id="wrapper">
      <?php $this->load->view('templates/sidebar'); ?>

      <div id="page-content-wrapper" class="w-100">
        <div class="container-fluid py-4 position-relative">

          

          <?php
            // Reusable header partial
            $header_data = [
              'title' => 'SHD Reports',
              'current_filters' => isset($current_filters) ? $current_filters : [],
              'reports' => isset($reports) ? $reports : []
            ];
            $this->load->view('templates/default_header', $header_data);
          ?>

          <!-- Action Buttons (below header) -->
          <div class="shd-action-buttons mb-3">
            <div class="btn-group" role="group" aria-label="SHD Actions">
              <button id="btnStudents" class="btn btn-primary btn-sm">
                <i class="fas fa-user-graduate me-1"></i> Students
              </button>
              <button id="btnTeachers" class="btn btn-secondary btn-sm">
                <i class="fas fa-chalkboard-teacher me-1"></i> Teachers
              </button>
            </div>
          </div>

          <div class="card mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <strong>Total Reports:</strong> <?php echo number_format($total_reports ?? 0); ?>
                </div>
              </div>

              <?php if (empty($reports)): ?>
                <div class="text-center py-5 shd-empty">
                  <i class="fas fa-inbox fa-3x mb-3"></i>
                  <h5 class="text-muted">No SHD reports found.</h5>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-bordered table-hover" id="shdReportsTable">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>School</th>
                        <th>Grade</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i=1; foreach ($reports as $r): ?>
                      <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($r->name ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($r->school_name ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($r->grade_level ?? 'N/A'); ?></td>
                        <td><?php echo !empty($r->date_of_weighing) ? date('M j, Y', strtotime($r->date_of_weighing)) : 'N/A'; ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/shd_reports.js'); ?>"></script>
  </body>
</html>
