<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/superadmin_dashboard.css'); ?>">
  </head>
  <body class="bg-light">
    <div id="wrapper">
      <div id="page-content-wrapper">
        <div class="container-fluid py-4">

          <div class="card bg-gradient-primary text-white mb-4">
            <div class="card-body">
              <h1 class="h2 font-weight-bold mb-2">Super Admin Dashboard</h1>
              <p class="mb-0 opacity-8">Manage users, districts, and system-wide settings</p>
            </div>
          </div>

          <!-- Stats Cards -->
          <div class="row">
            <!-- Total Users -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                        Total Users
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($userCounts['total']); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Super Admins -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
              <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                        Super Admins
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($userCounts['super_admins']); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Admins -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                        Admins
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($userCounts['admins']); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-cog fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- District Users -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                        District Users
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($userCounts['district']); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Division Users -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                        Division Users
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($userCounts['division']); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-building fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Regular Users -->
            <div class="col-xl-2 col-md-4 col-sm-6 mb-4">
              <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                        Regular Users
                      </div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">
                        <?php echo number_format($userCounts['users']); ?>
                      </div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-user fa-2x text-gray-300"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Management Section -->
          <div class="row">
            <!-- User Management -->
            <div class="col-lg-8 mb-4">
              <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                  <h6 class="m-0 font-weight-bold text-primary">User Management</h6>
                  <div class="d-flex align-items-center">
                    <span class="badge bg-primary rounded-pill me-3">
                      <?php echo number_format($userCounts['total']); ?> Users
                    </span>
                    <a href="<?php echo site_url('superadmin/add-user'); ?>" class="btn btn-primary btn-sm me-2">
                      <i class="fas fa-user-plus me-1"></i> Add New User
                    </a>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#updateAllModal">
                      <i class="fas fa-sync-alt me-1"></i> Update All Roles
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#resetAllSchoolInfoModal">
                      <i class="fas fa-undo-alt me-1"></i> Reset All School Info
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#resetNutritionalModal">
                      <i class="fas fa-trash-alt me-1"></i> Reset Nutritional Data
                    </button>
                  </div>
                </div>
                <div class="card-body">
                  <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                      <?php echo $this->session->flashdata('success'); ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                  <?php endif; ?>
                  
                  <?php if ($this->session->flashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <?php echo $this->session->flashdata('error'); ?>
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                  <?php endif; ?>

                  <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="userTable" width="100%" cellspacing="0">
                      <thead class="table-light">
                        <tr>
                          <th>Name</th>
                          <th>Email</th>
                          <th>District</th>
                          <th>Role</th>
                          <th class="text-center">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?php echo $user->id; ?>">
                          <td class="fw-bold"><?php echo htmlspecialchars($user->name ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($user->email ?? 'N/A'); ?></td>
                          <td><?php echo htmlspecialchars($user->school_district ?? $user->district ?? $user->school_district_name ?? 'N/A'); ?></td>
                          <td>
                            <form method="post" action="<?php echo site_url('superadmin/update_user_role/' . $user->id); ?>" class="d-inline">
                              <select name="role" class="form-select form-select-sm role-select" onchange="this.form.submit()">
                                <?php foreach ($availableRoles as $role): ?>
                                <option value="<?php echo $role; ?>" <?php echo $user->role == $role ? 'selected' : ''; ?>>
                                  <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                                </option>
                                <?php endforeach; ?>
                              </select>
                            </form>
                          </td>
                          <td>
                            <div class="d-flex justify-content-center">
                              <a href="<?php echo site_url('superadmin/edit-user/' . $user->id); ?>" class="btn btn-info btn-sm me-1">
                                <i class="fas fa-edit me-1"></i> Edit
                              </a>
                              <button type="button" class="btn btn-warning btn-sm reset-user-btn me-1" 
                                      data-user-id="<?php echo $user->id; ?>" 
                                      data-user-name="<?php echo htmlspecialchars($user->name ?? 'N/A'); ?>"
                                      data-bs-toggle="modal" 
                                      data-bs-target="#resetUserModal">
                                <i class="fas fa-undo me-1"></i> Reset
                              </button>
                              <button type="button" class="btn btn-danger btn-sm delete-user-btn" 
                                      data-user-id="<?php echo $user->id; ?>" 
                                      data-user-name="<?php echo htmlspecialchars($user->name ?? 'N/A'); ?>"
                                      data-bs-toggle="modal" 
                                      data-bs-target="#deleteUserModal">
                                <i class="fas fa-trash me-1"></i> Delete
                              </button>
                            </div>
                          </td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Update All Modal -->
    <div class="modal fade" id="updateAllModal" tabindex="-1" aria-labelledby="updateAllModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateAllModalLabel">Update All Roles</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to update all user roles? This action cannot be undone.</p>
            <p class="small text-muted">You can either submit an explicit payload (collects current role selections) or perform a server-driven update.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

            <!-- Form that will submit a JSON payload of users -->
            <form id="bulkUpdateForm" method="post" action="<?php echo site_url('superadmin/update_all_roles'); ?>">
              <input type="hidden" name="users" id="bulkUsersInput" value="">
              <button type="button" id="submitBulkPayload" class="btn btn-info">Submit Payload</button>
              <button type="submit" name="server_driven" value="1" class="btn btn-success">Server-driven Update</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetUserModal" tabindex="-1" aria-labelledby="resetUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="resetUserModalLabel">Confirm Reset User Data</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
            <div class="modal-body">
                <p>Are you sure you want to reset the <strong>school_info_completed</strong> flag for 
                  <span id="resetUserNamePlaceholder" class="fw-bold"></span>?</p>
                <p>This will set the user's school information status to "incomplete", allowing them to go through the 
                  school setup process again. <strong>No uploaded files or other data will be removed.</strong></p>
                <p class="text-warning fw-bold">Only the completion flag will be reset – all existing school data remains intact.</p>
            </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <form id="resetUserForm" method="post" action="">
              <button type="submit" class="btn btn-warning">Reset</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset All School Info Modal -->
    <div class="modal fade" id="resetAllSchoolInfoModal" tabindex="-1" aria-labelledby="resetAllSchoolInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="resetAllSchoolInfoModalLabel">Reset All School Info Flags</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to reset <strong>school_info_completed</strong> to <strong>0</strong> for <strong>ALL users</strong>?</p>
            <p class="text-danger fw-bold">This action cannot be undone. All users will be required to complete the school information setup again.</p>
            <p class="text-warning">No other user data (uploads, profiles, etc.) will be affected – only the completion flag.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <form id="resetAllSchoolInfoForm" method="post" action="<?php echo site_url('superadmincontroller/reset_all_school_info'); ?>">
              <button type="submit" class="btn btn-warning">Reset All</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Reset Nutritional Data Modal -->
    <div class="modal fade" id="resetNutritionalModal" tabindex="-1" aria-labelledby="resetNutritionalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetNutritionalModalLabel">Delete All Nutritional Assessment Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to <strong class="text-danger">PERMANENTLY DELETE ALL</strong> records from the <code>nutritional_assessments</code> table?</p>
                    <p>This action <strong>cannot be undone</strong>. All student assessments, SBFP beneficiaries, and associated data will be lost.</p>
                    <p class="text-warning fw-bold">Back up your data before proceeding.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" action="<?php echo site_url('superadmincontroller/delete_all_nutritional_assessments'); ?>">
                        <button type="submit" class="btn btn-danger">Delete All Permanently</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteUserModalLabel">Confirm User Deletion</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete <span id="userNamePlaceholder" class="fw-bold"></span>? This action cannot be undone.</p>
            <p class="text-danger fw-bold">All data associated with this user will be permanently removed.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <form id="deleteUserForm" method="post" action="">
              <button type="submit" class="btn btn-danger">Delete User</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
    window.SuperAdminConfig = {
      delete_user_base: '<?= site_url("superadmincontroller/delete_user/"); ?>',
      reset_user_base: '<?= site_url("superadmincontroller/reset_user_data/"); ?>'
    };
    </script>
    <script src="<?= base_url('assets/js/superadmin_dashboard.js'); ?>"></script>
  </body>
</html>