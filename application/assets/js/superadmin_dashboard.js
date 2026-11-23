/* Externalized JS for SuperAdminDashboard */
$(document).ready(function() {
    var table = $('#userTable').DataTable({
        "pageLength": 25,
        "lengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]],
        "ordering": true,
        "autoWidth": false,          // Let us control widths manually
        "scrollX": true,             // Enable horizontal scroll
        "columnDefs": [
            { "orderable": false, "targets": [10] }, // Actions column not orderable
            { "width": "5%",  "targets": 0 },  // User ID
            { "width": "11%", "targets": 1 },  // Name
            { "width": "14%", "targets": 2 },  // Email
            { "width": "9%",  "targets": 3 },  // Legislative District
            { "width": "9%",  "targets": 4 },  // School District
            { "width": "7%",  "targets": 5 },  // School ID
            { "width": "11%", "targets": 6 },  // School Address
            { "width": "7%",  "targets": 7 },  // School Level
            { "width": "9%",  "targets": 8 },  // School Head
            { "width": "8%",  "targets": 9 },  // Role
            { "width": "10%", "targets": 10 }  // Actions
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
