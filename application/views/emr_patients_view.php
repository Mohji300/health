<!-- emr_patients_view.php -->
<?php $this->load->view('templates/header', ['title' => 'DepEd EMR · Patients View & Follow-ups']); ?>

<!-- Page-specific stylesheet -->
<link rel="stylesheet" href="<?= base_url(ASSETS_PATH . '/css/emr_patients_view.css'); ?>">


<div class="main-content">

    <!-- ===== PAGE HEADER ===== -->
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div class="page-header-copy">
            <h1 id="pageTitle">Patients <span>&amp; Follow-ups</span></h1>
            <p class="page-subtitle mb-0">Manage patient records and lab results</p>
        </div>
        <div class="page-header-mark" aria-hidden="true"><i class="fas fa-user-injured"></i></div>
    </div>

    <!-- ===== PATIENTS TABLE ===== -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="mb-0 section-title"><i class="fas fa-users me-2"></i>Patients</h5>
        <div class="d-flex align-items-center gap-2">
            <div class="patient-search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input
                    type="text"
                    id="patientSearchInput"
                    class="form-control patient-search-input"
                    placeholder="Search patients..."
                    autocomplete="off"
                />
                <button type="button" class="btn-clear-search" id="clearPatientSearch" title="Clear search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <button class="btn btn-primary" id="addPatientBtn"><i class="fas fa-plus"></i> New Patient</button>
        </div>
    </div>

    <div id="searchSummary" style="display:none;" class="alert alert-info py-2 mb-2"></div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>School</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="patientsTable">
                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-content -->

<!-- ========================================================== -->
<!-- ======================== MODALS ============================ -->
<!-- ========================================================== -->

<!-- Generic Modal (wizard) -->
<div class="modal fade" id="emrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Modal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <div id="modalContent"></div>
                <div id="modalErrorMessage" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer" id="modalFooter"></div>
        </div>
    </div>
</div>

<!-- View Patient Modal -->
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

<!-- View Follow-up Modal -->
<div class="modal fade" id="viewVisitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visit Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitDetailsBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Config passed from PHP to JS -->
<script>
    window.EMR_PATIENTS_CONFIG = {
        MED_API_BASE: "<?= site_url('emrmedication_controller/') ?>",
        API_BASE:     "<?= site_url('emr_api/') ?>",
        LOGOUT_URL:   "<?= site_url('logout') ?>"
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url(ASSETS_PATH . '/js/emr_patients_view.js'); ?>"></script>

<?php $this->load->view('templates/footer', ['skip_bootstrap_js' => true]); ?>