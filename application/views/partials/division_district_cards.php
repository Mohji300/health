<?php if (!empty($district_schools_summary)): ?>
    <?php foreach ($district_schools_summary as $district_name => $district_data): ?>
        <?php
            $total_schools     = $district_data['total_schools'];
            $submitted_schools = $district_data['submitted_schools'];
            $completion_rate   = $district_data['completion_rate'];
            $status            = $district_data['status'];
            $statusClass = $status === 'Completed' ? 'bg-success text-white' :
                          ($status === 'In Progress' ? 'bg-warning text-dark' : 'bg-secondary text-white');
            $progressClass = $completion_rate >= 75 ? 'bg-success' :
                            ($completion_rate >= 40 ? 'bg-warning' : 'bg-danger');
        ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 cursor-pointer district-card" data-district="<?= htmlspecialchars($district_name); ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="card-title mb-0"><?= htmlspecialchars($district_name); ?></h6>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary district-report-btn"
                                    data-district="<?= htmlspecialchars($district_name); ?>">
                                <i class="fas fa-eye"></i>
                            </button>
                            <span class="badge rounded-pill <?= $statusClass; ?>"><?= $status; ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Schools:</small>
                            <small class="fw-bold"><?= $submitted_schools; ?>/<?= $total_schools; ?></small>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?= $progressClass; ?>" style="width: <?= $completion_rate; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Completion:</small>
                            <small class="fw-bold"><?= $completion_rate; ?>%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-12">
        <div class="text-center py-4 text-muted">No districts found.</div>
    </div>
<?php endif; ?>