<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 25px;
            border: 2px dashed #007bff;
            border-radius: 10px;
            background-color: #f8f9fa;
        }
        .stats-card {
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .card-header {
            font-weight: 600;
        }
        .preview-table {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-container">
            <h2 class="text-center mb-4"><i class="fas fa-file-csv text-success"></i> CSV Data Upload System</h2>
            
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
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Upload CSV File</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo site_url('excel_upload/upload_excel'); ?>" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Select CSV File</label>
                            <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".csv" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> Supported format: .csv (Max: 5MB)<br>
                                <i class="fas fa-table"></i> File must have columns in this order: Legislative District, School District, School ID, School Name
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
                        <div class="col-md-4">
                            <div class="stats-card">
                                <h3 class="text-primary"><?php echo $summary['legislative_districts']; ?></h3>
                                <p class="text-muted"><i class="fas fa-landmark"></i> Legislative Districts</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <h3 class="text-success"><?php echo $summary['school_districts']; ?></h3>
                                <p class="text-muted"><i class="fas fa-school"></i> School Districts</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <h3 class="text-warning"><?php echo $summary['schools']; ?></h3>
                                <p class="text-muted"><i class="fas fa-graduation-cap"></i> Schools</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clear Data Button -->
            <div class="text-center mt-4">
                <a href="<?php echo site_url('excel_upload/clear_data'); ?>" class="btn btn-danger btn-lg" onclick="return confirm('⚠️ Are you sure you want to clear ALL data? This action cannot be undone!')">
                    <i class="fas fa-trash"></i> Clear All Data
                </a>
            </div>

            <!-- Data Preview -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-eye"></i> Expected CSV Format</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm preview-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Legislative District</th>
                                    <th>School District</th>
                                    <th>School ID</th>
                                    <th>School Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>2ND DISTRICT</td>
                                    <td>AROROY EAST</td>
                                    <td>113390</td>
                                    <td>PINANAAN ELEMENTARY SCHOOL</td>
                                </tr>
                                <tr>
                                    <td>2ND DISTRICT</td>
                                    <td>AROROY EAST</td>
                                    <td>113382</td>
                                    <td>JABOYOAN ELEMENTARY SCHOOL</td>
                                </tr>
                                <tr>
                                    <td>3RD DISTRICT</td>
                                    <td>CATAINGAN EAST</td>
                                    <td>113502</td>
                                    <td>T.C.G STO. NINO ELEMENTARY SCHOOL</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <h6>How to convert your Excel file to CSV:</h6>
                        <ol class="small">
                            <li>Open your Excel file in Microsoft Excel</li>
                            <li>Click <strong>File → Save As</strong></li>
                            <li>Choose <strong>CSV (Comma delimited) (*.csv)</strong> as the file type</li>
                            <li>Click <strong>Save</strong></li>
                            <li>Upload the saved CSV file using the form above</li>
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
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item">Prepare your CSV file with exactly these columns in order:
                            <ul class="mt-2">
                                <li><strong>Column A:</strong> Legislative District (1ST DISTRICT, 2ND DISTRICT, 3RD DISTRICT)</li>
                                <li><strong>Column B:</strong> School District (AROROY EAST, AROROY WEST, etc.)</li>
                                <li><strong>Column C:</strong> School ID (113390, 113382, etc.)</li>
                                <li><strong>Column D:</strong> School Name</li>
                            </ul>
                        </li>
                        <li class="list-group-item">Ensure the first row contains headers exactly as shown above</li>
                        <li class="list-group-item">Upload the CSV file using the form above</li>
                        <li class="list-group-item">The system will automatically process and insert data into the database</li>
                        <li class="list-group-item">Check the data summary after upload to verify successful import</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>