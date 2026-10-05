// emr_view.js
(function () {
    'use strict';

    // ---- Config from PHP (set in emr_view.php before this file loads) ----
    const API_URLS   = (window.EMR_CONFIG && window.EMR_CONFIG.API_URLS)   || {};
    const LOGOUT_URL = (window.EMR_CONFIG && window.EMR_CONFIG.LOGOUT_URL) || '/';

    let schools = [];
    let currentImpressionFilter = null;
    let dashboardData = null;
    const patientById = new Map();
    const schoolById = new Map();
    let impressionPage = 1;

    // ---------- ESCAPE ----------
    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // ---------- CSRF ----------
    function getCsrfToken() {
        const m = document.cookie.match(/(?:^|;\s*)csrf_cookie_name=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    }

    // ---------- API ----------
    async function apiFetch(url, options = {}) {
        const method = String(options.method || 'GET').toUpperCase();
        const response = await fetch(url, {
            ...options,
            method,
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                ...(method === 'GET' || method === 'HEAD' ? {} : { 'Content-Type': 'application/json' }),
                ...(options.headers || {})
            },
            credentials: 'same-origin'
        });

        const contentType = response.headers.get('content-type') || '';
        if (response.status === 401) {
            window.location.href = LOGOUT_URL;
            throw new Error('Unauthorized');
        }

        if (!response.ok) {
            let message = `HTTP ${response.status}`;
            if (contentType.includes('application/json')) {
                const errData = await response.json().catch(() => ({}));
                message = errData.error || errData.message || message;
            } else {
                const allow = response.headers.get('Allow');
                if (allow) message += ` (Allow: ${allow})`;
            }
            throw new Error(`${url}: ${message}`);
        }

        if (!contentType.includes('application/json')) {
            throw new Error(`${url}: server returned non-JSON (status ${response.status}).`);
        }
        return response.json();
    }

    async function fetchData() {
        const errorBanner = document.getElementById('emrErrorBanner');
        const district = document.getElementById('filterDistrict')?.value || '';
        const school = document.getElementById('filterSchool')?.value || '';
        const year = document.getElementById('filterYear')?.value || '';
        const query = new URLSearchParams({ district, school, year }).toString();
        const requests = [];
        if (!schools.length) requests.push(['schools', API_URLS.schools]);
        requests.push(['dashboard', `${API_URLS.dashboard}?${query}`]);
        const results = await Promise.allSettled(requests.map(([, url]) => apiFetch(url)));
        const failures = [];
        results.forEach((result, index) => {
            const [name] = requests[index];
            if (result.status === 'rejected') {
                failures.push(`${name} (${requests[index][1]}): ${result.reason.message}`);
                return;
            }
            if (name === 'schools') schools = Array.isArray(result.value) ? result.value : [];
            if (name === 'dashboard') dashboardData = result.value;
        });
        if (errorBanner) {
            errorBanner.classList.toggle('d-none', failures.length === 0);
            errorBanner.textContent = failures.length ? `Dashboard data unavailable: ${failures.join('; ')}` : '';
        }
        if (!dashboardData) return;

        const dashboardVisits = [
            ...(dashboardData.latest_visits || []),
            ...(dashboardData.slideshow || []),
            ...((dashboardData.visits && dashboardData.visits.data) || [])
        ];
        patientById.clear();
        dashboardVisits.forEach(visit => {
            if (visit.patient_id != null) {
                patientById.set(String(visit.patient_id), {
                    id: visit.patient_id,
                    name: visit.patient_name,
                    school_id: visit.school_id
                });
            }
        });
        schoolById.clear();
        schools.forEach(schoolRow => schoolById.set(String(schoolRow.id), schoolRow));
        renderAll();
    }

    // ---------- HELPERS ----------
    function getPatient(id) { return patientById.get(String(id)); }
    function getSchool(id)  { return schoolById.get(String(id)); }
    function getSchoolName(id) { const s = getSchool(id); return s ? s.name : 'N/A'; }
    function impressionKey(visit) {
        if (visit && visit.impression_norm) return String(visit.impression_norm).trim().toUpperCase();
        return String((visit && visit.impression) || '').trim().toUpperCase();
    }
    function formatDate(d) {
        if (!d) return '—';
        return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

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

    // ---------- RENDER ----------
    function renderAll() {
        renderFilters();
        renderStats();
        renderDashboardVisits();
        renderImpressionSlideshow();
        renderVisits();
        renderImpressionChart();
        clearImpressionDetails();
    }

    function renderFilters() {
        const districtSet = new Set();
        schools.forEach(s => districtSet.add(s.district));
        const districtSelect = document.getElementById('filterDistrict');
        const currentDist = districtSelect.value;
        districtSelect.innerHTML = '<option value="">All Districts</option>';
        districtSet.forEach(d => {
            districtSelect.innerHTML += `<option value="${escapeHtml(d)}" ${d === currentDist ? 'selected' : ''}>${escapeHtml(d)}</option>`;
        });

        const schoolSelect = document.getElementById('filterSchool');
        const currentSchool = schoolSelect.value;
        let filteredSchools = schools;
        if (currentDist) filteredSchools = schools.filter(s => s.district === currentDist);
        schoolSelect.innerHTML = '<option value="">All Schools</option>';
        filteredSchools.forEach(s => {
            schoolSelect.innerHTML += `<option value="${escapeHtml(s.id)}" ${s.id == currentSchool ? 'selected' : ''}>${escapeHtml(s.name)}</option>`;
        });

        const yearSelect = document.getElementById('filterYear');
        const currentYear = yearSelect.value;
        const years = new Set();
        const baseYear = new Date().getFullYear();
        for (let offset = 0; offset < 10; offset++) years.add(String(baseYear - offset));
        yearSelect.innerHTML = '<option value="">All Years</option>';
        [...years].sort().reverse().forEach(y => {
            yearSelect.innerHTML += `<option value="${escapeHtml(y)}" ${y === currentYear ? 'selected' : ''}>${escapeHtml(y)}</option>`;
        });
    }

    function renderStats() {
        document.getElementById('statPatientsDoc').textContent = dashboardData?.patient_count || 0;
    }

    // ---------- RECENT VISITS: LATEST PER PATIENT ----------
    function renderDashboardVisits() {
        const filtered = dashboardData?.latest_visits || [];
        const tbody = document.getElementById('dashboardVisitsTableDoc');
        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center">No visits match filters</td></tr>`;
            return;
        }

        const latestByPatient = {};
        filtered.forEach(v => {
            const pid = v.patient_id;
            if (!latestByPatient[pid] || new Date(v.followup_date) > new Date(latestByPatient[pid].followup_date)) {
                latestByPatient[pid] = v;
            }
        });

        const latestVisits = Object.values(latestByPatient).sort(
            (a, b) => new Date(b.followup_date) - new Date(a.followup_date)
        );
        const displayVisits = latestVisits.slice(0, 10);

        tbody.innerHTML = displayVisits.map(v => {
            const p = getPatient(v.patient_id) || { name: v.patient_name, id: v.patient_id };
            const s = { name: v.school_name };
            const impRaw = v.impression ? v.impression.substring(0, 30) : '';
            const impTxt = impRaw + (v.impression && v.impression.length > 30 ? '…' : '');
            return `<tr>
                        <td>${escapeHtml(formatDate(v.followup_date))}</td>
                        <td>${escapeHtml(p ? p.name : 'Unknown')}</td>
                        <td>${escapeHtml(s ? s.name : 'N/A')}</td>
                        <td>${escapeHtml(impTxt)}</td>
                        <td>
                            <button class="btn btn-sm btn-info viewPatientBtn" data-id="${escapeHtml(p ? p.id : '')}" title="View Patient"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>`;
        }).join('');

    }

    function renderImpressionSlideshow() {
        const filtered = dashboardData?.slideshow || [];
        const inner = document.getElementById('impressionSlides');
        const counter = document.getElementById('slideCounter');

        if (!filtered.length) {
            inner.innerHTML = `<div class="carousel-item active"><div class="impression-slide" style="cursor:default;"><div class="text-center text-muted py-4">No impressions to display</div></div></div>`;
            counter.textContent = '0 / 0';
            return;
        }

        let slidesHtml = '';
        filtered.forEach((v, idx) => {
            const p = getPatient(v.patient_id) || { name: v.patient_name };
            const s = v.school_name ? { name: v.school_name } : null;
            const isActive = idx === 0 ? 'active' : '';
            slidesHtml += `
                <div class="carousel-item ${isActive}" data-visit-id="${escapeHtml(v.id)}">
                    <div class="impression-slide" data-visit-id="${escapeHtml(v.id)}">
                        <div class="slide-title">${escapeHtml(v.impression || 'No impression recorded')}</div>
                        <div class="slide-meta">
                            <span><strong>Patient:</strong> ${escapeHtml(p ? p.name : 'Unknown')}</span>
                            <span><strong>Date:</strong> ${escapeHtml(formatDate(v.followup_date))}</span>
                            ${s ? `<span><strong>School:</strong> ${escapeHtml(s.name)}</span>` : ''}
                        </div>
                        <div class="slide-content">
                            <div class="detail-row"><span class="detail-label">Doctor's Notes:</span> ${escapeHtml(v.doctor_notes || '—')}</div>
                        </div>
                        <div class="click-hint"><i class="fas fa-expand-alt"></i> Details</div>
                    </div>
                </div>
            `;
        });

        inner.innerHTML = slidesHtml;
        counter.textContent = `1 / ${filtered.length}`;

        const carousel = document.getElementById('impressionCarousel');
        // remove any previous handler bound to this element
        if (carousel._emrCounterHandler) {
            carousel.removeEventListener('slid.bs.carousel', carousel._emrCounterHandler);
        }
        carousel._emrCounterHandler = function (e) {
            counter.textContent = `${e.to + 1} / ${filtered.length}`;
        };
        carousel.addEventListener('slid.bs.carousel', carousel._emrCounterHandler);

        let bsCarousel = bootstrap.Carousel.getInstance(carousel);
        if (bsCarousel) bsCarousel.dispose();
        bsCarousel = new bootstrap.Carousel(carousel, { ride: 'carousel', interval: 5000, pause: 'hover' });
    }

    function renderVisits() {
        const filtered = dashboardData?.visits?.data || [];
        const tbody = document.getElementById('visitsTable');
        if (!filtered.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center">No visits</td></tr>`;
            return;
        }
        tbody.innerHTML = filtered.slice(0, 20).map(v => {
            const p = getPatient(v.patient_id) || { name: v.patient_name, id: v.patient_id };
            const impRaw = v.impression ? v.impression.substring(0, 30) : '';
            const impTxt = impRaw + (v.impression && v.impression.length > 30 ? '…' : '');
            return `<tr>
                        <td>${escapeHtml(formatDate(v.followup_date))}</td>
                        <td><strong>${escapeHtml(p ? p.name : 'Unknown')}</strong></td>
                        <td>${escapeHtml(impTxt)}</td>
                        <td>
                            <button class="btn btn-sm btn-info viewPatientBtn" data-id="${escapeHtml(p ? p.id : '')}" title="View Patient"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>`;
        }).join('');
    }

    // ---------- TOP IMPRESSIONS CHART ----------
    function renderImpressionChart() {
        const sorted = (dashboardData?.top_impressions || []).map(row => [impressionKey(row), Number(row.total)]);
        const ctx = document.getElementById('impressionChart').getContext('2d');
        if (window.impressionChartInstance) window.impressionChartInstance.destroy();

        if (currentImpressionFilter && !sorted.some(([label]) => label === currentImpressionFilter)) {
            clearImpressionDetails();
        }

        window.impressionChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sorted.map(d => d[0].length > 25 ? d[0].substring(0, 25) + '…' : d[0]),
                datasets: [{ label: 'Frequency', data: sorted.map(d => d[1]), backgroundColor: '#0d6efd' }]
            },
            options: {
                responsive: true,
                onClick: function (e, elements) {
                    if (elements.length > 0) {
                        const idx = elements[0].index;
                        impressionPage = 1;
                        showImpressionDetails(sorted[idx][0]);
                    }
                },
                onHover: function (event, elements) {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: (items) => sorted[items[0].dataIndex][0],
                            afterLabel: () => 'Click to view patients'
                        }
                    }
                },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // ---------- IMPRESSION DETAILS ----------
    function showImpressionDetails(impressionText) {
        currentImpressionFilter = impressionKey({ impression_norm: impressionText });
        const tbody = document.getElementById('impressionDetailsTable');
        const title = document.getElementById('impressionDetailsTitle');
        const subtitle = document.getElementById('impressionDetailsSubtitle');
        const detailsPagination = document.getElementById('impressionDetailsPagination');

        title.innerHTML = `<i class="fas fa-list-ul"></i> Impression Details: <span class="text-primary">${escapeHtml(impressionText)}</span>`;
        subtitle.textContent = 'Loading visits...';
        if (detailsPagination) detailsPagination.innerHTML = '';
        const params = new URLSearchParams({
            district: document.getElementById('filterDistrict').value,
            school: document.getElementById('filterSchool').value,
            year: document.getElementById('filterYear').value,
            impression: currentImpressionFilter,
            page: String(impressionPage),
            per_page: '20'
        });
        apiFetch(`${API_URLS.dashboard}?${params}`)
            .then(result => {
                const page = result.visits;
                const matchingVisits = (page.data || []).filter(visit => impressionKey(visit) === currentImpressionFilter);
                subtitle.textContent = `${page.total} visit${page.total !== 1 ? 's' : ''} found`;
                if (!matchingVisits.length) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No visits found</td></tr>`;
                    if (detailsPagination) detailsPagination.innerHTML = '';
                    return;
                }
                tbody.innerHTML = matchingVisits.map(v => {
            return `<tr>
                        <td>${escapeHtml(formatDate(v.followup_date))}</td>
                        <td><strong>${escapeHtml(v.patient_name || 'Unknown')}</strong></td>
                        <td>${escapeHtml(v.school_name || 'N/A')}</td>
                        <td>
                            <button class="btn btn-sm btn-info viewPatientBtn" data-id="${escapeHtml(v.patient_id)}" title="View Patient"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>`;
                }).join('');
                const pagination = document.getElementById('impressionDetailsPagination');
                if (pagination) pagination.innerHTML = `
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-impression-page="${Math.max(1, page.page - 1)}" ${page.page <= 1 ? 'disabled' : ''}>Previous</button>
                    <span class="mx-2">Page ${page.page} of ${Math.max(1, Math.ceil(page.total / page.per_page))}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-impression-page="${Math.min(Math.ceil(page.total / page.per_page), page.page + 1)}" ${page.page * page.per_page >= page.total ? 'disabled' : ''}>Next</button>`;
            })
            .catch(error => {
                subtitle.textContent = `Could not load ${API_URLS.dashboard}: ${error.message}`;
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Unable to load impression visits</td></tr>`;
            });
    }

    function clearImpressionDetails() {
        currentImpressionFilter = null;
        const title = document.getElementById('impressionDetailsTitle');
        const subtitle = document.getElementById('impressionDetailsSubtitle');
        const tbody = document.getElementById('impressionDetailsTable');
        const pagination = document.getElementById('impressionDetailsPagination');
        if (!title) return;
        title.innerHTML = `<i class="fas fa-list-ul"></i> Impression Details`;
        subtitle.textContent = 'Click a bar in the chart';
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No selection yet</td></tr>`;
        if (pagination) pagination.innerHTML = '';
    }

    // ---------- VIEW PATIENT ----------
    async function viewPatient(id) {
        let patient;
        let patientVisits;
        try {
            [patient, patientVisits] = await Promise.all([
                apiFetch(API_URLS.patient + encodeURIComponent(id)),
                apiFetch(`${API_URLS.visits}?patient_id=${encodeURIComponent(id)}`)
            ]);
        } catch (error) {
            alert(`Unable to load patient ${id}: ${error.message}`);
            return;
        }
        patientById.set(String(patient.id), patient);

        let visitsHtml = '';
        if (patientVisits.length) {
            visitsHtml = patientVisits.map(v => `
                <div class="timeline-item">
                    <div class="timeline-date">${escapeHtml(formatDate(v.followup_date))}</div>
                    <div class="timeline-content">
                        <strong>Impression:</strong> ${escapeHtml((v.impression || '—').toUpperCase())}<br>
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

    // ---------- VIEW VISIT ----------
    async function viewVisit(id) {
        let found;
        try {
            found = await apiFetch(API_URLS.visit + '/' + encodeURIComponent(id));
        } catch (error) {
            alert(`Unable to load visit ${id}: ${error.message}`);
            return;
        }
        let p;
        try {
            p = await apiFetch(API_URLS.patient + encodeURIComponent(found.patient_id));
            patientById.set(String(p.id), p);
        } catch (error) {
            alert(`Unable to load patient ${found.patient_id}: ${error.message}`);
            return;
        }
        const s = getSchool(p.school_id) || { name: found.school_name, district: found.district };
        const lab = normalizeLab(found.lab);
        const prescription = normalizePrescription(found.prescription);

        // --- Simplified: Parameter | Result (no Ref. Range) ---
        function renderLabCategory(data) {
            if (!data) return '<p class="text-muted">No data</p>';
            if (Array.isArray(data) && data.length) {
                let html = `<table class="table table-bordered"><thead><tr><th>Parameter</th><th>Result</th></tr></thead><tbody>`;
                data.forEach(row => {
                    html += `<tr><td>${escapeHtml((row.parameter || '—').toUpperCase())}</td><td>${escapeHtml((row.result || '—').toUpperCase())}</td></tr>`;
                });
                html += '</tbody></table>';
                return html;
            } else if (typeof data === 'string') {
                return `<p><strong>Result:</strong> ${escapeHtml(data.toUpperCase())}</p>`;
            }
            return '<p class="text-muted">No data recorded.</p>';
        }

        // --- Simplified: Analysis | Result (no Method / Range) ---
        function renderChemistryAnalyses(analyses) {
            if (!analyses || !analyses.length) return '<p class="text-muted">No data</p>';
            let html = `<table class="table table-bordered"><thead><tr><th>Analysis</th><th>Result</th></tr></thead><tbody>`;
            analyses.forEach(row => {
                html += `<tr><td>${escapeHtml((row.name || '—').toUpperCase())}</td><td>${escapeHtml((row.result || '—').toUpperCase())}</td></tr>`;
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
            if (lab.blood_chemistry && Array.isArray(lab.blood_chemistry.analyses) && lab.blood_chemistry.analyses.length) {
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
                <dt class="col-sm-3">Gender</dt><dd class="col-sm-9">${escapeHtml(p ? (p.gender || '—') : '—')}</dd>
                <dt class="col-sm-3">AGE</dt><dd class="col-sm-9">${escapeHtml(p ? (p.age ?? '—') : '—')}</dd>
                <dt class="col-sm-3">School</dt><dd class="col-sm-9">${escapeHtml(s ? s.name + ' (' + s.district + ')' : 'N/A')}</dd>
                <dt class="col-sm-3">Date</dt><dd class="col-sm-9">${escapeHtml(formatDate(found.followup_date))}</dd>
                <dt class="col-sm-3">Impression / Diagnoses</dt><dd class="col-sm-9">${escapeHtml(mergedImpression(found.impression, found.diagnoses) || '—')}</dd>
                <dt class="col-sm-3">Maintenance</dt><dd class="col-sm-9">${escapeHtml((found.maintenance || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Doctor's Notes</dt><dd class="col-sm-9">${escapeHtml((found.doctor_notes || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">BP</dt><dd class="col-sm-9">${escapeHtml(found.bp ?? '—')}</dd>
                <dt class="col-sm-3">HR</dt><dd class="col-sm-9">${escapeHtml(found.hr ?? '—')} bpm</dd>
                <dt class="col-sm-3">SpO₂</dt><dd class="col-sm-9">${escapeHtml(found.spo2 ?? '—')}%</dd>
                <dt class="col-sm-3">RR</dt><dd class="col-sm-9">${escapeHtml(found.rr ?? '—')} /min</dd>
                <dt class="col-sm-3">Temperature</dt><dd class="col-sm-9">${escapeHtml(found.temp ?? '—')} °C</dd>
                <dt class="col-sm-3">Vision</dt><dd class="col-sm-9">${escapeHtml((found.vision_left || '—').toUpperCase())} / ${escapeHtml((found.vision_right || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Hearing</dt><dd class="col-sm-9">${escapeHtml((found.hearing_left || '—').toUpperCase())} / ${escapeHtml((found.hearing_right || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Dental</dt><dd class="col-sm-9">${escapeHtml((found.dental_findings || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">X-ray Results</dt><dd class="col-sm-9">${escapeHtml(found.xray_results || '—')}</dd>
                <dt class="col-sm-3">Immunizations</dt><dd class="col-sm-9">${escapeHtml((found.immunizations || '—').toUpperCase())}</dd>
                <dt class="col-sm-3">Referral</dt><dd class="col-sm-9">${escapeHtml((found.referral_status || '—').toUpperCase())} ${found.referral_notes ? '(' + escapeHtml(found.referral_notes.toUpperCase()) + ')' : ''}</dd>
            </dl>
            <hr>
            <h6>Prescription</h6>
            ${prescriptionHtml}
            <hr>
            <h6>Lab Results</h6>
            ${labHtml || '<p class="text-muted">No lab data recorded.</p>'}
        `;
        document.getElementById('viewBody').innerHTML = body;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('viewModal')).show();
    }

    function exportReport(type) { alert(`Exporting ${type} report... (Simulated)`); }

    // ---------- NAVIGATION ----------
    function navigateTo(section) {
        document.querySelectorAll('.page-section').forEach(el => el.classList.remove('active'));
        document.getElementById('section-' + section)?.classList.add('active');
        document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
        const link = document.querySelector(`.nav-link[data-section="${section}"]`);
        if (link) link.classList.add('active');
        if (section === 'dashboard') setTimeout(() => { renderImpressionSlideshow(); renderImpressionChart(); }, 100);
    }
    document.querySelectorAll('.nav-link[data-section]').forEach(el => {
        el.addEventListener('click', function (e) { e.preventDefault(); navigateTo(this.dataset.section); });
    });

    // ---------- EVENT BINDINGS ----------
    document.getElementById('addVisitBtn')?.addEventListener('click', () => alert('Use the Patients & Follow-ups page to create visits.'));
    document.getElementById('exportReportsBtn')?.addEventListener('click', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('exportModal')).show());
    document.getElementById('exportSummaryBtn')?.addEventListener('click', () => exportReport('School Health Summary'));
    document.getElementById('exportAbnormalBtn')?.addEventListener('click', () => exportReport('Abnormal Findings'));

    document.getElementById('filterDistrict')?.addEventListener('change', function () {
        renderFilters(); fetchData();
    });
    document.getElementById('filterSchool')?.addEventListener('change', function () {
        fetchData();
    });
    document.getElementById('filterYear')?.addEventListener('change', function () {
        fetchData();
    });
    document.getElementById('resetFilters')?.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('filterDistrict').value = '';
        document.getElementById('filterSchool').value = '';
        document.getElementById('filterYear').value = '';
        fetchData();
    });

    document.addEventListener('click', function (e) {
        const pageButton = e.target.closest('[data-impression-page]');
        if (pageButton && currentImpressionFilter) {
            impressionPage = Number(pageButton.dataset.impressionPage);
            showImpressionDetails(currentImpressionFilter);
            return;
        }
        const patientButton = e.target.closest('.viewPatientBtn');
        if (patientButton) {
            viewPatient(patientButton.dataset.id);
            return;
        }
        const slide = e.target.closest('.impression-slide[data-visit-id]');
        if (slide && slide.dataset.visitId) viewVisit(slide.dataset.visitId);
    });

    document.getElementById('viewPatientModal').addEventListener('click', function (e) {
        const btn = e.target.closest('.viewVisitBtn');
        if (!btn) return;
        const id = btn.dataset.id;
        if (!id) return;
        const inst = bootstrap.Modal.getInstance(this);
        if (inst) inst.hide();
        setTimeout(() => viewVisit(id), 200);
    });

    document.getElementById('logoutBtn')?.addEventListener('click', function () {
        if (confirm('Sign out?')) window.location.href = LOGOUT_URL;
    });

    // ---------- INIT ----------
    fetchData();
})();