// Nutritional Upload JavaScript

(function() {
    'use strict';
    
    // DOM Elements
    const elements = {
        excelFileInput: document.getElementById('excelFile'),
        chooseFileBtn: document.getElementById('chooseFileBtn'),
        uploadForm: document.getElementById('uploadForm'),
        loadingSpinner: document.getElementById('loadingSpinner'),
        resultsSection: document.getElementById('resultsSection'),
        resultsMessage: document.getElementById('resultsMessage'),
        studentsTableContainer: document.getElementById('studentsTableContainer'),
        modalTitle: document.getElementById('modalTitle'),
        modalMessage: document.getElementById('modalMessage')
    };
    
    // Initialize Modal
    let messageModal = null;
    if (document.getElementById('messageModal')) {
        messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
    }
    
    // Configuration
    const config = {
        maxFileSize: window.uploadConfig?.maxFileSize || 5 * 1024 * 1024,
        allowedExtensions: window.uploadConfig?.allowedExtensions || ['xlsx', 'xls', 'csv'],
        processUrl: window.uploadConfig?.processUrl || '',
        heightTolerance: 3 // meters above this = likely cm
    };

    let heightGuideModal = null;
    let heightGuideSlideIndex = 0;
    let pendingReupload = null; // remembers the file for the "Try again" button
    
    /**
     * Validate required DOM elements
     */
    function validateElements() {
        const required = ['excelFileInput', 'chooseFileBtn', 'loadingSpinner', 'resultsSection', 'resultsMessage'];
        const missing = required.filter(key => !elements[key]);
        
        if (missing.length > 0) {
            console.error('Missing required DOM elements:', missing);
            return false;
        }
        return true;
    }
    
    /**
     * Initialize event listeners
     */
    function initializeEventListeners() {
        // Choose file button click
        if (elements.chooseFileBtn && elements.excelFileInput) {
            elements.chooseFileBtn.addEventListener('click', () => {
                elements.excelFileInput.click();
            });
        }
        
        // File input change
        if (elements.excelFileInput) {
            elements.excelFileInput.addEventListener('change', (event) => {
                if (event.target.files.length > 0) {
                    extractFromExcel();
                }
            });
        }
        
        // Drag and drop support
        setupDragAndDrop();
        
        // Keyboard shortcuts
        setupKeyboardShortcuts();
    }
    
    /**
     * Setup drag and drop functionality
     */
    function setupDragAndDrop() {
        const dropZone = document.querySelector('.bg-purple-light');
        
        if (!dropZone) return;
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('bg-light', 'border-primary');
            });
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('bg-light', 'border-primary');
            });
        });
        
        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0 && elements.excelFileInput) {
                elements.excelFileInput.files = files;
                extractFromExcel();
            }
        });
    }
    
    /**
     * Setup keyboard shortcuts
     */
    function setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+O to open file selector
            if (e.ctrlKey && e.key === 'o') {
                e.preventDefault();
                if (elements.excelFileInput) {
                    elements.excelFileInput.click();
                }
            }
            
            // Escape to close modal
            if (e.key === 'Escape' && messageModal) {
                messageModal.hide();
            }
        });
    }

        function initHeightGuide() {
        const modalEl = document.getElementById('heightGuideModal');
        if (!modalEl) return;
        if (heightGuideModal) return;   // already initialized

        heightGuideModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });

        const prevBtn  = document.getElementById('heightGuidePrev');
        const nextBtn  = document.getElementById('heightGuideNext');
        const reupBtn  = document.getElementById('heightGuideReupload');
        const closeBtn = document.getElementById('heightGuideClose');
        const dots     = document.querySelectorAll('#heightGuideDots .guide-dot');
        const slides   = document.querySelectorAll('#heightGuideSlides .height-slide');
        const totalSlides = slides.length;

        function render() {
            slides.forEach((s, i) => s.classList.toggle('d-none', i !== heightGuideSlideIndex));
            dots.forEach((d, i) => d.classList.toggle('active', i === heightGuideSlideIndex));
            prevBtn.disabled = heightGuideSlideIndex === 0;
            const isLast = heightGuideSlideIndex === totalSlides - 1;
            nextBtn.classList.toggle('d-none', isLast);
            reupBtn.classList.toggle('d-none', !isLast);
        }

        prevBtn.addEventListener('click', () => { if (heightGuideSlideIndex > 0) { heightGuideSlideIndex--; render(); } });
        nextBtn.addEventListener('click', () => { if (heightGuideSlideIndex < totalSlides - 1) { heightGuideSlideIndex++; render(); } });
        dots.forEach(d => d.addEventListener('click', () => { heightGuideSlideIndex = parseInt(d.dataset.dot, 10) || 0; render(); }));

        reupBtn.addEventListener('click', () => {
            if (heightGuideModal) heightGuideModal.hide();
                isUploading = false;
                currentUploadController = null;
            setTimeout(() => {
                const fi = document.getElementById('excelFile');
                if (fi) { fi.value = ''; fi.click(); }
            }, 200);
        });

        closeBtn.addEventListener('click', () => { if (heightGuideModal) heightGuideModal.hide(); });

        render();
    }

    function showHeightGuide(heightErrors, onDone) {
        initHeightGuide();
        if (!heightGuideModal || typeof heightGuideModal.show !== 'function') {
            console.error('[showHeightGuide] modal not available — falling back to alert');
            if (Array.isArray(heightErrors) && heightErrors.length) {
                const msg = heightErrors.slice(0, 5)
                    .map(e => `Row ${e.row}: ${e.name} — entered ${e.raw}, use ${e.suggested}`)
                    .join('\n');
                window.alert('Height must be in meters (not cm).\n\n' + msg);
            } else {
                window.alert('Height must be in meters. Example: 1.45 (not 145).');
            }
            if (typeof onDone === 'function') onDone();
            return;
        }

        const list = document.getElementById('heightGuideErrorList');
        if (list && Array.isArray(heightErrors) && heightErrors.length) {
            const rows = heightErrors.slice(0, 8).map(e =>
                `<li>Row ${e.row}: <strong>${escapeHtml(e.name)}</strong> — you entered <code>${e.raw}</code>, use <code>${e.suggested}</code> instead</li>`
            ).join('');
            list.innerHTML =
                `<strong>${heightErrors.length} student(s) had height in centimeters:</strong>` +
                `<ul class="mb-0 mt-2">${rows}</ul>` +
                (heightErrors.length > 8 ? `<div class="small mt-1">…and ${heightErrors.length - 8} more.</div>` : '');
            list.classList.remove('d-none');
        } else if (list) {
            list.classList.add('d-none');
        }

        // Chain: call onDone when the modal closes
        const modalEl = document.getElementById('heightGuideModal');
        if (modalEl && typeof onDone === 'function') {
            const onceHandler = function () {
                modalEl.removeEventListener('hidden.bs.modal', onceHandler);
                onDone();
            };
            modalEl.addEventListener('hidden.bs.modal', onceHandler);
        }

        heightGuideSlideIndex = 0;
        heightGuideModal.show();
    }
    
    /**
     * Extract data from Excel file
     */
    function extractFromExcel() {
        if (!elements.excelFileInput || !elements.excelFileInput.files.length) return;
        
        const file = elements.excelFileInput.files[0];
        
        // Validate file
        const validationError = validateFile(file);
        if (validationError) {
            showModal('Invalid File', validationError, 'warning');
            resetFileInput();
            return;
        }
        
        // Show loading state
        showLoading(true);
        
        const formData = new FormData();
        formData.append('excel_file', file);
        
        // Send file to server
        fetch(config.processUrl, {
            method: 'POST',
            body: formData,
            credentials: 'include',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(handleResponse)
        .then(handleSuccess)
        .catch(handleError)
        .finally(() => {
            showLoading(false);
            resetFileInput();
        });
    }
    
    /**
     * Validate file before upload
     */
    function validateFile(file) {
        // Check if file exists
        if (!file) {
            return 'No file selected.';
        }
        
        // Check file size
        if (file.size > config.maxFileSize) {
            return `File size (${formatFileSize(file.size)}) exceeds maximum allowed size (${formatFileSize(config.maxFileSize)})`;
        }
        
        // Check file extension
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!config.allowedExtensions.includes(fileExtension)) {
            return `Invalid file type. Allowed types: ${config.allowedExtensions.join(', ')}`;
        }
        
        return null;
    }
    
    /**
     * Format file size for display
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Handle fetch response
     */
    function handleResponse(response) {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    }
    
    /**
     * Handle successful response
     */
    function handleSuccess(data) {
        // 1) Height-unit errors take priority — show guide and abort import
        if (data.has_height_unit_errors && Array.isArray(data.height_unit_errors) && data.height_unit_errors.length) {
            showHeightGuide(data.height_unit_errors);
            // If the server also returned valid students alongside errors, still import them
            if (data.students && data.students.length) {
                showResults(data.message, data.students);
            }
            return;
        }

        // 2) Existing flow
        if (data.success) {
            showResults(data.message, data.students);
            showModal('Success', 'File processed successfully!', 'success');
        } else {
            showModal('Processing Error', data.message || 'Error processing file', 'danger');
        }
    }
    
    /**
     * Handle error response
     */
    function handleError(error) {
        console.error('Upload error:', error);
        let errorMessage = 'An error occurred while processing the file.';
        
        if (error.message.includes('HTTP error')) {
            errorMessage = 'Server error. Please try again later.';
        } else if (error.message.includes('Failed to fetch')) {
            errorMessage = 'Network error. Please check your connection.';
        } else if (error.message.includes('Unexpected end of JSON input')) {
            errorMessage = 'Server returned an invalid response. The file may be too large or corrupted.';
        }
        
        showModal('Error', errorMessage, 'danger');
    }
    
    /**
     * Show/hide loading spinner
     */
    function showLoading(show) {
        if (elements.loadingSpinner) {
            elements.loadingSpinner.classList.toggle('d-none', !show);
        }
        if (elements.chooseFileBtn) {
            elements.chooseFileBtn.disabled = show;
        }
    }
    
    /**
     * Reset file input
     */
    function resetFileInput() {
        if (elements.excelFileInput) {
            elements.excelFileInput.value = '';
        }
    }
    
    /**
     * Show modal with message
     */
    function showModal(title, message, variant = 'primary') {
        if (!messageModal || !elements.modalTitle || !elements.modalMessage) return;
        
        elements.modalTitle.textContent = title;
        elements.modalMessage.textContent = message;
        elements.modalMessage.className = 'alert alert-' + getVariantClass(variant);
        
        messageModal.show();
        
        // Auto-hide after 3 seconds for success messages
        if (variant === 'success') {
            setTimeout(() => {
                messageModal.hide();
            }, 3000);
        }
    }
    
    /**
     * Get Bootstrap alert variant class
     */
    function getVariantClass(variant) {
        const variants = {
            'warning': 'warning',
            'danger': 'danger',
            'success': 'success',
            'primary': 'primary',
            'info': 'info'
        };
        return variants[variant] || 'primary';
    }
    
    /**
     * Show results section with extracted data
     */
    function showResults(message, students) {
        if (!elements.resultsMessage || !elements.resultsSection) return;
        
        elements.resultsMessage.textContent = message;
        elements.resultsSection.classList.remove('d-none');
        
        // Scroll to results
        elements.resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        if (students && students.length > 0 && elements.studentsTableContainer) {
            elements.studentsTableContainer.innerHTML = generateStudentsTable(students);
        } else if (elements.studentsTableContainer) {
            elements.studentsTableContainer.innerHTML = '<p class="text-muted text-center py-3">No student data found in the file.</p>';
        }
    }
    
    /**
     * Generate HTML table from student data
     */
    function generateStudentsTable(students) {
        if (!students || students.length === 0) return '';
        
        let tableHtml = `
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
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
        
        students.forEach((student, index) => {
            tableHtml += `
                <tr class="fade-in" style="animation-delay: ${index * 0.03}s">
                    <td>${index + 1}</td>
                    <td>${escapeHtml(student.name)}</td>
                    <td>${escapeHtml(student.birthday)}</td>
                    <td>${student.weight ? Number(student.weight).toFixed(2) : ''}</td>
                    <td>${student.height ? Number(student.height).toFixed(2) : ''}</td>
                    <td>${escapeHtml(student.sex)}</td>
                    <td>${student.bmi ? Number(student.bmi).toFixed(2) : ''}</td>
                    <td>${escapeHtml(student.nutritional_status)}</td>
                    <td>${escapeHtml(student.height_for_age)}</td>
                </tr>
            `;
        });
        
        tableHtml += `
                    </tbody>
                </table>
                <div class="text-muted mt-2 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Total: <strong>${students.length}</strong> student${students.length !== 1 ? 's' : ''} extracted
                </div>
            </div>
        `;
        
        return tableHtml;
    }
    
    /**
     * Escape HTML special characters
     */
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
    
    /**
     * Show notification toast (alternative to modal)
     */
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.maxWidth = '400px';
        notification.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
        notification.style.animation = 'slideDown 0.3s ease-out';
        
        notification.innerHTML = `
            <div class="d-flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                      type === 'danger' ? 'exclamation-circle' : 
                                      type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                </div>
                <div class="flex-grow-1">
                    ${message}
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-20px)';
            notification.style.transition = 'all 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
    
    /**
     * Initialize the application
     */
    function init() {
        if (!validateElements()) {
            console.error('Nutritional upload initialization failed: missing elements');
            return;
        }
        
        initializeEventListeners();
        console.log('Nutritional upload page initialized successfully');
    }
    
    // Start initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    
    /* =========================================================
    Height unit guide (slideshow)
    ========================================================= */
    function initHeightGuide() {
        const modalEl = document.getElementById('heightGuideModal');
        if (!modalEl) return;

        const prevBtn   = document.getElementById('heightGuidePrev');
        const nextBtn   = document.getElementById('heightGuideNext');
        const reupBtn   = document.getElementById('heightGuideReupload');
        const closeBtn  = document.getElementById('heightGuideClose');
        const dots      = document.querySelectorAll('#heightGuideDots .guide-dot');
        const slides    = document.querySelectorAll('#heightGuideSlides .height-slide');
        const totalSlides = slides.length;

        function render() {
            slides.forEach((s, i) => s.classList.toggle('d-none', i !== heightGuideSlideIndex));
            dots.forEach((d, i) => d.classList.toggle('active', i === heightGuideSlideIndex));

            prevBtn.disabled = heightGuideSlideIndex === 0;
            const isLast = heightGuideSlideIndex === totalSlides - 1;

            nextBtn.classList.toggle('d-none', isLast);
            reupBtn.classList.toggle('d-none', !isLast);
        }

        prevBtn.addEventListener('click', () => {
            if (heightGuideSlideIndex > 0) { heightGuideSlideIndex--; render(); }
        });
        nextBtn.addEventListener('click', () => {
            if (heightGuideSlideIndex < totalSlides - 1) { heightGuideSlideIndex++; render(); }
        });
        dots.forEach(d => d.addEventListener('click', () => {
            heightGuideSlideIndex = parseInt(d.dataset.dot, 10) || 0;
            render();
        }));

        reupBtn.addEventListener('click', () => {
            // Reset file input and trigger chooser so user can pick the fixed file
            if (heightGuideModal) heightGuideModal.hide();
            const fi = document.getElementById('excelFile');
            if (fi) { fi.value = ''; fi.click(); }
        });

        closeBtn.addEventListener('click', () => {
            if (heightGuideModal) heightGuideModal.hide();
        });

        render();
    }

    function showHeightGuide(heightErrors) {
        if (!heightGuideModal) initHeightGuide();
        if (!heightGuideModal) {
            // Fallback if Bootstrap isn't loaded
            alert('Height must be in meters. Example: 1.45 (not 145).');
            return;
        }

        // Populate the error list on slide 4
        const list = document.getElementById('heightGuideErrorList');
        if (list && Array.isArray(heightErrors) && heightErrors.length) {
            const rows = heightErrors.slice(0, 8).map(e =>
                `<li>Row ${e.row}: <strong>${escapeHtml(e.name)}</strong> — ` +
                `you entered <code>${e.raw}</code>, use <code>${e.suggested}</code> instead</li>`
            ).join('');
            list.innerHTML =
                `<strong>${heightErrors.length} student(s) had height in centimeters:</strong>` +
                `<ul class="mb-0 mt-2">${rows}</ul>` +
                (heightErrors.length > 8 ? `<div class="small mt-1">…and ${heightErrors.length - 8} more.</div>` : '');
            list.classList.remove('d-none');
        } else if (list) {
            list.classList.add('d-none');
        }

        heightGuideSlideIndex = 0;
        heightGuideModal.show();
    }
    
})();

/**
 * Utility functions for nutritional upload
 */
const NutritionalUploadUtils = {
    /**
     * Validate Excel file structure
     */
    validateExcelStructure: function(data) {
        const requiredColumns = ['name', 'birthday', 'weight', 'height', 'sex', 'bmi', 'nutritional_status', 'height_for_age'];
        const errors = [];
        
        if (!data || data.length === 0) {
            errors.push('No data found in file');
            return { valid: false, errors };
        }
        
        // Check first row for required columns
        const firstRow = data[0];
        requiredColumns.forEach(column => {
            if (!firstRow.hasOwnProperty(column)) {
                errors.push(`Missing required column: ${column}`);
            }
        });
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    },
    
    /**
     * Parse student data from Excel
     */
    parseStudentData: function(rawData) {
        if (!rawData || !Array.isArray(rawData)) return [];

        return rawData.map(row => {
            // Try various column names for full name and components
            const rawName = row.name || row.Name || row['Name'] || row['Full Name'] || row['full_name'] || row.full_name || '';

            const firstName = (row.first_name || row.FirstName || row['First Name'] || row.first || row.First || '').toString().trim();
            const middleInitial = (row.middle_initial || row.MI || row['M.I.'] || row.middleInitial || row['Middle Initial'] || '').toString().trim();
            const lastName = (row.last_name || row.LastName || row['Last Name'] || row.last || row.Last || '').toString().trim();

            // Normalize name: prefer provided full name, otherwise build LastName MiddleInitial FirstName
            let name = rawName.toString().trim();
            if (!name) {
                if (lastName || firstName || middleInitial) {
                    name = lastName;
                    if (middleInitial) {
                        name += (name ? ' ' : '') + middleInitial.replace(/\.?$/, '.') ;
                    }
                    if (firstName) {
                        name += (name ? ' ' : '') + firstName;
                    }
                    name = name.trim();
                }
            }

            return {
                name: name || '',
                birthday: this.formatDate(row.birthday || row.Birthday || ''),
                weight: parseFloat(row.weight || row.Weight || 0) || 0,
                height: parseFloat(row.height || row.Height || 0) || 0,
                sex: (row.sex || row.Sex || '').toUpperCase(),
                bmi: parseFloat(row.bmi || row.BMI || 0) || 0,
                nutritional_status: row.nutritional_status || row['Nutritional Status'] || '',
                height_for_age: row.height_for_age || row['Height-for-Age'] || ''
            };
        }).filter(student => student.name);
    },
    
    /**
     * Format date for display
     */
    formatDate: function(dateString) {
        if (!dateString) return '';
        
        try {
            // Handle Excel serial date
            if (!isNaN(dateString) && typeof dateString === 'number') {
                const date = new Date((dateString - 25569) * 86400 * 1000);
                return this.formatDateObject(date);
            }
            
            // Handle string date
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            
            return this.formatDateObject(date);
        } catch (e) {
            return dateString;
        }
    },
    
    /**
     * Format date object
     */
    formatDateObject: function(date) {
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const year = date.getFullYear();
        return `${month}/${day}/${year}`;
    },
    
    /**
     * Calculate BMI
     */
    calculateBMI: function(weight, height) {
        if (!weight || !height || height <= 0) return 0;
        
        // Convert height from cm to m if needed
        const heightInMeters = height > 10 ? height / 100 : height;
        const bmi = weight / (heightInMeters * heightInMeters);
        
        return Math.round(bmi * 10) / 10;
    },
    
    /**
     * Get nutritional status based on BMI
     */
    getNutritionalStatus: function(bmi, age, sex) {
        // Simplified example - in production, use WHO standards
        if (bmi < 18.5) return 'Underweight';
        if (bmi < 25) return 'Normal';
        if (bmi < 30) return 'Overweight';
        return 'Obese';
    },
    
    /**
     * Get height-for-age status
     */
    getHeightForAge: function(height, age, sex) {
        // Simplified example - in production, use WHO standards
        if (height < 100) return 'Stunted';
        return 'Normal';
    }
};

// Export for use in other modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NutritionalUploadUtils;
}