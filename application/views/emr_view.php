<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DepEd EMR · Health & Nutrition</title>
    <!-- Bootstrap 5 + Font Awesome + Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* ===== LAYOUT & SIDEBAR ===== */
        body {
            display: flex;
            min-height: 100vh;
            background: #f4f6f9;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid #dee2e6;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
        }
        .sidebar .logo {
            font-size: 22px;
            font-weight: 700;
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 16px;
        }
        .sidebar .logo i {
            color: #0d6efd;
            margin-right: 10px;
        }
        .nav-link {
            border-radius: 0;
            color: #495057;
            font-weight: 500;
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            background: #e9ecef;
            color: #0d6efd;
            border-left-color: #0d6efd;
        }
        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }
        .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid #dee2e6;
            font-size: 14px;
            color: #6c757d;
        }
        .sidebar-footer .user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-footer .avatar {
            width: 40px;
            height: 40px;
            background: #0d6efd;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .main-content {
            flex: 1;
            padding: 24px 32px;
            overflow-y: auto;
            height: 100vh;
        }
        .page-section {
            display: none;
        }
        .page-section.active {
            display: block;
        }

        /* ===== CARDS & STATS ===== */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
        }
        .stat-card .icon {
            font-size: 28px;
            color: #0d6efd;
            opacity: 0.7;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        .badge-active { background: #d1e7dd; color: #0a3622; }
        .badge-inactive { background: #f8d7da; color: #58151c; }
        .badge-underweight { background: #ffe5d9; color: #7a2e0a; }
        .badge-normal { background: #d1e7dd; color: #0a3622; }
        .badge-overweight { background: #fff3cd; color: #664d03; }
        .badge-obese { background: #f8d7da; color: #58151c; }

        .lab-group {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .lab-group .row {
            margin-bottom: 10px;
        }

        .role-badge {
            background: #e9ecef;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .role-doctor { background: #cfe2ff; color: #0d6efd; }
        .role-user { background: #d1e7dd; color: #0a3622; }

        /* ===== IMPRESSION SLIDESHOW ===== */
        .impression-slide {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            min-height: 280px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e9ecef;
            cursor: pointer;
            position: relative;
        }
        .impression-slide:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            border-color: #000;
        }
        .impression-slide .slide-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #0d6efd;
        }
        .impression-slide .slide-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 16px;
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        .impression-slide .slide-meta strong { color: #212529; }
        .impression-slide .slide-content {
            font-size: 14px;
            line-height: 1.6;
        }
        .impression-slide .slide-content .detail-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            min-width: 100px;
        }
        .click-hint {
            position: absolute;
            bottom: 12px;
            right: 16px;
            font-size: 12px;
            color: #000;
            opacity: 0.7;
            font-weight: 500;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: #000 !important;
            border-radius: 50%;
            padding: 20px;
            background-size: 50%;
        }
        .carousel-indicators [data-bs-target] {
            background-color: #000;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: none;
        }
        .carousel-indicators .active { background-color: #000; opacity: 1; }
        .carousel-indicators { position: relative; margin-top: 12px; margin-bottom: 0; }
        .slide-counter {
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            margin-top: 8px;
        }

        /* ===== PATIENT PROFILE TIMELINE (still used for visit details) ===== */
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid #fff;
        }
        .timeline-item .timeline-date {
            font-size: 12px;
            color: #6c757d;
        }
        .timeline-item .timeline-content {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar { width: 72px; padding: 12px 0; }
            .sidebar .logo span, .sidebar .nav-link span, .sidebar-footer .user .info { display: none; }
            .sidebar .logo i { font-size: 26px; }
            .nav-link { padding: 12px 0; text-align: center; justify-content: center; }
            .nav-link i { margin: 0; }
            .main-content { padding: 16px; }
        }
    </style>
</head>
<body>

    <!-- ======== SIDEBAR ======== -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-heartbeat"></i>
            <span>EMR·Pro</span>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" data-section="dashboard" href="#"><i class="fas fa-th-large"></i><span> Dashboard</span></a>
            <a class="nav-link" data-section="visits" href="#"><i class="fas fa-notes-medical"></i><span> Visits</span></a>
            <a class="nav-link" data-section="announcements" href="#"><i class="fas fa-bullhorn"></i><span> Announcements</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="user">
                <div class="avatar">JD</div>
                <div class="info">
                    <div style="font-weight:600;">Dr. Jane Doe</div>
                    <div style="font-size:12px;" id="roleDisplay">Doctor</div>
                </div>
            </div>
            <button class="btn btn-link text-danger p-0 mt-2" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
        </div>
    </div>

    <!-- ======== MAIN CONTENT ======== -->
    <div class="main-content">

        <!-- ===== HEADER ===== -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 id="pageTitle">Dashboard</h1>
            <div class="d-flex align-items-center gap-3">
                <span class="role-badge" id="roleBadge">👨‍⚕️ Doctor</span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-shield"></i> Role
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" data-role="doctor" href="#"><i class="fas fa-user-md"></i> Doctor</a></li>
                        <li><a class="dropdown-item" data-role="user" href="#"><i class="fas fa-user-nurse"></i> Nurse</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ========================================================== -->
        <!-- ======================= DASHBOARD ========================= -->
        <!-- ========================================================== -->
        <section id="section-dashboard" class="page-section active">

            <!-- DOCTOR DASHBOARD -->
            <div id="doctorDashboard">
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">District</label>
                                <select id="filterDistrict" class="form-select"><option value="">All Districts</option></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">School</label>
                                <select id="filterSchool" class="form-select"><option value="">All Schools</option></select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Year</label>
                                <select id="filterYear" class="form-select"><option value="">All Years</option></select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="reset" class="btn btn-secondary" id="resetFilters">Reset</button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-success" id="exportReportsBtn"><i class="fas fa-file-export"></i> Export</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Stats Row (expanded) -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="stat-card"><div class="icon float-end"><i class="fas fa-users"></i></div><div class="label text-muted">Total Patients</div><div class="value" id="statPatientsDoc">0</div></div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card"><div class="icon float-end"><i class="fas fa-prescription"></i></div><div class="label text-muted">Active Maintenance</div><div class="value" id="statMaintenanceDoc">0</div></div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card"><div class="icon float-end"><i class="fas fa-flask"></i></div><div class="label text-muted">Labs Pending</div><div class="value" id="statLabsDoc">0</div></div>
                    </div>
                </div>

                <!-- Analytics Widgets Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar"></i> Top Diagnoses</h5></div>
                            <div class="card-body">
                                <canvas id="diagnosisChart" height="150"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="fas fa-bell"></i> Alerts & Follow-ups</h5></div>
                            <div class="card-body" id="alertsList">
                                <p class="text-muted">No alerts</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Visits & Impressions -->
                <div class="row">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h5 class="mb-0">Recent Visits</h5></div>
                            <div class="card-body table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead><tr><th>Date</th><th>Patient</th><th>School</th><th>Impression</th><th>Actions</th></tr></thead>
                                    <tbody id="dashboardVisitsTableDoc"><tr><td colspan="5" class="text-center">No visits</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card h-100">
                            <div class="card-header"><h5 class="mb-0"><i class="fas fa-comment-medical"></i> Impressions Slideshow</h5></div>
                            <div class="card-body">
                                <div id="impressionCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
                                    <div class="carousel-inner" id="impressionSlides">
                                        <div class="carousel-item active">
                                            <div class="impression-slide" style="cursor:default;">
                                                <div class="text-center text-muted">No impressions</div>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#impressionCarousel" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#impressionCarousel" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="slide-counter" id="slideCounter">0 / 0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NURSE DASHBOARD (simplified) -->
            <div id="userDashboard" style="display:none;">
                <div class="alert alert-info">You are viewing data for <strong id="userSchoolName">your school</strong>.</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-3"><div class="stat-card"><div class="icon float-end"><i class="fas fa-users"></i></div><div class="label text-muted">Patients</div><div class="value" id="statPatientsUser">0</div></div></div>
                    <div class="col-md-3"><div class="stat-card"><div class="icon float-end"><i class="fas fa-calendar-check"></i></div><div class="label text-muted">Visits</div><div class="value" id="statVisitsUser">0</div></div></div>
                    <div class="col-md-3"><div class="stat-card"><div class="icon float-end"><i class="fas fa-prescription"></i></div><div class="label text-muted">Active Maintenance</div><div class="value" id="statMaintenanceUser">0</div></div></div>
                    <div class="col-md-3"><div class="stat-card"><div class="icon float-end"><i class="fas fa-flask"></i></div><div class="label text-muted">Labs Pending</div><div class="value" id="statLabsUser">0</div></div></div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Visits</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead><tr><th>Date</th><th>Patient</th><th>Impression</th><th>Actions</th></tr></thead>
                            <tbody id="dashboardVisitsTableUser"><tr><td colspan="4" class="text-center">No visits</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================== -->
        <!-- ======================= VISITS =========================== -->
        <!-- ========================================================== -->
        <section id="section-visits" class="page-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Visits</h1>
                <button class="btn btn-primary" id="addVisitBtn"><i class="fas fa-plus"></i> New Visit</button>
            </div>
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead><tr><th>Date</th><th>Patient</th><th>Impression</th><th>Diagnoses</th><th>Actions</th></tr></thead>
                        <tbody id="visitsTable"><tr><td colspan="5" class="text-center">No visits</td></tr></tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ========================================================== -->
        <!-- =================== ANNOUNCEMENTS ======================== -->
        <!-- ========================================================== -->
        <section id="section-announcements" class="page-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Announcements</h1>
                <button class="btn btn-primary" id="addAnnouncementBtn"><i class="fas fa-plus"></i> New Announcement</button>
            </div>
            <div class="row" id="announcementsList">
                <div class="col-12 text-muted text-center">No announcements</div>
            </div>
        </section>

    </div> <!-- /main-content -->

    <!-- ========================================================== -->
    <!-- ======================== MODALS ============================ -->
    <!-- ========================================================== -->

    <!-- Generic Modal -->
    <div class="modal fade" id="emrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="modalSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Visit Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Reports Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Export Reports</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" id="exportSummaryBtn">📊 School Health Summary (PDF)</button>
                        <button class="btn btn-outline-danger" id="exportAbnormalBtn">⚠️ Abnormal Findings (CSV)</button>
                    </div>
                    <p class="text-muted mt-3 small">(Simulated – data is stored locally)</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ========================================================== -->
    <!-- ======================== APP JS ============================ -->
    <!-- ========================================================== -->
    <script>
        (function() {
            'use strict';

            // ---------- PROFILE ----------
            let profile = { role: 'doctor', school_id: null };

            // ---------- DATA ----------
            let patients = [];
            let visits = [];
            let schools = [];
            let announcements = [];

            // ---------- MOCK DATA GENERATION ----------
            function generateMockData() {
                const firstNames = ['Emily', 'Michael', 'Sarah', 'David', 'Jessica', 'Daniel', 'Ashley', 'Matthew', 'Emma', 'Christopher', 'Olivia', 'Andrew', 'Sophia', 'Joshua', 'Ava', 'James', 'Mia', 'Benjamin', 'Charlotte', 'Ethan', 'Amelia', 'Alexander', 'Ella', 'Jacob', 'Abigail'];
                const lastNames = ['Johnson', 'Chen', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez'];
                const districts = ['District 1', 'District 2', 'District 3', 'District 4', 'District 5'];
                const schoolNames = ['Central High School', 'Eastside Elementary', 'Westview Middle School', 'Northstar Academy', 'Southridge High School', 'Greenwood Elementary', 'Pinecrest Middle School', 'Lakeside Academy', 'Hilltop High School', 'Meadowbrook Elementary'];
                const grades = ['Kindergarten', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'];
                const diagnosesList = ['None', 'Tension headache', 'Acute pharyngitis', 'Asthma exacerbation', 'Allergic rhinitis', 'Iron deficiency anemia', 'Conjunctivitis', 'Otitis media', 'Gastroenteritis', 'Urinary tract infection', 'Dengue', 'COVID-19', 'Influenza', 'Pneumonia', 'Skin infection', 'Malnutrition', 'Obesity'];
                const medications = ['Amoxicillin 500mg', 'Paracetamol 500mg', 'Cetirizine 10mg', 'Ibuprofen 400mg', 'Salbutamol inhaler', 'Ferrous sulfate 200mg', 'Mefenamic acid 500mg', 'Azithromycin 500mg', 'Clarithromycin 500mg', 'Omeprazole 20mg'];

                const schoolList = [];
                for (let i = 0; i < 8; i++) {
                    const d = districts[i % districts.length];
                    const s = {
                        id: i + 1,
                        name: schoolNames[i % schoolNames.length],
                        district: d
                    };
                    if (!schoolList.some(ex => ex.name === s.name && ex.district === s.district)) {
                        schoolList.push(s);
                    } else {
                        s.name += ' ' + (i + 1);
                        schoolList.push(s);
                    }
                }

                const patientList = [];
                const visitList = [];
                const announcementList = [];

                // Generate patients (30 patients) – we keep them internally for visits
                for (let i = 0; i < 30; i++) {
                    const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
                    const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
                    const name = firstName + ' ' + lastName;
                    const school = schoolList[Math.floor(Math.random() * schoolList.length)];
                    const dob = new Date(Date.now() - Math.floor(Math.random() * 20 + 5) * 365 * 24 * 60 * 60 * 1000);
                    const dobStr = dob.toISOString().slice(0, 10);
                    const gender = ['Male', 'Female', 'Other'][Math.floor(Math.random() * 3)];
                    const phone = '555-' + String(Math.floor(100 + Math.random() * 900)) + '-' + String(Math.floor(1000 + Math
                    .random() * 9000));
                    const grade = grades[Math.floor(Math.random() * grades.length)];
                    const guardianName = firstNames[Math.floor(Math.random() * firstNames.length)] + ' ' + lastNames[Math.floor(
                        Math.random() * lastNames.length)];
                    const guardianPhone = '555-' + String(Math.floor(100 + Math.random() * 900)) + '-' + String(Math.floor(1000 +
                        Math.random() * 9000));
                    const consent = Math.random() > 0.2 ? 'Yes' : 'No';
                    const status = Math.random() > 0.15 ? 'active' : 'inactive';
                    const lrn = '2025-' + String(i + 1).padStart(4, '0');

                    const patient = {
                        id: 'p' + (i + 1),
                        name,
                        lrn,
                        dob: dobStr,
                        gender,
                        phone,
                        address: Math.floor(100 + Math.random() * 900) + ' ' + ['Main St', 'Oak Ave', 'Pine Rd', 'Elm St',
                            'Maple Dr', 'Cedar Ln', 'Birch Blvd', 'Spruce Way'
                        ][Math.floor(Math.random() * 8)],
                        school_id: school.id,
                        grade,
                        status,
                        guardian_name: guardianName,
                        guardian_phone: guardianPhone,
                        consent
                    };
                    patientList.push(patient);
                }

                // Generate visits (each patient has 1-3 visits)
                const labTypes = [
                    { cbc: 'WBC 7.5, RBC 4.8, Hgb 14.2', urinalysis: 'pH 6.5, protein trace', fbs_hba1c: 'FBS 95 mg/dL',
                        lipid_panel: 'Chol 190, HDL 52, LDL 110', bua: '4.2 mg/dL', bun: '12 mg/dL',
                        creatinine: '0.9 mg/dL', sgpt_sgot: 'SGPT 22, SGOT 25', others: '' },
                    { cbc: 'WBC 6.0, RBC 5.0, Hgb 15.1', urinalysis: 'Clear, negative', fbs_hba1c: 'FBS 88 mg/dL',
                        lipid_panel: 'Chol 175, HDL 58, LDL 100', bua: '3.8 mg/dL', bun: '10 mg/dL',
                        creatinine: '0.8 mg/dL', sgpt_sgot: 'SGPT 18, SGOT 20', others: '' },
                    { cbc: 'WBC 11.2, RBC 4.6, Hgb 13.8', urinalysis: 'pH 6.0, trace ketones', fbs_hba1c: 'FBS 102 mg/dL',
                        lipid_panel: 'Chol 195, HDL 48, LDL 120', bua: '5.0 mg/dL', bun: '14 mg/dL',
                        creatinine: '1.0 mg/dL', sgpt_sgot: 'SGPT 30, SGOT 28', others: '' },
                    { cbc: 'WBC 8.0, RBC 4.9, Hgb 14.5', urinalysis: 'Normal', fbs_hba1c: 'FBS 90 mg/dL',
                        lipid_panel: 'Chol 180, HDL 55, LDL 105', bua: '4.0 mg/dL', bun: '11 mg/dL',
                        creatinine: '0.9 mg/dL', sgpt_sgot: 'SGPT 20, SGOT 22', others: '' },
                    { cbc: 'WBC 9.5, RBC 5.2, Hgb 15.0', urinalysis: 'pH 7.0, negative', fbs_hba1c: 'FBS 110 mg/dL',
                        lipid_panel: 'Chol 210, HDL 45, LDL 140', bua: '6.0 mg/dL', bun: '16 mg/dL',
                        creatinine: '1.1 mg/dL', sgpt_sgot: 'SGPT 35, SGOT 30', others: '' }
                ];

                let visitCounter = 1;
                patientList.forEach(p => {
                    const numVisits = Math.floor(Math.random() * 3) + 1;
                    for (let i = 0; i < numVisits; i++) {
                        const visitDate = new Date(2026, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) +
                        1);
                        const dateStr = visitDate.toISOString().slice(0, 10);
                        const diag = diagnosesList[Math.floor(Math.random() * diagnosesList.length)];
                        const maintenance = diag === 'None' ? 'None' : medications[Math.floor(Math.random() * medications
                            .length)];
                        const impression = diag === 'None' ? 'Routine check-up' : (diag + ', ' + (Math.random() > 0.5 ?
                            'mild' : 'moderate') + ' symptoms');
                        const doctorNotes = diag === 'None' ? 'Healthy' : 'Follow-up in ' + (Math.floor(Math.random() *
                            4) + 1) + ' weeks';
                        const height = Math.floor(120 + Math.random() * 60);
                        const weight = Math.floor(20 + Math.random() * 40);
                        const bmi = weight / ((height / 100) * (height / 100));
                        const bmiVal = Math.round(bmi * 10) / 10;
                        let bmiCat = 'Normal';
                        if (bmiVal < 18.5) bmiCat = 'Underweight';
                        else if (bmiVal >= 25 && bmiVal < 30) bmiCat = 'Overweight';
                        else if (bmiVal >= 30) bmiCat = 'Obese';

                        const visionL = Math.random() > 0.2 ? '20/20' : '20/25';
                        const visionR = Math.random() > 0.2 ? '20/20' : '20/25';
                        const hearingL = Math.random() > 0.1 ? 'Pass' : 'Fail';
                        const hearingR = Math.random() > 0.1 ? 'Pass' : 'Fail';
                        const dental = ['No cavities', 'Mild plaque', 'Cavity #' + Math.floor(Math.random() * 4 + 1)][
                            Math.floor(Math.random() * 3)
                        ];
                        const immunizations = Math.random() > 0.2 ? 'Up-to-date' : 'Pending - ' + ['BCG', 'DTP', 'HepB',
                            'MMR'
                        ][Math.floor(Math.random() * 4)];
                        const referralStatus = ['', 'Pending', 'Completed'][Math.floor(Math.random() * 3)];
                        const referralNotes = referralStatus ? ('Refer to ' + ['dentist', 'pulmonologist', 'pediatrician',
                            'ENT'
                        ][Math.floor(Math.random() * 4)]) : '';

                        let lab = null;
                        if (Math.random() > 0.3) {
                            const labData = labTypes[Math.floor(Math.random() * labTypes.length)];
                            lab = { ...labData };
                            lab.cbc = 'WBC ' + (Math.floor(5 + Math.random() * 8)) + ', RBC ' + (Math.floor(4 + Math.random() *
                                1)) + '.0, Hgb ' + (Math.floor(12 + Math.random() * 4));
                            lab.fbs_hba1c = 'FBS ' + (Math.floor(80 + Math.random() * 40)) + ' mg/dL';
                            lab.lipid_panel = 'Chol ' + (Math.floor(150 + Math.random() * 80)) + ', HDL ' + (Math.floor(40 +
                                Math.random() * 20)) + ', LDL ' + (Math.floor(80 + Math.random() * 60));
                            lab.bua = (Math.floor(3 + Math.random() * 4)) + '.' + (Math.floor(Math.random() * 9)) +
                            ' mg/dL';
                            lab.bun = (Math.floor(8 + Math.random() * 8)) + ' mg/dL';
                            lab.creatinine = (Math.floor(0.6 + Math.random() * 0.6)).toFixed(1) + ' mg/dL';
                            lab.sgpt_sgot = 'SGPT ' + (Math.floor(15 + Math.random() * 25)) + ', SGOT ' + (Math.floor(15 +
                                Math.random() * 25));
                            lab.status = Math.random() > 0.7 ? 'completed' : 'pending';
                        }

                        const visit = {
                            id: 'v' + visitCounter++,
                            patient_id: p.id,
                            visit_date: dateStr,
                            impression: impression,
                            diagnoses: diag,
                            maintenance: maintenance,
                            doctor_notes: doctorNotes,
                            height: height,
                            weight: weight,
                            bmi: bmiVal,
                            bmi_category: bmiCat,
                            vision_left: visionL,
                            vision_right: visionR,
                            hearing_left: hearingL,
                            hearing_right: hearingR,
                            dental_findings: dental,
                            immunizations: immunizations,
                            referral_notes: referralNotes,
                            referral_status: referralStatus,
                            lab: lab
                        };
                        visitList.push(visit);
                    }
                });

                // Generate announcements
                const announcementTitles = ['Nutrition Month Celebration', 'Dental Check-up Schedule', 'Vaccination Drive',
                    'School Feeding Program Launch', 'Health Awareness Seminar', 'Deworming Day', 'Vision Screening',
                    'Mental Health Webinar'
                ];
                const announcementContents = [
                    'We will have a feeding program and nutrition education on July 20.',
                    'Dentist will visit on August 25 for all students.',
                    'Free vaccines for all students on September 10.',
                    'New feeding program starts this month for malnourished students.',
                    'Join our health awareness seminar on hygiene and nutrition.',
                    'Deworming tablets will be distributed next week.',
                    'Vision screening for all grades on October 5.',
                    'Mental health webinar for teachers and students on November 3.'
                ];
                for (let i = 0; i < 5; i++) {
                    const idx = Math.floor(Math.random() * announcementTitles.length);
                    const date = new Date(2026, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1);
                    announcementList.push({
                        id: 'a' + (i + 1),
                        title: announcementTitles[idx % announcementTitles.length] + (i > 0 ? ' ' + (i + 1) : ''),
                        content: announcementContents[idx % announcementContents.length],
                        date: date.toISOString().slice(0, 10)
                    });
                }

                return { schools: schoolList, patients: patientList, visits: visitList, announcements: announcementList };
            }

            // ---------- STORAGE ----------
            function loadData() {
                let stored = localStorage.getItem('emr_ui_data');
                if (stored) {
                    try {
                        const data = JSON.parse(stored);
                        schools = data.schools || [];
                        patients = data.patients || [];
                        visits = data.visits || [];
                        announcements = data.announcements || [];
                        visits.forEach(v => { if (!v.lab) v.lab = null; });
                        return;
                    } catch (e) { /* fallback */ }
                }
                const mock = generateMockData();
                schools = mock.schools;
                patients = mock.patients;
                visits = mock.visits;
                announcements = mock.announcements;
                saveData();
            }

            function saveData() {
                localStorage.setItem('emr_ui_data', JSON.stringify({ schools, patients, visits, announcements }));
            }

            // ---------- PROFILE ----------
            function loadProfile() {
                const stored = localStorage.getItem('emr_profile');
                if (stored) {
                    try {
                        const p = JSON.parse(stored);
                        profile.role = p.role || 'doctor';
                        profile.school_id = p.school_id || null;
                        if (profile.role === 'user' && !profile.school_id && schools.length) {
                            profile.school_id = schools[0].id;
                        }
                        return;
                    } catch (e) {}
                }
                profile.role = 'doctor';
                profile.school_id = null;
                saveProfile();
            }

            function saveProfile() {
                localStorage.setItem('emr_profile', JSON.stringify({ role: profile.role, school_id: profile.school_id }));
            }

            // ---------- HELPERS ----------
            function getPatient(id) { return patients.find(p => p.id === id); }
            function getSchool(id) { return schools.find(s => s.id === id); }
            function getPatientName(id) { const p = getPatient(id); return p ? p.name : 'Unknown'; }
            function getSchoolName(id) { const s = getSchool(id); return s ? s.name : 'N/A'; }
            function generateId() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }
            function formatDate(d) { if (!d) return '—'; return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }); }
            function getStatusBadge(status) {
                const map = {
                    'active': 'badge-active',
                    'inactive': 'badge-inactive',
                    'scheduled': 'badge-scheduled',
                    'confirmed': 'badge-confirmed',
                    'pending': 'badge-pending',
                    'completed': 'badge-completed',
                    'cancelled': 'badge-cancelled'
                };
                const cls = map[status] || 'badge-pending';
                return `<span class="badge-status ${cls}">${status || 'N/A'}</span>`;
            }
            function computeBMI(height, weight) {
                if (!height || !weight || height <= 0) return null;
                const h = height / 100;
                const bmi = weight / (h * h);
                return Math.round(bmi * 10) / 10;
            }
            function getBMICategory(bmi) {
                if (bmi === null) return 'Unknown';
                if (bmi < 18.5) return 'Underweight';
                if (bmi < 25) return 'Normal';
                if (bmi < 30) return 'Overweight';
                return 'Obese';
            }

            // ---------- FILTER HELPERS ----------
            function getFilteredVisits(roleFilter = null) {
                const r = roleFilter || profile.role;
                let filtered = visits;
                if (r === 'user') {
                    const schoolId = profile.school_id;
                    if (schoolId) {
                        const patientIds = patients.filter(p => p.school_id === schoolId).map(p => p.id);
                        filtered = visits.filter(v => patientIds.includes(v.patient_id));
                    } else { filtered = []; }
                }
                if (r === 'doctor') {
                    const district = document.getElementById('filterDistrict')?.value;
                    const schoolId = document.getElementById('filterSchool')?.value;
                    const year = document.getElementById('filterYear')?.value;
                    filtered = filtered.filter(v => {
                        const p = getPatient(v.patient_id);
                        if (!p) return false;
                        const s = getSchool(p.school_id);
                        if (district && s && s.district !== district) return false;
                        if (schoolId && p.school_id != schoolId) return false;
                        if (year && v.visit_date.substring(0, 4) !== year) return false;
                        return true;
                    });
                }
                return filtered;
            }

            function getPatientsForRole(role = null) {
                const r = role || profile.role;
                if (r === 'user') {
                    const schoolId = profile.school_id;
                    if (schoolId) { return patients.filter(p => p.school_id === schoolId); }
                    return [];
                }
                return patients;
            }

            // ---------- RENDER: MAIN ----------
            function renderAll() {
                renderRoleUI();
                renderFilters();
                renderStats();
                renderDashboardVisits();
                renderImpressionSlideshow();
                renderVisits();
                renderAnnouncements();
                renderDiagnosisChart();
                renderAlerts();
            }

            // ---------- RENDER: UI ELEMENTS ----------
            function renderRoleUI() {
                const isDoctor = profile.role === 'doctor';
                document.getElementById('doctorDashboard').style.display = isDoctor ? 'block' : 'none';
                document.getElementById('userDashboard').style.display = isDoctor ? 'none' : 'block';
                document.getElementById('pageTitle').textContent = isDoctor ? 'Dashboard (Doctor)' : 'Dashboard (Nurse)';
                document.getElementById('roleBadge').textContent = isDoctor ? '👨‍⚕️ Doctor' : '👩‍⚕️ Nurse';
                document.getElementById('roleBadge').className = 'role-badge ' + (isDoctor ? 'role-doctor' : 'role-user');
                document.getElementById('roleDisplay').textContent = isDoctor ? 'Doctor' : 'Nurse';
                if (!isDoctor) {
                    const school = getSchool(profile.school_id);
                    document.getElementById('userSchoolName').textContent = school ? school.name : 'your school';
                }
            }

            function renderFilters() {
                if (profile.role !== 'doctor') return;
                const districtSet = new Set();
                schools.forEach(s => districtSet.add(s.district));
                const districtSelect = document.getElementById('filterDistrict');
                const currentDist = districtSelect.value;
                districtSelect.innerHTML = '<option value="">All Districts</option>';
                districtSet.forEach(d => {
                    districtSelect.innerHTML += `<option value="${d}" ${d===currentDist?'selected':''}>${d}</option>`;
                });

                const schoolSelect = document.getElementById('filterSchool');
                const currentSchool = schoolSelect.value;
                let filteredSchools = schools;
                if (currentDist) filteredSchools = schools.filter(s => s.district === currentDist);
                schoolSelect.innerHTML = '<option value="">All Schools</option>';
                filteredSchools.forEach(s => {
                    schoolSelect.innerHTML += `<option value="${s.id}" ${s.id==currentSchool?'selected':''}>${s.name}</option>`;
                });

                const yearSelect = document.getElementById('filterYear');
                const currentYear = yearSelect.value;
                const years = new Set();
                visits.forEach(v => years.add(v.visit_date.substring(0, 4)));
                yearSelect.innerHTML = '<option value="">All Years</option>';
                [...years].sort().reverse().forEach(y => {
                    yearSelect.innerHTML += `<option value="${y}" ${y===currentYear?'selected':''}>${y}</option>`;
                });
            }

            // ---------- RENDER: STATS ----------
            function renderStats() {
                const docVisits = getFilteredVisits('doctor');
                const docPatients = getPatientsForRole('doctor');
                document.getElementById('statPatientsDoc').textContent = docPatients.length;
                const docMaintenance = docVisits.filter(v => v.maintenance && v.maintenance.trim() !== '' && v.maintenance !== 'None').length;
                document.getElementById('statMaintenanceDoc').textContent = docMaintenance;
                const docPendingLabs = docVisits.filter(v => v.lab && (v.lab.cbc || v.lab.urinalysis || v.lab.fbs_hba1c || v.lab.lipid_panel || v.lab.bua || v.lab.bun || v.lab.creatinine || v.lab.sgpt_sgot || v.lab.others) && v.lab.status !== 'completed').length;
                document.getElementById('statLabsDoc').textContent = docPendingLabs;

                // User stats
                const userVisits = getFilteredVisits('user');
                const userPatients = getPatientsForRole('user');
                document.getElementById('statPatientsUser').textContent = userPatients.length;
                document.getElementById('statVisitsUser').textContent = userVisits.length;
                const userMaintenance = userVisits.filter(v => v.maintenance && v.maintenance.trim() !== '' && v.maintenance !== 'None').length;
                document.getElementById('statMaintenanceUser').textContent = userMaintenance;
                const userPendingLabs = userVisits.filter(v => v.lab && (v.lab.cbc || v.lab.urinalysis || v.lab.fbs_hba1c || v.lab.lipid_panel || v.lab.bua || v.lab.bun || v.lab.creatinine || v.lab.sgpt_sgot || v.lab.others) && v.lab.status !== 'completed').length;
                document.getElementById('statLabsUser').textContent = userPendingLabs;
            }

            // ---------- RENDER: DASHBOARD VISITS TABLE ----------
            function renderDashboardVisits() {
                const docVisits = getFilteredVisits('doctor');
                const tbodyDoc = document.getElementById('dashboardVisitsTableDoc');
                if (!docVisits.length) {
                    tbodyDoc.innerHTML = `<tr><td colspan="5" class="text-center">No visits match filters</td></tr>`;
                } else {
                    tbodyDoc.innerHTML = docVisits.slice(0, 10).map(v => {
                        const p = getPatient(v.patient_id);
                        const s = p ? getSchool(p.school_id) : null;
                        return `<tr>
                                    <td>${formatDate(v.visit_date)}</td>
                                    <td><strong>${p ? p.name : 'Unknown'}</strong></td>
                                    <td>${s ? s.name : 'N/A'}</td>
                                    <td>${v.impression ? v.impression.substring(0, 30) : ''}${v.impression && v.impression.length > 30 ? '…' : ''}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info viewVisitBtn" data-id="${v.id}"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-warning editVisitBtn" data-id="${v.id}"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger deleteVisitBtn" data-id="${v.id}"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>`;
                    }).join('');
                }
                // User table
                const userVisits = getFilteredVisits('user');
                const tbodyUser = document.getElementById('dashboardVisitsTableUser');
                if (!userVisits.length) {
                    tbodyUser.innerHTML = `<tr><td colspan="4" class="text-center">No visits for your school</td></tr>`;
                } else {
                    tbodyUser.innerHTML = userVisits.map(v => {
                        const p = getPatient(v.patient_id);
                        return `<tr>
                                    <td>${formatDate(v.visit_date)}</td>
                                    <td><strong>${p ? p.name : 'Unknown'}</strong></td>
                                    <td>${v.impression ? v.impression.substring(0, 30) : ''}${v.impression && v.impression.length > 30 ? '…' : ''}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info viewVisitBtn" data-id="${v.id}"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-warning editVisitBtn" data-id="${v.id}"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger deleteVisitBtn" data-id="${v.id}"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>`;
                    }).join('');
                }
                // Re-attach events
                document.querySelectorAll('.viewVisitBtn').forEach(btn => btn.addEventListener('click', function() { viewVisit(this.dataset.id); }));
                document.querySelectorAll('.editVisitBtn').forEach(btn => btn.addEventListener('click', function() { editVisit(this.dataset.id); }));
                document.querySelectorAll('.deleteVisitBtn').forEach(btn => btn.addEventListener('click', function() { deleteVisit(this.dataset.id); }));
            }

            // ---------- RENDER: IMPRESSION SLIDESHOW ----------
            function renderImpressionSlideshow() {
                if (profile.role !== 'doctor') {
                    document.getElementById('impressionCarousel').style.display = 'none';
                    document.getElementById('slideCounter').style.display = 'none';
                    return;
                }
                document.getElementById('impressionCarousel').style.display = 'block';
                document.getElementById('slideCounter').style.display = 'block';

                const filtered = getFilteredVisits('doctor');
                const inner = document.getElementById('impressionSlides');
                const counter = document.getElementById('slideCounter');

                if (!filtered.length) {
                    inner.innerHTML = `<div class="carousel-item active"><div class="impression-slide" style="cursor:default;"><div class="text-center text-muted">No impressions to display</div></div></div>`;
                    counter.textContent = '0 / 0';
                    return;
                }

                let slidesHtml = '';
                filtered.slice(0, 10).forEach((v, idx) => {
                    const p = getPatient(v.patient_id);
                    const s = p ? getSchool(p.school_id) : null;
                    const isActive = idx === 0 ? 'active' : '';
                    slidesHtml += `
                        <div class="carousel-item ${isActive}" data-visit-id="${v.id}">
                            <div class="impression-slide" data-visit-id="${v.id}">
                                <div class="slide-title">${v.impression || 'No impression recorded'}</div>
                                <div class="slide-meta">
                                    <span><strong>Patient:</strong> ${p ? p.name : 'Unknown'}</span>
                                    <span><strong>Date:</strong> ${formatDate(v.visit_date)}</span>
                                    ${s ? `<span><strong>School:</strong> ${s.name}</span>` : ''}
                                    ${s ? `<span><strong>District:</strong> ${s.district}</span>` : ''}
                                    ${p ? `<span><strong>Grade:</strong> ${p.grade || '—'}</span>` : ''}
                                </div>
                                <div class="slide-content">
                                    <div class="detail-row"><span class="detail-label">Diagnoses:</span> ${v.diagnoses || '—'}</div>
                                    <div class="detail-row"><span class="detail-label">Maintenance:</span> ${v.maintenance || '—'}</div>
                                    <div class="detail-row"><span class="detail-label">Doctor's Notes:</span> ${v.doctor_notes || '—'}</div>
                                </div>
                                <div class="click-hint"><i class="fas fa-expand-alt"></i> Click for details & lab results</div>
                            </div>
                        </div>
                    `;
                });

                inner.innerHTML = slidesHtml;
                counter.textContent = `1 / ${Math.min(filtered.length, 10)}`;

                // Click handlers
                document.querySelectorAll('#impressionSlides .impression-slide').forEach(slide => {
                    slide.addEventListener('click', function(e) {
                        const visitId = this.dataset.visitId;
                        if (visitId) viewVisit(visitId);
                    });
                });

                // Counter update
                const carousel = document.getElementById('impressionCarousel');
                carousel.removeEventListener('slid.bs.carousel', updateCounter);
                carousel.addEventListener('slid.bs.carousel', updateCounter);
                function updateCounter(e) { const idx = e.to + 1; counter.textContent = `${idx} / ${Math.min(filtered.length, 10)}`; }

                // Re-init carousel
                let bsCarousel = bootstrap.Carousel.getInstance(carousel);
                if (bsCarousel) bsCarousel.dispose();
                bsCarousel = new bootstrap.Carousel(carousel, { ride: 'carousel', interval: 5000, pause: 'hover' });
                window.impressionCarousel = bsCarousel;
            }

            // ---------- RENDER: VISITS TABLE ----------
            function renderVisits() {
                const filtered = getFilteredVisits();
                const tbody = document.getElementById('visitsTable');
                if (!filtered.length) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center">No visits</td></tr>`;
                    return;
                }
                tbody.innerHTML = filtered.slice(0, 20).map(v => {
                    const p = getPatient(v.patient_id);
                    return `<tr>
                                <td>${formatDate(v.visit_date)}</td>
                                <td><strong>${p ? p.name : 'Unknown'}</strong></td>
                                <td>${v.impression ? v.impression.substring(0, 30) : ''}${v.impression && v.impression.length > 30 ? '…' : ''}</td>
                                <td>${v.diagnoses ? v.diagnoses.substring(0, 30) : ''}${v.diagnoses && v.diagnoses.length > 30 ? '…' : ''}</td>
                                <td>
                                    <button class="btn btn-sm btn-info viewVisitBtn" data-id="${v.id}"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-warning editVisitBtn" data-id="${v.id}"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger deleteVisitBtn" data-id="${v.id}"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>`;
                }).join('');
                document.querySelectorAll('#visitsTable .viewVisitBtn').forEach(btn => btn.addEventListener('click', function() { viewVisit(this.dataset.id); }));
                document.querySelectorAll('#visitsTable .editVisitBtn').forEach(btn => btn.addEventListener('click', function() { editVisit(this.dataset.id); }));
                document.querySelectorAll('#visitsTable .deleteVisitBtn').forEach(btn => btn.addEventListener('click', function() { deleteVisit(this.dataset.id); }));
            }

            // ---------- RENDER: ANNOUNCEMENTS ----------
            function renderAnnouncements() {
                const container = document.getElementById('announcementsList');
                if (!announcements.length) {
                    container.innerHTML = '<div class="col-12 text-muted text-center">No announcements</div>';
                    return;
                }
                container.innerHTML = announcements.map(a => `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">${a.title}</h5>
                                <h6 class="card-subtitle mb-2 text-muted">${formatDate(a.date)}</h6>
                                <p class="card-text">${a.content}</p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <button class="btn btn-sm btn-danger deleteAnnouncementBtn" data-id="${a.id}"><i class="fas fa-trash"></i> Delete</button>
                            </div>
                        </div>
                    </div>
                `).join('');
                document.querySelectorAll('.deleteAnnouncementBtn').forEach(btn => btn.addEventListener('click', function() {
                    if (confirm('Delete announcement?')) {
                        announcements = announcements.filter(a => a.id !== this.dataset.id);
                        saveData();
                        renderAnnouncements();
                    }
                }));
            }

            // ---------- CHARTS ----------
            function renderDiagnosisChart() {
                if (profile.role !== 'doctor') {
                    document.getElementById('diagnosisChart').parentElement.style.display = 'none';
                    return;
                }
                document.getElementById('diagnosisChart').parentElement.style.display = 'block';
                const filtered = getFilteredVisits('doctor');
                const diagCounts = {};
                filtered.forEach(v => {
                    if (v.diagnoses && v.diagnoses !== 'None') {
                        const diag = v.diagnoses.split(',').map(d => d.trim());
                        diag.forEach(d => { diagCounts[d] = (diagCounts[d] || 0) + 1; });
                    }
                });
                const sorted = Object.entries(diagCounts).sort((a,b) => b[1] - a[1]).slice(0, 5);
                const ctx = document.getElementById('diagnosisChart').getContext('2d');
                if (window.diagnosisChartInstance) window.diagnosisChartInstance.destroy();
                window.diagnosisChartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: sorted.map(d => d[0]),
                        datasets: [{ label: 'Frequency', data: sorted.map(d => d[1]), backgroundColor: '#0d6efd' }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });
            }

            // ---------- ALERTS ----------
            function renderAlerts() {
                if (profile.role !== 'doctor') {
                    document.getElementById('alertsList').parentElement.style.display = 'none';
                    return;
                }
                document.getElementById('alertsList').parentElement.style.display = 'block';
                const filtered = getFilteredVisits('doctor');
                const alerts = [];
                const patientLatest = {};
                filtered.forEach(v => {
                    if (!patientLatest[v.patient_id] || new Date(v.visit_date) > new Date(patientLatest[v.patient_id].visit_date)) {
                        patientLatest[v.patient_id] = v;
                    }
                });
                Object.values(patientLatest).forEach(v => {
                    if (v.referral_status && v.referral_status === 'Pending') {
                        alerts.push(`🔔 ${getPatientName(v.patient_id)} – Referral pending (${v.referral_notes || 'no notes'})`);
                    }
                    if (v.lab && (v.lab.cbc || v.lab.urinalysis) && v.lab.status !== 'completed') {
                        alerts.push(`🧪 ${getPatientName(v.patient_id)} – Lab results pending`);
                    }
                });
                const container = document.getElementById('alertsList');
                if (!alerts.length) {
                    container.innerHTML = '<p class="text-muted">No alerts</p>';
                } else {
                    container.innerHTML = `<ul class="list-group">${alerts.slice(0, 10).map(a => `<li class="list-group-item">${a}</li>`).join('')}</ul>`;
                }
            }

            // ---------- CRUD: VISITS ----------
            function openVisitModal(data = null, isEdit = false) {
                const visit = data || { patient_id: '', visit_date: new Date().toISOString().slice(0,10), impression: '', diagnoses: '', maintenance: '', doctor_notes: '', lab: {}, height: '', weight: '', vision_left: '', vision_right: '', hearing_left: '', hearing_right: '', dental_findings: '', immunizations: '', referral_notes: '', referral_status: '' };
                let patientList = patients;
                if (profile.role === 'user' && profile.school_id) patientList = patients.filter(p => p.school_id === profile.school_id);
                const patientOptions = patientList.map(p => `<option value="${p.id}" ${p.id==visit.patient_id?'selected':''}>${p.name}</option>`).join('');
                const lab = visit.lab || {};
                const labFields = [
                    { key: 'cbc', label: 'CBC' }, { key: 'urinalysis', label: 'Urinalysis' }, { key: 'fbs_hba1c', label: 'FBS / HbA1c' },
                    { key: 'lipid_panel', label: 'Lipid Panel' }, { key: 'bua', label: 'BUA' }, { key: 'bun', label: 'BUN' },
                    { key: 'creatinine', label: 'Creatinine' }, { key: 'sgpt_sgot', label: 'SGPT / SGOT' }, { key: 'others', label: 'Others' }
                ];
                let labHtml = labFields.map(f => `<div class="col-md-4"><label>${f.label}</label><input name="lab_${f.key}" class="form-control" value="${lab[f.key]||''}" /></div>`).join('');

                const html = `
                    <form id="visitForm">
                        <div class="row">
                            <div class="col-md-6"><label>Patient *</label><select name="patient_id" class="form-select" required>${patientOptions}</select></div>
                            <div class="col-md-6"><label>Visit Date *</label><input name="visit_date" type="date" class="form-control" value="${visit.visit_date}" required /></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><label>Height (cm)</label><input name="height" type="number" step="0.1" class="form-control" value="${visit.height||''}" id="heightInput" /></div>
                            <div class="col-md-4"><label>Weight (kg)</label><input name="weight" type="number" step="0.1" class="form-control" value="${visit.weight||''}" id="weightInput" /></div>
                            <div class="col-md-4"><label>BMI</label><input name="bmi" type="number" step="0.1" class="form-control" value="${visit.bmi||''}" id="bmiInput" readonly /></div>
                        </div>
                        <div class="mb-3"><label>Impression</label><textarea name="impression" class="form-control" rows="2">${visit.impression||''}</textarea></div>
                        <div class="row">
                            <div class="col-md-6"><label>Diagnoses</label><textarea name="diagnoses" class="form-control" rows="2">${visit.diagnoses||''}</textarea></div>
                            <div class="col-md-6"><label>Maintenance</label><textarea name="maintenance" class="form-control" rows="2">${visit.maintenance||''}</textarea></div>
                        </div>
                        <div class="mb-3"><label>Doctor's Notes</label><textarea name="doctor_notes" class="form-control" rows="2">${visit.doctor_notes||''}</textarea></div>
                        <hr>
                        <h6>Screenings</h6>
                        <div class="row">
                            <div class="col-md-4"><label>Vision (L/R)</label><input name="vision_left" class="form-control" placeholder="L" value="${visit.vision_left||''}" /> <input name="vision_right" class="form-control mt-1" placeholder="R" value="${visit.vision_right||''}" /></div>
                            <div class="col-md-4"><label>Hearing (L/R)</label><input name="hearing_left" class="form-control" placeholder="L" value="${visit.hearing_left||''}" /> <input name="hearing_right" class="form-control mt-1" placeholder="R" value="${visit.hearing_right||''}" /></div>
                            <div class="col-md-4"><label>Dental Findings</label><input name="dental_findings" class="form-control" value="${visit.dental_findings||''}" /></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><label>Immunizations</label><input name="immunizations" class="form-control" value="${visit.immunizations||''}" /></div>
                            <div class="col-md-6"><label>Referral Status</label><select name="referral_status" class="form-select"><option value="">None</option><option value="Pending" ${visit.referral_status==='Pending'?'selected':''}>Pending</option><option value="Completed" ${visit.referral_status==='Completed'?'selected':''}>Completed</option></select></div>
                        </div>
                        <div class="mb-3"><label>Referral Notes</label><textarea name="referral_notes" class="form-control" rows="2">${visit.referral_notes||''}</textarea></div>
                        <hr>
                        <h6>Lab Results</h6>
                        <div class="lab-group"><div class="row">${labHtml}</div></div>
                    </form>
                `;
                showModal(isEdit ? 'Edit Visit' : 'New Visit', html, () => {
                    const form = document.getElementById('visitForm');
                    const formData = new FormData(form);
                    const obj = {};
                    const labData = {};
                    formData.forEach((v,k) => {
                        if (k.startsWith('lab_')) labData[k.replace('lab_','')] = v;
                        else obj[k] = v;
                    });
                    if (!obj.patient_id || !obj.visit_date) { alert('Patient and Date are required'); return false; }
                    const h = parseFloat(obj.height);
                    const w = parseFloat(obj.weight);
                    if (h && w && h>0) {
                        const bmi = computeBMI(h, w);
                        obj.bmi = bmi;
                        obj.bmi_category = getBMICategory(bmi);
                    } else {
                        obj.bmi = null;
                        obj.bmi_category = 'Unknown';
                    }
                    obj.lab = labData;
                    if (isEdit) {
                        const idx = visits.findIndex(v => v.id === visit.id);
                        if (idx > -1) visits[idx] = { ...visits[idx], ...obj };
                    } else {
                        obj.id = generateId();
                        visits.push(obj);
                    }
                    saveData();
                    renderAll();
                    return true;
                });
                document.getElementById('heightInput')?.addEventListener('input', calcBMI);
                document.getElementById('weightInput')?.addEventListener('input', calcBMI);
                function calcBMI() {
                    const h = parseFloat(document.getElementById('heightInput').value);
                    const w = parseFloat(document.getElementById('weightInput').value);
                    if (h && w && h>0) {
                        const bmi = computeBMI(h, w);
                        document.getElementById('bmiInput').value = bmi || '';
                    } else {
                        document.getElementById('bmiInput').value = '';
                    }
                }
            }

            function editVisit(id) { const v = visits.find(v => v.id === id); if (v) openVisitModal(v, true); }

            function viewVisit(id) {
                const v = visits.find(v => v.id === id);
                if (!v) return;
                const p = getPatient(v.patient_id);
                const s = p ? getSchool(p.school_id) : null;
                const lab = v.lab || {};
                const labFields = ['cbc','urinalysis','fbs_hba1c','lipid_panel','bua','bun','creatinine','sgpt_sgot','others'];
                let labRows = labFields.map(key => `<tr><th>${key.toUpperCase().replace('_','/')}</th><td>${lab[key]||'—'}</td></tr>`).join('');

                const body = `
                    <dl class="row">
                        <dt class="col-sm-3">Patient</dt><dd class="col-sm-9">${p ? p.name : 'Unknown'} ${p ? '(Grade '+p.grade+')' : ''}</dd>
                        <dt class="col-sm-3">School</dt><dd class="col-sm-9">${s ? s.name+' ('+s.district+')' : 'N/A'}</dd>
                        <dt class="col-sm-3">Date</dt><dd class="col-sm-9">${formatDate(v.visit_date)}</dd>
                        <dt class="col-sm-3">Impression</dt><dd class="col-sm-9">${v.impression||'—'}</dd>
                        <dt class="col-sm-3">Diagnoses</dt><dd class="col-sm-9">${v.diagnoses||'—'}</dd>
                        <dt class="col-sm-3">Maintenance</dt><dd class="col-sm-9">${v.maintenance||'—'}</dd>
                        <dt class="col-sm-3">Doctor's Notes</dt><dd class="col-sm-9">${v.doctor_notes||'—'}</dd>
                        <dt class="col-sm-3">BMI</dt><dd class="col-sm-9">${v.bmi||'—'} (${v.bmi_category||'Unknown'})</dd>
                        <dt class="col-sm-3">Vision</dt><dd class="col-sm-9">${v.vision_left||'—'} / ${v.vision_right||'—'}</dd>
                        <dt class="col-sm-3">Hearing</dt><dd class="col-sm-9">${v.hearing_left||'—'} / ${v.hearing_right||'—'}</dd>
                        <dt class="col-sm-3">Dental</dt><dd class="col-sm-9">${v.dental_findings||'—'}</dd>
                        <dt class="col-sm-3">Immunizations</dt><dd class="col-sm-9">${v.immunizations||'—'}</dd>
                        <dt class="col-sm-3">Referral</dt><dd class="col-sm-9">${v.referral_status||'—'} ${v.referral_notes ? '('+v.referral_notes+')' : ''}</dd>
                    </dl>
                    <hr>
                    <h6>Lab Results</h6>
                    <table class="table table-bordered">${labRows}</table>
                `;
                document.getElementById('viewBody').innerHTML = body;
                const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                viewModal.show();
            }

            function deleteVisit(id) {
                if (!confirm('Delete this visit?')) return;
                visits = visits.filter(v => v.id !== id);
                saveData();
                renderAll();
            }

            // ---------- ANNOUNCEMENTS ----------
            function openAnnouncementModal(data = null) {
                const ann = data || { title: '', content: '', date: new Date().toISOString().slice(0,10) };
                const html = `
                    <form id="announcementForm">
                        <div class="mb-3"><label>Title *</label><input name="title" class="form-control" value="${ann.title}" required /></div>
                        <div class="mb-3"><label>Content</label><textarea name="content" class="form-control" rows="3">${ann.content||''}</textarea></div>
                        <div class="mb-3"><label>Date</label><input name="date" type="date" class="form-control" value="${ann.date}" /></div>
                    </form>
                `;
                showModal('New Announcement', html, () => {
                    const form = document.getElementById('announcementForm');
                    const formData = new FormData(form);
                    const obj = {};
                    formData.forEach((v,k) => obj[k] = v);
                    if (!obj.title) { alert('Title is required'); return false; }
                    obj.id = generateId();
                    announcements.push(obj);
                    saveData();
                    renderAnnouncements();
                    return true;
                });
            }

            // ---------- EXPORT (simulated) ----------
            function exportReport(type) {
                alert(`Exporting ${type} report... (Data would be generated here)`);
            }

            // ---------- MODAL HELPER ----------
            function showModal(title, bodyHTML, onSave) {
                document.getElementById('modalTitle').textContent = title;
                document.getElementById('modalBody').innerHTML = bodyHTML;
                const modal = new bootstrap.Modal(document.getElementById('emrModal'));
                const saveBtn = document.getElementById('modalSaveBtn');
                const newSave = saveBtn.cloneNode(true);
                saveBtn.parentNode.replaceChild(newSave, saveBtn);
                newSave.addEventListener('click', function() {
                    if (onSave()) modal.hide();
                });
                modal.show();
            }

            // ---------- NAVIGATION ----------
            function navigateTo(section) {
                document.querySelectorAll('.page-section').forEach(el => el.classList.remove('active'));
                document.getElementById('section-' + section).classList.add('active');
                document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
                document.querySelector(`.nav-link[data-section="${section}"]`).classList.add('active');
                if (section === 'dashboard') setTimeout(() => { renderImpressionSlideshow(); renderDiagnosisChart(); renderAlerts(); }, 100);
            }

            document.querySelectorAll('.nav-link[data-section]').forEach(el => {
                el.addEventListener('click', function(e) { e.preventDefault(); navigateTo(this.dataset.section); });
            });

            // ---------- EVENT BINDINGS ----------
            document.getElementById('addVisitBtn').addEventListener('click', () => openVisitModal(null, false));
            document.getElementById('addAnnouncementBtn').addEventListener('click', () => openAnnouncementModal(null));
            document.getElementById('exportReportsBtn').addEventListener('click', () => new bootstrap.Modal(document.getElementById('exportModal')).show());
            document.getElementById('exportSummaryBtn').addEventListener('click', () => exportReport('School Health Summary'));
            document.getElementById('exportAbnormalBtn').addEventListener('click', () => exportReport('Abnormal Findings'));

            // Role switcher
            document.querySelectorAll('[data-role]').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const newRole = this.dataset.role;
                    if (newRole === 'user') {
                        if (!profile.school_id && schools.length) profile.school_id = schools[0].id;
                        if (!schools.length) { alert('No schools available.'); return; }
                        if (!profile.school_id) {
                            const choice = prompt('Enter school ID:', schools[0].id);
                            if (choice && schools.some(s => s.id == choice)) profile.school_id = parseInt(choice);
                            else { alert('Invalid'); return; }
                        }
                    } else {
                        profile.school_id = null;
                    }
                    profile.role = newRole;
                    saveProfile();
                    renderAll();
                });
            });

            // Filters
            document.getElementById('filterDistrict').addEventListener('change', function() { renderFilters(); renderDashboardVisits(); renderImpressionSlideshow(); renderStats(); renderDiagnosisChart(); renderAlerts(); });
            document.getElementById('filterSchool').addEventListener('change', function() { renderDashboardVisits(); renderImpressionSlideshow(); renderStats(); renderDiagnosisChart(); renderAlerts(); });
            document.getElementById('filterYear').addEventListener('change', function() { renderDashboardVisits(); renderImpressionSlideshow(); renderStats(); renderDiagnosisChart(); renderAlerts(); });
            document.getElementById('resetFilters').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('filterDistrict').value = '';
                document.getElementById('filterSchool').value = '';
                document.getElementById('filterYear').value = '';
                renderFilters();
                renderDashboardVisits();
                renderImpressionSlideshow();
                renderStats();
                renderDiagnosisChart();
                renderAlerts();
            });

            // logout
            document.getElementById('logoutBtn').addEventListener('click', function() {
                if (confirm('Sign out?')) { loadData(); renderAll(); alert('Signed out – data reloaded.'); }
            });

            // ---------- INIT ----------
            loadData();
            loadProfile();
            renderAll();
            navigateTo('dashboard');

        })();
    </script>

</body>
</html>