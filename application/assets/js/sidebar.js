(function(){
  const btn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('mainSidebar');
  const spacer = document.getElementById('sidebarSpacer');

  if(!btn || !sidebar) return;

  btn.addEventListener('click', function(){

    if(window.innerWidth < 768){
      sidebar.classList.toggle('show');
      return;
    }

    sidebar.classList.toggle('collapsed');
    spacer.classList.toggle('collapsed');
  });

  document.addEventListener('click', function(ev){
    if(window.innerWidth >= 768) return;
    if(!sidebar.classList.contains('show')) return;

    if(!sidebar.contains(ev.target) && !btn.contains(ev.target)){
      sidebar.classList.remove('show');
    }
  });

  window.addEventListener('resize', function(){
    if(window.innerWidth >= 768){
      sidebar.classList.remove('show');
    }
  });
})();
