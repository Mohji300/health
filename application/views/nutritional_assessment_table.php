<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>

<div class="container-fluid py-4">
    <h1 class="h3 mb-4">Nutritional Assessment Table</h1>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo html_escape($this->session->flashdata('success')); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo html_escape($this->session->flashdata('error')); ?></div>
    <?php endif; ?>

    <?php foreach ($tables as $table_name => $table_data): ?>
        <section class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><?php echo html_escape($table_data['title']); ?></h2>
                <?php if ($table_data['exists']): ?>
                    <span class="badge badge-primary">Showing <?php echo number_format(count($table_data['rows'])); ?>of <?php echo number_format($table_data['total']); ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$table_data['exists']): ?>
                    <div class="alert alert-warning mb-0">
                        The <code><?php echo html_escape($table_name); ?></code> table is not available.
                    </div>
                <?php elseif (empty($table_data['fields'])): ?>
                    <p class="text-muted mb-0">This table has no fields to display.</p>
                <?php else: ?>
                    <div>
                        <table id="<?php echo html_escape($table_name); ?>Table" class="table table-striped table-bordered table-sm w-100">
                            <thead class="thead-light">
                                <tr>
                                    <?php foreach ($table_data['fields'] as $field): ?>
                                        <th><?php echo html_escape($field->name); ?></th>
                                    <?php endforeach; ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="editTableRecordModal" tabindex="-1" role="dialog" aria-labelledby="editTableRecordTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <form method="post" action="<?php echo site_url('nutritional-assessment-table/update'); ?>" id="editTableRecordForm">
                <input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTableRecordTitle">Edit Record</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <input type="hidden" name="table" id="editRecordTable">
                        <input type="hidden" name="record_id" id="editRecordId">
                        <div id="editRecordFields" class="row"></div>
                    </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editRecordSave" disabled>Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    var SKIP_FIELDS = [
        'id', 'created_at', 'updated_at', 'is_deleted', 'deleted_at', 'is_locked',
        'classification_of_beneficiary', 'pregnant', 'with_0_1_year_old_child',
        'dewormed', 'parent_consent_milk', 'participation_4ps', 'previous_sbfp'
    ];

    // 1) EDIT MODAL FIRST, so a DataTables problem can never break editing.
    //    Bootstrap 4 passes the clicked button as event.relatedTarget, so this works
    //    for every row on every page, after sorting, and after searching.
    $('#editTableRecordModal').on('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var container = document.getElementById('editRecordFields');
        var saveButton = document.getElementById('editRecordSave');

        // Always start from a clean, locked state so stale data can never be saved.
        container.replaceChildren();
        document.getElementById('editRecordTable').value = '';
        document.getElementById('editRecordId').value = '';
        saveButton.disabled = true;

        if (!button) {
            event.preventDefault();
            return;
        }

        var record, tableName;
        try {
            record = JSON.parse(button.getAttribute('data-record'));
            tableName = button.getAttribute('data-table');
        } catch (e) {
            event.preventDefault();
            alert('Could not read this record. Please reload the page.');
            return;
        }
        if (!record || !record.id || !tableName) {
            event.preventDefault();
            alert('Could not read this record. Please reload the page.');
            return;
        }

        document.getElementById('editRecordTable').value = tableName;
        document.getElementById('editRecordId').value = record.id;
        document.getElementById('editTableRecordTitle').textContent = 'Edit ' +
            (tableName === 'grade_sections' ? 'Grade Sections' : 'Nutritional Assessment') +
            ' Record #' + record.id;

        Object.keys(record).forEach(function (field) {
            if (SKIP_FIELDS.indexOf(field) !== -1) {
                return;
            }
            var column = document.createElement('div');
            column.className = 'col-md-6 form-group';
            var label = document.createElement('label');
            label.textContent = field;
            label.htmlFor = 'edit-value-' + field;
            var input = document.createElement('input');
            input.className = 'form-control';
            input.type = 'text';
            input.name = 'values[' + field + ']';
            input.id = 'edit-value-' + field;
            input.value = record[field] === null ? '' : record[field];
            column.appendChild(label);
            column.appendChild(input);
            container.appendChild(column);
        });

        saveButton.disabled = false;
    });

    // Clear everything when the modal closes so nothing stale remains.
    $('#editTableRecordModal').on('hidden.bs.modal', function () {
        document.getElementById('editRecordFields').replaceChildren();
        document.getElementById('editRecordTable').value = '';
        document.getElementById('editRecordId').value = '';
        document.getElementById('editRecordSave').disabled = true;
    });

    // 2) DATATABLES SECOND, guarded so a missing plugin doesn't throw.
    if (!$.fn || !$.fn.DataTable) {
        console.error('DataTables is not loaded. Check templates/footer.');
        return;
    }
    <?php foreach ($tables as $table_name => $table_data): ?>
        <?php if ($table_data['exists'] && !empty($table_data['fields'])): ?>
            (function () {
                var tableId = <?php echo json_encode($table_name); ?>;
                var $table  = $('#<?php echo html_escape($table_name); ?>Table');

                // Build columns: one per visible field + an actions column.
                var columns = [
                    <?php foreach ($table_data['fields'] as $field): ?>
                    { data: <?php echo json_encode($field->name); ?> },
                    <?php endforeach; ?>
                    {
                        data: null,
                        orderable: false,
                        className: 'text-nowrap',
                        render: function (row) {
                            if (!row || row.id === undefined) { return ''; }
                            // Returned as HTML string. The Edit button uses a data-record
                            // attribute so the existing modal code keeps working unchanged.
                            var recordJson = JSON.stringify(row)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;');
                            return ''
                                + '<button type="button" class="btn btn-sm btn-primary edit-table-record"'
                                +   ' data-toggle="modal" data-target="#editTableRecordModal"'
                                +   ' data-table="' + tableId + '"'
                                +   ' data-record="' + recordJson + '">Edit</button>'
                                + ' <form method="post" class="d-inline"'
                                +   ' action="<?php echo site_url('nutritional-assessment-table/delete'); ?>"'
                                +   ' onsubmit="return confirm(\'Continue?\');">'
                                +   '<input type="hidden" name="<?php echo $csrf_name; ?>" value="<?php echo $csrf_hash; ?>">'
                                +   '<input type="hidden" name="table" value="' + tableId + '">'
                                +   '<input type="hidden" name="record_id" value="' + row.id + '">'
                                +   '<button type="submit" class="btn btn-sm btn-danger">Delete</button>'
                                + '</form>';
                        }
                    }
                ];

                $table.DataTable({
                    serverSide: true,
                    processing: true,
                    ajax: {
                        url: <?php echo json_encode(site_url('nutritional-assessment-table/data')); ?>,
                        data: { table: tableId }
                    },
                    columns: columns,
                    order: [[0, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50], [10, 25, 50]],
                    scrollX: true
                });
            })();
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>