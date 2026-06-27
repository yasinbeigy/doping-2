document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.querySelector('.admin-menu-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  const backdrop = document.querySelector('.admin-menu-backdrop');

  if (!toggle || !sidebar) return;

  const closeMenu = () => {
    sidebar.classList.remove('is-open');
    document.body.classList.remove('admin-menu-open');
    toggle.setAttribute('aria-expanded', 'false');
    if (backdrop) backdrop.classList.remove('is-open');
  };

  const openMenu = () => {
    sidebar.classList.add('is-open');
    document.body.classList.add('admin-menu-open');
    toggle.setAttribute('aria-expanded', 'true');
    if (backdrop) backdrop.classList.add('is-open');
  };

  toggle.addEventListener('click', function () {
    sidebar.classList.contains('is-open') ? closeMenu() : openMenu();
  });

  if (backdrop) backdrop.addEventListener('click', closeMenu);

  sidebar.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.matchMedia('(max-width: 768px)').matches) closeMenu();
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeMenu();
  });
});

// Preview-only router: separate admin sections inside one HTML file
(function () {
  const initPreviewTabs = () => {
    const links = document.querySelectorAll('[data-admin-preview-tab]');
    const panels = document.querySelectorAll('[data-admin-preview-panel]');
    if (!links.length || !panels.length) return;

    const activate = (tab) => {
      panels.forEach((panel) => {
        const active = panel.dataset.adminPreviewPanel === tab;
        panel.hidden = !active;
        panel.classList.toggle('is-active', active);
        panel.style.display = active ? 'block' : 'none';
      });

      links.forEach((link) => {
        link.classList.toggle('active', link.dataset.adminPreviewTab === tab);
      });

      if (history.replaceState) {
        history.replaceState(null, '', '#' + tab);
      }

      window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    links.forEach((link) => {
      link.addEventListener('click', (event) => {
        event.preventDefault();
        activate(link.dataset.adminPreviewTab);
      });
    });

    const initial = (location.hash || '#dashboard').replace('#', '');
    const exists = Array.from(panels).some((panel) => panel.dataset.adminPreviewPanel === initial);
    activate(exists ? initial : 'dashboard');
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPreviewTabs);
  } else {
    initPreviewTabs();
  }
})();
