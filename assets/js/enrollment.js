// ------------------------------------------------------------
// Enrollment Tracker JavaScript
// ------------------------------------------------------------
const grades = [
    { id: 'kindergarten', label: 'Kindergarten' }, { id: 'sped', label: 'SPED' },
    { id: 'grade_1', label: 'Grade 1' }, { id: 'grade_2', label: 'Grade 2' },
    { id: 'grade_3', label: 'Grade 3' }, { id: 'grade_4', label: 'Grade 4' },
    { id: 'grade_5', label: 'Grade 5' }, { id: 'grade_6', label: 'Grade 6' },
    { id: 'grade_7', label: 'Grade 7' }, { id: 'grade_8', label: 'Grade 8' },
    { id: 'grade_9', label: 'Grade 9' }, { id: 'grade_10', label: 'Grade 10' },
    { id: 'grade_11', label: 'Grade 11' }, { id: 'grade_12', label: 'Grade 12' }
];
let totals = {};
let students = {};
let currentSchoolYear = '';

function getTotalCount(gradeId) { return totals[gradeId] || 0; }
function getOverallTotal() { let sum = 0; for (let g of grades) sum += getTotalCount(g.id); return sum; }

function saveToLocalStorage() {
    localStorage.setItem('enrollment_school_year', currentSchoolYear);
    localStorage.setItem('enrollment_totals', JSON.stringify(totals));
    localStorage.setItem('enrollment_students', JSON.stringify(students));
}
function loadFromLocalStorage() {
    currentSchoolYear = localStorage.getItem('enrollment_school_year') || '';
    const savedTotals = localStorage.getItem('enrollment_totals');
    const savedStudents = localStorage.getItem('enrollment_students');
    if (savedTotals) totals = JSON.parse(savedTotals);
    if (savedStudents) students = JSON.parse(savedStudents);
    if (currentSchoolYear) document.getElementById('selectedSchoolYearDisplay').innerText = `School year: ${currentSchoolYear}`;
    return currentSchoolYear !== '' && savedTotals !== null;
}

function renderOverallSummary() {
    document.getElementById('overallSummary').innerHTML = `<div class="d-flex justify-content-between"><strong>Total Enrolled Students:</strong> ${getOverallTotal()}</div>`;
}

function renderCircles() {
    const container = document.getElementById('circlesGrid');
    if (!currentSchoolYear || Object.keys(totals).length === 0) {
        container.innerHTML = `<div class="col-12 text-center text-muted py-5"><i class="bi bi-calendar-x"></i><br>Set school year and total enrollment first.</div>`;
        renderOverallSummary();
        return;
    }
    let html = '';
    for (let grade of grades) {
        const id = grade.id;
        const total = getTotalCount(id);
        html += `
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="circle-card">
                    <div class="grade-circle">
                        <div class="circle-numbers">
                            <span class="zero">0</span>
                            <span class="divider">/</span>
                            <span class="total">${total}</span>
                        </div>
                    </div>
                    <div class="grade-label">${grade.label}</div>
                    <div class="progress-status"><i class="bi bi-person-check"></i> ${total} enrolled</div>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-success btn-action add-btn" data-grade="${id}"><i class="bi bi-plus-circle"></i> Add</button>
                        <button class="btn btn-sm btn-warning btn-action remove-btn" data-grade="${id}"><i class="bi bi-dash-circle"></i> Remove</button>
                        <button class="btn btn-sm btn-info btn-action notes-btn" data-grade="${id}"><i class="bi bi-journal-bookmark-fill"></i> Notes</button>
                    </div>
                </div>
            </div>
        `;
    }
    container.innerHTML = html;
    document.querySelectorAll('.add-btn').forEach(btn => btn.addEventListener('click', () => openAddModal(btn.getAttribute('data-grade'))));
    document.querySelectorAll('.remove-btn').forEach(btn => btn.addEventListener('click', () => openRemoveModal(btn.getAttribute('data-grade'))));
    document.querySelectorAll('.notes-btn').forEach(btn => btn.addEventListener('click', () => openNotesModal(btn.getAttribute('data-grade'))));
    renderOverallSummary();
}

let currentAddGrade = null;
function openAddModal(gradeId) {
    currentAddGrade = gradeId;
    document.getElementById('addGradeId').value = gradeId;
    document.getElementById('studentName').value = '';
    document.getElementById('addReason').value = '';
    new bootstrap.Modal(document.getElementById('addStudentModal')).show();
}
document.getElementById('confirmAddBtn').addEventListener('click', () => {
    const gradeId = document.getElementById('addGradeId').value;
    const name = document.getElementById('studentName').value.trim();
    const reason = document.getElementById('addReason').value.trim();
    if (!name) { alert('Please enter student name.'); return; }
    totals[gradeId] = (totals[gradeId] || 0) + 1;
    if (!students[gradeId]) students[gradeId] = [];
    students[gradeId].push({ name, reason, action: 'added', timestamp: new Date().toLocaleString(), removed: false });
    saveToLocalStorage();
    renderCircles();
    updateBeneficiaryStats();
    bootstrap.Modal.getInstance(document.getElementById('addStudentModal')).hide();
});

let currentRemoveGrade = null;
let selectedRemoveStudentIndex = null;
function openRemoveModal(gradeId) {
    currentRemoveGrade = gradeId;
    const listContainer = document.getElementById('removeStudentList');
    const activeStudents = (students[gradeId] || []).filter(s => !s.removed);
    if (activeStudents.length === 0) {
        listContainer.innerHTML = '<div class="alert alert-warning">No active students to remove.</div>';
        document.getElementById('confirmRemoveBtn').disabled = true;
    } else {
        let html = '<div class="list-group">';
        activeStudents.forEach((s, idx) => {
            html += `<button type="button" class="list-group-item list-group-item-action student-remove-item" data-index="${idx}">
                        <strong>${escapeHtml(s.name)}</strong><br><small>Added: ${s.timestamp} - Reason: ${escapeHtml(s.reason || 'N/A')}</small>
                     </button>`;
        });
        html += '</div>';
        listContainer.innerHTML = html;
        document.getElementById('confirmRemoveBtn').disabled = false;
        document.querySelectorAll('.student-remove-item').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.student-remove-item').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedRemoveStudentIndex = parseInt(btn.getAttribute('data-index'));
            });
        });
    }
    document.getElementById('removeReason').value = '';
    selectedRemoveStudentIndex = null;
    new bootstrap.Modal(document.getElementById('removeStudentModal')).show();
}
document.getElementById('confirmRemoveBtn').addEventListener('click', () => {
    if (selectedRemoveStudentIndex === undefined || selectedRemoveStudentIndex === null) { alert('Select a student.'); return; }
    const gradeId = currentRemoveGrade;
    const activeStudents = (students[gradeId] || []).filter(s => !s.removed);
    if (selectedRemoveStudentIndex >= activeStudents.length) return;
    const originalIndex = students[gradeId].findIndex(s => s === activeStudents[selectedRemoveStudentIndex]);
    if (originalIndex !== -1) {
        if ((totals[gradeId] || 0) <= 0) { alert('Total enrollment already zero.'); return; }
        totals[gradeId] = (totals[gradeId] || 0) - 1;
        students[gradeId][originalIndex].removed = true;
        students[gradeId][originalIndex].removalReason = document.getElementById('removeReason').value.trim() || 'No reason provided';
        students[gradeId][originalIndex].removalTimestamp = new Date().toLocaleString();
    }
    saveToLocalStorage();
    renderCircles();
    updateBeneficiaryStats();
    bootstrap.Modal.getInstance(document.getElementById('removeStudentModal')).hide();
});

function openNotesModal(gradeId) {
    const gradeLabel = grades.find(g => g.id === gradeId)?.label || gradeId;
    const allRecords = students[gradeId] || [];
    if (allRecords.length === 0) {
        document.getElementById('notesContent').innerHTML = '<div class="alert alert-info">No records.</div>';
    } else {
        let html = `<h6>${gradeLabel} - History</h6><div class="list-group">`;
        allRecords.forEach(record => {
            const status = record.removed ? '<span class="badge bg-danger">Removed</span>' : '<span class="badge bg-success">Active</span>';
            html += `<div class="list-group-item"><strong>${escapeHtml(record.name)}</strong> ${status}<br>
                     <small>Action: ${record.action} on ${record.timestamp}<br>Reason: ${escapeHtml(record.reason || 'N/A')}</small>`;
            if (record.removed) html += `<br><small>Removal reason: ${escapeHtml(record.removalReason)} (${record.removalTimestamp})</small>`;
            html += `</div>`;
        });
        html += `</div>`;
        document.getElementById('notesContent').innerHTML = html;
    }
    new bootstrap.Modal(document.getElementById('notesModal')).show();
}

function escapeHtml(str) { return str.replace(/[&<>]/g, m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;' }[m])); }

function openTotalModal() {
    for (let grade of grades) {
        const input = document.getElementById(`total_${grade.id}`);
        if (input) input.value = totals[grade.id] || 0;
    }
    new bootstrap.Modal(document.getElementById('totalModal'), { backdrop: 'static', keyboard: false }).show();
}
function setTotalsFromModal() {
    const newTotals = {};
    for (let grade of grades) {
        const val = parseInt(document.getElementById(`total_${grade.id}`).value, 10);
        newTotals[grade.id] = isNaN(val) ? 0 : val;
    }
    totals = newTotals;
    students = {};
    saveToLocalStorage();
    renderCircles();
    updateBeneficiaryStats();
    bootstrap.Modal.getInstance(document.getElementById('totalModal')).hide();
}

// SBFP mock data
const beneficiaryData = {
    kindergarten: [5,12,18,4], sped: [3,8,12,4],
    grade_1:[8,15,22,6], grade_2:[7,14,20,5], grade_3:[6,13,19,5], grade_4:[9,16,24,7],
    grade_5:[8,14,21,6], grade_6:[7,12,18,5], grade_7:[10,18,26,8], grade_8:[9,17,24,7],
    grade_9:[8,15,22,6], grade_10:[7,14,20,5], grade_11:[6,12,18,4], grade_12:[5,10,15,3]
};
const categories = ['Severely Wasted', 'Wasted', 'Stunted', 'Severely Stunted'];
function updateBeneficiaryStats() {
    const filter = document.getElementById('beneficiaryGradeFilter').value;
    let data;
    if (filter === 'all') {
        data = [0,0,0,0];
        for (let g in beneficiaryData) for (let i=0;i<4;i++) data[i] += beneficiaryData[g][i];
    } else data = beneficiaryData[filter] || [0,0,0,0];

    // decide which category indices to show based on modal selections or single filter
    let selectedCats = [];
    if (Array.isArray(beneficiaryCategorySelections) && beneficiaryCategorySelections.length > 0) selectedCats = beneficiaryCategorySelections.slice();
    else if (beneficiaryCategoryFilter) selectedCats = [beneficiaryCategoryFilter];

    let indices = [];
    const mapCatToIndex = (c) => {
        if (c === 'severely_wasted') return 0;
        if (c === 'wasted') return 1;
        if (c === 'stunted') return 2;
        if (c === 'severely_stunted') return 3;
        return null;
    };
    if (selectedCats.length === 0 || selectedCats.includes('all')) indices = [0,1,2,3];
    else {
        selectedCats.forEach(c => {
            const idx = mapCatToIndex(c);
            if (idx !== null && !indices.includes(idx)) indices.push(idx);
        });
    }

    const colors = ['#dc3545','#fd7e14','#ffc107','#6f42c1'];
    const html = indices.map(idx => ` <div class="stat-card" style="border-left-color: ${colors[idx]}"><h5>${data[idx]}</h5><small>${categories[idx]}</small></div>`).join('');
    const container = document.getElementById('beneficiaryStats');
    container.innerHTML = html;
    container.style.display = beneficiaryVisible ? '' : 'none';
}

let nutritionChart;
const periodData = { baseline: [45,112,203,78], midline: [32,98,187,54], endline: [18,65,142,31] };
function updateChart(period) {
    if (nutritionChart) nutritionChart.destroy();
    const ctx = document.getElementById('nutritionChart').getContext('2d');
    nutritionChart = new Chart(ctx, {
        type: 'bar',
        data: { labels: categories, datasets: [{ label: period.toUpperCase(), data: periodData[period], backgroundColor: ['#dc3545','#fd7e14','#ffc107','#6f42c1'], borderRadius: 8 }] },
        options: { responsive: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Students Count' } } } }
    });
}
function setActiveFilter(activePeriod) {
    document.querySelectorAll('#filterButtons .btn').forEach(btn => {
        btn.classList.remove('btn-active', 'btn-primary'); btn.classList.add('btn-outline-primary');
        if (btn.getAttribute('data-period') === activePeriod) btn.classList.add('btn-active', 'btn-primary');
    });
}

    // Beneficiary UI state and helpers
    let beneficiaryCategoryFilter = 'all'; // legacy single selection
    let beneficiaryCategorySelections = []; // array for multi-select from modal
    let beneficiaryVisible = true;
    let classificationCurrentGrade = null;
    let classificationSelectedCategories = [];
    function setBeneficiaryCategory(filter) {
        beneficiaryCategoryFilter = filter;
        // clear any modal multi-selections when using single selection control
        beneficiaryCategorySelections = [];
        document.querySelectorAll('#btnAllGrades,#btnKindergarten,#btnWasted,#btnStunted').forEach(b => b.classList.remove('active'));
        if (filter === 'all') document.getElementById('btnAllGrades').classList.add('active');
        if (filter === 'kindergarten') document.getElementById('btnKindergarten').classList.add('active');
        // mark quick buttons for broader groups
        if (filter === 'wasted' || filter === 'severely_wasted') document.getElementById('btnWasted').classList.add('active');
        if (filter === 'stunted' || filter === 'severely_stunted') document.getElementById('btnStunted').classList.add('active');
        updateBeneficiaryStats();
    }

// Initialization
function showSchoolYearModal() {
    new bootstrap.Modal(document.getElementById('schoolYearModal'), { backdrop: 'static', keyboard: false }).show();
}
document.getElementById('confirmSchoolYearBtn').addEventListener('click', () => {
    const sy = document.getElementById('schoolYearInput').value.trim();
    if (!sy) { alert('Enter school year'); return; }
    currentSchoolYear = sy;
    document.getElementById('selectedSchoolYearDisplay').innerText = `School year: ${currentSchoolYear}`;
    bootstrap.Modal.getInstance(document.getElementById('schoolYearModal')).hide();
    openTotalModal();
});
document.getElementById('resetTotalBtn').addEventListener('click', openTotalModal);
document.getElementById('confirmTotalBtn').addEventListener('click', setTotalsFromModal);
document.getElementById('beneficiaryGradeFilter').addEventListener('change', (e) => {
    const val = e.target.value;
    const catSelect = document.getElementById('beneficiaryCategorySelect');
    if (catSelect) {
        if (val && val !== 'all') {
            catSelect.classList.remove('d-none');
            catSelect.value = 'all';
            beneficiaryCategoryFilter = 'all';
        } else {
            catSelect.classList.add('d-none');
            catSelect.value = 'all';
            beneficiaryCategoryFilter = 'all';
        }
    }
    updateBeneficiaryStats();
});
const catSelectElem = document.getElementById('beneficiaryCategorySelect');
if (catSelectElem) catSelectElem.addEventListener('change', (e) => { setBeneficiaryCategory(e.target.value); });
document.querySelectorAll('#filterButtons .btn').forEach(btn => btn.addEventListener('click', () => { updateChart(btn.getAttribute('data-period')); setActiveFilter(btn.getAttribute('data-period')); }));

function init() {
    const hasData = loadFromLocalStorage();
    if (hasData && currentSchoolYear && Object.keys(totals).length > 0) {
        renderCircles();
        updateBeneficiaryStats();
        updateChart('baseline'); setActiveFilter('baseline');
    } else {
        showSchoolYearModal();
        updateChart('baseline'); setActiveFilter('baseline');
        renderCircles();
    }
        // Beneficiary buttons wiring
        const toggleBtn = document.getElementById('beneficiaryToggleBtn');
        if (toggleBtn) toggleBtn.addEventListener('click', () => {
            beneficiaryVisible = !beneficiaryVisible;
            document.getElementById('beneficiaryStats').style.display = beneficiaryVisible ? '' : 'none';
            toggleBtn.innerText = beneficiaryVisible ? 'Hide' : 'Show';
        });
        const btnAll = document.getElementById('btnAllGrades'); if (btnAll) btnAll.addEventListener('click', () => { document.getElementById('beneficiaryGradeFilter').value = 'all'; setBeneficiaryCategory('all'); });
        const btnKinder = document.getElementById('btnKindergarten'); if (btnKinder) btnKinder.addEventListener('click', () => { document.getElementById('beneficiaryGradeFilter').value = 'kindergarten'; setBeneficiaryCategory('kindergarten'); });
        const btnWasted = document.getElementById('btnWasted'); if (btnWasted) btnWasted.addEventListener('click', () => { setBeneficiaryCategory('wasted'); });
        const btnStunted = document.getElementById('btnStunted'); if (btnStunted) btnStunted.addEventListener('click', () => { setBeneficiaryCategory('stunted'); });
}
    init();

// Classification modal builder & handlers
function buildClassificationModal() {
    const gradesContainer = document.getElementById('classificationGrades');
    const categoriesContainer = document.getElementById('classificationCategories');
    const preview = document.getElementById('classificationPreview');
    const selectedTitle = document.getElementById('classificationSelectedGrade');
    if (!gradesContainer || !categoriesContainer || !preview || !selectedTitle) return;
    gradesContainer.innerHTML = '';
    grades.forEach(g => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-outline-primary text-start';
        btn.type = 'button';
        btn.setAttribute('data-grade', g.id);
        btn.innerText = g.label;
        btn.addEventListener('click', () => {
            // highlight
            gradesContainer.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedTitle.innerText = g.label;
            // set current grade and reset selections
            classificationCurrentGrade = g.id;
            classificationSelectedCategories = [];
            const saveBtn = document.getElementById('classificationSaveBtn'); if (saveBtn) saveBtn.classList.add('d-none');
            // show category buttons
            renderClassificationCategories(g.id);
            // preview overall numbers for grade
            const d = beneficiaryData[g.id] || [0,0,0,0];
            preview.innerHTML = `<div class="small text-muted">Preview totals — Severely Wasted: ${d[0]}, Wasted: ${d[1]}, Stunted: ${d[2]}, Severely Stunted: ${d[3]}</div>`;
        });
        gradesContainer.appendChild(btn);
    });
    categoriesContainer.innerHTML = '';
}

function renderClassificationCategories(gradeId) {
    const categoriesContainer = document.getElementById('classificationCategories');
    if (!categoriesContainer) return;
    const opts = [
        { v: 'all', t: 'All' },
        { v: 'severely_wasted', t: 'Severely Wasted' },
        { v: 'wasted', t: 'Wasted' },
        { v: 'severely_stunted', t: 'Severely Stunted' },
        { v: 'stunted', t: 'Stunted' }
    ];
    categoriesContainer.innerHTML = '';
    const saveBtn = document.getElementById('classificationSaveBtn');
    opts.forEach(o => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-outline-secondary btn-sm me-2 mb-2';
        b.innerText = o.t;
        b.setAttribute('data-cat', o.v);
        // initialize active state
        if (classificationSelectedCategories.includes(o.v)) b.classList.add('active');
        b.addEventListener('click', () => {
            const idx = classificationSelectedCategories.indexOf(o.v);
            if (o.v === 'all') {
                // toggle 'all' — if selecting all, clear others
                if (idx === -1) {
                    classificationSelectedCategories = ['all'];
                    // remove active from other buttons
                    categoriesContainer.querySelectorAll('button').forEach(bb => bb.classList.remove('active'));
                    b.classList.add('active');
                } else {
                    // deselect all
                    classificationSelectedCategories = [];
                    b.classList.remove('active');
                }
            } else {
                // toggle specific category
                if (idx === -1) {
                    // add
                    // if 'all' was selected, remove it
                    const aIdx = classificationSelectedCategories.indexOf('all');
                    if (aIdx !== -1) classificationSelectedCategories.splice(aIdx,1);
                    classificationSelectedCategories.push(o.v);
                    b.classList.add('active');
                } else {
                    classificationSelectedCategories.splice(idx,1);
                    b.classList.remove('active');
                }
            }
            // show/hide save button
            if (saveBtn) {
                if (classificationSelectedCategories.length > 0) saveBtn.classList.remove('d-none');
                else saveBtn.classList.add('d-none');
            }
        });
        categoriesContainer.appendChild(b);
    });
}

// open modal and build on demand
const classificationBtn = document.getElementById('classificationBtn');
if (classificationBtn) classificationBtn.addEventListener('click', () => {
    buildClassificationModal();
    new bootstrap.Modal(document.getElementById('classificationModal')).show();
});

// Save handler for classification modal
const classificationSaveBtn = document.getElementById('classificationSaveBtn');
if (classificationSaveBtn) classificationSaveBtn.addEventListener('click', () => {
    if (!classificationCurrentGrade) return;
    // apply selections
    if (classificationSelectedCategories.includes('all') || classificationSelectedCategories.length === 0) {
        beneficiaryCategorySelections = [];
    } else {
        beneficiaryCategorySelections = classificationSelectedCategories.slice();
    }
    // set grade
    const gradeSelect = document.getElementById('beneficiaryGradeFilter');
    if (gradeSelect) gradeSelect.value = classificationCurrentGrade;
    // hide category select since modal drives multi-select
    const catSelect = document.getElementById('beneficiaryCategorySelect');
    if (catSelect) { catSelect.classList.add('d-none'); catSelect.value = 'all'; }
    updateBeneficiaryStats();
    bootstrap.Modal.getInstance(document.getElementById('classificationModal')).hide();
});

// Optional: adjust main content margin when sidebar toggles (depends on sidebar.js)
const sidebarToggle = document.getElementById('sidebarToggle');
const mainContent = document.getElementById('mainContent');
if (sidebarToggle && mainContent) {
    sidebarToggle.addEventListener('click', function() {
        setTimeout(() => {
            if (document.getElementById('mainSidebar').classList.contains('show')) {
                mainContent.classList.add('shifted');
            } else {
                mainContent.classList.remove('shifted');
            }
        }, 100);
    });
}