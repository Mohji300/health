<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="<?= base_url('favicon.ico'); ?>">
    <style>
        .bg-purple-light {
            background-color: #f8f9fa;
            border-left: 4px solid #6f42c1;
        }
        .btn-purple {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: white;
        }
        .btn-purple:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
        }
        .alert-purple {
            background-color: #e9ecef;
            border-color: #6f42c1;
            color: #495057;
        }
    </style>
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
        document.addEventListener('DOMContentLoaded', function() {
            const excelFileInput = document.getElementById('excelFile');
            const chooseFileBtn = document.getElementById('chooseFileBtn');
            const uploadForm = document.getElementById('uploadForm');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultsSection = document.getElementById('resultsSection');
            const resultsMessage = document.getElementById('resultsMessage');
            const studentsTableContainer = document.getElementById('studentsTableContainer');
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));

            chooseFileBtn.addEventListener('click', function() {
                excelFileInput.click();
            });

            excelFileInput.addEventListener('change', function(event) {
                if (event.target.files.length > 0) {
                    extractFromExcel();
                }
            });

            function extractFromExcel() {
                const file = excelFileInput.files[0];
                if (!file) return;

                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!['xlsx', 'xls', 'csv'].includes(fileExtension)) {
                    showModal('Invalid File', 'Please select an Excel file (.xlsx, .xls) or CSV file', 'warning');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) { // 5MB
                    showModal('File Too Large', 'Please select a file smaller than 5MB', 'warning');
                    return;
                }

                loadingSpinner.classList.remove('d-none');
                chooseFileBtn.disabled = true;

                const formData = new FormData();
                formData.append('excel_file', file);

                fetch('<?php echo site_url("nutritional_upload/process_excel"); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.classList.add('d-none');
                    chooseFileBtn.disabled = false;
                    excelFileInput.value = '';

                    if (data.success) {
                        showResults(data.message, data.students);
                    } else {
                        showModal('Processing Error', data.message, 'danger');
                    }
                })
                .catch(error => {
                    loadingSpinner.classList.add('d-none');
                    chooseFileBtn.disabled = false;
                    excelFileInput.value = '';
                    showModal('Error', 'An error occurred while processing the file: ' + error.message, 'danger');
                });
            }

            function showModal(title, message, variant = 'primary') {
                const modalTitle = document.getElementById('modalTitle');
                const modalMessage = document.getElementById('modalMessage');
                
                modalTitle.textContent = title;
                modalMessage.textContent = message;
                modalMessage.className = 'alert alert-' + getVariantClass(variant);
                
                messageModal.show();
            }

            function getVariantClass(variant) {
                const variants = {
                    'warning': 'warning',
                    'danger': 'danger',
                    'success': 'success',
                    'primary': 'primary'
                };
                return variants[variant] || 'primary';
            }

            function showResults(message, students) {
                resultsMessage.textContent = message;
                resultsSection.classList.remove('d-none');

                if (students && students.length > 0) {
                    let tableHtml = `
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Birthday</th>
                                        <th>Weight</th>
                                        <th>Height</th>
                                        <th>Sex</th>
                                        <th>BMI</th>
                                        <th>Nutritional Status</th>
                                        <th>Height-for-Age</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    students.forEach(student => {
                        tableHtml += `
                            <tr>
                                <td>${escapeHtml(student.name)}</td>
                                <td>${escapeHtml(student.birthday)}</td>
                                <td>${student.weight || ''}</td>
                                <td>${student.height || ''}</td>
                                <td>${escapeHtml(student.sex)}</td>
                                <td>${student.bmi || ''}</td>
                                <td>${escapeHtml(student.nutritional_status)}</td>
                                <td>${escapeHtml(student.height_for_age)}</td>
                            </tr>
                        `;
                    });

                    tableHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    studentsTableContainer.innerHTML = tableHtml;
                }
            }

            function escapeHtml(unsafe) {
                if (unsafe === null || unsafe === undefined) return '';
                return unsafe
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>
</html>