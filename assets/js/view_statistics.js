$(document).ready(function() {
  const table = $('#nutritionalStatusTable').DataTable({
    pageLength: 25,
    ordering: true,
    order: [[0, 'asc']],
    responsive: true,
    scrollX: true,
    scrollCollapse: true,
    language: {
      emptyTable: 'No students found with current filters',
      info: 'Showing _START_ to _END_ of _TOTAL_ students',
      infoEmpty: 'Showing 0 to 0 of 0 students',
      infoFiltered: '(filtered from _MAX_ total students)',
      lengthMenu: 'Show _MENU_ students',
      search: 'Search:',
      zeroRecords: 'No matching students found'
    },
    columnDefs: [ { targets: [0,1,2,3,4,5,6,7,8,9,10,11,12,13], orderable: true } ]
  });

  $('form').on('submit', function() {
    var btn = $(this).find('button[type="submit"]');
    if (btn && btn.length) { btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Applying...'); }
  });

  document.getElementById('sidebarToggle')?.addEventListener('click', function(){
    setTimeout(function(){ table.columns.adjust().responsive.recalc(); }, 300);
  });
});
