<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// Expected variables (optional):
// $title (string), $current_filters (array), $reports (array)
$title = isset($title) ? $title : (isset($title_text) ? $title_text : 'Reports');
$current_filters = isset($current_filters) ? $current_filters : ([]);
$reports = isset($reports) ? $reports : ([]);

// Determine role and whether regular user
$role = $this->session->userdata('role');
$reports_base = (in_array($role, ['admin', 'super_admin', 'district', 'division'])) ? 'admin/reports' : 'user/reports';
$is_regular_user = !in_array($role, ['admin', 'super_admin', 'district', 'division']);
?>

<!-- Reports Header -->
<div class="card bg-gradient-primary text-white mb-4">
  <div class="card-body">
    <h1 class="h2 font-weight-bold mb-2"><?php echo htmlspecialchars($title); ?></h1>
    <?php if (!empty($current_filters['school_name'])): ?>
      <p class="mb-0 opacity-8">Showing reports for <strong><?php echo htmlspecialchars($current_filters['school_name']); ?></strong></p>
    <?php else: ?>
      <p class="mb-0 opacity-8">View and analyze all submitted assessment data</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($is_regular_user && !empty($current_filters['school_name'])): ?>
  <div class="row mb-4">
    <div class="col-12">
      <div class="card shadow border-left-info">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                  <i class="fas fa-school text-info fa-2x"></i>
                </div>
                <div>
                  <small class="text-muted text-uppercase">School</small>
                  <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($current_filters['school_name']); ?></h5>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                  <i class="fas fa-map-marker-alt text-success fa-2x"></i>
                </div>
                <div>
                  <small class="text-muted text-uppercase">School District</small>
                  <h5 class="mb-0 fw-bold">
                    <?php $school_district_display = !empty($reports) ? ($reports[0]->school_district ?? 'N/A') : 'N/A';
                    echo htmlspecialchars($school_district_display);
                    ?>
                  </h5>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                  <i class="fas fa-landmark text-primary fa-2x"></i>
                </div>
                <div>
                  <small class="text-muted text-uppercase">Legislative District</small>
                  <h5 class="mb-0 fw-bold">
                    <?php $legislative_district_display = !empty($reports) ? ($reports[0]->legislative_district ?? 'N/A') : 'N/A';
                    echo htmlspecialchars($legislative_district_display);
                    ?>
                  </h5>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
