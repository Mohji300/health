    // nutritional_assessment.js

    // Storage prefix for localStorage
    const STORAGE_PREFIX = 'nutritional_assessment_';
    let students = [];
    let alertModal = null;
    let loadingModal = null;
    let uploadLoadingModal = null;
    let confirmModal = null;
    let submitConfirmModal = null;
    let editingIndex = -1; // Track which student is being edited

    function getStorageKey() {
        const ld = document.getElementById('legislative_district')?.value || 'na';
        const sd = document.getElementById('school_district')?.value || 'na';
        const gr = document.getElementById('grade')?.value || 'na';
        const sc = document.getElementById('section')?.value || 'na';
        const sy = document.getElementById('school_year')?.value || 'na';
        const sn = document.getElementById('school_name')?.value || 'na';
        return STORAGE_PREFIX + [ld, sd, gr, sc, sy, sn].join('_');
    }

    // Load students from localStorage
    function loadStudents() {
        const key = getStorageKey();
        const stored = localStorage.getItem(key);
        if (stored) {
            try { 
                students = JSON.parse(stored); 
            } catch (e) { 
                console.error('Error loading students:', e); 
                students = []; 
            }
        }
    }

    // Save students to localStorage
    function saveStudents() { 
        localStorage.setItem(getStorageKey(), JSON.stringify(students)); 
    }

    // Clear stored students
    function clearStoredStudents() { 
        localStorage.removeItem(getStorageKey()); 
    }

    // Calculate all derived student data
    function calculateStudentData(name, birthday, weight, height, sex) {
        let ageYears = 0, ageMonths = 0;
        if (birthday) {
            const bdate = new Date(birthday);
            const today = new Date();
            ageYears = today.getFullYear() - bdate.getFullYear();
            ageMonths = today.getMonth() - bdate.getMonth();
            if (ageMonths < 0) { 
                ageYears--; 
                ageMonths += 12; 
            }
        }

        const heightSq = (height * height).toFixed(4);
        const bmi = (weight / (height * height)).toFixed(2);

        let nutritionalStatus = 'Normal';
        if (bmi < 16) nutritionalStatus = 'Severely Wasted';
        else if (bmi < 18.5) nutritionalStatus = 'Wasted';
        else if (bmi < 25) nutritionalStatus = 'Normal';
        else if (bmi < 30) nutritionalStatus = 'Overweight';
        else nutritionalStatus = 'Obese';

        const sbfpBeneficiary = (nutritionalStatus === 'Severely Wasted' || nutritionalStatus === 'Wasted') ? 'Yes' : 'No';

        return {
            name: name.trim(), 
            birthday, 
            weight: parseFloat(weight), 
            height: parseFloat(height), 
            sex,
            grade: document.getElementById('grade')?.value || '', 
            section: document.getElementById('section')?.value || '',
            school_year: document.getElementById('school_year')?.value || '', 
            date: document.getElementById('date')?.value || '',
            legislative_district: document.getElementById('legislative_district')?.value || '',
            school_district: document.getElementById('school_district')?.value || '',
            school_id: document.getElementById('school_id')?.value || '', 
            school_name: document.getElementById('school_name')?.value || '',
            heightSquared: parseFloat(heightSq), 
            age: ageYears + '|' + ageMonths, 
            ageYears, 
            ageMonths, 
            ageDisplay: ageYears + '|' + ageMonths,
            bmi: parseFloat(bmi), 
            nutritionalStatus, 
            heightForAge: 'Normal', 
            sbfpBeneficiary
        };
    }

    // Add or update a student
    function addOrUpdateStudent() {
        const form = document.getElementById('assessmentForm');
        if (!form.checkValidity()) { 
            form.classList.add('was-validated'); 
            return; 
        }
        
        const name = document.getElementById('name').value;
        const birthday = document.getElementById('birthday').value;
        const weight = document.getElementById('weight').value;
        const height = document.getElementById('height').value;
        const sex = document.getElementById('sex').value;
        
        const student = calculateStudentData(name, birthday, weight, height, sex);
        
        if (editingIndex >= 0 && editingIndex < students.length) {
            // Update existing student
            students[editingIndex] = student;
            saveStudents();
            cancelEdit();
            showAlert('Success', 'Student record updated successfully!');
        } else {
            // Add new student
            students.push(student);
            saveStudents();
            clearForm();
            showAlert('Success', 'Student added to the list!');
        }
        
        updateUI();
    }

    // Edit a student
    function editStudent(index) {
        if (index < 0 || index >= students.length) return;
        
        const student = students[index];
        editingIndex = index;
        
        // Populate the form with student data
        document.getElementById('name').value = student.name || '';
        document.getElementById('birthday').value = student.birthday || '';
        document.getElementById('weight').value = student.weight || '';
        document.getElementById('height').value = student.height || '';
        document.getElementById('sex').value = student.sex || '';
        document.getElementById('date').value = student.date || new Date().toISOString().split('T')[0];
        
        // Change submit button to Update mode
        const submitBtn = document.querySelector('#assessmentForm button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Student';
        submitBtn.classList.remove('btn-success');
        submitBtn.classList.add('btn-warning');
        
        // Add cancel button if not exists
        if (!document.getElementById('cancelEditBtn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.id = 'cancelEditBtn';
            cancelBtn.className = 'btn btn-secondary ms-2';
            cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
            cancelBtn.onclick = cancelEdit;
            submitBtn.parentNode.appendChild(cancelBtn);
        }
        
        // Scroll to form
        document.querySelector('.card-header.bg-primary').scrollIntoView({ behavior: 'smooth' });
    }

    // Cancel editing
    function cancelEdit() {
        editingIndex = -1;
        clearForm();
        
        // Change button back to Add mode
        const submitBtn = document.querySelector('#assessmentForm button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Student to List';
        submitBtn.classList.remove('btn-warning');
        submitBtn.classList.add('btn-success');
        
        // Remove cancel button
        const cancelBtn = document.getElementById('cancelEditBtn');
        if (cancelBtn) {
            cancelBtn.remove();
        }
    }

    // Confirm delete student
    function confirmDeleteStudent(index) {
        if (index < 0 || index >= students.length) return;
        
        const studentName = students[index].name || 'this student';
        
        // Use existing confirm modal
        const confirmBody = document.getElementById('confirmBody');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmYesBtn = document.getElementById('confirmYesBtn');
        
        if (confirmTitle) confirmTitle.textContent = 'Delete Student';
        if (confirmBody) confirmBody.innerHTML = `Are you sure you want to delete <strong>${escapeHtml(studentName)}</strong>? This action cannot be undone.`;
        
        // Store the index to delete
        confirmYesBtn.setAttribute('data-delete-index', index);
        
        // Remove existing event listeners and add new one
        const newConfirmYesBtn = confirmYesBtn.cloneNode(true);
        confirmYesBtn.parentNode.replaceChild(newConfirmYesBtn, confirmYesBtn);
        
        newConfirmYesBtn.addEventListener('click', function() {
            const deleteIndex = this.getAttribute('data-delete-index');
            if (deleteIndex !== null) {
                performDeleteStudent(parseInt(deleteIndex));
            }
            if (confirmModal) confirmModal.hide();
        });
        
        if (confirmModal) confirmModal.show();
    }

    // Perform the actual delete
    function performDeleteStudent(index) {
        if (index < 0 || index >= students.length) return;
        
        const studentName = students[index].name || 'Student';
        
        // If we're editing this student, cancel edit mode
        if (editingIndex === index) {
            cancelEdit();
        } else if (editingIndex > index) {
            // If we deleted a student before the editing index, update the editing index
            editingIndex--;
        }
        
        // Remove the student
        students.splice(index, 1);
        saveStudents();
        updateUI();
        
        showAlert('Success', `${escapeHtml(studentName)} has been deleted successfully!`);
    }

    // Clear the input form
    function clearForm() { 
        document.getElementById('name').value = ''; 
        document.getElementById('birthday').value = ''; 
        document.getElementById('weight').value = ''; 
        document.getElementById('height').value = ''; 
        document.getElementById('sex').value = ''; 
        document.getElementById('assessmentForm')?.classList.remove('was-validated'); 
    }

    // Clear all students
    function clearAllStudents() {
        if (students.length === 0) { 
            showAlert('No Records', 'There are no student records to clear.'); 
            return; 
        }
        
        const confirmBody = document.getElementById('confirmBody');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmYesBtn = document.getElementById('confirmYesBtn');
        
        if (confirmTitle) confirmTitle.textContent = 'Clear All Records';
        if (confirmBody) confirmBody.innerHTML = `Clear all <strong>${students.length}</strong> student record(s)? This action cannot be undone.`;
        
        // Reset the confirm button for clear all
        const newConfirmYesBtn = confirmYesBtn.cloneNode(true);
        confirmYesBtn.parentNode.replaceChild(newConfirmYesBtn, confirmYesBtn);
        
        newConfirmYesBtn.addEventListener('click', function() {
            performClearAll();
            if (confirmModal) confirmModal.hide();
        });
        
        if (confirmModal) confirmModal.show();
    }

    // Perform the actual clear all
    function performClearAll() {
        students = [];
        editingIndex = -1; // Reset editing state
        saveStudents();
        updateUI();
        cancelEdit(); // Cancel any active edit
        showAlert('Cleared', 'All student records have been cleared.');
    }

    // Update the UI (table, counts, buttons)
    function updateUI() {
        const tbody = document.getElementById('studentTableBody');
        const count = document.getElementById('studentCount');
        const clearBtn = document.getElementById('clearAllBtn');
        const submitBtn = document.getElementById('submitBtn');
        
        if (count) count.textContent = students.length + ' Student' + (students.length !== 1 ? 's' : '');
        if (clearBtn) clearBtn.disabled = students.length === 0; 
        if (submitBtn) submitBtn.disabled = students.length === 0;
        
        if (!tbody) return;
        
        if (students.length === 0) { 
            tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted">No student records yet. Add some students above.</td></tr>'; 
            return; 
        }
        
        tbody.innerHTML = students.map((s, idx) => `
            <tr class="${getRowClass(s.nutritionalStatus)}">
                <td>${escapeHtml(s.name)}</td>
                <td>${s.birthday}</td>
                <td>${escapeHtml(s.grade)}</td>
                <td>${escapeHtml(s.school_year || s.year || 'N/A')}</td>
                <td>${s.weight}</td>
                <td>${s.height}</td>
                <td>${s.sex}</td>
                <td>${s.heightSquared}</td>
                <td>${s.ageDisplay}</td>
                <td>${s.bmi}</td>
                <td>${escapeHtml(s.nutritionalStatus)}</td>
                <td>${escapeHtml(s.heightForAge)}</td>
                <td>${escapeHtml(s.sbfpBeneficiary)}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-warning" onclick="editStudent(${idx})" title="Edit Student">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmDeleteStudent(${idx})" title="Delete Student">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Helper function to prevent XSS attacks
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Get row class based on nutritional status
    function getRowClass(status) { 
        switch (status) { 
            case 'Severely Wasted': return 'status-severely-wasted'; 
            case 'Wasted': return 'status-wasted'; 
            case 'Overweight': return 'status-overweight'; 
            case 'Obese': return 'status-obese'; 
            default: return ''; 
        } 
    }

    // Submit the report
    async function submitReport() {
        console.log('submitReport function called');
        
        if (!loadingModal) {
            console.error('Loading modal not initialized');
            // Try to initialize it again
            try {
                const loadingModalEl = document.getElementById('loadingModal');
                if (loadingModalEl) {
                    loadingModal = new bootstrap.Modal(loadingModalEl, { keyboard: false, backdrop: 'static' });
                } else {
                    showAlert('Error', 'Loading modal not found');
                    return;
                }
            } catch (e) {
                console.error('Error initializing loading modal:', e);
                showAlert('Error', 'Could not initialize loading modal');
                return;
            }
        }
        
        // Show loading modal
        try {
            loadingModal.show();
        } catch (e) {
            console.error('Error showing loading modal:', e);
        }
        
        try {
            const urlParams = new URLSearchParams(window.location.search);
            let assessmentType = urlParams.get('assessment_type') || 'baseline';
            const validTypes = ['baseline','midline','endline']; 
            if (!validTypes.includes(assessmentType)) assessmentType = 'baseline';
            
            // Get the bulk_store URL from config
            const bulkStoreUrl = window.nutritionalassessmentConfig?.urls?.bulk_store;
            if (!bulkStoreUrl) {
                throw new Error('Bulk store URL not configured');
            }
            
            console.log('Submitting to:', bulkStoreUrl);
            console.log('Students count:', students.length);
            
            const response = await fetch(bulkStoreUrl, {
                method: 'POST', 
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'students=' + encodeURIComponent(JSON.stringify(students)) + 
                    '&assessment_type=' + encodeURIComponent(assessmentType)
            });
            
            const result = await response.json();
            console.log('Submit result:', result);
            
            if (result.success) {
                students = []; 
                editingIndex = -1; // Reset editing state
                saveStudents(); 
                clearStoredStudents(); 
                updateUI(); 
                cancelEdit(); // Cancel any active edit
                
                // Hide loading modal
                try {
                    loadingModal.hide();
                } catch (e) {
                    console.error('Error hiding loading modal:', e);
                }
                
                showAlert('Success', result.message || 'Records submitted successfully!'); 
                
                // Redirect after success
                const redirectUrl = window.nutritionalassessmentConfig?.redirect_after;
                if (redirectUrl) {
                    setTimeout(() => { 
                        window.location.href = redirectUrl; 
                    }, 1500);
                } else {
                    console.warn('No redirect URL configured');
                }
            } else { 
                // Hide loading modal
                try {
                    loadingModal.hide();
                } catch (e) {
                    console.error('Error hiding loading modal:', e);
                }
                
                showAlert('Error', result.message || 'Error submitting records. Check console for details.'); 
                console.error('Submission errors:', result.errors); 
            }
        } catch (e) { 
            // Hide loading modal
            try {
                if (loadingModal) loadingModal.hide();
            } catch (err) {
                console.error('Error hiding loading modal:', err);
            }
            
            console.error('Network error:', e); 
            showAlert('Network Error', 'Error communicating with server: ' + e.message); 
        }
    }

    // Show submit confirmation modal
    function showSubmitConfirmation() {
        console.log('showSubmitConfirmation called');
        
        if (students.length === 0) {
            showAlert('No Records', 'There are no student records to submit.');
            return;
        }
        
        const confirmText = document.getElementById('submitConfirmText');
        if (confirmText) {
            confirmText.textContent = `You are about to submit ${students.length} student record(s) for assessment.`;
        }
        
        if (submitConfirmModal) {
            submitConfirmModal.show();
        } else {
            // Try to initialize submit confirm modal
            try {
                const submitConfirmModalEl = document.getElementById('submitConfirmModal');
                if (submitConfirmModalEl) {
                    submitConfirmModal = new bootstrap.Modal(submitConfirmModalEl);
                    submitConfirmModal.show();
                } else {
                    // Fallback if modal not initialized
                    if (confirm(`Submit ${students.length} student record(s)?`)) {
                        submitReport();
                    }
                }
            } catch (e) {
                console.error('Error showing submit confirm modal:', e);
                if (confirm(`Submit ${students.length} student record(s)?`)) {
                    submitReport();
                }
            }
        }
    }

    // Show alert modal
    function showAlert(title, message) { 
        console.log('showAlert:', title, message);
        
        const tEl = document.getElementById('alertTitle'); 
        const bEl = document.getElementById('alertBody'); 
        if (tEl) tEl.textContent = title; 
        if (bEl) bEl.textContent = message; 
        
        if (alertModal) { 
            try {
                alertModal.show(); 
            } catch (e) {
                console.error('Error showing alert modal:', e);
                window.alert(title + '\n\n' + message);
            }
        } else { 
            // Try to initialize alert modal
            try {
                const alertModalEl = document.getElementById('alertModal');
                if (alertModalEl) {
                    alertModal = new bootstrap.Modal(alertModalEl);
                    alertModal.show();
                } else {
                    window.alert(title + '\n\n' + message);
                }
            } catch (e) {
                console.error('Error initializing alert modal:', e);
                window.alert(title + '\n\n' + message);
            }
        } 
    }

    // Switch assessment type
    function switchAssessmentType(type) {
        if (students.length > 0) {
            if (!confirm('Switching assessment type will clear all current student records. Continue?')) {
                return;
            }
            // Clear students if user confirms
            students = [];
            editingIndex = -1;
            saveStudents();
            updateUI();
            cancelEdit();
        }
        
        // Redirect to the same page with new assessment type
        const url = new URL(window.location.href);
        url.searchParams.set('assessment_type', type);
        window.location.href = url.toString();
    }

    // Extract data from Excel
    function extractFromExcel() {
        const fileInput = document.getElementById('excelFile');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showAlert('No File', 'Please select a file to upload.');
            return;
        }
        
        const file = fileInput.files[0];
        
        const fileExtension = file.name.split('.').pop().toLowerCase(); 
        if (!['xlsx','xls','csv'].includes(fileExtension)) { 
            showAlert('Invalid File', 'Please select an Excel file (.xlsx, .xls) or CSV file'); 
            return; 
        }
        if (file.size > 5 * 1024 * 1024) { 
            showAlert('File Too Large', 'Please select a file smaller than 5MB'); 
            return; 
        }
        
        if (uploadLoadingModal) uploadLoadingModal.show(); 
        
        const chooseFileBtn = document.getElementById('chooseFileBtn');
        if (chooseFileBtn) chooseFileBtn.disabled = true;
        
        const formData = new FormData(); 
        formData.append('excel_file', file);
        
        // Get the process_excel URL from config
        const processExcelUrl = window.nutritionalassessmentConfig?.urls?.process_excel;
        if (!processExcelUrl) {
            uploadLoadingModal?.hide();
            if (chooseFileBtn) chooseFileBtn.disabled = false;
            showAlert('Configuration Error', 'Excel processing URL not configured');
            return;
        }
        
        fetch(processExcelUrl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                uploadLoadingModal?.hide(); 
                if (chooseFileBtn) chooseFileBtn.disabled = false; 
                if (fileInput) fileInput.value = '';
                
                if (data.success) {
                    addExtractedStudentsDirectly(data.students || [], data.message);
                } else {
                    showAlert('Processing Error', data.message);
                }
            })
            .catch(error => { 
                uploadLoadingModal?.hide(); 
                if (chooseFileBtn) chooseFileBtn.disabled = false; 
                if (fileInput) fileInput.value = ''; 
                showAlert('Error', 'An error occurred while processing the file: ' + error.message); 
            });
    }

    // Add extracted students to the list
    function addExtractedStudentsDirectly(extractedStudents, message) {
        if (extractedStudents.length === 0) { 
            showAlert('No Students', 'No valid student data found in the file.'); 
            return; 
        }
        
        let addedCount = 0; 
        let skippedCount = 0;
        
        extractedStudents.forEach((extractedStudent) => {
            const birthday = extractedStudent.birthday; 
            let ageYears=0, ageMonths=0, ageDisplay='0|0';
            
            if (birthday) { 
                const bdate = new Date(birthday); 
                const today = new Date(); 
                ageYears = today.getFullYear() - bdate.getFullYear(); 
                ageMonths = today.getMonth() - bdate.getMonth(); 
                if (ageMonths < 0) { 
                    ageYears--; 
                    ageMonths += 12; 
                } 
                ageDisplay = ageYears + '|' + ageMonths; 
            }
            
            if (!extractedStudent.name || !extractedStudent.birthday || !extractedStudent.weight || !extractedStudent.height || !extractedStudent.sex) { 
                skippedCount++; 
                return; 
            }
            
            const student = {
                name: extractedStudent.name, 
                birthday: extractedStudent.birthday, 
                weight: extractedStudent.weight, 
                height: extractedStudent.height, 
                sex: extractedStudent.sex,
                grade: document.getElementById('grade')?.value || '', 
                section: document.getElementById('section')?.value || '', 
                school_year: document.getElementById('school_year')?.value || '',
                date: document.getElementById('date')?.value || '', 
                legislative_district: document.getElementById('legislative_district')?.value || '', 
                school_district: document.getElementById('school_district')?.value || '',
                school_id: document.getElementById('school_id')?.value || '', 
                school_name: document.getElementById('school_name')?.value || '',
                heightSquared: extractedStudent.height_squared || (extractedStudent.height ? (extractedStudent.height * extractedStudent.height).toFixed(4) : null),
                age: ageDisplay, 
                ageYears, 
                ageMonths, 
                ageDisplay, 
                bmi: extractedStudent.bmi, 
                nutritionalStatus: extractedStudent.nutritional_status || 'Not Specified', 
                heightForAge: extractedStudent.height_for_age || 'Not Specified',
                sbfpBeneficiary: extractedStudent.sbfp_beneficiary || ((extractedStudent.nutritional_status === 'Severely Wasted' || extractedStudent.nutritional_status === 'Wasted') ? 'Yes' : 'No')
            };
            students.push(student); 
            addedCount++;
        });
        
        saveStudents(); 
        updateUI(); 
        
        let successMessage = `Successfully added ${addedCount} student(s) to the list.`; 
        if (skippedCount > 0) successMessage += ` ${skippedCount} record(s) were skipped due to missing data.`; 
        showAlert('Excel Import Complete', successMessage);
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Nutritional Assessment JS initialized');
        
        // Initialize Bootstrap modals with error handling
        try {
            const alertModalEl = document.getElementById('alertModal');
            if (alertModalEl) alertModal = new bootstrap.Modal(alertModalEl);
            
            const loadingModalEl = document.getElementById('loadingModal');
            if (loadingModalEl) loadingModal = new bootstrap.Modal(loadingModalEl, { keyboard: false, backdrop: 'static' });
            
            const uploadLoadingModalEl = document.getElementById('uploadLoadingModal');
            if (uploadLoadingModalEl) uploadLoadingModal = new bootstrap.Modal(uploadLoadingModalEl, { keyboard: false, backdrop: 'static' });
            
            const confirmModalEl = document.getElementById('confirmModal');
            if (confirmModalEl) confirmModal = new bootstrap.Modal(confirmModalEl);
            
            const submitConfirmModalEl = document.getElementById('submitConfirmModal');
            if (submitConfirmModalEl) submitConfirmModal = new bootstrap.Modal(submitConfirmModalEl);
        } catch (e) {
            console.error('Error initializing modals:', e);
        }

        // Set default date to today
        const dateInput = document.getElementById('date');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        
        // Load existing students from localStorage
        loadStudents(); 
        updateUI();

        // Event listeners - using named functions to avoid conflicts
        const assessmentForm = document.getElementById('assessmentForm');
        if (assessmentForm) {
            // Remove any existing listeners and add new one
            assessmentForm.removeEventListener('submit', addOrUpdateStudent);
            assessmentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                addOrUpdateStudent();
            });
        }
        
        const clearFormBtn = document.getElementById('clearFormBtn');
        if (clearFormBtn) {
            clearFormBtn.removeEventListener('click', clearForm);
            clearFormBtn.addEventListener('click', clearForm);
        }
        
        const clearAllBtn = document.getElementById('clearAllBtn');
        if (clearAllBtn) {
            clearAllBtn.removeEventListener('click', clearAllStudents);
            clearAllBtn.addEventListener('click', clearAllStudents);
        }
        
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            console.log('Submit button found, adding event listener');
            submitBtn.removeEventListener('click', showSubmitConfirmation);
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Submit button clicked');
                showSubmitConfirmation();
            });
        }

        // File upload handlers
        const chooseFileBtn = document.getElementById('chooseFileBtn');
        if (chooseFileBtn) {
            chooseFileBtn.removeEventListener('click', function() {});
            chooseFileBtn.addEventListener('click', function() { 
                document.getElementById('excelFile')?.click(); 
            });
        }
        
        const excelFile = document.getElementById('excelFile');
        if (excelFile) {
            excelFile.removeEventListener('change', function() {});
            excelFile.addEventListener('change', function(event) { 
                if (event.target.files.length > 0) extractFromExcel(); 
            });
        }

        // Submit confirm modal button
        const submitConfirmYesBtn = document.getElementById('submitConfirmYesBtn');
        if (submitConfirmYesBtn) {
            submitConfirmYesBtn.removeEventListener('click', function() {});
            submitConfirmYesBtn.addEventListener('click', function() {
                console.log('Submit confirm button clicked');
                if (submitConfirmModal) submitConfirmModal.hide();
                submitReport();
            });
        }

        // Assessment type switchers (if they exist in the DOM)
        const switchToBaseline = document.getElementById('switchToBaseline'); 
        const switchToEndline = document.getElementById('switchToEndline');
        const switchToMidline = document.getElementById('switchToMidline');
        
        if (switchToBaseline) {
            switchToBaseline.removeEventListener('click', function() {});
            switchToBaseline.addEventListener('click', function() { switchAssessmentType('baseline'); });
        }
        if (switchToEndline) {
            switchToEndline.removeEventListener('click', function() {});
            switchToEndline.addEventListener('click', function() { switchAssessmentType('endline'); });
        }
        if (switchToMidline) {
            switchToMidline.removeEventListener('click', function() {});
            switchToMidline.addEventListener('click', function() { switchAssessmentType('midline'); });
        }
    });

    // Make functions globally available
    window.editStudent = editStudent;
    window.confirmDeleteStudent = confirmDeleteStudent;
    window.removeStudent = removeStudent;
    window.cancelEdit = cancelEdit;
    window.addOrUpdateStudent = addOrUpdateStudent;
    window.showSubmitConfirmation = showSubmitConfirmation;
    window.submitReport = submitReport;