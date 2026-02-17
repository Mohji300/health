// Externalized JS from nutritional_assessment.php
const STORAGE_PREFIX = 'nutritional_assessment_';
let students = [];
let alertModal = null;
let loadingModal = null;
let uploadLoadingModal = null;
let confirmModal = null;
let submitConfirmModal = null;

function getStorageKey() {
    const ld = document.getElementById('legislative_district').value || 'na';
    const sd = document.getElementById('school_district').value || 'na';
    const gr = document.getElementById('grade').value || 'na';
    const sc = document.getElementById('section').value || 'na';
    const sy = document.getElementById('school_year').value || 'na';
    const sn = document.getElementById('school_name').value || 'na';
    return STORAGE_PREFIX + [ld, sd, gr, sc, sy, sn].join('_');
}

function loadStudents() {
  const key = getStorageKey();
  const stored = localStorage.getItem(key);
  if (stored) {
    try { students = JSON.parse(stored); } catch (e) { console.error('Error loading students:', e); students = []; }
  }
}

function saveStudents() { localStorage.setItem(getStorageKey(), JSON.stringify(students)); }
function clearStoredStudents() { localStorage.removeItem(getStorageKey()); }

function calculateStudentData(name, birthday, weight, height, sex) {
    let ageYears = 0, ageMonths = 0;
    if (birthday) {
        const bdate = new Date(birthday);
        const today = new Date();
        ageYears = today.getFullYear() - bdate.getFullYear();
        ageMonths = today.getMonth() - bdate.getMonth();
        if (ageMonths < 0) { ageYears--; ageMonths += 12; }
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
        name: name.trim(), birthday, weight: parseFloat(weight), height: parseFloat(height), sex,
        grade: document.getElementById('grade').value, section: document.getElementById('section').value,
        school_year: document.getElementById('school_year').value, date: document.getElementById('date').value,
        legislative_district: document.getElementById('legislative_district').value,
        school_district: document.getElementById('school_district').value,
        school_id: document.getElementById('school_id').value, school_name: document.getElementById('school_name').value,
        heightSquared: parseFloat(heightSq), age: ageYears + '|' + ageMonths, ageYears, ageMonths, ageDisplay: ageYears + '|' + ageMonths,
        bmi: parseFloat(bmi), nutritionalStatus, heightForAge: 'Normal', sbfpBeneficiary
    };
}

function addStudent() {
  const form = document.getElementById('assessmentForm');
  if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
  const name = document.getElementById('name').value;
  const birthday = document.getElementById('birthday').value;
  const weight = document.getElementById('weight').value;
  const height = document.getElementById('height').value;
  const sex = document.getElementById('sex').value;
  const student = calculateStudentData(name, birthday, weight, height, sex);
  students.push(student); saveStudents(); clearForm(); updateUI(); showAlert('Success', 'Student added to the list!');
}

function removeStudent(idx) { if (confirm('Remove this student?')) { students.splice(idx, 1); saveStudents(); updateUI(); } }

function clearForm() { 
    document.getElementById('name').value=''; 
    document.getElementById('birthday').value=''; 
    document.getElementById('weight').value=''; 
    document.getElementById('height').value=''; 
    document.getElementById('sex').value=''; 
    document.getElementById('assessmentForm').classList.remove('was-validated'); 
}

function clearAllStudents() {
  if (students.length === 0) { showAlert('No Records', 'There are no student records to clear.'); return; }
  document.getElementById('confirmBody').textContent = `Clear all ${students.length} student record(s)? This action cannot be undone.`;
  confirmModal.show();
}

function performClearAll() {
    students = [];
    saveStudents();
    updateUI();
    showAlert('Cleared', 'All student records have been cleared.');
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
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeStudent(${idx})"><i class="fas fa-trash"></i></button></td>
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

function getRowClass(status) { 
    switch (status) { 
        case 'Severely Wasted': return 'status-severely-wasted'; 
        case 'Wasted': return 'status-wasted'; 
        case 'Overweight': return 'status-overweight'; 
        case 'Obese': return 'status-obese'; 
        default: return ''; 
    } 
}

async function submitReport() {
  loadingModal.show();
  try {
      const urlParams = new URLSearchParams(window.location.search);
      let assessmentType = urlParams.get('assessment_type') || 'baseline';
      const validTypes = ['baseline','midline','endline']; 
      if (!validTypes.includes(assessmentType)) assessmentType = 'baseline';
      
      const response = await fetch((window.NutritionalAssessmentConfig && window.NutritionalAssessmentConfig.urls ? window.NutritionalAssessmentConfig.urls.bulk_store : ''), {
          method: 'POST', 
          headers: { 'Content-Type': 'application/x-www-form-urlencoded','X-Requested-With': 'XMLHttpRequest' },
          body: 'students=' + encodeURIComponent(JSON.stringify(students)) + '&assessment_type=' + encodeURIComponent(assessmentType)
      });
      const result = await response.json();
      if (result.success) {
          students = []; 
          saveStudents(); 
          clearStoredStudents(); 
          updateUI(); 
          loadingModal.hide(); 
          showAlert('Success', result.message || 'Records submitted successfully!'); 
          setTimeout(() => window.location.href = (window.NutritionalAssessmentConfig && window.NutritionalAssessmentConfig.redirect_after ? window.NutritionalAssessmentConfig.redirect_after : '/'), 1500);
      } else { 
          loadingModal.hide(); 
          showAlert('Error', result.message || 'Error submitting records. Check console for details.'); 
          console.error('Submission errors:', result.errors); 
      }
  } catch (e) { 
      loadingModal.hide(); 
      console.error('Network error:', e); 
      showAlert('Network Error', 'Error communicating with server: ' + e.message); 
  }
}

function showSubmitConfirmation() {
    if (students.length === 0) {
        showAlert('No Records', 'There are no student records to submit.');
        return;
    }
    
    // Update the confirmation modal with the count
    const confirmText = document.getElementById('submitConfirmText');
    if (confirmText) {
        confirmText.textContent = `You are about to submit ${students.length} student record(s) for assessment.`;
    }
    
    // Show the confirmation modal
    if (submitConfirmModal) {
        submitConfirmModal.show();
    }
}

function showAlert(title, message) { 
    const tEl = document.getElementById('alertTitle'); 
    const bEl = document.getElementById('alertBody'); 
    if (tEl) tEl.textContent = title; 
    if (bEl) bEl.textContent = message; 
    if (alertModal) alertModal.show(); 
    else window.alert(title + '\n\n' + message); 
}

function switchAssessmentType(type) {
    if (students.length > 0) {
        if (!confirm('Switching assessment type will clear all current student records. Continue?')) {
            return;
        }
        // Clear students if user confirms
        students = [];
        saveStudents();
        updateUI();
    }
    
    // Redirect to the same page with new assessment type
    const url = new URL(window.location.href);
    url.searchParams.set('assessment_type', type);
    window.location.href = url.toString();
}

// Excel upload helper
function extractFromExcel() {
  const file = document.getElementById('excelFile').files[0]; 
  if (!file) return;
  
  const fileExtension = file.name.split('.').pop().toLowerCase(); 
  if (!['xlsx','xls','csv'].includes(fileExtension)) { 
      showAlert('Invalid File', 'Please select an Excel file (.xlsx, .xls) or CSV file'); 
      return; 
  }
  if (file.size > 5 * 1024 * 1024) { 
      showAlert('File Too Large', 'Please select a file smaller than 5MB'); 
      return; 
  }
  
  uploadLoadingModal.show(); 
  document.getElementById('chooseFileBtn').disabled = true;
  
  const formData = new FormData(); 
  formData.append('excel_file', file);
  
  fetch((window.NutritionalAssessmentConfig && window.NutritionalAssessmentConfig.urls ? window.NutritionalAssessmentConfig.urls.process_excel : ''), 
    { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
      uploadLoadingModal.hide(); 
      document.getElementById('chooseFileBtn').disabled = false; 
      document.getElementById('excelFile').value = '';
      if (data.success) {
          addExtractedStudentsDirectly(data.students || [], data.message);
      } else {
          showAlert('Processing Error', data.message);
      }
    })
    .catch(error => { 
      uploadLoadingModal.hide(); 
      document.getElementById('chooseFileBtn').disabled = false; 
      document.getElementById('excelFile').value = ''; 
      showAlert('Error', 'An error occurred while processing the file: ' + error.message); 
    });
}

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
        ageYears = today.getFullYear()-bdate.getFullYear(); 
        ageMonths = today.getMonth()-bdate.getMonth(); 
        if (ageMonths<0){ ageYears--; ageMonths+=12; } 
        ageDisplay = ageYears+'|'+ageMonths; 
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
      grade: document.getElementById('grade').value, 
      section: document.getElementById('section').value, 
      school_year: document.getElementById('school_year').value,
      date: document.getElementById('date').value, 
      legislative_district: document.getElementById('legislative_district').value, 
      school_district: document.getElementById('school_district').value,
      school_id: document.getElementById('school_id').value, 
      school_name: document.getElementById('school_name').value,
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
  if (skippedCount>0) successMessage+=` ${skippedCount} record(s) were skipped due to missing data.`; 
  showAlert('Excel Import Complete', successMessage);
}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Bootstrap modals
  alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
  loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'), { keyboard: false, backdrop: 'static' });
  uploadLoadingModal = new bootstrap.Modal(document.getElementById('uploadLoadingModal'), { keyboard: false, backdrop: 'static' });
  confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
  submitConfirmModal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));

  // Set default date to today
  document.getElementById('date').value = new Date().toISOString().split('T')[0];
  
  // Load existing students from localStorage
  loadStudents(); 
  updateUI();

  // Event listeners
  document.getElementById('assessmentForm').addEventListener('submit', (e) => { e.preventDefault(); addStudent(); });
  document.getElementById('clearFormBtn').addEventListener('click', clearForm);
  document.getElementById('clearAllBtn').addEventListener('click', clearAllStudents);
  document.getElementById('submitBtn').addEventListener('click', showSubmitConfirmation);

  // File upload handlers
  document.getElementById('chooseFileBtn').addEventListener('click', function() { document.getElementById('excelFile').click(); });
  document.getElementById('excelFile').addEventListener('change', function(event) { if (event.target.files.length > 0) extractFromExcel(); });

  // Modal confirmation handlers
  document.getElementById('confirmYesBtn').addEventListener('click', function() { 
      confirmModal.hide(); 
      performClearAll(); 
  });
  
  document.getElementById('submitConfirmYesBtn').addEventListener('click', function() { 
      submitConfirmModal.hide(); 
      submitReport(); 
  });

  // Assessment type switchers (if they exist in the DOM)
  const switchToBaseline = document.getElementById('switchToBaseline'); 
  const switchToEndline = document.getElementById('switchToEndline');
  const switchToMidline = document.getElementById('switchToMidline');
  
  if (switchToBaseline) switchToBaseline.addEventListener('click', function() { switchAssessmentType('baseline'); });
  if (switchToEndline) switchToEndline.addEventListener('click', function() { switchAssessmentType('endline'); });
  if (switchToMidline) switchToMidline.addEventListener('click', function() { switchAssessmentType('midline'); });
});