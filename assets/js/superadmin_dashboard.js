/* Externalized JS for SuperAdminDashboard */
$(document).ready(function() {
    var table = $('#userTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "columnDefs": [ { "orderable": false, "targets": [3] } ]
    });

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
