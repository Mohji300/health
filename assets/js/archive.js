// Nutritional Assessment Archive JavaScript

// Global Variables
let archiveConfirmModal = null;
let archiveProgressModal = null;
let alertModal = null;
let schoolDetailsModal = null;
let selectedSchoolYear = '';
let currentAssessmentType = 'all';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize modals
    alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
    schoolDetailsModal = new bootstrap.Modal(document.getElementById('schoolDetailsModal'));
    
    // Initialize archive modals only for admin users
    if (isAdmin) {
        archiveConfirmModal = new bootstrap.Modal(document.getElementById('archiveConfirmModal'));
        archiveProgressModal = new bootstrap.Modal(document.getElementById('archiveProgressModal'));
        
        // Archive event listeners only for admin
        const archiveBtn = document.getElementById('archiveBtn');
        const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
        const confirmArchive = document.getElementById('confirmArchive');
        
        if (archiveBtn) {
            archiveBtn.addEventListener('click', showArchiveConfirmation);
        }
        if (confirmArchiveBtn) {
            confirmArchiveBtn.addEventListener('click', startArchiveProcess);
        }
        if (confirmArchive) {
            confirmArchive.addEventListener('change', toggleArchiveButton);
        }
    }
    
    // Common event listeners
    updateRecordCount();
    
    // Search bar event listeners
    const searchBar = document.getElementById('searchBar');
    const clearSearchBtn = document.getElementById('clearSearch');

    if (searchBar) {
        searchBar.addEventListener('input', performSearch);
    }
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', clearSearch);
    }

    // Year filter event
    const yearFilter = document.getElementById('yearFilter');
    if (yearFilter) {
        yearFilter.addEventListener('change', filterByYear);
    }

    // Assessment filter event
    const assessmentFilter = document.getElementById('assessmentFilter');
    if (assessmentFilter) {
        assessmentFilter.addEventListener('change', filterByAssessment);
    }

    // Attach event listeners to view school details buttons
    document.addEventListener('click', function(event) {
        // View school details button
        if (event.target.closest('.view-school-details')) {
            const button = event.target.closest('.view-school-details');
            const year = button.getAttribute('data-year');
            const school = button.getAttribute('data-school');
            viewSchoolDetails(year, school, 'all');
        }
        
        // Assessment filter buttons in modal
        if (event.target.closest('.assessment-filter')) {
            const button = event.target.closest('.assessment-filter');
            const type = button.getAttribute('data-type');
            filterSchoolDetailsByAssessment(type);
        }
    });
    
    // Initialize assessment filter buttons
    updateAssessmentFilterButtons('all');
});

// Record Count Functions
function updateRecordCount() {
    let totalRecords = 0;
    const schoolRows = document.querySelectorAll('.school-row');
    
    schoolRows.forEach(row => {
        if (row.style.display !== 'none') {
            const baseline = parseInt(row.getAttribute('data-baseline')) || 0;
            const midline = parseInt(row.getAttribute('data-midline')) || 0;
            const endline = parseInt(row.getAttribute('data-endline')) || 0;
            totalRecords += (baseline + midline + endline);
        }
    });
    
    const recordCountElement = document.getElementById('recordCount');
    if (recordCountElement) {
        recordCountElement.textContent = totalRecords + ' Record' + (totalRecords !== 1 ? 's' : '');
    }
}

// Filter Functions
function filterByYear() {
    const selectedYear = document.getElementById('yearFilter').value;
    const yearGroups = document.querySelectorAll('.year-group');
    
    yearGroups.forEach(group => {
        const year = group.getAttribute('data-year');
        
        if (!selectedYear || year === selectedYear) {
            group.style.display = 'block';
            const schoolRows = group.querySelectorAll('.school-row');
            schoolRows.forEach(row => row.style.display = 'table-row');
        } else {
            group.style.display = 'none';
        }
    });
    
    filterByAssessment();
    updateRecordCount();
}

function filterByAssessment() {
    const selectedAssessment = document.getElementById('assessmentFilter').value;
    const visibleRows = document.querySelectorAll('.year-group:not([style*="display: none"]) .school-row');
    
    visibleRows.forEach(row => {
        const baseline = parseInt(row.getAttribute('data-baseline')) || 0;
        const midline = parseInt(row.getAttribute('data-midline')) || 0;
        const endline = parseInt(row.getAttribute('data-endline')) || 0;
        
        let shouldShow = false;
        
        if (!selectedAssessment) {
            shouldShow = true;
        } else if (selectedAssessment === 'baseline' && baseline > 0) {
            shouldShow = true;
        } else if (selectedAssessment === 'midline' && midline > 0) {
            shouldShow = true;
        } else if (selectedAssessment === 'endline' && endline > 0) {
            shouldShow = true;
        }
        
        row.style.display = shouldShow ? 'table-row' : 'none';
    });
    
    updateRecordCount();
}

// Search Functions
function performSearch() {
    const searchTerm = document.getElementById('searchBar').value.toLowerCase().trim();
    
    if (!searchTerm) {
        document.querySelectorAll('.school-row').forEach(row => {
            row.style.display = 'table-row';
        });
        updateSearchResultCount(null);
        return;
    }
    
    const schoolRows = document.querySelectorAll('.school-row');
    let matchCount = 0;
    
    schoolRows.forEach(row => {
        const schoolName = row.querySelector('td:first-child strong').textContent.toLowerCase();
        const schoolIdElement = row.querySelector('td:nth-child(2) .badge');
        const schoolId = schoolIdElement ? schoolIdElement.textContent.toLowerCase() : '';
        
        const matchesSchoolName = schoolName.includes(searchTerm);
        const matchesSchoolId = schoolId.includes(searchTerm);
        
        if (matchesSchoolName || matchesSchoolId) {
            row.style.display = 'table-row';
            highlightText(row, searchTerm);
            matchCount++;
        } else {
            row.style.display = 'none';
            removeHighlights(row);
        }
    });
    
    document.querySelectorAll('.year-group').forEach(group => {
        const collapseElement = group.querySelector('.collapse');
        if (collapseElement) {
            const yearId = collapseElement.id;
            const visibleRows = group.querySelectorAll(`#${yearId} .school-row[style*="table-row"]`);
            
            if (visibleRows.length > 0) {
                group.style.display = 'block';
                const collapse = new bootstrap.Collapse(document.getElementById(yearId), {
                    toggle: false
                });
                collapse.show();
            } else {
                group.style.display = 'none';
            }
        }
    });
    
    updateSearchResultCount(matchCount);
    updateRecordCount();
}

function highlightText(row, searchTerm) {
    removeHighlights(row);
    
    const schoolNameCell = row.querySelector('td:first-child');
    const schoolNameText = schoolNameCell.textContent;
    const highlightedName = highlightTerm(schoolNameText, searchTerm);
    schoolNameCell.innerHTML = `<strong>${highlightedName}</strong>`;
    
    const schoolIdElement = row.querySelector('td:nth-child(2) .badge');
    if (schoolIdElement) {
        const schoolIdText = schoolIdElement.textContent;
        const highlightedId = highlightTerm(schoolIdText, searchTerm);
        schoolIdElement.innerHTML = highlightedId;
    }
}

function highlightTerm(text, term) {
    if (!term) return text;
    const regex = new RegExp(`(${term})`, 'gi');
    return text.replace(regex, '<mark class="bg-warning text-dark">$1</mark>');
}

function removeHighlights(row) {
    const schoolNameCell = row.querySelector('td:first-child');
    if (schoolNameCell) {
        const originalText = schoolNameCell.textContent.replace(/\n/g, '').trim();
        schoolNameCell.innerHTML = `<strong>${originalText}</strong>`;
    }
    
    const schoolIdElement = row.querySelector('td:nth-child(2) .badge');
    if (schoolIdElement) {
        const originalId = schoolIdElement.textContent.replace(/\n/g, '').trim();
        schoolIdElement.innerHTML = originalId;
    }
    
    row.querySelectorAll('mark').forEach(mark => {
        mark.replaceWith(mark.textContent);
    });
}

function clearSearch() {
    const searchBar = document.getElementById('searchBar');
    searchBar.value = '';
    
    document.querySelectorAll('.school-row').forEach(row => {
        row.style.display = 'table-row';
        removeHighlights(row);
    });
    
    document.querySelectorAll('.year-group').forEach(group => {
        group.style.display = 'block';
    });
    
    expandAll();
    updateSearchResultCount(null);
    updateRecordCount();
}

function updateSearchResultCount(matchCount) {
    const searchBar = document.getElementById('searchBar');
    const searchTerm = searchBar.value.trim();
    
    let resultBadge = document.querySelector('.search-result-badge');
    
    if (!searchTerm) {
        if (resultBadge) {
            resultBadge.remove();
        }
        return;
    }
    
    if (!resultBadge) {
        resultBadge = document.createElement('span');
        resultBadge.className = 'search-result-badge badge bg-info ms-2';
        searchBar.parentNode.appendChild(resultBadge);
    }
    
    if (matchCount === 0) {
        resultBadge.textContent = 'No results found';
        resultBadge.className = 'search-result-badge badge bg-danger ms-2';
    } else {
        resultBadge.textContent = `${matchCount} result${matchCount !== 1 ? 's' : ''} found`;
        resultBadge.className = 'search-result-badge badge bg-success ms-2';
    }
}

function expandAll() {
    const collapses = document.querySelectorAll('.collapse');
    collapses.forEach(collapse => {
        const bsCollapse = new bootstrap.Collapse(collapse, {
            toggle: false
        });
        bsCollapse.show();
    });
}

// School Details Functions
function viewSchoolDetails(year, school, assessmentType = 'all') {
    currentAssessmentType = assessmentType;
    const title = document.getElementById('schoolModalTitle');
    const schoolName = document.getElementById('schoolName');
    const schoolYear = document.getElementById('schoolYear');
    const schoolId = document.getElementById('schoolId');
    const detailsTable = document.getElementById('schoolDetailsTable');
    
    detailsTable.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading school details...</p>
        </div>
    `;
    
    title.textContent = `Archived Records - ${school}`;
    schoolName.textContent = `School: ${school}`;
    schoolYear.textContent = `School Year: ${year}`;
    schoolId.textContent = `Loading school ID...`;
    
    updateAssessmentFilterButtons(assessmentType);
    
    const url = `${siteUrl}?year=${encodeURIComponent(year)}&school=${encodeURIComponent(school)}&type=${encodeURIComponent(assessmentType)}`;
    
    console.log('Fetching URL:', url);
    
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Non-JSON response:', contentType);
            return response.text().then(text => {
                console.error('Response text:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            if (data.records && data.records.length > 0 && data.records[0].school_id) {
                schoolId.textContent = `School ID: ${data.records[0].school_id}`;
            } else {
                schoolId.textContent = `School ID: Not Available`;
            }
            
            renderSchoolDetailsTable(data.records, detailsTable, assessmentType);
        } else {
            detailsTable.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to load school details.'}
                </div>
            `;
            schoolId.textContent = `School ID: Error loading data`;
        }
    })
    .catch(error => {
        console.error('Error loading school details:', error);
        detailsTable.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Error loading school details: ${error.message}
            </div>
        `;
        schoolId.textContent = `School ID: Connection error`;
    });
    
    schoolDetailsModal.show();
}

function updateAssessmentFilterButtons(selectedType) {
    document.querySelectorAll('.assessment-filter').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.remove('btn-primary', 'btn-info', 'btn-success');
        btn.classList.add('btn-outline-secondary', 'btn-outline-primary', 'btn-outline-info', 'btn-outline-success');
    });
    
    const selectedButton = document.querySelector(`.assessment-filter[data-type="${selectedType}"]`);
    if (selectedButton) {
        selectedButton.classList.remove('btn-outline-secondary', 'btn-outline-primary', 'btn-outline-info', 'btn-outline-success');
        switch(selectedType) {
            case 'all':
                selectedButton.classList.add('btn-secondary', 'active');
                break;
            case 'baseline':
                selectedButton.classList.add('btn-primary', 'active');
                break;
            case 'midline':
                selectedButton.classList.add('btn-info', 'active');
                break;
            case 'endline':
                selectedButton.classList.add('btn-success', 'active');
                break;
        }
    }
}

function filterSchoolDetailsByAssessment(type) {
    currentAssessmentType = type;
    updateAssessmentFilterButtons(type);
    
    const tableBody = document.querySelector('#schoolDetailsTable tbody');
    if (!tableBody) return;
    
    const rows = tableBody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const assessmentType = row.getAttribute('data-assessment-type');
        
        if (type === 'all' || assessmentType === type) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    const footer = document.querySelector('#schoolDetailsTable tfoot');
    if (footer) {
        footer.querySelector('td').innerHTML = `<strong>Showing ${visibleCount} of ${rows.length} records</strong>`;
    }
}

function renderSchoolDetailsTable(records, container, assessmentType = 'all') {
    if (!records || records.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No records found for this school.</div>';
        return;
    }
    
    const baselineCount = records.filter(r => r.assessment_type === 'baseline').length;
    const midlineCount = records.filter(r => r.assessment_type === 'midline').length;
    const endlineCount = records.filter(r => r.assessment_type === 'endline').length;
    
    let html = `
        <div class="alert alert-light mb-3">
            <div class="row">
                <div class="col-md-4">
                    <span class="badge bg-primary">${baselineCount} Baseline</span>
                </div>
                <div class="col-md-4">
                    <span class="badge bg-info">${midlineCount} Midline</span>
                </div>
                <div class="col-md-4">
                    <span class="badge bg-success">${endlineCount} Endline</span>
                </div>
            </div>
        </div>
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>Assessment Type</th>
                    <th>Weight (kg)</th>
                    <th>Height (m)</th>
                    <th>BMI</th>
                    <th>Nutritional Status</th>
                    <th>Date Weighed</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    let displayedCount = 0;
    
    records.forEach(record => {
        if (assessmentType !== 'all' && record.assessment_type !== assessmentType) {
            return;
        }
        
        displayedCount++;
        
        html += `
            <tr class="${getStatusClass(record.nutritional_status)}" data-assessment-type="${record.assessment_type}">
                <td>${escapeHtml(record.name)}</td>
                <td>${escapeHtml(record.grade_level)}</td>
                <td>${escapeHtml(record.section)}</td>
                <td><span class="badge ${getAssessmentBadgeClass(record.assessment_type)}">${record.assessment_type}</span></td>
                <td>${record.weight}</td>
                <td>${record.height}</td>
                <td>${record.bmi}</td>
                <td><span class="badge ${getStatusBadgeClass(record.nutritional_status)}">${record.nutritional_status}</span></td>
                <td>${record.date_of_weighing}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
            <tfoot>
                <tr class="table-info">
                    <td colspan="9" class="text-center">
                        <strong>Showing ${displayedCount} of ${records.length} records</strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    `;
    
    container.innerHTML = html;
    updateAssessmentFilterButtons(assessmentType);
}

// Print and Export Functions
function printSchoolDetails() {
    const printContent = document.getElementById('schoolDetailsTable').innerHTML;
    const originalContent = document.body.innerHTML;
    const schoolTitle = document.getElementById('schoolModalTitle').textContent;
    const schoolName = document.getElementById('schoolName').textContent;
    const schoolYear = document.getElementById('schoolYear').textContent;
    const schoolId = document.getElementById('schoolId').textContent;
    
    document.body.innerHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>School Archive Details</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .badge { padding: 3px 8px; border-radius: 4px; font-size: 12px; }
                .bg-danger { background-color: #dc3545 !important; color: white; }
                .bg-warning { background-color: #ffc107 !important; color: black; }
                .bg-success { background-color: #28a745 !important; color: white; }
                .bg-info { background-color: #17a2b8 !important; color: white; }
                .bg-primary { background-color: #007bff !important; color: white; }
                .bg-secondary { background-color: #6c757d !important; color: white; }
                .status-severely-wasted { background-color: #f8d7da !important; }
                .status-wasted { background-color: #fff3cd !important; }
                .status-overweight { background-color: #d1ecf1 !important; }
                .status-obese { background-color: #f5c6cb !important; }
                .table-info { background-color: #d1ecf1; }
                @media print {
                    body { margin: 0; padding: 10px; }
                    table { font-size: 12px; }
                }
            </style>
        </head>
        <body>
            <h4>${schoolTitle}</h4>
            <p><strong>${schoolName}</strong></p>
            <p><strong>${schoolYear}</strong></p>
            <p><strong>${schoolId}</strong></p>
            <hr>
            ${printContent}
            <p class="text-muted" style="margin-top: 20px; font-size: 12px;">
                Printed on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
                <br>Assessment Type Filter: ${currentAssessmentType === 'all' ? 'All Types' : currentAssessmentType}
                <br>Printed by: ${username} (Role: ${userRole})
            </p>
        </body>
        </html>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

function exportSchoolCSV() {
    const table = document.querySelector('#schoolDetailsTable table');
    if (!table) {
        showAlert('Error', 'No data to export.');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td, th');
        
        cells.forEach(cell => {
            let text = cell.textContent.trim();
            text = text.replace(/Baseline|Midline|Endline/g, '');
            text = text.replace(/\s+/g, ' ').trim();
            
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            
            rowData.push(text);
        });
        
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `archived_records_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showAlert('Success', 'CSV file downloaded successfully!');
}

// Archive Functions
function showArchiveConfirmation() {
    if (!isAdmin) {
        showAlert('Permission Denied', 'Only administrators can archive records.');
        return;
    }
    
    const schoolYearSelect = document.getElementById('schoolYearSelect');
    selectedSchoolYear = schoolYearSelect.value;
    
    if (!selectedSchoolYear) {
        showAlert('Error', 'Please select a school year to archive.');
        return;
    }
    
    document.getElementById('confirmArchive').checked = false;
    document.getElementById('confirmArchiveBtn').disabled = true;
    document.getElementById('confirmSchoolYear').textContent = selectedSchoolYear;
    archiveConfirmModal.show();
}

function toggleArchiveButton() {
    const checkbox = document.getElementById('confirmArchive');
    const button = document.getElementById('confirmArchiveBtn');
    button.disabled = !checkbox.checked;
}

function startArchiveProcess() {
    archiveConfirmModal.hide();
    archiveProgressModal.show();
    
    const progressBar = document.getElementById('archiveProgressBar');
    const statusText = document.getElementById('archiveStatus');
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    statusText.textContent = 'Starting archive process...';
    
    simulateProgress();
    
    fetch(archiveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'school_year=' + encodeURIComponent(selectedSchoolYear)
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response');
        }
        return response.json();
    })
    .then(data => {
        progressBar.style.width = '100%';
        progressBar.textContent = '100%';
        statusText.textContent = 'Archive completed successfully!';
        
        setTimeout(() => {
            archiveProgressModal.hide();
            
            if (data.success) {
                showAlert('Success', data.message || 'Records archived successfully!');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showAlert('Error', data.message || 'Error archiving records.');
            }
        }, 1500);
    })
    .catch(error => {
        console.error('Archive error:', error);
        archiveProgressModal.hide();
        
        if (error.message.includes('non-JSON response')) {
            showAlert('Server Error', 'The server returned an unexpected response.');
        } else {
            showAlert('Network Error', 'Failed to connect to server: ' + error.message);
        }
    });
}

function simulateProgress() {
    const progressBar = document.getElementById('archiveProgressBar');
    const statusText = document.getElementById('archiveStatus');
    let progress = 0;
    
    const interval = setInterval(() => {
        if (progress >= 90) {
            clearInterval(interval);
            return;
        }
        
        progress += 10;
        progressBar.style.width = progress + '%';
        progressBar.textContent = progress + '%';
        
        if (progress <= 30) {
            statusText.textContent = 'Preparing records for archive...';
        } else if (progress <= 60) {
            statusText.textContent = 'Transferring records to archive...';
        } else {
            statusText.textContent = 'Finalizing archive process...';
        }
    }, 500);
}

// Utility Functions
function showAlert(title, message) {
    document.getElementById('alertTitle').textContent = title;
    document.getElementById('alertBody').textContent = message;
    alertModal.show();
}

function getStatusBadgeClass(status) {
    switch(status) {
        case 'Severely Wasted': return 'bg-danger';
        case 'Wasted': return 'bg-warning text-dark';
        case 'Overweight': return 'bg-info';
        case 'Obese': return 'bg-danger';
        default: return 'bg-success';
    }
}

function getAssessmentBadgeClass(type) {
    switch(type) {
        case 'baseline': return 'bg-primary';
        case 'midline': return 'bg-info';
        case 'endline': return 'bg-success';
        default: return 'bg-secondary';
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'Severely Wasted': return 'status-severely-wasted';
        case 'Wasted': return 'status-wasted';
        case 'Overweight': return 'status-overweight';
        case 'Obese': return 'status-obese';
        default: return '';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}