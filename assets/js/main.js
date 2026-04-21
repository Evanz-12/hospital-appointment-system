/* main.js — Shared JS: alerts, sidebar toggle, modal, confirm */

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

// Sidebar toggle (mobile)
(function () {
  var toggle = document.getElementById('sidebarToggle');
  var sidebar = document.querySelector('.sidebar');
  if (!toggle || !sidebar) return;
  toggle.addEventListener('click', function () {
    sidebar.classList.toggle('open');
  });
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function (e) {
    if (sidebar.classList.contains('open') &&
        !sidebar.contains(e.target) &&
        e.target !== toggle) {
      sidebar.classList.remove('open');
    }
  });
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
