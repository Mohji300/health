<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/excel-upload.css'); ?>">
</head>
<body>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>