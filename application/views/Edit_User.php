<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
        <a href="<?php echo site_url('superadmin'); ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                </div>
                <div class="card-body">
                    <?php if (validation_errors()): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo validation_errors(); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($this->session->flashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $this->session->flashdata('error'); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo site_url('superadmin/edit-user/' . $user->id); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="font-weight-bold text-primary">Full Name *</label>
                                    <input type="text" class="form-control <?php echo form_error('name') ? 'is-invalid' : ''; ?>" 
                                           id="name" name="name" value="<?php echo set_value('name', $user->name); ?>" 
                                           placeholder="Enter full name" required>
                                    <?php if (form_error('name')): ?>
                                        <div class="invalid-feedback"><?php echo form_error('name'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="font-weight-bold text-primary">Email Address *</label>
                                    <input type="email" class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>" 
                                           id="email" name="email" value="<?php echo set_value('email', $user->email); ?>" 
                                           placeholder="Enter email address" required>
                                    <?php if (form_error('email')): ?>
                                        <div class="invalid-feedback"><?php echo form_error('email'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role" class="font-weight-bold text-primary">User Role *</label>
                                    <select class="form-control <?php echo form_error('role') ? 'is-invalid' : ''; ?>" 
                                            id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <?php foreach ($availableRoles as $role): ?>
                                            <option value="<?php echo $role; ?>" <?php echo set_select('role', $role, $user->role == $role); ?>>
                                                <?php echo ucwords(str_replace('_', ' ', $role)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (form_error('role')): ?>
                                        <div class="invalid-feedback"><?php echo form_error('role'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="school_id" class="font-weight-bold text-primary">School ID</label>
                                    <input type="text" class="form-control" id="school_id" name="school_id" 
                                           value="<?php echo set_value('school_id', $user->school_id); ?>" placeholder="Enter school ID">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3 font-weight-bold text-warning">Change Password (Optional)</h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password" class="font-weight-bold text-primary">New Password</label>
                                    <input type="password" class="form-control <?php echo form_error('password') ? 'is-invalid' : ''; ?>" 
                                           id="password" name="password" placeholder="Leave blank to keep current password">
                                    <small class="form-text text-muted">If you want to change the password, enter a new one (minimum 8 characters).</small>
                                    <?php if (form_error('password')): ?>
                                        <div class="invalid-feedback"><?php echo form_error('password'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password" class="font-weight-bold text-primary">Confirm Password</label>
                                    <input type="password" class="form-control <?php echo form_error('confirm_password') ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                                    <?php if (form_error('confirm_password')): ?>
                                        <div class="invalid-feedback"><?php echo form_error('confirm_password'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="legislative_district" class="font-weight-bold text-primary">Legislative District</label>
                                    <input type="text" class="form-control" id="legislative_district" name="legislative_district" 
                                           value="<?php echo set_value('legislative_district', $user->legislative_district); ?>" placeholder="Enter legislative district">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="school_district" class="font-weight-bold text-primary">School District</label>
                                    <input type="text" class="form-control" id="school_district" name="school_district" 
                                           value="<?php echo set_value('school_district', $user->school_district); ?>" placeholder="Enter school district">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                            <a href="<?php echo site_url('superadmin'); ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- User Info Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">User Details</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>User ID:</strong> <?php echo htmlspecialchars($user->id); ?></p>
                            <p><strong>Created:</strong> <?php echo htmlspecialchars($user->created_at); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($user->updated_at); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
