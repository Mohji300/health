    // Storage prefix for localStorage
    const STORAGE_PREFIX = 'nutritional_assessment_';
    let students = [];
    let alertModal = null;
    let loadingModal = null;
    let uploadLoadingModal = null;
    let confirmModal = null;
    let submitConfirmModal = null;
    let editingIndex = -1;
    let currentUploadController = null;

    // Track if upload is in progress to prevent concurrent uploads
    let isUploading = false;

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
    function calculateStudentData(name, birthday, weight, height, sex, weighingDate) {
        let ageYears = 0, ageMonths = 0;
        if (birthday && weighingDate) {
            const bdate = new Date(birthday);
            const wdate = new Date(weighingDate);
            ageYears = wdate.getFullYear() - bdate.getFullYear();
            ageMonths = wdate.getMonth() - bdate.getMonth();
            if (ageMonths < 0) { 
                ageYears--; 
                ageMonths += 12; 
            }
                const days = wdate.getDate() - bdate.getDate();
                if (days < 0) {
                    ageMonths--;
                    if (ageMonths < 0) {
                        ageMonths += 12;
                        ageYears--;
                    }
                }
        }

        const heightSq = (height * height).toFixed(4);
        // Use 2 decimal places to match server rounding
        const bmi = parseFloat((weight / (height * height)).toFixed(2));

        // Do not compute WHO classification on client; show PENDING until server confirms
        let nutritionalStatus = 'PENDING';
        const sbfpBeneficiary = 'NO';

        return {
            name: name.trim(), 
            birthday, 
            weight: parseFloat(weight), 
            height: parseFloat(height), 
            sex,
            grade: document.getElementById('grade')?.value || '', 
            section: document.getElementById('section')?.value || '',
            school_year: document.getElementById('school_year')?.value || '', 
            date: weighingDate,
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

    function addOrUpdateStudent() {
        const form = document.getElementById('assessmentForm');
        if (!form.checkValidity()) { 
            form.classList.add('was-validated'); 
            return; 
        }
        
        // Get values and convert to uppercase
        let firstName = document.getElementById('first_name').value.trim().toUpperCase();
        let middleInitial = document.getElementById('middle_initial').value.trim().toUpperCase();
        let lastName = document.getElementById('last_name').value.trim().toUpperCase();
        const birthday = document.getElementById('birthday').value;
        const weight = document.getElementById('weight').value;
        const height = document.getElementById('height').value;
        let sex = document.getElementById('sex').value.toUpperCase();
        const date = document.getElementById('date').value;
        
        if (!firstName || !lastName || !birthday || !weight || !height || !sex || !date) {
            showAlert('Please fill in all required fields', 'warning');
            form.classList.add('was-validated');
            return;
        }
        
        // Build full name in "LAST, FIRST M.I" format (all uppercase)
        let fullName = lastName;
        if (middleInitial) {
            fullName += ' ' + middleInitial + '.';
        }
        fullName += ' ' + firstName;
        
        const student = calculateStudentData(fullName, birthday, weight, height, sex, date);
        
        // Store individual components in uppercase
        student.first_name = firstName;
        student.middle_initial = middleInitial;
        student.last_name = lastName;

        const currentDate = document.getElementById('date').value;
        if (currentDate) {
            localStorage.setItem('last_weighing_date', currentDate);
        }

        if (editingIndex >= 0 && editingIndex < students.length) {
            const idx = editingIndex;
            students[idx] = student;
            saveStudents();
            fetchClassificationForStudent(idx);
            cancelEdit();
            showAlert('Success', 'Student record updated successfully!');
        } else {
            students.push(student);
            saveStudents();
            clearForm();
            showAlert('Success', 'Student added to the list!');
            fetchClassificationForStudent(students.length - 1);
        }
        updateUI();
        fetchClassificationForStudent(editingIndex);
    }

    function fetchClassificationForStudent(index) {
        if (index < 0 || index >= students.length) {
            return;
        }
        const student = students[index];

        const classifyUrl = window.nutritionalassessmentConfig?.urls?.classify;
        if (!classifyUrl) {
            return;
        }

        // Guard against missing fields
        if (!student.birthday || !student.weight || !student.height || !student.sex || !student.date) {
            student.nutritionalStatus = 'NORMAL';
            student.heightForAge = 'NORMAL';
            saveStudents();
            updateUI();
            return;
        }

        const payload = {
            birthday: student.birthday,
            weight: student.weight,
            height: student.height,
            sex: student.sex,
            date: student.date
        };

        fetch(classifyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data && data.success) {
                // Update student with authoritative values
                student.bmi = parseFloat(data.bmi);
                student.nutritionalStatus = (data.nutritional_status || '').toUpperCase();
                student.heightForAge = (data.height_for_age || 'Normal').toUpperCase();

                // Update beneficiary
                const nutStatus = student.nutritionalStatus;
                const hfa = student.heightForAge;
                student.sbfpBeneficiary = (nutStatus === 'SEVERELY WASTED' || nutStatus === 'WASTED') ? 'YES' : 'NO';

                saveStudents();
                updateUI();
                // Show a brief success notification (optional)
                showNotification('Classification updated', 'success');
            } else {
                const msg = data.message || 'Classification failed';
                // Fallback to NORMAL
                student.nutritionalStatus = 'NORMAL';
                student.heightForAge = 'NORMAL';
                saveStudents();
                updateUI();
                showAlert('Classification Error', msg);
            }
        })
        .catch(err => {
            // Retry logic (max 2 attempts)
            if (!student._classificationRetries) student._classificationRetries = 0;
            if (student._classificationRetries < 2) {
                student._classificationRetries++;
                setTimeout(() => fetchClassificationForStudent(index), 2000);
            } else {
                student.nutritionalStatus = 'NORMAL';
                student.heightForAge = 'NORMAL';
                saveStudents();
                updateUI();
                showAlert('Classification Failed', 'Could not classify after multiple attempts.');
            }
        });
    }

    function showNotification(msg, type) {
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed top-0 end-0 m-3';
        alertDiv.style.zIndex = 9999;
        alertDiv.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alertDiv);
        setTimeout(function() {
            alertDiv.classList.remove('show');
            setTimeout(function() { alertDiv.remove(); }, 300);
        }, 3000);
    }

    function editStudent(index) {
        if (index < 0 || index >= students.length) return;
        
        const student = students[index];
        editingIndex = index;
        
        document.getElementById('first_name').value = student.first_name || '';
        document.getElementById('middle_initial').value = student.middle_initial || '';
        document.getElementById('last_name').value = student.last_name || '';
        document.getElementById('birthday').value = student.birthday || '';
        document.getElementById('weight').value = student.weight || '';
        document.getElementById('height').value = student.height || '';
        document.getElementById('sex').value = student.sex || '';
        
        const dateInput = document.getElementById('date');
        if (dateInput) {
            if (student.date) {
                dateInput.value = student.date;
            } else {
                dateInput.value = new Date().toISOString().split('T')[0];
            }
        }
        
        const submitBtn = document.querySelector('#assessmentForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Student';
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-warning');
        }
        
        if (!document.getElementById('cancelEditBtn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.id = 'cancelEditBtn';
            cancelBtn.className = 'btn btn-secondary ms-2';
            cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
            cancelBtn.onclick = cancelEdit;
            submitBtn.parentNode.appendChild(cancelBtn);
        }
        
        const header = document.querySelector('.card-header.bg-primary');
        if (header) header.scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * Parse a full name into First Name, Middle Initial, Last Name
     * Handles format: "Last, First M.I" or "First Last" or "Last, First"
     */
    function parseFullName(fullName) {
        if (!fullName) return { firstName: '', middleInitial: '', lastName: '' };
        
        let firstName = '';
        let middleInitial = '';
        let lastName = '';
        
        // Check if name contains a comma (Last, First M.I format)
        if (fullName.includes(',')) {
            const parts = fullName.split(',');
            lastName = parts[0].trim();
            
            const firstPart = parts[1] ? parts[1].trim() : '';
            const nameParts = firstPart.split(' ');
            
            if (nameParts.length >= 1) {
                firstName = nameParts[0];
            }
            if (nameParts.length >= 2) {
                // Check if second part is a middle initial
                let mi = nameParts[nameParts.length - 1];
                if ((mi.length === 1 || (mi.length === 2 && mi.endsWith('.'))) && /^[A-Za-z]\.?$/.test(mi)) {
                    middleInitial = mi.replace('.', '');
                    // If the middle initial was at the end, the first name is everything before it
                    firstName = nameParts.slice(0, -1).join(' ');
                }
            }
        } else {
            const nameParts = fullName.trim().split(' ');
            
            if (nameParts.length === 1) {
                firstName = nameParts[0];
            } else if (nameParts.length === 2) {
                firstName = nameParts[0];
                lastName = nameParts[1];
            } else if (nameParts.length >= 3) {
                firstName = nameParts[0];
                const possibleMI = nameParts[1];
                if ((possibleMI.length === 1 || (possibleMI.length === 2 && possibleMI.endsWith('.'))) && /^[A-Za-z]\.?$/.test(possibleMI)) {
                    middleInitial = possibleMI.replace('.', '');
                    lastName = nameParts.slice(2).join(' ');
                } else {
                    lastName = nameParts.slice(1).join(' ');
                }
            }
        }
        
        return { firstName, middleInitial, lastName };
    }

    function cancelEdit() {
        editingIndex = -1;
        clearForm();
        
        const submitBtn = document.querySelector('#assessmentForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Student to List';
            submitBtn.classList.remove('btn-warning');
            submitBtn.classList.add('btn-success');
        }
        
        const cancelBtn = document.getElementById('cancelEditBtn');
        if (cancelBtn) {
            cancelBtn.remove();
        }
    }

    function confirmDeleteStudent(index) {
        if (index < 0 || index >= students.length) return;
        
        const studentName = students[index].name || 'this student';
        
        const confirmBody = document.getElementById('confirmBody');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmYesBtn = document.getElementById('confirmYesBtn');
        
        if (confirmTitle) confirmTitle.textContent = 'Delete Student';
        if (confirmBody) confirmBody.innerHTML = `Are you sure you want to delete <strong>${escapeHtml(studentName)}</strong>? This action cannot be undone.`;
        
        confirmYesBtn.setAttribute('data-delete-index', index);
        
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

    function performDeleteStudent(index) {
        if (index < 0 || index >= students.length) return;
        
        const studentName = students[index].name || 'Student';
        
        if (editingIndex === index) {
            cancelEdit();
        } else if (editingIndex > index) {
            editingIndex--;
        }
        
        students.splice(index, 1);
        saveStudents();
        updateUI();
        
        showAlert('Success', `${escapeHtml(studentName)} has been deleted successfully!`);
    }

    function clearForm() { 
        document.getElementById('first_name').value = ''; 
        document.getElementById('middle_initial').value = ''; 
        document.getElementById('last_name').value = ''; 
        document.getElementById('birthday').value = ''; 
        document.getElementById('weight').value = ''; 
        document.getElementById('height').value = ''; 
        document.getElementById('sex').value = ''; 
        document.getElementById('assessmentForm')?.classList.remove('was-validated'); 
    }

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
        
        const newConfirmYesBtn = confirmYesBtn.cloneNode(true);
        confirmYesBtn.parentNode.replaceChild(newConfirmYesBtn, confirmYesBtn);
        
        newConfirmYesBtn.addEventListener('click', function() {
            performClearAll();
            if (confirmModal) confirmModal.hide();
        });
        
        if (confirmModal) confirmModal.show();
    }

    function performClearAll() {
        students = [];
        editingIndex = -1;
        saveStudents();
        updateUI();
        cancelEdit();
        showAlert('Cleared', 'All student records have been cleared.');
    }

    function formatDateToMonthDayYear(dateString) {
        if (!dateString) return '';
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            
            return `${month}/${day}/${year}`;
        } catch (e) {
            return dateString;
        }
    }

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
        
        tbody.innerHTML = students.map((s, idx) => {
            let displayName = '';
            if (s.last_name) {
                displayName = s.last_name;
                if (s.first_name) {
                    displayName += ', ' + s.first_name;
                    if (s.middle_initial) {
                        displayName += ' ' + s.middle_initial + '.';
                    }
                }
            } else if (s.name) {
                displayName = s.name;
            }
            
            let formattedBirthday = s.birthday;
            if (s.birthday) {
                formattedBirthday = formatDateToMonthDayYear(s.birthday);
            }
            
            return `
                <tr class="${getRowClass(s.nutritionalStatus)}">
                    <td>${escapeHtml(displayName)}</td>
                    <td>${escapeHtml(formattedBirthday)}</td>
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
            `;
        }).join('');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getRowClass(status) { 
        switch (status) { 
            case 'SEVERELY WASTED': return 'status-severely-wasted'; 
            case 'WASTED': return 'status-wasted'; 
            case 'OVERWEIGHT': return 'status-overweight'; 
            case 'OBESE': return 'status-obese'; 
            default: return ''; 
        } 
    }

    async function submitReport() {
        if (!loadingModal) {
            try {
                const loadingModalEl = document.getElementById('loadingModal');
                if (loadingModalEl) {
                    loadingModal = new bootstrap.Modal(loadingModalEl, { keyboard: false, backdrop: 'static' });
                } else {
                    showAlert('Error', 'Loading modal not found');
                    return;
                }
            } catch (e) {
                showAlert('Error', 'Could not initialize loading modal');
                return;
            }
        }

        try {
            loadingModal.show();
        } catch (e) {}

        try {
            const urlParams = new URLSearchParams(window.location.search);
            let assessmentType = urlParams.get('assessment_type') || 'baseline';
            const validTypes = ['baseline', 'midline', 'endline'];
            if (!validTypes.includes(assessmentType)) assessmentType = 'baseline';

            const studentsToSubmit = students.map(student => {
                const studentData = {};

                let fullName = '';
                if (student.last_name) {
                    fullName = student.last_name + ', ' + (student.first_name || '');
                    if (student.middle_initial) {
                        fullName += ' ' + student.middle_initial + '.';
                    }
                } else if (student.name) {
                    fullName = student.name;
                }

                studentData.name = fullName;
                studentData.first_name = student.first_name || '';
                studentData.middle_initial = student.middle_initial || '';
                studentData.last_name = student.last_name || '';
                studentData.birthday = student.birthday || '';
                studentData.weight = parseFloat(student.weight) || 0;
                studentData.height = parseFloat(student.height) || 0;
                studentData.sex = student.sex || '';
                studentData.grade = student.grade || document.getElementById('grade')?.value || '';
                studentData.section = student.section || document.getElementById('section')?.value || '';
                studentData.school_year = student.school_year || document.getElementById('school_year')?.value || '';
                studentData.date = student.date || document.getElementById('date')?.value || '';
                studentData.legislative_district = student.legislative_district || document.getElementById('legislative_district')?.value || '';
                studentData.school_district = student.school_district || document.getElementById('school_district')?.value || '';
                studentData.school_id = student.school_id || document.getElementById('school_id')?.value || '';
                studentData.school_name = student.school_name || document.getElementById('school_name')?.value || '';

                studentData.bmi = student.bmi || 0;
                studentData.nutritionalStatus = student.nutritionalStatus || 'Normal';
                studentData.heightSquared = student.heightSquared || (student.height * student.height).toFixed(4);
                studentData.age = student.age || '0|0';
                studentData.ageDisplay = student.ageDisplay || student.age || '0|0';
                studentData.heightForAge = student.heightForAge || 'Normal';
                studentData.sbfpBeneficiary = student.sbfpBeneficiary ||
                    ((student.nutritionalStatus === 'Severely Wasted' || student.nutritionalStatus === 'Wasted') ? 'Yes' : 'No');

                return studentData;
            });

            const bulkStoreUrl = window.nutritionalassessmentConfig?.urls?.bulk_store;
            if (!bulkStoreUrl) {
                throw new Error('Bulk store URL not configured');
            }

            const response = await fetch(bulkStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'students=' + encodeURIComponent(JSON.stringify(studentsToSubmit)) +
                    '&assessment_type=' + encodeURIComponent(assessmentType)
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server error (${response.status}): ${errorText.substring(0, 200)}`);
            }

            const result = await response.json();

            const created = result.created_records || [];
            const updated = result.updated_records || [];
            const merged = created.concat(updated);
            if (merged.length > 0) {
                const mapped = merged.map(r => {
                    const ageParts = (r.age || '0|0').split('|');
                    const ageYears = parseInt(ageParts[0]) || 0;
                    const ageMonths = parseInt(ageParts[1]) || 0;

                    return {
                        name: r.name || '',
                        first_name: r.first_name || '',
                        middle_initial: r.middle_initial || '',
                        last_name: r.last_name || '',
                        birthday: r.birthday || '',
                        weight: parseFloat(r.weight) || 0,
                        height: parseFloat(r.height) || 0,
                        sex: r.sex || '',
                        grade: r.grade_level || r.grade || '',
                        section: r.section || '',
                        school_year: r.year || r.school_year || '',
                        date: r.date_of_weighing || r.date || '',
                        legislative_district: r.legislative_district || '',
                        school_district: r.school_district || '',
                        school_id: r.school_id || '',
                        school_name: r.school_name || '',
                        heightSquared: r.height_squared || (r.height ? (r.height * r.height).toFixed(4) : ''),
                        age: r.age || (ageYears + '|' + ageMonths),
                        ageYears: ageYears,
                        ageMonths: ageMonths,
                        ageDisplay: r.age || (ageYears + '|' + ageMonths),
                        bmi: parseFloat(r.bmi) || 0,
                        nutritionalStatus: r.nutritional_status ? r.nutritional_status.toUpperCase() : 'PENDING',
                        heightForAge: r.height_for_age || 'Normal',
                        sbfpBeneficiary: (r.sbfp_beneficiary || 'No').toUpperCase()
                    };
                });
                students = mapped;
                saveStudents();
                updateUI();
            } else {
                students = [];
                editingIndex = -1;
                saveStudents();
                clearStoredStudents();
                updateUI();
                cancelEdit();
            }

            try {
                loadingModal.hide();
            } catch (e) {}

            if (result.success) {
                let successMessage = result.message || 'Records submitted successfully!';
                if (result.created_count !== undefined && result.updated_count !== undefined) {
                    successMessage = `Submitted successfully!\n\nNew records: ${result.created_count}\nUpdated records: ${result.updated_count}\nTotal: ${result.total_count}`;
                }
                showAlert('Success', successMessage);

                const redirectUrl = window.nutritionalassessmentConfig?.redirect_after;
                if (redirectUrl) {
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 2000);
                } else {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } else {
                let errorMessage = result.message || 'Error submitting records.';
                if (result.errors && result.errors.length > 0) {
                    errorMessage += '\n\nErrors:\n' + result.errors.slice(0, 5).join('\n');
                    if (result.errors.length > 5) {
                        errorMessage += `\n... and ${result.errors.length - 5} more errors.`;
                    }
                }
                showAlert('Error', errorMessage);
            }
        } catch (e) {
            try {
                if (loadingModal) loadingModal.hide();
            } catch (err) {}
            showAlert('Network Error', 'Error communicating with server: ' + e.message);
        }
    }

    function showSubmitConfirmation() {
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
            try {
                const submitConfirmModalEl = document.getElementById('submitConfirmModal');
                if (submitConfirmModalEl) {
                    submitConfirmModal = new bootstrap.Modal(submitConfirmModalEl);
                    submitConfirmModal.show();
                } else {
                    if (confirm(`Submit ${students.length} student record(s)?`)) {
                        submitReport();
                    }
                }
            } catch (e) {
                if (confirm(`Submit ${students.length} student record(s)?`)) {
                    submitReport();
                }
            }
        }
    }

    function showAlert(title, message) { 
        const tEl = document.getElementById('alertTitle'); 
        const bEl = document.getElementById('alertBody'); 
        if (tEl) tEl.textContent = title; 
        if (bEl) bEl.textContent = message; 
        
        if (alertModal) { 
            try {
                alertModal.show(); 
            } catch (e) {
                window.alert(title + '\n\n' + message);
            }
        } else { 
            try {
                const alertModalEl = document.getElementById('alertModal');
                if (alertModalEl) {
                    alertModal = new bootstrap.Modal(alertModalEl);
                    alertModal.show();
                } else {
                    window.alert(title + '\n\n' + message);
                }
            } catch (e) {
                window.alert(title + '\n\n' + message);
            }
        } 
    }

    function switchAssessmentType(type) {
        if (students.length > 0) {
            if (!confirm('Switching assessment type will clear all current student records. Continue?')) {
                return;
            }
            students = [];
            editingIndex = -1;
            saveStudents();
            updateUI();
            cancelEdit();
        }
        
        const url = new URL(window.location.href);
        url.searchParams.set('assessment_type', type);
        window.location.href = url.toString();
    }

    function extractFromExcel() {
        // Prevent concurrent uploads
        if (isUploading) {
            showAlert('Please Wait', 'An upload is already in progress. Please wait.');
            return;
        }

        const fileInput = document.getElementById('excelFile');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showAlert('No File', 'Please select a file to upload.');
            return;
        }

        const file = fileInput.files[0];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(fileExtension)) {
            showAlert('Invalid File', 'Please select an Excel file (.xlsx, .xls) or CSV file');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showAlert('File Too Large', 'Please select a file smaller than 5MB');
            return;
        }

        // --- Start upload process ---
        isUploading = true;
        currentUploadController = new AbortController();
        const timeoutId = setTimeout(() => {
            if (currentUploadController) {
                currentUploadController.abort();
            }
        }, 60000); // 60 second timeout

        // Show the loading modal (assumed already initialized once on DOMContentLoaded)
        if (uploadLoadingModal) {
            uploadLoadingModal.show();
        } else {
            // Fallback if modal not initialized
            console.warn('uploadLoadingModal not initialized');
        }

        // Disable the "Choose File" button while processing
        const chooseFileBtn = document.getElementById('chooseFileBtn');
        if (chooseFileBtn) chooseFileBtn.disabled = true;

        const formData = new FormData();
        formData.append('excel_file', file);

        const processExcelUrl = window.nutritionalassessmentConfig?.urls?.process_excel;
        if (!processExcelUrl) {
            // Cleanup and exit
            cleanupUpload('Excel processing URL not configured');
            return;
        }

        fetch(processExcelUrl, {
            method: 'POST',
            body: formData,
            signal: currentUploadController.signal
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.students && data.students.length > 0 && data.students[0].date) {
                        localStorage.setItem('last_weighing_date', data.students[0].date);
                    }
                    addExtractedStudentsDirectly(data.students || [], data.message);
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                let errorMessage = 'An error occurred while processing the file.';
                if (error.name === 'AbortError') {
                    errorMessage = 'Request timeout. The file may be too large or the server is slow.';
                } else if (error.message.includes('HTTP error')) {
                    errorMessage = 'Server error. Please try again later.';
                } else if (error.message.includes('Failed to fetch')) {
                    errorMessage = 'Network error. Please check your connection.';
                } else if (error.message) {
                    errorMessage = error.message;
                }
                showAlert('Error', errorMessage);
            })
            .finally(() => {
                // --- ALWAYS clean up, whether success or error ---
                cleanupUpload(null);
            });

        // Helper function for consistent cleanup
        function cleanupUpload(errorMsg) {
            // Clear timeout
            clearTimeout(timeoutId);

            // Hide the loading modal
            if (uploadLoadingModal) {
                try {
                    uploadLoadingModal.hide();
                } catch (e) {
                    console.warn('Error hiding modal:', e);
                }
            }

            // Re-enable the file chooser button
            if (chooseFileBtn) chooseFileBtn.disabled = false;
            if (fileInput) fileInput.value = '';

            // Reset upload flags
            isUploading = false;
            currentUploadController = null;

            // Optionally show an error message if one was passed
            if (errorMsg) {
                showAlert('Upload Error', errorMsg);
            }
        }
    }
    
    // Add extracted students to the list
    function addExtractedStudentsDirectly(extractedStudents, message) {
        if (extractedStudents.length === 0) {
            showAlert('No Students', 'No valid student data found in the file.');
            return;
        }

        let addedCount = 0;
        let skippedCount = 0;

        const weighingDateFromExcel = extractedStudents[0]?.date;
        const dateInput = document.getElementById('date');
        if (dateInput && weighingDateFromExcel) {
            dateInput.value = weighingDateFromExcel;
        }

        extractedStudents.forEach((extractedStudent) => {
            const birthday = extractedStudent.birthday;
            let ageYears = 0, ageMonths = 0, ageDisplay = '0|0';

            const weighingDate = extractedStudent.date ? new Date(extractedStudent.date) : null;

            if (birthday && weighingDate) {
                const bdate = new Date(birthday);
                ageYears = weighingDate.getFullYear() - bdate.getFullYear();
                ageMonths = weighingDate.getMonth() - bdate.getMonth();
                if (ageMonths < 0) {
                    ageYears--;
                    ageMonths += 12;
                }
                // Day adjustment
                const days = weighingDate.getDate() - bdate.getDate();
                if (days < 0) {
                    ageMonths--;
                    if (ageMonths < 0) {
                        ageMonths += 12;
                        ageYears--;
                    }
                }
                ageDisplay = ageYears + '|' + ageMonths;
            }

            if (!extractedStudent.name || !extractedStudent.birthday || !extractedStudent.weight || !extractedStudent.height || !extractedStudent.sex) {
                skippedCount++;
                return;
            }

            // --- Parse name and convert everything to UPPERCASE ---
            const fullName = extractedStudent.name.trim().toUpperCase();
            let lastName = '';
            let firstName = '';
            let middleInitial = '';

            if (fullName.includes(',')) {
                const commaIndex = fullName.indexOf(',');
                lastName = fullName.substring(0, commaIndex).trim();
                let rightPart = fullName.substring(commaIndex + 1).trim();
                const nameParts = rightPart.split(' ');

                if (nameParts.length > 0) {
                    const lastPart = nameParts[nameParts.length - 1];
                    const isMiddleInitial = (lastPart.length === 1 || (lastPart.length === 2 && lastPart.endsWith('.'))) &&
                        /^[A-Za-z]\.?$/.test(lastPart);

                    if (isMiddleInitial && nameParts.length >= 2) {
                        middleInitial = lastPart.replace('.', '');
                        firstName = nameParts.slice(0, -1).join(' ');
                    } else if (nameParts.length === 1) {
                        firstName = nameParts[0];
                    } else {
                        firstName = nameParts.join(' ');
                    }
                }
            } else {
                const nameParts = fullName.split(' ');
                if (nameParts.length === 1) {
                    firstName = nameParts[0];
                } else if (nameParts.length === 2) {
                    firstName = nameParts[0];
                    lastName = nameParts[1];
                } else if (nameParts.length >= 3) {
                    const possibleMI = nameParts[1];
                    if ((possibleMI.length === 1 || (possibleMI.length === 2 && possibleMI.endsWith('.'))) &&
                        /^[A-Za-z]\.?$/.test(possibleMI)) {
                        firstName = nameParts[0];
                        middleInitial = possibleMI.replace('.', '');
                        lastName = nameParts.slice(2).join(' ');
                    } else {
                        firstName = nameParts[0];
                        lastName = nameParts.slice(1).join(' ');
                    }
                }
            }

            // No case conversion – everything already uppercase
            // Build combined name in "LAST, FIRST M.I" format (uppercase)
            let combinedName = lastName;
            if (firstName) {
                combinedName += ', ' + firstName;
                if (middleInitial) {
                    combinedName += ' ' + middleInitial + '.';
                }
            }

            // Ensure sex is uppercase
            const sex = extractedStudent.sex ? extractedStudent.sex.toUpperCase() : '';

            const student = {
                name: combinedName,
                first_name: firstName,
                middle_initial: middleInitial,
                last_name: lastName,
                birthday: extractedStudent.birthday,
                weight: extractedStudent.weight,
                height: extractedStudent.height,
                sex: sex,
                grade: document.getElementById('grade')?.value || '',
                section: document.getElementById('section')?.value || '',
                school_year: document.getElementById('school_year')?.value || '',
                date: extractedStudent.date || dateInput?.value || '',
                legislative_district: document.getElementById('legislative_district')?.value || '',
                school_district: document.getElementById('school_district')?.value || '',
                school_id: document.getElementById('school_id')?.value || '',
                school_name: document.getElementById('school_name')?.value || '',
                heightSquared: extractedStudent.height_squared ? parseFloat(extractedStudent.height_squared) : (extractedStudent.height ? parseFloat((extractedStudent.height * extractedStudent.height).toFixed(4)) : null),
                age: ageDisplay,
                ageYears: ageYears,
                ageMonths: ageMonths,
                ageDisplay: ageDisplay,
                bmi: parseFloat(extractedStudent.bmi) || 0,
                nutritionalStatus: extractedStudent.nutritional_status ? extractedStudent.nutritional_status.toUpperCase() : 'PENDING',
                heightForAge: extractedStudent.height_for_age ? extractedStudent.height_for_age.toUpperCase() : 'NOT SPECIFIED',
                sbfpBeneficiary: (extractedStudent.nutritional_status === 'Severely Wasted' || extractedStudent.nutritional_status === 'Wasted') ? 'YES' : 'NO'
            };

            students.push(student);
            const newIndex = students.length - 1;
            // If nutritional status not provided by extractor, request server classification
            if (!extractedStudent.nutritional_status) {
                fetchClassificationForStudent(newIndex);
            }
            addedCount++;
        });

        saveStudents();
        updateUI();

        let successMessage = `Successfully added ${addedCount} student(s) to the list.`;
        if (skippedCount > 0) successMessage += ` ${skippedCount} record(s) were skipped due to missing data.`;
        if (!dateInput?.value && !weighingDateFromExcel) {
            successMessage += `\n\nNote: No weighing date was found in the Excel file. Please enter the date manually.`;
        }
        showAlert('Excel Import Complete', successMessage);
    }

    function removeStudent(index) {
        confirmDeleteStudent(index);
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap modals with error handling
        try {
            const alertModalEl = document.getElementById('alertModal');
            if (alertModalEl) alertModal = new bootstrap.Modal(alertModalEl);
            
            const loadingModalEl = document.getElementById('loadingModal');
            if (loadingModalEl) loadingModal = new bootstrap.Modal(loadingModalEl, { keyboard: false, backdrop: 'static' });
            
            // Don't initialize uploadLoadingModal here - will be created fresh on each upload
            
            const confirmModalEl = document.getElementById('confirmModal');
            if (confirmModalEl) confirmModal = new bootstrap.Modal(confirmModalEl);
            
            const submitConfirmModalEl = document.getElementById('submitConfirmModal');
            if (submitConfirmModalEl) submitConfirmModal = new bootstrap.Modal(submitConfirmModalEl);
        } catch (e) {
            console.error('Modal initialization error:', e);
        }
        
        loadStudents(); 
        // Apply URL parameters (if any) to pre-fill the form when opened from SBFP dashboard
        try {
            applyUrlParamsToForm();
        } catch (e) { console.warn('applyUrlParamsToForm error', e); }
        updateUI();

        // If opened with a weigh_date param, pre-fill the date and focus first name
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const dateInput = document.getElementById('date');
            
            // 1. URL date parameter has highest priority
            if (urlParams.has('date')) {
                dateInput.value = decodeURIComponent(urlParams.get('date'));
            }
            // 2. Otherwise, use existing assessment date from server
            else if (existingWeighingDate) {
                dateInput.value = existingWeighingDate;
                localStorage.setItem('last_weighing_date', existingWeighingDate);
            }
            // 3. Otherwise, use stored date from localStorage
            else {
                const storedDate = localStorage.getItem('last_weighing_date');
                if (storedDate && dateInput && !dateInput.value) {
                    dateInput.value = storedDate;
                }

                const firstNameInput = document.getElementById('first_name');
                if (firstNameInput) {
                    try { firstNameInput.focus(); } catch (e) {}
                }
            }
        } catch (e) {
            console.warn('Error parsing weigh_date param', e);
        }

        const assessmentForm = document.getElementById('assessmentForm');
        if (assessmentForm) {
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
            submitBtn.removeEventListener('click', showSubmitConfirmation);
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showSubmitConfirmation();
            });
        }

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

        const submitConfirmYesBtn = document.getElementById('submitConfirmYesBtn');
        if (submitConfirmYesBtn) {
            submitConfirmYesBtn.removeEventListener('click', function() {});
            submitConfirmYesBtn.addEventListener('click', function() {
                if (submitConfirmModal) submitConfirmModal.hide();
                submitReport();
            });
        }

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

    // Apply common query params to the form so Add Student opens with the correct grade/section
    function applyUrlParamsToForm() {
        const params = new URLSearchParams(window.location.search);
        const grade = params.get('grade');
        const section = params.get('section');
        const school_year = params.get('school_year');
        const dateParam = params.get('date');
        const legislative_district = params.get('legislative_district');
        const school_district = params.get('school_district');
        const school_id = params.get('school_id');
        const school_name = params.get('school_name');

        if (grade && document.getElementById('grade')) {
            document.getElementById('grade').value = decodeURIComponent(grade);
        }
        if (section && document.getElementById('section')) {
            document.getElementById('section').value = decodeURIComponent(section);
        }
        if (school_year && document.getElementById('school_year')) {
            document.getElementById('school_year').value = decodeURIComponent(school_year);
        }
        if (legislative_district && document.getElementById('legislative_district')) {
            document.getElementById('legislative_district').value = decodeURIComponent(legislative_district);
        }
        if (school_district && document.getElementById('school_district')) {
            document.getElementById('school_district').value = decodeURIComponent(school_district);
        }
        if (school_id && document.getElementById('school_id')) {
            document.getElementById('school_id').value = decodeURIComponent(school_id);
        }
        if (school_name && document.getElementById('school_name')) {
            document.getElementById('school_name').value = decodeURIComponent(school_name);
        }
        // If date param provided use it, otherwise default to today if empty
        const dateInput = document.getElementById('date');
        if (dateParam && dateInput) {
            dateInput.value = decodeURIComponent(dateParam);
        } else if (dateInput && !dateInput.value) {
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
        }
    }

    // Make functions globally available
    window.editStudent = editStudent;
    window.confirmDeleteStudent = confirmDeleteStudent;
    window.removeStudent = removeStudent;
    window.cancelEdit = cancelEdit;
    window.addOrUpdateStudent = addOrUpdateStudent;
    window.showSubmitConfirmation = showSubmitConfirmation;
    window.submitReport = submitReport;
    window.clearForm = clearForm;
    window.clearAllStudents = clearAllStudents;