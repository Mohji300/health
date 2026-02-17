document.addEventListener('DOMContentLoaded', function() {
  var tableEl = document.getElementById('nutritionalStatusTable');
  if (tableEl && window.jQuery && $.fn.DataTable) {
    $(tableEl).DataTable({
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
      columnDefs: [ { targets: Array.from({length:14}, (_,i)=>i), orderable: true } ]
    });
  }

  document.querySelectorAll('form').forEach(function(f){
    f.addEventListener('submit', function(){
      var btn = f.querySelector('button[type="submit"]');
      if(btn){ btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Applying...'; }
    });
  });
});
