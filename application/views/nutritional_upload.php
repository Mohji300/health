<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/nutritional-upload.css'); ?>">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="bg-purple-light p-4 rounded mb-4">
                    <h2 class="h3 text-dark mb-3">Extract Data from Nutritional Status Report</h2>
                    <p class="text-muted mb-3">
                        Upload an Excel file (.xlsx, .xls) following the <strong>"NUTRITIONAL STATUS REPORT" template</strong>.
                    </p>
                    <ul class="list-unstyled text-muted mb-3 ms-3">
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column C:</strong> Student Names</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column D:</strong> Birthday</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column E:</strong> Weight (kg)</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column F:</strong> Height (meters)</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column G:</strong> Sex (M/F)</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column L:</strong> BMI</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column M:</strong> Nutritional Status</li>
                        <li><i class="fas fa-arrow-right text-purple me-2"></i><strong>Column N:</strong> Height-for-Age</li>
                    </ul>
                    
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="d-flex flex-column align-items-center">
                            <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls,.csv" class="d-none">
                            <button type="button" id="chooseFileBtn" class="btn btn-purple">
                                <i class="fas fa-file-excel me-2"></i>Choose Nutritional Report File
                            </button>
                            <small class="text-muted mt-2">Supported formats: .xlsx, .xls, .csv (Max 5MB)</small>
                        </div>
                    </form>
                </div>

                <!-- Results Section -->
                <div id="resultsSection" class="d-none">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Extraction Results</h5>
                        </div>
                        <div class="card-body">
                            <div id="resultsMessage" class="alert alert-success"></div>
                            <div id="studentsTableContainer"></div>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="text-center d-none">
                    <div class="spinner-border text-purple" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Extracting data from Excel file...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Messages -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        const uploadConfig = {
            processUrl: '<?php echo site_url("nutritional_upload/process_excel"); ?>',
            maxFileSize: 5 * 1024 * 1024, // 5MB
            allowedExtensions: ['xlsx', 'xls', 'csv']
        };
    </script>
    <script src="<?= base_url('assets/js/nutritional-upload.js'); ?>"></script>
</body>
</html>