<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/school-info.css'); ?>">
</head>
<body class="gradient-bg">
    <div class="container py-5">
        <div class="form-container mx-auto p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="school-icon">
                    <?php if ($user_role == 'division'): ?>
                        <i class="fas fa-landmark"></i>
                    <?php elseif ($user_role == 'district'): ?>
                        <i class="fas fa-building"></i>
                    <?php else: ?>
                        <i class="fas fa-school"></i>
                    <?php endif; ?>
                </div>
                <h2 class="fw-bold text-dark">
                    <?php 
                    if ($user_role == 'division') {
                        echo 'Complete Division Office Profile';
                    } elseif ($user_role == 'district') {
                        echo 'Complete District Office Profile';
                    } else {
                        echo 'Complete School Profile';
                    }
                    ?>
                </h2>
                <p class="text-muted mb-0">
                    <?php 
                    if ($user_role == 'division') {
                        echo 'Please provide your division office details to continue';
                    } elseif ($user_role == 'district') {
                        echo 'Please provide your district office details to continue';
                    } else {
                        echo 'Please provide your school details to continue using the platform';
                    }
                    ?>
                </p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle me-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">Error saving information</h6>
                            <p class="mb-0"><?php echo $error_message; ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?php echo site_url('school-info/store'); ?>" method="POST" id="schoolForm">
                <div class="space-y-4">
                    <!-- Name Field (Dynamic based on role) -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-<?php echo $user_role == 'division' ? 'landmark' : ($user_role == 'district' ? 'building' : 'school'); ?> text-primary me-1"></i>
                            <?php 
                            if ($user_role == 'division') {
                                echo 'Division Office Name';
                            } elseif ($user_role == 'district') {
                                echo 'District Office Name';
                            } else {
                                echo 'School Name';
                            }
                            ?>
                        </label>
                        <input type="text" 
                               class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                               id="name" 
                               name="name" 
                               value="<?php echo isset($input_data['name']) ? $input_data['name'] : $user->name; ?>" 
                               placeholder="<?php 
                               if ($user_role == 'division') {
                                   echo 'Enter division office name';
                               } elseif ($user_role == 'district') {
                                   echo 'Enter district office name';
                               } else {
                                   echo 'Enter school name';
                               }
                               ?>" 
                               required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Address Field -->
                    <div class="mb-3">
                        <label for="address" class="form-label">
                            <i class="fas fa-map-marker-alt text-primary me-1"></i>
                            <?php echo $user_role == 'division' ? 'Office Address' : 'Address'; ?>
                        </label>
                        <textarea class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" 
                                  id="address" 
                                  name="address" 
                                  rows="3" 
                                  placeholder="<?php echo $user_role == 'division' ? 'Enter complete division office address' : ($user_role == 'district' ? 'Enter complete district office address' : 'Enter complete school address'); ?>" 
                                  required><?php echo isset($input_data['address']) ? $input_data['address'] : ''; ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['address']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Legislative District (Only for school users) -->
                    <?php if ($user_role == 'user'): ?>
                    <div class="mb-3">
                        <label for="legislativeDistricts" class="form-label">
                            <i class="fas fa-map text-primary me-1"></i>
                            Legislative District
                        </label>
                        <select class="form-control <?php echo isset($errors['legislativeDistricts']) ? 'is-invalid' : ''; ?>" 
                                id="legislativeDistricts" 
                                name="legislativeDistricts" 
                                required>
                            <option value="" disabled selected>Select Legislative District</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo htmlspecialchars($district->name); ?>" 
                                    <?php echo (isset($input_data['legislativeDistricts']) && $input_data['legislativeDistricts'] == $district->name) || ($user->legislative_district == $district->name) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['legislativeDistricts'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['legislativeDistricts']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- School District / District Office / Division Office selection -->
                    <?php if ($user_role == 'user'): ?>
                    <div class="mb-3">
                        <label for="SchoolDistricts" class="form-label">
                            <i class="fas fa-school text-primary me-1"></i>
                            School District
                        </label>
                        <select class="form-control <?php echo isset($errors['SchoolDistricts']) ? 'is-invalid' : ''; ?>" 
                                id="SchoolDistricts" 
                                name="SchoolDistricts" 
                                disabled 
                                required>
                            <option value="" disabled selected>Select legislative district first</option>
                        </select>
                        <?php if (isset($errors['SchoolDistricts'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['SchoolDistricts']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($user_role == 'district'): ?>
                    <div class="mb-3">
                        <label for="SchoolDistricts" class="form-label">
                            <i class="fas fa-building text-primary me-1"></i>
                            School District
                        </label>
                        <input type="text"
                               class="form-control <?php echo isset($errors['SchoolDistricts']) ? 'is-invalid' : ''; ?>"
                               id="SchoolDistricts"
                               name="SchoolDistricts"
                               value="<?php echo isset($input_data['SchoolDistricts']) ? $input_data['SchoolDistricts'] : ($user->school_district ? $user->school_district : $user->name); ?>"
                               readonly
                               required>
                        <?php if (isset($errors['SchoolDistricts'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['SchoolDistricts']; ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">
                            This is read-only and mirrors the District Office name
                        </small>
                    </div>
                    <?php elseif ($user_role == 'division'): ?>
                    <div class="mb-3">
                        <label for="SchoolDistricts" class="form-label">
                            <i class="fas fa-landmark text-primary me-1"></i>
                            Division Office
                        </label>
                        <input type="text" 
                               class="form-control <?php echo isset($errors['SchoolDistricts']) ? 'is-invalid' : ''; ?>" 
                               id="SchoolDistricts" 
                               name="SchoolDistricts" 
                               value="<?php echo isset($input_data['SchoolDistricts']) ? $input_data['SchoolDistricts'] : $user->name; ?>" 
                               placeholder="Enter division office name" 
                               readonly>
                        <?php if (isset($errors['SchoolDistricts'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['SchoolDistricts']; ?></div>
                        <?php endif; ?>
                        <small class="form-text text-muted">
                            This will be automatically set to your division office name
                        </small>
                    </div>
                    <?php endif; ?>

                    <!-- Level/Type Field -->
                    <div class="mb-3">
                        <label for="level" class="form-label">
                            <i class="fas fa-sitemap text-primary me-1"></i>
                            Level / Type
                        </label>
                        <select class="form-control <?php echo isset($errors['level']) ? 'is-invalid' : ''; ?>" 
                                id="level" 
                                name="level" 
                                required>
                            <option value="" disabled selected>Select Level / Type</option>
                            <?php if ($user_role == 'division'): ?>
                                <option value="Regional Division" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Regional Division') ? 'selected' : ''; ?>>Regional Division</option>
                                <option value="Provincial Division" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Provincial Division') ? 'selected' : ''; ?>>Provincial Division</option>
                                <option value="City Division" <?php echo (isset($input_data['level']) && $input_data['level'] == 'City Division') ? 'selected' : ''; ?>>City Division</option>
                            <?php elseif ($user_role == 'district'): ?>
                                <option value="Municipal District" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Municipal District') ? 'selected' : ''; ?>>Municipal District</option>
                                <option value="School District" <?php echo (isset($input_data['level']) && $input_data['level'] == 'School District') ? 'selected' : ''; ?>>School District</option>
                                <option value="Provincial District" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Provincial District') ? 'selected' : ''; ?>>Provincial District</option>
                            <?php else: ?>
                                <option value="Elementary" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Elementary') ? 'selected' : ''; ?>>Elementary</option>
                                <option value="Secondary" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Secondary') ? 'selected' : ''; ?>>Secondary</option>
                                <option value="Integrated" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Integrated') ? 'selected' : ''; ?>>Integrated</option>
                                <option value="Stand Alone SHS" <?php echo (isset($input_data['level']) && $input_data['level'] == 'Stand Alone SHS') ? 'selected' : ''; ?>>Stand Alone SHS</option>
                            <?php endif; ?>
                        </select>
                        <?php if (isset($errors['level'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['level']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Head Name Field -->
                    <div class="mb-4">
                        <label for="head_name" class="form-label">
                            <i class="fas fa-<?php echo $user_role == 'division' ? 'user-tie' : ($user_role == 'district' ? 'user-tie' : 'user-tie'); ?> text-primary me-1"></i>
                            <?php echo $user_role == 'division' ? 'Division Superintendent' : ($user_role == 'district' ? 'District Supervisor' : 'School Head Name'); ?>
                        </label>
                        <input type="text" 
                               class="form-control <?php echo isset($errors['head_name']) ? 'is-invalid' : ''; ?>" 
                               id="head_name" 
                               name="head_name" 
                               value="<?php echo isset($input_data['head_name']) ? $input_data['head_name'] : ''; ?>" 
                               placeholder="<?php echo $user_role == 'division' ? 'Enter division superintendent name' : ($user_role == 'district' ? 'Enter district supervisor name' : 'Enter school head name'); ?>" 
                               required>
                        <?php if (isset($errors['head_name'])): ?>
                            <div class="invalid-feedback d-block"><?php echo $errors['head_name']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3" id="submitBtn">
                    <i class="fas fa-paper-plane me-2"></i>
                    <span id="submitText">
                        <?php 
                        if ($user_role == 'division') {
                            echo 'Save Division Information';
                        } elseif ($user_role == 'district') {
                            echo 'Save District Information';
                        } else {
                            echo 'Save School Information';
                        }
                        ?>
                    </span>
                    <div class="spinner-border spinner-border-sm d-none" id="spinner" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        const userRole = '<?php echo $user_role; ?>';
        const siteUrl = '<?php echo site_url('school-info/get_school_districts'); ?>';
        const userSchoolDistrict = '<?php echo $user->school_district; ?>';
        const userLegislativeDistrict = '<?php echo $user->legislative_district; ?>';
    </script>
    <script src="<?php echo base_url('assets/js/school-info.js'); ?>"></script>
</body>
</html>