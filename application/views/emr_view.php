<!-- emr_view.php -->
<?php $this->load->view('templates/header', ['title' => 'DepEd EMR · Health & Nutrition']); ?>

<!-- Page-specific stylesheet -->
<link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/emr_view.css'); ?>">

<div class="main-content">

    <!-- ========================================================== -->
    <!-- ======================= DASHBOARD ========================= -->
    <!-- ========================================================== -->
    <section id="section-dashboard" class="page-section active">
        <div id="emrErrorBanner" class="alert alert-danger d-none" role="alert"></div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="filterForm" class="row">
                    <div class="col">
                        <label class="form-label">District</label>
                        <select id="filterDistrict" class="form-select"><option value="">All Districts</option></select>
                    </div>
                    <div class="col-5">
                        <label class="form-label">School</label>
                        <select id="filterSchool" class="form-select"><option value="">All Schools</option></select>
                    </div>
                    <div class="col">
                        <label class="form-label">Year</label>
                        <select id="filterYear" class="form-select"><option value="">All Years</option></select>
                    </div>
                    <div class="col d-flex align-items-end">
                        <button type="reset" class="btn btn-secondary" id="resetFilters">Reset</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Row 1: Total Patients -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="icon float-end"><i class="fas fa-users"></i></div>
                    <div class="label text-muted">Total Patients</div>
                    <div class="value" id="statPatientsDoc">0</div>
                </div>
            </div>
        </div>

        <!-- Row 2: Top Impressions + Impressions Slideshow -->
        <div class="row g-4 mb-4">
            <!-- LEFT COLUMN -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Top Impressions</h5>
                        <small class="text-muted">Click a bar to see the matching patients</small>
                    </div>
                    <div class="card-body">
                        <canvas id="impressionChart" height="140" class="clickable-chart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0" id="impressionDetailsTitle">
                            <i class="fas fa-list-ul"></i> Impression Details
                        </h5>
                        <small class="text-muted" id="impressionDetailsSubtitle">Click a bar in the chart</small>
                    </div>
                    <div class="card-body table-responsive" style="max-height: 320px; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>School</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="impressionDetailsTable">
                                <tr><td colspan="4" class="text-center text-muted">No selection yet</td></tr>
                            </tbody>
                        </table>
                        <div id="impressionDetailsPagination" class="d-flex justify-content-end align-items-center mt-2"></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comment-medical"></i> Impressions Slideshow</h5>
                        <small class="text-muted">Click any card for full details</small>
                    </div>
                    <div class="card-body pb-2">
                        <div id="impressionCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
                            <div class="carousel-inner" id="impressionSlides">
                                <div class="carousel-item active">
                                    <div class="impression-slide" style="cursor:default;">
                                        <div class="text-center text-muted py-4">No impressions</div>
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

        <!-- Row 3: Recent Visits (latest per patient) -->
        <div class="row g-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Recent Visits (Latest per Patient)</h5></div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>School</th>
                                    <th>Impression</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="dashboardVisitsTableDoc">
                                <tr><td colspan="5" class="text-center">No visits</td></tr>
                            </tbody>
                        </table>
                    </div>
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
                    <thead><tr><th>Date</th><th>Patient</th><th>Impression</th><th>Actions</th></tr></thead>
                    <tbody id="visitsTable"><tr><td colspan="4" class="text-center">No visits</td></tr></tbody>
                </table>
            </div>
        </div>
    </section>

</div><!-- /.main-content -->

<!-- ========================================================== -->
<!-- ======================== MODALS ============================ -->
<!-- ========================================================== -->

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

<div class="modal fade" id="viewPatientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Patient Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="patientDetailsBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

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

<!-- Config passed from PHP to JS -->
<script>
    window.EMR_CONFIG = {
        API_URLS: {
            profile:        "<?= site_url('emr_api/profile') ?>",
            schools:        "<?= site_url('emr_api/schools') ?>",
            dashboard:     "<?= site_url('emr_api/dashboard') ?>",
            patient:       "<?= site_url('emr_api/patient/') ?>",
            patients:       "<?= site_url('emr_api/patients') ?>",
            visits:         "<?= site_url('emr_api/visits') ?>",
            visit:          "<?= site_url('emr_api/visit') ?>",
            delete_visit:   "<?= site_url('emr_api/delete_visit/') ?>",
            search_schools: "<?= site_url('emr_api/search_schools') ?>"
        },
        LOGOUT_URL: "<?= site_url('logout') ?>"
    };
</script>

<!-- Chart.js — MUST load before emr_view.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Bootstrap 5 JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- EMR page logic -->
<script src="<?= base_url(ASSETS_PATH . '/js/emr_view.js'); ?>"></script>

<?php $this->load->view('templates/footer', ['skip_bootstrap_js' => true]); ?>