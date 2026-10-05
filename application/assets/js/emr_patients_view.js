//emr_patients_view.js
(function () {
    'use strict';

    // ---- Config from PHP ----
    const CFG           = window.EMR_PATIENTS_CONFIG || {};
    const MED_API_BASE  = CFG.MED_API_BASE || '';
    const API_BASE      = CFG.API_BASE     || '';
    const LOGOUT_URL    = CFG.LOGOUT_URL   || '/';

    let wizardClickHandler = null;
    const modalFooter = document.getElementById('modalFooter');
    if (modalFooter) {
        modalFooter.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-action]');
            if (!btn || !wizardClickHandler) return;
            wizardClickHandler(btn, e);
        });
    }
    const modalContent = document.getElementById('modalContent');
    if (modalContent) {
        modalContent.addEventListener('click', function (e) {
            const removeRow = e.target.closest('.btn-remove');
            if (removeRow) {
                removeRow.closest('.dynamic-row')?.remove();
                return;
            }
            const removeMedication = e.target.closest('.btn-remove-med');
            if (removeMedication) {
                removeMedication.closest('.med-entry')?.remove();
                return;
            }
            const addRow = e.target.closest('button[data-container][data-fields]');
            if (addRow) {
                addDynamicRow(addRow.dataset.container, JSON.parse(addRow.dataset.fields));
            }
        });
    }

    let medList = [];
    let patients = [];
    let schools  = [];
    let visits   = [];
    let searchQuery = '';
    let patientPage = 1;
    const patientPerPage = 25;
    let patientTotal = 0;
    let userRole = null;
    const patientById = new Map();
    const schoolById = new Map();

    const patientsTable = document.getElementById('patientsTable');
    if (patientsTable) {
        patientsTable.addEventListener('click', function (e) {
            const button = e.target.closest('button');
            if (!button) return;
            if (button.dataset.page) {
                fetchPatientPage(Number(button.dataset.page));
                return;
            }
            const id = button.dataset.id;
            if (!id) return;
            if (button.classList.contains('viewPatientBtn')) viewPatient(id);
            else if (button.classList.contains('editPatientBtn')) editPatient(id);
            else if (button.classList.contains('deletePatientBtn')) deletePatient(id);
            else if (button.classList.contains('addVisitForPatientBtn')) openPatientModal(id, 'visit');
        });
    }

    // --------------------------------------------------------------
    // API HELPERS
    // --------------------------------------------------------------
    // Read CI CSRF cookie (adjust name to match your config: csrf_cookie_name)
    function getCsrfToken() {
        const m = document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    }

    async function api(method, endpoint, body = null) {
        const requestMethod = String(method || 'GET').toUpperCase();
        const options = {
            method: requestMethod,
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                ...(requestMethod === 'GET' || requestMethod === 'HEAD' ? {} : { 'Content-Type': 'application/json' })
            },
            credentials: 'same-origin'
        };
        if (body) options.body = JSON.stringify(body);

        const res = await fetch(API_BASE + endpoint, options);

        if (res.status === 401) {
            window.location.href = LOGOUT_URL;
            throw new Error('Unauthorized');
        }

        const contentType = res.headers.get('content-type') || '';
        if (!res.ok) {
            let message = `HTTP ${res.status}`;
            if (contentType.includes('application/json')) {
                const errData = await res.json().catch(() => ({}));
                message = errData.error || errData.message || message;
            } else {
                const allow = res.headers.get('Allow');
                if (allow) message += ` (Allow: ${allow})`;
            }
            throw new Error(`${API_BASE + endpoint}: ${message}`);
        }

        if (!contentType.includes('application/json')) {
            throw new Error(`${API_BASE + endpoint}: server returned non-JSON (status ${res.status}).`);
        }
        return res.json();
    }

    // --------------------------------------------------------------
    // DATA FETCH
    // --------------------------------------------------------------
    async function fetchAll() {
        try {
            console.log('EMR: Starting data load...');

            const [patientsData, schoolsData, visitsData, profileData] = await Promise.all([
                api('GET', `patients?page=${patientPage}&per_page=${patientPerPage}&q=${encodeURIComponent(searchQuery)}`),
                api('GET', 'schools'),
                api('GET', 'visits'),
                userRole === null ? api('GET', 'profile').catch(() => ({ role: '' })) : Promise.resolve({ role: userRole })
            ]);

            patients = patientsData && Array.isArray(patientsData.data) ? patientsData.data : [];
            patientPage = patientsData?.page || 1;
            patientTotal = patientsData?.total || 0;
            schools  = Array.isArray(schoolsData)  ? schoolsData  : [];
            visits   = Array.isArray(visitsData)   ? visitsData   : [];
            userRole = profileData?.role || '';
            patientById.clear();
            patients.forEach(patient => patientById.set(String(patient.id), patient));
            schoolById.clear();
            schools.forEach(school => schoolById.set(String(school.id), school));

            // Medication list is best-effort. A failure must not blank
            // the patient table.
            try {
                const medsResponse = await fetch(MED_API_BASE + 'medications', {
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': getCsrfToken() }
                });
                if (medsResponse.ok) {
                    const ct = medsResponse.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        const medsData = await medsResponse.json();
                        medList = Array.isArray(medsData) ? medsData : [];
                    }
                }
            } catch (medErr) {
                console.warn('EMR: medication list unavailable', medErr);
                medList = [];
            }

            renderTable();
            console.log('EMR: Data loaded successfully.');
        } catch (e) {
            console.error('EMR LOAD ERROR:', e);
            const tbody = document.getElementById('patientsTable');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            <strong>Error loading EMR data</strong><br>
                            ${escapeHtml(e.message || 'Unknown error')}
                        </td>
                    </tr>
                `;
            }
        }
    }

    async function fetchPatientPage(page) {
        try {
            const result = await api('GET', `patients?page=${page}&per_page=${patientPerPage}&q=${encodeURIComponent(searchQuery)}`);
            patients = Array.isArray(result.data) ? result.data : [];
            patientPage = result.page || 1;
            patientTotal = result.total || 0;
            if (!patients.length && patientPage > 1) {
                return fetchPatientPage(patientPage - 1);
            }
            patientById.clear();
            patients.forEach(patient => patientById.set(String(patient.id), patient));
            renderTable();
        } catch (e) {
            const tbody = document.getElementById('patientsTable');
            if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">${escapeHtml(e.message || 'Unable to load patients')}</td></tr>`;
        }
    }

    // --------------------------------------------------------------
    // HELPERS
    // --------------------------------------------------------------
    function getSchool(id) { return schoolById.get(String(id)); }
    function getSchoolName(id) { const s = getSchool(id); return s ? s.name : 'N/A'; }
    function getPatient(id) { return patientById.get(String(id)); }
    function getPatientVisits(id) { return visits.filter(v => v.patient_id == id); }
    function getLatestVisit(id) {
        const patientVisits = getPatientVisits(id);
        if (!patientVisits.length) return null;
        return patientVisits.sort((a, b) => new Date(b.followup_date) - new Date(a.followup_date))[0];
    }
    function formatDate(d) {
        if (!d) return '—';
        return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    // NOTE: calculateAge() removed — age is now stored on the patient record.

    // Escape user input before injecting into HTML
    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Wrap matched substrings in <mark> so the user sees why a row matched
    function highlightMatch(text, query) {
        const safe = escapeHtml(text);
        const q = (query || '').trim();
        if (!q) return safe;
        const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const re = new RegExp(`(${escaped})`, 'ig');
        return safe.replace(re, '<mark>$1</mark>');
    }

    // NOTE: computeBMI() / getBMICategory() removed — Height/Weight no longer captured.

    function mergedImpression(imp, diag) {
        const parts = [imp, diag]
            .filter(x => x && String(x).trim())
            .map(x => String(x).toUpperCase().trim());
        const unique = parts.filter((v, i, arr) => arr.indexOf(v) === i);
        return unique.join('\n');
    }

    function normalizePrescription(prescription) {
        if (!prescription) return [];
        if (Array.isArray(prescription)) return prescription;
        if (typeof prescription === 'string') {
            const trimmed = prescription.trim();
            if (!trimmed) return [];
            if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
                try {
                    const parsed = JSON.parse(trimmed);
                    if (Array.isArray(parsed)) return parsed;
                    if (parsed && typeof parsed === 'object') return Object.values(parsed);
                } catch (e) { /* fall through */ }
            }
            return trimmed.split('\n')
                .map(line => line.trim())
                .filter(line => line && line !== '--- PRESCRIPTION ---')
                .map(line => {
                    const m = line.split(/\s+[–-]\s+/);
                    if (m.length > 1) return { name: m[0].trim(), notes: m.slice(1).join(' - ').trim() };
                    return { name: line, notes: '' };
                });
        }
        if (typeof prescription === 'object') return Object.values(prescription);
        return [];
    }

    function normalizeLab(lab) {
        if (!lab) return {};
        if (typeof lab === 'string') {
            try {
                const parsed = JSON.parse(lab);
                return (parsed && typeof parsed === 'object') ? parsed : {};
            } catch (e) { return {}; }
        }
        if (typeof lab === 'object') return lab;
        return {};
    }

    // --------------------------------------------------------------
    // RENDER TABLE (with search filter)
    // --------------------------------------------------------------
    function renderTable() {
        const tbody = document.getElementById('patientsTable');
        const q = (searchQuery || '').trim().toLowerCase();

        // ---- Apply filter ----
        const filtered = patients;

        // ---- Update summary line ----
        const summary = document.getElementById('searchSummary');
        if (summary) {
            if (q) {
                summary.style.display = 'block';
                summary.innerHTML = `Showing <strong>${filtered.length}</strong> of <strong>${patientTotal}</strong> patients for "<strong>${escapeHtml(searchQuery)}</strong>"`;
            } else {
                summary.style.display = 'none';
                summary.innerHTML = '';
            }
        }

        // ---- Empty states ----
        if (!patientTotal) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No patients found</td></tr>';
            return;
        }
        if (!filtered.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center empty-state">
                        <i class="fas fa-search"></i>
                        No patients match "<strong>${escapeHtml(searchQuery)}</strong>"
                    </td>
                </tr>`;
            return;
        }

        // ---- Rows ----
        const pageCount = Math.max(1, Math.ceil(patientTotal / patientPerPage));
        tbody.innerHTML = filtered.map(p => `
            <tr>
                <td><strong>${escapeHtml(p.id)}</strong></td>
                <td><strong>${highlightMatch(p.name || '', searchQuery)}</strong></td>
                <td>${escapeHtml(p.gender || '—')}</td>
                <td>${highlightMatch(getSchoolName(p.school_id), searchQuery)}</td>
                <td><span class="badge-status ${p.status === 'active' ? 'badge-active' : 'badge-inactive'}">${escapeHtml(p.status || 'inactive')}</span></td>
                <td>
                    <button class="btn btn-sm btn-info viewPatientBtn" data-id="${escapeHtml(p.id)}"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-sm btn-warning editPatientBtn" data-id="${escapeHtml(p.id)}"><i class="fas fa-edit"></i></button>
                    ${userRole === 'doctor' ? `<button class="btn btn-sm btn-danger deletePatientBtn" data-id="${escapeHtml(p.id)}"><i class="fas fa-trash"></i></button>` : ''}
                    <button class="btn btn-sm btn-success addVisitForPatientBtn" data-id="${escapeHtml(p.id)}"><i class="fas fa-plus-circle"></i> Follow Up</button>
                </td>
            </tr>
        `).join('') + `
            <tr><td colspan="6" class="text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-page="${Math.max(1, patientPage - 1)}" ${patientPage <= 1 ? 'disabled' : ''}>Previous</button>
                <span class="mx-2">Page ${patientPage} of ${pageCount}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-page="${Math.min(pageCount, patientPage + 1)}" ${patientPage >= pageCount ? 'disabled' : ''}>Next</button>
            </td></tr>`;
    }

    // --------------------------------------------------------------
    // VIEW PATIENT DETAILS
    // --------------------------------------------------------------
    async function viewPatient(id) {
        const patient = getPatient(id);
        if (!patient) return;
        const patientVisits = getPatientVisits(id);

        let visitsHtml = '';
        if (patientVisits.length) {
            visitsHtml = patientVisits.map(v => `
                <div class="timeline-item">
                    <div class="timeline-date">${escapeHtml(formatDate(v.followup_date))}</div>
                    <div class="timeline-content">
                        <strong>Impression / Diagnoses:</strong> ${escapeHtml(mergedImpression(v.impression, v.diagnoses) || '—')}<br>
                        <strong>Maintenance:</strong> ${escapeHtml((v.maintenance || '—').toUpperCase())}<br>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1 viewVisitBtn" data-id="${escapeHtml(v.id)}">View Full Details</button>
                    </div>
                </div>
            `).join('');
        } else {
            visitsHtml = '<p class="text-muted">No follow-ups recorded.</p>';
        }

        const body = `
            <dl class="row">
                <dt class="col-sm-3">ID</dt><dd class="col-sm-9">${escapeHtml(patient.id)}</dd>
                <dt class="col-sm-3">Name</dt><dd class="col-sm-9">${escapeHtml(patient.name)}</dd>
                <dt class="col-sm-3">Gender</dt><dd class="col-sm-9">${escapeHtml(patient.gender || '—')}</dd>
                <dt class="col-sm-3">Age</dt><dd class="col-sm-9">${escapeHtml(patient.age ?? '—')}</dd>
                <dt class="col-sm-3">School</dt><dd class="col-sm-9">${escapeHtml(getSchoolName(patient.school_id))}</dd>
                <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge-status ${patient.status === 'active' ? 'badge-active' : 'badge-inactive'}">${escapeHtml(patient.status || 'inactive')}</span></dd>
                <dt class="col-sm-3">Phone</dt><dd class="col-sm-9">${escapeHtml(patient.phone || '—')}</dd>
                <dt class="col-sm-3">Address</dt><dd class="col-sm-9">${escapeHtml(patient.address || '—')}</dd>
            </dl>
            <hr>
            <h6>Follow-up History</h6>
            <div class="timeline">${visitsHtml}</div>
        `;
        document.getElementById('patientDetailsBody').innerHTML = body;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewPatientModal')).show();
    }

    // --------------------------------------------------------------
    // VIEW FOLLOW-UP DETAILS
    // --------------------------------------------------------------
    async function viewVisit(id) {
        let v;
        try {
            v = await api('GET', 'visit/' + encodeURIComponent(id));
        } catch (e) {
            alert('Error loading follow-up details: ' + e.message);
            return;
        }

        const p = getPatient(v.patient_id) || { name: v.patient_name, school_id: v.school_id };
        const s = getSchool(p.school_id) || { name: v.school_name, district: v.district };
        const lab = normalizeLab(v.lab);
        const prescription = normalizePrescription(v.prescription);

        // --- Simplified: Parameter | Result (no Ref. Range) ---
        function renderLabCategory(data) {
            if (!data) return '<p class="text-muted">No data</p>';

            if (Array.isArray(data)) {
                let html = `
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach(row => {
                    html += `
                        <tr>
                            <td>${escapeHtml((row.parameter || '—').toUpperCase())}</td>
                            <td>${escapeHtml((row.result || '—').toUpperCase())}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                return html;
            }

            return `<p><strong>Result:</strong> ${escapeHtml((data || '').toUpperCase())}</p>`;
        }

        // --- Simplified: Analysis | Result (no Method / Range) ---
        function renderChemistryAnalyses(analyses) {
            if (!analyses || !analyses.length) {
                return '<p class="text-muted">No data</p>';
            }

            let html = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Analysis</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            analyses.forEach(row => {
                html += `
                    <tr>
                        <td>${escapeHtml((row.name || '—').toUpperCase())}</td>
                        <td>${escapeHtml((row.result || '—').toUpperCase())}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            return html;
        }

        let labHtml = '';

        if (lab.cbc || lab.blood_chemistry) {
            labHtml += `<h6>CBC / Blood Chemistry</h6>`;

            if (lab.cbc) {
                labHtml += `<h6 class="mt-3">Complete Blood Count (CBC)</h6>`;
                labHtml += renderLabCategory(lab.cbc);
            }

            if (
                lab.blood_chemistry &&
                Array.isArray(lab.blood_chemistry.analyses) &&
                lab.blood_chemistry.analyses.length
            ) {
                labHtml += `<h6 class="mt-3">Blood Chemistry</h6>`;
                labHtml += renderChemistryAnalyses(lab.blood_chemistry.analyses);
            }
        }

        if (lab.urinalysis) {
            labHtml += `<h6 class="mt-3">Urinalysis</h6>`;
            labHtml += renderLabCategory(lab.urinalysis);
        }

        let prescriptionHtml = '';
        if (prescription && prescription.length) {
            prescriptionHtml = '<ul class="list-unstyled">';
            prescription.forEach(item => {
                const itemName = (item && item.name) ? String(item.name) : '';
                const itemNotes = (item && item.notes) ? String(item.notes) : '';
                prescriptionHtml += `<li><strong>${escapeHtml(itemName)}</strong> ${itemNotes ? '— ' + escapeHtml(itemNotes) : ''}</li>`;
            });
            prescriptionHtml += '</ul>';
        } else {
            prescriptionHtml = '<p class="text-muted">No prescription recorded.</p>';
        }

        const body = `
            <dl class="row">
                <dt class="col-sm-3">Patient</dt><dd class="col-sm-9">${escapeHtml(p ? p.name : 'Unknown')}</dd>
                <dt class="col-sm-3">School</dt><dd class="col-sm-9">${escapeHtml(s ? s.name + ' (' + s.district + ')' : 'N/A')}</dd>
                <dt class="col-sm-3">Date</dt><dd class="col-sm-9">${escapeHtml(formatDate(v.followup_date))}</dd>
                <dt class="col-sm-3">Impression / Diagnoses</dt><dd class="col-sm-9">${escapeHtml(mergedImpression(v.impression, v.diagnoses) || '—')}</dd>
                <dt class="col-sm-3">Maintenance</dt><dd class="col-sm-9">${escapeHtml((v.maintenance || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Doctor's Notes</dt><dd class="col-sm-9">${escapeHtml((v.doctor_notes || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">BP</dt><dd class="col-sm-9">${escapeHtml(v.bp ?? '—')}</dd>
                <dt class="col-sm-3">HR</dt><dd class="col-sm-9">${escapeHtml(v.hr ?? '—')} bpm</dd>
                <dt class="col-sm-3">SpO₂</dt><dd class="col-sm-9">${escapeHtml(v.spo2 ?? '—')}%</dd>
                <dt class="col-sm-3">RR</dt><dd class="col-sm-9">${escapeHtml(v.rr ?? '—')} /min</dd>
                <dt class="col-sm-3">Temperature</dt><dd class="col-sm-9">${escapeHtml(v.temp ?? '—')} °C</dd>
                <dt class="col-sm-3">Vision</dt><dd class="col-sm-9">${escapeHtml((v.vision_left || '—').toUpperCase())} / ${escapeHtml((v.vision_right || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Hearing</dt><dd class="col-sm-9">${escapeHtml((v.hearing_left || '—').toUpperCase())} / ${escapeHtml((v.hearing_right || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Dental</dt><dd class="col-sm-9">${escapeHtml((v.dental_findings || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">X-ray Results</dt><dd class="col-sm-9">${escapeHtml(v.xray_results || '—')}</dd>
                <dt class="col-sm-3">Immunizations</dt><dd class="col-sm-9">${escapeHtml((v.immunizations || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Referral</dt><dd class="col-sm-9">${escapeHtml((v.referral_status || '—').toUpperCase())} ${v.referral_notes ? '(' + escapeHtml(v.referral_notes.toUpperCase()) + ')' : ''}</dd>
            </dl>
            <h6>Prescription</h6>
            ${prescriptionHtml}
            <hr>
            <h6>Lab Results</h6>
            ${labHtml || '<p class="text-muted">No lab data recorded.</p>'}
            <hr>
        `;
        document.getElementById('visitDetailsBody').innerHTML = body;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewVisitModal')).show();
    }

    // --------------------------------------------------------------
    // PATIENT MODAL (WIZARD) – 3 steps: Patient, Follow-up, Lab
    // --------------------------------------------------------------
    function openPatientModal(patientId = null, mode = 'new') {
        let patient = null;
        let isEdit = false;
        let currentStep = 0;

        if (patientId) {
            patient = getPatient(patientId);
            if (!patient) return;
            isEdit = true;
            currentStep = (mode === 'visit') ? 1 : 0;
        } else {
            // age replaces dob
            patient = { name: '', gender: '', age: '', school_id: '', status: 'active', phone: '', address: '' };
            isEdit = false;
            currentStep = 0;
        }

        // Patient field on Follow-up tab
        let patientFieldHtml;
        if (mode === 'new' || mode === 'visit') {
            patientFieldHtml = `
                <div class="col-md-6">
                    <label>Patient</label>
                    <input type="text" class="form-control" id="visitPatientNameDisplay" value="${escapeHtml((patient.name || '').toUpperCase())}" disabled />
                    <input type="hidden" name="patient_id" id="visitPatientIdHidden" value="${escapeHtml(mode === 'visit' ? patient.id : '')}" />
                </div>
            `;
        } else {
            const patientOptions = patients.map(p =>
                `<option value="${escapeHtml(p.id)}" ${p.id == (patient.id || '') ? 'selected' : ''}>${escapeHtml(p.name)}</option>`
            ).join('');
            patientFieldHtml = `
                <div class="col-md-6">
                    <label>Patient *</label>
                    <select name="patient_id" class="form-select" required>${patientOptions}</select>
                </div>
            `;
        }

        const modalTitle = isEdit ? (mode === 'visit' ? 'Add Follow-up' : 'Edit Patient') : 'New Patient';

        const v = {};
        const lab = normalizeLab(v.lab);
        const prescription = normalizePrescription(v.prescription);
        const combinedImpression = mergedImpression(v.impression, v.diagnoses);

        // --- LAB ROWS ---
        function generateLabRows(categoryData, fieldMapping, defaultParam = 'Result') {
            if (typeof categoryData === 'string') {
                let rowHtml = '<div class="dynamic-row row g-2">';
                rowHtml += '<div class="col-11"><div class="row g-2">';
                fieldMapping.forEach(map => {
                    let value = '';
                    if (map.inputName.endsWith('_result')) {
                        value = String(categoryData || '').toUpperCase();
                    } else if (map.inputName.endsWith('_param')) {
                        value = String(defaultParam || '').toUpperCase();
                    }
                    rowHtml += `<div class="col-md-${map.col || 4}">
                                <input type="text" class="form-control form-control-sm" placeholder="${escapeHtml(map.placeholder)}" name="${escapeHtml(map.inputName)}" value="${escapeHtml(value)}" />
                             </div>`;
                });
                rowHtml += '</div></div>';
                rowHtml += `<div class="col-1"><button type="button" class="btn-remove"><i class="fas fa-trash-alt"></i></button></div>`;
                rowHtml += '</div>';
                return rowHtml;
            }
            if (Array.isArray(categoryData) && categoryData.length) {
                let html = '';
                categoryData.forEach(item => {
                    let rowHtml = '<div class="dynamic-row row g-2">';
                    rowHtml += '<div class="col-11"><div class="row g-2">';
                    fieldMapping.forEach(map => {
                        const raw = item[map.dataKey];
                        const value = (raw === null || raw === undefined) ? '' : String(raw).toUpperCase();
                        rowHtml += `<div class="col-md-${map.col || 4}">
                                    <input type="text" class="form-control form-control-sm" placeholder="${escapeHtml(map.placeholder)}" name="${escapeHtml(map.inputName)}" value="${escapeHtml(value)}" />
                                 </div>`;
                    });
                    rowHtml += '</div></div>';
                    rowHtml += `<div class="col-1"><button type="button" class="btn-remove"><i class="fas fa-trash-alt"></i></button></div>`;
                    rowHtml += '</div>';
                    html += rowHtml;
                });
                return html;
            }
            return '<div class="empty-lab-msg">No data recorded.</div>';
        }

        // --- Lab mappings (2 fields each; no ref_range / method / range) ---
        const cbcMapping = [
            {
                dataKey: 'parameter',
                inputName: 'cbc_param',
                placeholder: 'Parameter',
                col: 4
            },
            {
                dataKey: 'result',
                inputName: 'cbc_result',
                placeholder: 'Result',
                col: 4
            }
        ];

        const urineMapping = [
            {
                dataKey: 'parameter',
                inputName: 'urine_param',
                placeholder: 'Parameter',
                col: 4
            },
            {
                dataKey: 'result',
                inputName: 'urine_result',
                placeholder: 'Result',
                col: 4
            }
        ];

        const chemMapping = [
            {
                dataKey: 'name',
                inputName: 'chem_name',
                placeholder: 'Analysis',
                col: 4
            },
            {
                dataKey: 'result',
                inputName: 'chem_result',
                placeholder: 'Result',
                col: 4
            }
        ];

        const cbcData   = lab.cbc || '';
        const urineData = lab.urinalysis || '';
        let chemArray   = [];
        if (lab.blood_chemistry && Array.isArray(lab.blood_chemistry.analyses) && lab.blood_chemistry.analyses.length) {
            chemArray = lab.blood_chemistry.analyses;
        } else {
            // Legacy flat fields → only name/result kept
            const keys = ['fbs_hba1c', 'lipid_panel', 'bua', 'bun', 'creatinine', 'sgpt_sgot', 'others'];
            keys.forEach(key => {
                if (lab[key]) chemArray.push({ name: key.toUpperCase(), result: lab[key].toUpperCase() });
            });
        }
        const hasHba1c = chemArray.some(item => item.name && item.name.toUpperCase() === 'HBA1C');
        if (!hasHba1c) chemArray.unshift({ name: 'HBA1C', result: '' });

        const cbcRowsHtml   = generateLabRows(cbcData,   cbcMapping,   'Result');
        const urineRowsHtml = generateLabRows(urineData, urineMapping, 'Result');
        const chemRowsHtml  = generateLabRows(chemArray, chemMapping);

        // --- Prescription entries ---
        function generatePrescriptionEntries(prescriptionData) {
            const safeData = normalizePrescription(prescriptionData);
            if (!safeData.length) return '';
            let html = '';
            safeData.forEach((item, idx) => {
                const itemName  = (item && item.name)  ? item.name  : '';
                const itemNotes = (item && item.notes) ? item.notes : '';
                html += `
                    <div class="med-entry" data-index="${idx}">
                        <button type="button" class="btn-remove-med"><i class="fas fa-times-circle"></i></button>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Medication</label>
                                <div class="med-search-wrapper">
                                    <input type="text" class="form-control med-search-input" placeholder="Search medication..." value="${escapeHtml(itemName)}" />
                                    <div class="med-search-results list-group"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Instructions / Notes</label>
                                <textarea class="form-control med-notes-textarea" rows="2">${escapeHtml(itemNotes)}</textarea>
                            </div>
                        </div>
                    </div>
                `;
            });
            return html;
        }
        const prescriptionHtml = generatePrescriptionEntries(prescription);

        // --- Wizard HTML ---
        const html = `
            <div class="step-indicators">
                <div class="step ${currentStep === 0 ? 'active' : ''}" data-step="0">1. Patient</div>
                <div class="step ${currentStep === 1 ? 'active' : ''}" data-step="1">2. Follow-up</div>
                <div class="step ${currentStep === 2 ? 'active' : ''}" data-step="2">3. Lab</div>
            </div>

            <!-- STEP 0: PATIENT -->
            <div class="step-content ${currentStep === 0 ? 'active' : ''}" data-step="0">
                <form id="patientForm" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Patient ID</label>
                            <input type="text" class="form-control" value="${isEdit ? patient.id : 'Auto-generated'}" disabled />
                        </div>
                        <div class="col-md-6"><label>Name *</label><input name="name" class="form-control" value="${escapeHtml((patient.name || '').toUpperCase())}" required /></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><label>Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male" ${patient.gender === 'Male' ? 'selected' : ''}>Male</option>
                                <option value="Female" ${patient.gender === 'Female' ? 'selected' : ''}>Female</option>
                                <option value="Other" ${patient.gender === 'Other' ? 'selected' : ''}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Age</label>
                            <input
                                name="age"
                                type="number"
                                min="0"
                                max="150"
                                class="form-control"
                                value="${escapeHtml(patient.age ?? '')}"
                            />
                        </div>
                        <div class="col-md-4"><label>Phone</label><input name="phone" class="form-control" value="${escapeHtml((patient.phone || '').toUpperCase())}" /></div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <label>School</label>
                            <div style="position:relative;">
                                <input type="text" id="schoolSearchInput" class="form-control" placeholder="Type school name..." autocomplete="off" />
                                <input type="hidden" name="school_id" id="schoolIdHidden" value="${escapeHtml(patient.school_id || '')}" />
                                <div id="schoolSuggestions" class="list-group" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="col-md-4"><label>Status</label>
                            <select name="status" class="form-select">
                                <option value="active" ${patient.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${patient.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><label>District</label><input type="text" id="districtDisplay" class="form-control" readonly /></div>
                        <div class="col-md-6"><label>Legislative District</label><input type="text" id="legislativeDisplay" class="form-control" readonly /></div>
                    </div>
                    <div class="mb-3"><label>Address</label><textarea name="address" class="form-control" rows="2">${escapeHtml((patient.address || '').toUpperCase())}</textarea></div>
                </form>
            </div>

            <!-- STEP 1: FOLLOW-UP -->
            <div class="step-content ${currentStep === 1 ? 'active' : ''}" data-step="1">
                <form id="visitForm" novalidate>
                    <div class="row">
                        ${patientFieldHtml}
                        <div class="col-md-6"><label>Follow-up Date *</label><input name="followup_date" type="date" class="form-control" value="${escapeHtml(v.followup_date || new Date().toISOString().slice(0, 10))}" required /></div>
                    </div>

                    <!-- Vitals: BP / HR / SpO2 / RR / Temp (replaces Height/Weight/BMI) -->
                    <div class="row">
                        <div class="col-md-4">
                            <label>BP</label>
                            <input
                                name="bp"
                                type="text"
                                class="form-control"
                                placeholder="120/80"
                                value="${escapeHtml((v.bp ?? '').toUpperCase())}"
                            />
                        </div>

                        <div class="col-md-2">
                            <label>HR</label>
                            <input
                                name="hr"
                                type="number"
                                class="form-control"
                                placeholder="bpm"
                                value="${escapeHtml(v.hr ?? '')}"
                            />
                        </div>

                        <div class="col-md-2">
                            <label>SpO₂</label>
                            <input
                                name="spo2"
                                type="number"
                                class="form-control"
                                placeholder="%"
                                value="${escapeHtml(v.spo2 ?? '')}"
                            />
                        </div>

                        <div class="col-md-2">
                            <label>RR</label>
                            <input
                                name="rr"
                                type="number"
                                class="form-control"
                                placeholder="/min"
                                value="${escapeHtml(v.rr ?? '')}"
                            />
                        </div>

                        <div class="col-md-2">
                            <label>Temp</label>
                            <input
                                name="temp"
                                type="number"
                                step="0.1"
                                class="form-control"
                                placeholder="°C"
                                value="${escapeHtml(v.temp ?? '')}"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Impression / Diagnoses</label>
                        <textarea name="impression" class="form-control" rows="3">${escapeHtml(combinedImpression)}</textarea>
                    </div>
                    <div class="mb-3"><label>Maintenance</label><textarea name="maintenance" class="form-control" rows="2">${escapeHtml((v.maintenance || '').toUpperCase())}</textarea></div>
                    <div class="mb-3"><label>Doctor's Notes</label><textarea name="doctor_notes" class="form-control" rows="2">${escapeHtml((v.doctor_notes || '').toUpperCase())}</textarea></div>

                    <hr>
                    <h6><i class="fas fa-prescription-bottle-alt text-primary"></i> Prescription / Medications</h6>
                    <div id="prescriptionContainer">${prescriptionHtml}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary add-btn" id="addMedicationBtn">
                        <i class="fas fa-plus"></i> Add Medication
                    </button>

                    <hr>
                    <h6>Screenings</h6>
                    <div class="row">
                        <div class="col-md-4"><label>Vision (L/R)</label><input name="vision_left" class="form-control" placeholder="L" value="${escapeHtml((v.vision_left || '').toUpperCase())}" /> <input name="vision_right" class="form-control mt-1" placeholder="R" value="${escapeHtml((v.vision_right || '').toUpperCase())}" /></div>
                        <div class="col-md-4"><label>Hearing (L/R)</label><input name="hearing_left" class="form-control" placeholder="L" value="${escapeHtml((v.hearing_left || '').toUpperCase())}" /> <input name="hearing_right" class="form-control mt-1" placeholder="R" value="${escapeHtml((v.hearing_right || '').toUpperCase())}" /></div>
                        <div class="col-md-4"><label>Dental Findings</label><input name="dental_findings" class="form-control" value="${escapeHtml((v.dental_findings || '').toUpperCase())}" /></div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12"><label>X-ray Results</label><textarea name="xray_results" class="form-control" rows="2">${escapeHtml(v.xray_results || '')}</textarea></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><label>Immunizations</label><input name="immunizations" class="form-control" value="${escapeHtml((v.immunizations || '').toUpperCase())}" /></div>
                        <div class="col-md-6"><label>Referral Status</label>
                            <select name="referral_status" class="form-select">
                                <option value="">None</option>
                                <option value="Pending" ${v.referral_status === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Completed" ${v.referral_status === 'Completed' ? 'selected' : ''}>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label>Referral Notes</label><textarea name="referral_notes" class="form-control" rows="2">${escapeHtml((v.referral_notes || '').toUpperCase())}</textarea></div>
                </form>
            </div>

            <!-- STEP 2: LAB -->
            <div class="step-content ${currentStep === 2 ? 'active' : ''}" data-step="2">
                <div class="lab-sub-tabs nav nav-pills mb-3">
                    <button class="nav-link active" data-lab-group="cbc_blood_chemistry" type="button">
                        CBC/Blood Chemistry
                    </button>
                    <button class="nav-link" data-lab-group="urinalysis" type="button">
                        Urinalysis
                    </button>
                </div>

                <!-- COMBINED CBC / BLOOD CHEMISTRY TAB -->
                <div class="lab-sub-content active" data-lab-group="cbc_blood_chemistry">

                    <h6>Complete Blood Count (CBC)</h6>

                    <div id="cbcContainer">
                        ${cbcRowsHtml}
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary add-btn mb-3"
                        data-container="cbcContainer" data-fields='[{"name":"cbc_param","placeholder":"Parameter","col":4},{"name":"cbc_result","placeholder":"Result","col":4}]'>
                        <i class="fas fa-plus"></i> Create CBC Parameter
                    </button>

                    <hr>

                    <h6>Blood Chemistry</h6>

                    <div id="chemistryContainer">
                        ${chemRowsHtml}
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary add-btn"
                        data-container="chemistryContainer" data-fields='[{"name":"chem_name","placeholder":"Analysis","col":4},{"name":"chem_result","placeholder":"Result","col":4}]'>
                        <i class="fas fa-plus"></i> Create Analysis
                    </button>

                </div>

                <!-- URINALYSIS TAB -->
                <div class="lab-sub-content" data-lab-group="urinalysis">

                    <h6>Urinalysis</h6>

                    <div id="urinalysisContainer">
                        ${urineRowsHtml}
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary add-btn"
                        data-container="urinalysisContainer" data-fields='[{"name":"urine_param","placeholder":"Parameter","col":4},{"name":"urine_result","placeholder":"Result","col":4}]'>
                        <i class="fas fa-plus"></i> Create Parameter
                    </button>

                </div>
            </div>
        `;

        // --------------------------------------------------------------
        // WIZARD ENGINE
        // --------------------------------------------------------------

        function showModalWizard(title, bodyHTML, startStep, onSave) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalContent').innerHTML = bodyHTML;
            const errorDiv = document.getElementById('modalErrorMessage');
            errorDiv.style.display = 'none';
            errorDiv.textContent = '';

            const modalElement = document.getElementById('emrModal');
            const modal  = bootstrap.Modal.getOrCreateInstance(modalElement);
            const footer = document.getElementById('modalFooter');
            const subTabOrder = ['cbc_blood_chemistry', 'urinalysis'];
            const lastStep = (mode === 'edit') ? 0 : 2;

            function getActiveStep() {
                const el = document.querySelector('#modalContent .step-content.active');
                return el ? parseInt(el.dataset.step) : 0;
            }
            function getActiveSubTab() {
                const el = document.querySelector('#modalContent .lab-sub-content.active');
                return el ? el.dataset.labGroup : 'cbc_blood_chemistry';
            }

            function renderFooter() {
                if (mode === 'edit') {
                    footer.innerHTML = '<button type="button" class="btn btn-success" data-action="save"><i class="fas fa-save"></i> Save</button>';
                    return;
                }
                const step = getActiveStep();
                const subTab = getActiveSubTab();
                let buttons = '';

                if (step < lastStep) {
                    if (step > 0) {
                        buttons += `<button type="button" class="btn btn-secondary" data-action="prev-step"><i class="fas fa-arrow-left"></i> Previous</button>`;
                    }
                    buttons += `<button type="button" class="btn btn-primary" data-action="next-step">Next <i class="fas fa-arrow-right"></i></button>`;
                } else {
                    const idx = subTabOrder.indexOf(subTab);
                    buttons += `<button type="button" class="btn btn-secondary" data-action="prev-lab"><i class="fas fa-arrow-left"></i> Previous</button>`;
                    if (idx < subTabOrder.length - 1) {
                        buttons += `<button type="button" class="btn btn-primary" data-action="next-lab">Next <i class="fas fa-arrow-right"></i></button>`;
                    } else {
                        buttons += `<button type="button" class="btn btn-success" data-action="save"><i class="fas fa-save"></i> Save</button>`;
                    }
                }
                footer.innerHTML = buttons;
            }

            function showStep(step) {
                document.querySelectorAll('#modalContent .step-indicators .step').forEach(el => {
                    const s = parseInt(el.dataset.step);
                    el.classList.remove('active', 'done');
                    if (s === step) el.classList.add('active');
                    else if (s < step) el.classList.add('done');
                });
                document.querySelectorAll('#modalContent .step-content').forEach(el => {
                    el.classList.toggle('active', parseInt(el.dataset.step) === step);
                });
            }

            function showSubTab(group) {
                document.querySelectorAll('#modalContent .lab-sub-tabs .nav-link').forEach(t => {
                    t.classList.toggle('active', t.dataset.labGroup === group);
                });
                document.querySelectorAll('#modalContent .lab-sub-content').forEach(c => {
                    c.classList.toggle('active', c.dataset.labGroup === group);
                });
            }

            // Register the handler for this modal-open session.
            wizardClickHandler = function (btn) {
                const action = btn.dataset.action;

                if (action === 'prev-step') {
                    showStep(getActiveStep() - 1);
                    renderFooter();
                    return;
                }

                if (action === 'next-step') {
                    const step = getActiveStep();
                    if (step === 0 || mode === 'edit') {
                        const name = document.querySelector('#patientForm [name="name"]');
                        if (!name || !name.value.trim()) { alert('Patient name is required.'); return; }
                        const schoolId = document.getElementById('schoolIdHidden');
                        if (!schoolId || !schoolId.value) { alert('Please select a school.'); return; }
                    }
                    if (step === 1) {
                        const visitDate = document.querySelector('#visitForm [name="followup_date"]');
                        if (!visitDate || !visitDate.value) { alert('Follow-up date is required.'); return; }
                        const patientSelect = document.querySelector('#visitForm [name="patient_id"]');
                        if (patientSelect && patientSelect.tagName === 'SELECT' && !patientSelect.value) {
                            alert('Please select a patient.'); return;
                        }
                    }
                    const newStep = step + 1;
                    showStep(newStep);
                    if (newStep === lastStep) showSubTab('cbc_blood_chemistry');
                    renderFooter();
                    return;
                }

                if (action === 'prev-lab') {
                    const subTab = getActiveSubTab();
                    const idx = subTabOrder.indexOf(subTab);
                    if (idx > 0) showSubTab(subTabOrder[idx - 1]);
                    else showStep(lastStep - 1);
                    renderFooter();
                    return;
                }

                if (action === 'next-lab') {
                    const subTab = getActiveSubTab();
                    const idx = subTabOrder.indexOf(subTab);
                    if (idx < subTabOrder.length - 1) showSubTab(subTabOrder[idx + 1]);
                    renderFooter();
                    return;
                }

                if (action === 'save') {
                    if (mode === 'edit') {
                        const name = document.querySelector('#patientForm [name="name"]');
                        if (!name || !name.value.trim()) { alert('Patient name is required.'); return; }
                        const schoolId = document.getElementById('schoolIdHidden');
                        if (!schoolId || !schoolId.value) { alert('Please select a school.'); return; }
                    }
                    const result = onSave(btn);
                    if (result && result.then) {
                        result.then(success => { if (success === true) modal.hide(); });
                    } else if (result === true) {
                        modal.hide();
                    }
                    return;
                }
            };
            document.querySelectorAll('#modalContent .lab-sub-tabs .nav-link').forEach(tab => {
                tab.addEventListener('click', function () {
                    if (getActiveStep() === lastStep) {
                        showSubTab(this.dataset.labGroup);
                        renderFooter();
                    }
                });
            });

            const addMedBtn = document.getElementById('addMedicationBtn');
            if (addMedBtn) addMedBtn.addEventListener('click', function () { addPrescriptionEntry(); });
            setupPrescriptionAutocomplete();

            showStep(startStep);
            if (startStep === lastStep) showSubTab('cbc_blood_chemistry');
            renderFooter();

            modal.show();

            // Clear handler when modal closes so stale closures are released.
            modalElement.addEventListener('hidden.bs.modal', function () {
                wizardClickHandler = null;
            }, { once: true });
        }

        // --------------------------------------------------------------
        // PRESCRIPTION HELPERS
        // --------------------------------------------------------------
        function setupPrescriptionAutocomplete() {
            document.querySelectorAll('.med-search-wrapper').forEach(wrapper => {
                const input = wrapper.querySelector('.med-search-input');
                const resultsDiv = wrapper.querySelector('.med-search-results');
                if (!input) return;
                let debounceTimer;

                input.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim().toUpperCase();
                    if (query.length < 2) { resultsDiv.style.display = 'none'; resultsDiv.innerHTML = ''; return; }
                    debounceTimer = setTimeout(() => {
                        const matches = medList.filter(m => m.name.toUpperCase().includes(query));
                        resultsDiv.innerHTML = '';
                        if (matches.length === 0) { resultsDiv.style.display = 'none'; return; }
                        matches.forEach(med => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = `<strong>${escapeHtml(med.name)}</strong> ${med.default_notes ? '<br><small>' + escapeHtml(med.default_notes) + '</small>' : ''}`;
                            item.dataset.id = med.id;
                            item.dataset.notes = med.default_notes || '';
                            item.addEventListener('click', function () {
                                input.value = med.name;
                                const notesTextarea = wrapper.closest('.med-entry').querySelector('.med-notes-textarea');
                                if (notesTextarea) notesTextarea.value = med.default_notes || '';
                                resultsDiv.style.display = 'none';
                            });
                            resultsDiv.appendChild(item);
                        });
                        resultsDiv.style.display = 'block';
                    }, 300);
                });

                input.addEventListener('blur', function () {
                    setTimeout(() => { resultsDiv.style.display = 'none'; }, 200);
                });
                input.addEventListener('focus', function () {
                    if (this.value.trim().length >= 2) {
                        const items = resultsDiv.querySelectorAll('.list-group-item');
                        if (items.length) resultsDiv.style.display = 'block';
                    }
                });
            });
        }

        function addPrescriptionEntry(name = '', notes = '') {
            const container = document.getElementById('prescriptionContainer');
            if (!container) return;

            const entry = document.createElement('div');
            entry.className = 'med-entry';
            entry.innerHTML = `
                <button type="button" class="btn-remove-med"><i class="fas fa-times-circle"></i></button>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Medication</label>
                        <div class="med-search-wrapper">
                            <input type="text" class="form-control med-search-input" placeholder="Search medication..." value="${escapeHtml(name)}" />
                            <div class="med-search-results list-group"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Instructions / Notes</label>
                        <textarea class="form-control med-notes-textarea" rows="2">${escapeHtml(notes)}</textarea>
                    </div>
                </div>
            `;
            container.appendChild(entry);
            setupPrescriptionAutocomplete();
        }

        // --------------------------------------------------------------
        // SAVE
        // --------------------------------------------------------------
        showModalWizard(modalTitle, html, currentStep, async (saveBtn) => {
            const wasExistingPatient = isEdit;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            const errorDiv = document.getElementById('modalErrorMessage');
            errorDiv.style.display = 'none';

            try {
                // Save Patient
                const patientForm = document.getElementById('patientForm');
                const patientFormData = new FormData(patientForm);
                const patientObj = {};
                patientFormData.forEach((v, k) => patientObj[k] = v);
                const skipPatientUpper = ['age', 'school_id', 'gender', 'status', 'consent'];
                for (let key in patientObj) {
                    if (typeof patientObj[key] === 'string' && !skipPatientUpper.includes(key)) {
                        patientObj[key] = patientObj[key].toUpperCase();
                    }
                }
                const schoolId = document.getElementById('schoolIdHidden').value;
                if (!schoolId) throw new Error('Please select a valid school.');
                patientObj.school_id = schoolId;

                let patientId;
                if (isEdit) {
                    await api('PUT', 'patient/' + patient.id, patientObj);
                    patientId = patient.id;
                    const idx = patients.findIndex(p => p.id == patient.id);
                    if (idx > -1) patients[idx] = patientObj;
                } else {
                    const result = await api('POST', 'patient', patientObj);
                    if (!result || !result.patient) throw new Error('Patient save returned no patient.');
                    patients.push(result.patient);
                    patientId = result.patient.id;
                    patient   = result.patient;
                    isEdit    = true;                        // ← prevents re-POST on retry
                    const visitPatientIdHidden = document.getElementById('visitPatientIdHidden');
                    if (visitPatientIdHidden) visitPatientIdHidden.value = patientId;
                }

                // Save Follow-up
                const visitForm = document.getElementById('visitForm');
                if (visitForm && mode !== 'edit') {
                    const visitFormData = new FormData(visitForm);
                    const visitObj = {};
                    visitFormData.forEach((v, k) => {
                        if (!k.startsWith('lab_')) visitObj[k] = v;
                    });

                    const skipVisitUpper = [
                        'followup_date',
                        'bp', 'hr', 'spo2', 'rr', 'temp',
                        'referral_status',
                        'patient_id'
                    ];
                    for (let key in visitObj) {
                        if (typeof visitObj[key] === 'string' && !skipVisitUpper.includes(key)) {
                            visitObj[key] = visitObj[key].toUpperCase();
                        }
                    }

                    if (mode === 'new' || mode === 'visit') {
                        const hiddenPatientId = document.getElementById('visitPatientIdHidden')?.value;
                        if (hiddenPatientId) visitObj.patient_id = hiddenPatientId;
                    }

                    if (visitObj.followup_date && visitObj.patient_id) {
                        visitObj.patient_id = patientId;

                        // --- Lab: CBC ---
                        const labData = {};
                        const cbcParams = [];
                        document.querySelectorAll('#cbcContainer .dynamic-row').forEach(row => {
                            const inputs = row.querySelectorAll('input');
                            if (inputs.length >= 2) {
                                const parameter = inputs[0].value.trim();
                                const result = inputs[1].value.trim();
                                if (parameter || result) {
                                    cbcParams.push({
                                        parameter: parameter.toUpperCase(),
                                        result: result.toUpperCase()
                                    });
                                }
                            }
                        });
                        if (cbcParams.length) labData.cbc = cbcParams;

                        // --- Lab: Urinalysis ---
                        const urineParams = [];
                        document.querySelectorAll('#urinalysisContainer .dynamic-row').forEach(row => {
                            const inputs = row.querySelectorAll('input');
                            if (inputs.length >= 2) {
                                const parameter = inputs[0].value.trim();
                                const result = inputs[1].value.trim();
                                if (parameter || result) {
                                    urineParams.push({
                                        parameter: parameter.toUpperCase(),
                                        result: result.toUpperCase()
                                    });
                                }
                            }
                        });
                        if (urineParams.length) labData.urinalysis = urineParams;

                        // --- Lab: Blood Chemistry ---
                        const chemAnalyses = [];
                        document.querySelectorAll('#chemistryContainer .dynamic-row').forEach(row => {
                            const inputs = row.querySelectorAll('input');
                            if (inputs.length >= 2) {
                                const name = inputs[0].value.trim();
                                const result = inputs[1].value.trim();
                                if (name || result) {
                                    chemAnalyses.push({
                                        name: name.toUpperCase(),
                                        result: result.toUpperCase()
                                    });
                                }
                            }
                        });
                        labData.blood_chemistry = { analyses: chemAnalyses };

                        visitObj.lab = labData;

                        // Prescription
                        const prescription = [];
                        document.querySelectorAll('.med-entry').forEach(entry => {
                            const nameInput = entry.querySelector('.med-search-input');
                            const notesTextarea = entry.querySelector('.med-notes-textarea');
                            if (nameInput && nameInput.value.trim()) {
                                prescription.push({
                                    name:  nameInput.value.trim().toUpperCase(),
                                    notes: notesTextarea ? notesTextarea.value.trim().toUpperCase() : ''
                                });
                            }
                        });
                        visitObj.prescription = prescription;

                        const result = await api('POST', 'visit', visitObj);
                        const newVisit = result.followup || result.visit;
                        visits.push(newVisit);
                    }
                }

                await fetchPatientPage(wasExistingPatient ? patientPage : 1);
                return true;
            } catch (e) {
                errorDiv.textContent = e.message || 'An error occurred';
                errorDiv.style.display = 'block';
                return false;
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
            }
        });

        // After modal shown
        const modalElement = document.getElementById('emrModal');
        const shownHandler = function () {
            setupSchoolAutocomplete(patient.school_id);

            // NOTE: Height/Weight/BMI listeners removed.

            if (mode === 'new') {
                const nameInput = document.querySelector('#patientForm [name="name"]');
                const visitNameDisplay = document.getElementById('visitPatientNameDisplay');
                if (nameInput && visitNameDisplay) {
                    nameInput.addEventListener('input', function () {
                        visitNameDisplay.value = this.value.toUpperCase();
                    });
                }
            }
            setupPrescriptionAutocomplete();
            modalElement.removeEventListener('shown.bs.modal', shownHandler);
        };
        modalElement.addEventListener('shown.bs.modal', shownHandler);

        // NOTE: calcBMI() removed.
    }

    // --------------------------------------------------------------
    // DYNAMIC LAB ROWS
    // --------------------------------------------------------------
    function addDynamicRow(containerId, fields, rowClass = 'dynamic-row') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const row = document.createElement('div');
        row.className = rowClass + ' row g-2';
        let html = '<div class="col-11"><div class="row g-2">';
        fields.forEach(field => {
            html += `<div class="col-md-${field.col || 4}">
                        <input type="text" class="form-control form-control-sm" placeholder="${escapeHtml(field.placeholder)}"
                               name="${escapeHtml(field.name)}" style="text-transform:uppercase;" />
                     </div>`;
        });
        html += '</div></div>';
        html += '<div class="col-1"><button type="button" class="btn-remove"><i class="fas fa-trash-alt"></i></button></div>';
        row.innerHTML = html;
        container.appendChild(row);
    }
    // --------------------------------------------------------------
    // SCHOOL AUTOCOMPLETE
    // --------------------------------------------------------------
    function setupSchoolAutocomplete(selectedSchoolId = null) {
        const input = document.getElementById('schoolSearchInput');
        const suggestions = document.getElementById('schoolSuggestions');
        const schoolIdHidden = document.getElementById('schoolIdHidden');
        const districtDisplay = document.getElementById('districtDisplay');
        const legislativeDisplay = document.getElementById('legislativeDisplay');
        if (!input) return;

        if (selectedSchoolId) {
            const school = getSchool(selectedSchoolId);
            if (school) {
                input.value = school.name;
                schoolIdHidden.value = school.id;
                searchSchools(school.name, function (results) {
                    const found = results.find(r => r.id == selectedSchoolId);
                    if (found) {
                        districtDisplay.value = found.district_name || '';
                        legislativeDisplay.value = found.legislative_district_name || '';
                    }
                });
            }
        }

        let debounceTimer;
        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            if (query.length < 2) { suggestions.style.display = 'none'; return; }
            debounceTimer = setTimeout(() => {
                searchSchools(query, function (results) {
                    suggestions.innerHTML = '';
                    if (results.length === 0) { suggestions.style.display = 'none'; return; }
                    results.forEach(school => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'list-group-item list-group-item-action';
                        item.textContent = school.school_name;
                        item.dataset.id = school.id;
                        item.dataset.district = school.district_name || '';
                        item.dataset.legislative = school.legislative_district_name || '';
                        item.addEventListener('click', function () {
                            input.value = school.school_name;
                            schoolIdHidden.value = school.id;
                            districtDisplay.value = school.district_name || '';
                            legislativeDisplay.value = school.legislative_district_name || '';
                            suggestions.style.display = 'none';
                        });
                        suggestions.appendChild(item);
                    });
                    suggestions.style.display = 'block';
                });
            }, 300);
        });
        input.addEventListener('blur', function () { setTimeout(() => { suggestions.style.display = 'none'; }, 200); });
        input.addEventListener('focus', function () {
            if (this.value.trim().length >= 2) {
                const items = suggestions.querySelectorAll('.list-group-item');
                if (items.length) suggestions.style.display = 'block';
            }
        });
    }

    function searchSchools(query, callback) {
        fetch(API_BASE + 'search_schools?q=' + encodeURIComponent(query), {
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrfToken() }
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => callback(Array.isArray(data) ? data : []))
        .catch(err => { console.error(err); callback([]); });
    }

    // --------------------------------------------------------------
    // EDIT PATIENT
    // --------------------------------------------------------------
    async function editPatient(id) {
        const patient = getPatient(id);
        if (!patient) { alert('Patient not found.'); return; }
        // Reuse the wizard, starting on step 0 for patient edit.
        openPatientModal(id, 'edit');
    }

    // --------------------------------------------------------------
    // DELETE PATIENT
    // --------------------------------------------------------------
    async function deletePatient(id) {
        if (!confirm('Delete this patient? This will also delete their follow-ups.')) return;
        try {
            await api('DELETE', 'delete_patient/' + id);
            await fetchPatientPage(patientPage);
        } catch (e) {
            alert('Error deleting patient: ' + e.message);
        }
    }

    // --------------------------------------------------------------
    // EVENT BINDINGS
    // --------------------------------------------------------------
    document.getElementById('addPatientBtn').addEventListener('click', () => openPatientModal(null, 'new'));

    // ----- Patient search -----
    const searchInput  = document.getElementById('patientSearchInput');
    const clearBtn     = document.getElementById('clearPatientSearch');

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const val = this.value;
            clearBtn?.classList.toggle('visible', val.length > 0);
            debounceTimer = setTimeout(() => {
                searchQuery = val;
                fetchPatientPage(1);
            }, 150);
        });

        // Press Escape to clear
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                this.value = '';
                searchQuery = '';
                clearBtn?.classList.remove('visible');
                fetchPatientPage(1);
            }
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            const input = document.getElementById('patientSearchInput');
            if (input) input.value = '';
            searchQuery = '';
            this.classList.remove('visible');
            fetchPatientPage(1);
            input?.focus();
        });
    }

    document.querySelectorAll('[data-role]').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            alert('Role switched to ' + this.dataset.role + ' (UI only)');
        });
    });

    document.getElementById('logoutBtn')?.addEventListener('click', function () {
        if (confirm('Sign out?')) window.location.href = LOGOUT_URL;
    });

    // Close patient modal first, then open the visit modal
    document.getElementById('viewPatientModal').addEventListener('click', function (e) {
        const btn = e.target.closest('.viewVisitBtn');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;

        const patientModalInstance = bootstrap.Modal.getInstance(this);
        if (patientModalInstance) patientModalInstance.hide();

        setTimeout(() => { viewVisit(id); }, 200);
    });

    // --------------------------------------------------------------
    // INIT
    // --------------------------------------------------------------
    fetchAll();

})();