/* main.js — Shared JS: alerts, sidebar toggle, modal, confirm */

// Ensure viewport-fit=cover for safe-area-inset support on iOS/Android
(function () {
  var meta = document.querySelector('meta[name="viewport"]');
  if (meta && meta.content.indexOf('viewport-fit') === -1) {
    meta.content += ',viewport-fit=cover';
  }
})();

// Auto-wrap tables in scrollable container on mobile
(function () {
  if (window.innerWidth > 768) return;
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.main-content table').forEach(function (tbl) {
      if (tbl.parentElement.classList.contains('table-responsive')) return;
      var wrap = document.createElement('div');
      wrap.className = 'table-responsive';
      tbl.parentNode.insertBefore(wrap, tbl);
      wrap.appendChild(tbl);
    });
  });
})();

// Auto-dismiss flash messages after 4s
(function () {
  var flash = document.getElementById('flashMsg');
  if (flash) {
    setTimeout(function () {
      flash.style.animation = 'flashSlideOut .4s ease forwards';
      setTimeout(function () { flash.remove(); }, 400);
    }, 4000);
  }
})();

// Sidebar toggle (mobile) with overlay
(function () {
  var toggle  = document.getElementById('sidebarToggle');
  var sidebar = document.querySelector('.sidebar');
  if (!toggle || !sidebar) return;

  var overlay = document.createElement('div');
  overlay.className = 'sidebar-overlay';
  document.body.appendChild(overlay);

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
  }

  toggle.addEventListener('click', function () {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
  overlay.addEventListener('click', closeSidebar);
})();

// Generic confirmation modal
function openModal(modalId) {
  var m = document.getElementById(modalId);
  if (m) m.classList.add('open');
}
function closeModal(modalId) {
  var m = document.getElementById(modalId);
  if (m) m.classList.remove('open');
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// Confirm helper: attach to any element with data-confirm-form
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-confirm-form]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var formId = btn.getAttribute('data-confirm-form');
      var name   = btn.getAttribute('data-confirm-name');

      var msgEl = document.getElementById('confirmModalMsg');
      if (msgEl) {
        msgEl.textContent = name
          ? 'Are you sure you want to delete "' + name + '"? This cannot be undone.'
          : 'This action cannot be undone. The time slot will be released back for other patients.';
      }

      openModal('confirmModal');
      document.getElementById('confirmModalOk').setAttribute('data-target-form', formId);
    });
  });

  var confirmOk = document.getElementById('confirmModalOk');
  if (confirmOk) {
    confirmOk.addEventListener('click', function () {
      var formId = confirmOk.getAttribute('data-target-form');
      var form = document.getElementById(formId);
      if (form) form.submit();
      closeModal('confirmModal');
    });
  }
});
