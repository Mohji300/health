/* Externalized JS for SuperAdminDashboard */
$(document).ready(function() {
    var table = $('#userTable').DataTable({
        "pageLength": 25,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
        "ordering": true,
        "autoWidth": false,          // Let us control widths manually
        "scrollX": true,             // Enable horizontal scroll
        "columnDefs": [
            { "orderable": false, "targets": [9] },  // Actions column not orderable
            // Optionally set width for each column (adjust values as needed)
            { "width": "12%", "targets": 0 },  // Name
            { "width": "15%", "targets": 1 },  // Email
            { "width": "10%", "targets": 2 },  // Legislative District
            { "width": "10%", "targets": 3 },  // School District
            { "width": "8%",  "targets": 4 },  // School ID
            { "width": "12%", "targets": 5 },  // School Address
            { "width": "8%",  "targets": 6 },  // School Level
            { "width": "10%", "targets": 7 },  // School Head
            { "width": "8%",  "targets": 8 },  // Role
            { "width": "7%",  "targets": 9 }   // Actions
        ]
    });

    // Ensure the table fills its container
    $('#userTable').css('width', '100%');
    table.columns.adjust().draw();

    $('#submitBulkPayload').on('click', function(e) {
        var users = [];
        $('#userTable tbody tr').each(function() {
            var row = $(this);
            var userId = row.data('user-id');
            var role = row.find('select.role-select').val();
            if (userId !== undefined) { users.push({ id: userId, role: role }); }
        });
        $('#bulkUsersInput').val(JSON.stringify(users));
        $('#bulkUpdateForm').submit();
    });

    var deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));

    $('.delete-user-btn').on('click', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var deleteUrl = window.SuperAdminConfig.delete_user_base + userId;
        $('#userNamePlaceholder').text(userName);
        $('#deleteUserForm').attr('action', deleteUrl);
        deleteUserModal.show();
    });

    var resetUserModal = new bootstrap.Modal(document.getElementById('resetUserModal'));
    $('.reset-user-btn').on('click', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name');
        var resetUrl = window.SuperAdminConfig.reset_user_base + userId;
        $('#resetUserNamePlaceholder').text(userName);
        $('#resetUserForm').attr('action', resetUrl);
        resetUserModal.show();
    });

    $('.role-select').on('change', function() {
        var $form = $(this).closest('form');
        var $button = $form.find('button[type="submit"]');
        if ($button.length) {
            $button.html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');
            $button.prop('disabled', true);
        }
        setTimeout(function() { $form.submit(); }, 500);
    });
});
