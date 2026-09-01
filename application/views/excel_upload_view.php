<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/excel-upload.css'); ?>">
</head>
<body>
    <?php $this->load->view('templates/sidebar'); ?>

    <div class="container">
        <div class="upload-container">
            <h2 class="text-center mb-4"><i class="fas fa-file-excel text-success"></i> Excel/CSV Data Upload System</h2>
            
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $this->session->flashdata('success'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $this->session->flashdata('error'); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Upload Excel/CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo site_url('excel_upload/upload_excel'); ?>" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Select Excel or CSV File</label>
                            <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Supported formats: .xlsx, .xls, .csv (Max: 10MB)<br>
                                <i class="fas fa-table"></i> File must have columns in this order: 
                                <strong>Row# | School ID | School Name | District | Type | Legislative District</strong>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-upload"></i> Upload and Process
                        </button>
                    </form>
                </div>
            </div>

            <!-- Data Summary -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Current Data Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-primary"><?php echo $summary['legislative_districts']; ?></h3>
                                <p class="text-muted"><i class="fas fa-landmark"></i> Legislative Districts</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-success"><?php echo $summary['school_districts']; ?></h3>
                                <p class="text-muted"><i class="fas fa-school"></i> School Districts</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-warning"><?php echo $summary['schools']; ?></h3>
                                <p class="text-muted"><i class="fas fa-graduation-cap"></i> Total Schools</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-info"><?php echo isset($summary['school_levels']['Elementary']) ? $summary['school_levels']['Elementary'] : 0; ?></h3>
                                <p class="text-muted"><i class="fas fa-chalkboard-user"></i> Elementary</p>
                            </div>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-info"><?php echo isset($summary['school_levels']['Secondary']) ? $summary['school_levels']['Secondary'] : 0; ?></h3>
                                <p class="text-muted"><i class="fas fa-building"></i> Secondary</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-info"><?php echo isset($summary['school_levels']['Private']) ? $summary['school_levels']['Private'] : 0; ?></h3>
                                <p class="text-muted"><i class="fas fa-church"></i> Private</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-info"><?php echo isset($summary['school_levels']['Integrated']) ? $summary['school_levels']['Integrated'] : 0; ?></h3>
                                <p class="text-muted"><i class="fas fa-layer-group"></i> Integrated</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <h3 class="text-info"><?php echo isset($summary['school_levels']['Unknown']) ? $summary['school_levels']['Unknown'] : 0; ?></h3>
                                <p class="text-muted"><i class="fas fa-question"></i> Unknown Level</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (in_array($role, ['admin', 'super_admin'])): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Uploaded School Data Editor</h5>
                        <form class="d-flex align-items-center gap-2" action="<?php echo site_url('excel_upload'); ?>" method="get">
                            <input type="text" class="form-control form-control-sm" name="search" value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search school data" aria-label="Search uploaded school data">
                            <button type="submit" class="btn btn-light btn-sm"><i class="fas fa-search"></i> Search</button>
                            <?php if (!empty($search)): ?>
                                <a href="<?php echo site_url('excel_upload'); ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-times"></i> Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered align-middle editable-school-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>School ID</th>
                                    <th>School Name</th>
                                    <th>School District</th>
                                    <th>Legislative District</th>
                                    <th>Level</th>
                                    <th>Size</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($schools)): ?>
                                    <?php foreach ($schools as $school): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($school->school_id, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($school->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($school->school_district ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($school->legislative_district ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($school->school_level ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($school->school_size ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary edit-school-btn"
                                                    data-id="<?php echo (int) $school->id; ?>"
                                                    data-school-id="<?php echo htmlspecialchars($school->school_id, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-school-name="<?php echo htmlspecialchars($school->name, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-school-district="<?php echo htmlspecialchars($school->school_district ?: '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-legislative-district="<?php echo htmlspecialchars($school->legislative_district ?: '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-school-level="<?php echo htmlspecialchars($school->school_level ?: '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-school-size="<?php echo htmlspecialchars($school->school_size ?: '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editSchoolModal">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No school rows have been uploaded yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Clear Data Button -->
            <div class="text-center mt-4">
                <a href="<?php echo site_url('excel_upload/clear_data'); ?>" class="btn btn-danger btn-lg clear-data-btn" onclick="return confirm('⚠️ Are you sure you want to clear ALL data? This action cannot be undone!')">
                    <i class="fas fa-trash"></i> Clear All Data
                </a>
            </div>

            <!-- Data Preview -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-eye"></i> Expected File Format</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Your Excel file should have multiple sheets (Elementary, Secondary, Private) or a CSV with the following structure:
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm preview-table">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="6" class="text-center">Row 2: Title Row</th>
                                </tr>
                                <tr>
                                    <th colspan="2"></th>
                                    <th colspan="1">ELEMENTARY SCHOOL</th>
                                    <th colspan="3"></th>
                                </tr>
                                <tr>
                                    <th>Row 3: Headers</th>
                                    <th>A</th>
                                    <th>B</th>
                                    <th>C</th>
                                    <th>D</th>
                                    <th>E</th>
                                    <th>F</th>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td>School ID</td>
                                    <td>School Name</td>
                                    <td>District</td>
                                    <td>Type</td>
                                    <td>Legislative District</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-secondary">
                                    <td colspan="7" class="text-center"><strong>Data starts from Row 4 onwards</strong></td>
                                </tr>
                                <tr>
                                    <td>Row 4</td>
                                    <td>1</td>
                                    <td>113374</td>
                                    <td>Aroroy East CS</td>
                                    <td>Aroroy East</td>
                                    <td><span class="badge bg-success badge-level">Elementary</span></td>
                                    <td>1st District</td>
                                </tr>
                                <tr>
                                    <td>Row 5</td>
                                    <td>2</td>
                                    <td>113375</td>
                                    <td>Balawing Elem. School</td>
                                    <td>Aroroy East</td>
                                    <td><span class="badge bg-success badge-level">Elementary</span></td>
                                    <td>1st District</td>
                                </tr>
                                <tr>
                                    <td>Row 6</td>
                                    <td>3</td>
                                    <td>302111</td>
                                    <td>Aroroy National High School</td>
                                    <td>Aroroy East</td>
                                    <td><span class="badge bg-info badge-level">Secondary</span></td>
                                    <td>1st District</td>
                                </tr>
                                <tr>
                                    <td>Row 7</td>
                                    <td>4</td>
                                    <td>434509</td>
                                    <td>Yadah Christian School Inc.</td>
                                    <td>Aroroy East</td>
                                    <td><span class="badge bg-warning badge-level">Private</span></td>
                                    <td>1st District</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <h6><i class="fas fa-question-circle me-2"></i>School ID Prefix Meaning:</h6>
                        <ul class="small">
                            <li><span class="badge bg-success">1xxxxx</span> - Elementary School</li>
                            <li><span class="badge bg-info">3xxxxx</span> - Secondary/High School</li>
                            <li><span class="badge bg-warning">4xxxxx</span> - Private School</li>
                            <li><span class="badge bg-purple">5xxxxx</span> - Integrated School</li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <h6><i class="fas fa-download me-2"></i>How to prepare your file:</h6>
                        <ol class="small">
                            <li>Open your Excel file (like the SDO MASBATE MASTERLIST OF SCHOOLS.xlsx)</li>
                            <li>Ensure each sheet (Elementary, Secondary, Private) has the correct format</li>
                            <li>If uploading as CSV, save each sheet as a separate CSV file</li>
                            <li>Upload the file using the form above</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Instructions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-excel"></i> For Excel Files (.xlsx, .xls):</h6>
                            <ol class="list-group list-group-numbered mb-3">
                                <li class="list-group-item">Prepare your Excel file with multiple sheets (Elementary, Secondary, Private)</li>
                                <li class="list-group-item">Each sheet should have:
                                    <ul class="mt-2">
                                        <li><strong>Row 2, Column C:</strong> Sheet Title (ELEMENTARY SCHOOL, SECONDARY, PRIVATE SCHOOL)</li>
                                        <li><strong>Row 3:</strong> Column Headers (School ID, School Name, District, Type, Legislative District)</li>
                                        <li><strong>Row 4 onwards:</strong> Actual data</li>
                                    </ul>
                                </li>
                                <li class="list-group-item">Upload the Excel file directly</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-csv"></i> For CSV Files:</h6>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item">Export your Excel file as CSV (Comma delimited)</li>
                                <li class="list-group-item">Ensure columns are in order: 
                                    <strong>Row# | School ID | School Name | District | Type | Legislative District</strong>
                                </li>
                                <li class="list-group-item">The first data row should start at row 4</li>
                                <li class="list-group-item">Upload the CSV file</li>
                            </ol>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> 
                        The system will automatically determine the school level from:
                        <ul class="mb-0 mt-2">
                            <li>The "Type" column if provided</li>
                            <li>The School ID prefix (1=Elementary, 3=Secondary, 4=Private, 5=Integrated)</li>
                            <li>The sheet name (Elementary, Secondary, Private)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (in_array($role, ['admin', 'super_admin'])): ?>
    <div class="modal fade" id="editSchoolModal" tabindex="-1" aria-labelledby="editSchoolModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="<?php echo site_url('excel_upload/update_school'); ?>" method="post">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editSchoolModalLabel"><i class="fas fa-edit"></i> Edit Uploaded School</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_school_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_school_id_field" class="form-label">School ID</label>
                                <input type="text" class="form-control" id="edit_school_id_field" name="school_id" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_school_name" class="form-label">School Name</label>
                                <input type="text" class="form-control" id="edit_school_name" name="school_name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_school_district" class="form-label">School District</label>
                                <input type="text" class="form-control" id="edit_school_district" name="school_district" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_legislative_district" class="form-label">Legislative District</label>
                                <input type="text" class="form-control" id="edit_legislative_district" name="legislative_district" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_school_level" class="form-label">School Level</label>
                                <select class="form-select" id="edit_school_level" name="school_level">
                                    <option value="Elementary">Elementary</option>
                                    <option value="Secondary">Secondary</option>
                                    <option value="Private">Private</option>
                                    <option value="Integrated">Integrated</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_school_size" class="form-label">School Size</label>
                                <input type="number" class="form-control" id="edit_school_size" name="school_size" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url(ASSETS_PATH . '/js/sidebar.js'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-school-btn');
            editButtons.forEach((button) => {
                button.addEventListener('click', function() {
                    const fields = {
                        id: button.getAttribute('data-id'),
                        school_id: button.getAttribute('data-school-id'),
                        school_name: button.getAttribute('data-school-name'),
                        school_district: button.getAttribute('data-school-district'),
                        legislative_district: button.getAttribute('data-legislative-district'),
                        school_level: button.getAttribute('data-school-level'),
                        school_size: button.getAttribute('data-school-size')
                    };

                    document.getElementById('edit_school_id').value = fields.id;
                    document.getElementById('edit_school_id_field').value = fields.school_id;
                    document.getElementById('edit_school_name').value = fields.school_name;
                    document.getElementById('edit_school_district').value = fields.school_district;
                    document.getElementById('edit_legislative_district').value = fields.legislative_district;
                    document.getElementById('edit_school_level').value = fields.school_level || 'Unknown';
                    document.getElementById('edit_school_size').value = fields.school_size || '';
                });
            });
        });
    </script>
</body>
</html>