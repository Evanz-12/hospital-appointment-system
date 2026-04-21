/* booking.js — Multi-step booking form logic */

/* --- Inline toast notification (replaces browser alert) --- */
function showToast(message, type) {
  var existing = document.getElementById('bookingToast');
  if (existing) existing.remove();

  var toast = document.createElement('div');
  toast.id = 'bookingToast';
  toast.style.cssText = [
    'position:fixed', 'top:76px', 'right:20px', 'z-index:9999',
    'background:' + (type === 'error' ? '#FEF2F2' : '#ECFDF5'),
    'color:'       + (type === 'error' ? '#C62828' : '#2E7D32'),
    'border:1.5px solid ' + (type === 'error' ? '#FECACA' : '#86EFAC'),
    'border-radius:8px', 'padding:14px 20px',
    'display:flex', 'align-items:center', 'gap:10px',
    'font-size:.9rem', 'font-weight:500',
    'box-shadow:0 8px 32px rgba(0,0,0,.12)',
    'max-width:360px', 'font-family:inherit',
    'animation:slideIn .25s ease'
  ].join(';');

  var icon = type === 'error' ? '✕' : '✓';
  toast.innerHTML = '<span style="font-size:1rem;font-weight:700;">' + icon + '</span> ' + message +
    '<button onclick="this.parentElement.remove()" style="background:none;border:none;margin-left:auto;cursor:pointer;font-size:1.1rem;opacity:.6;color:inherit;">×</button>';

  document.body.appendChild(toast);
  setTimeout(function () {
    if (toast.parentElement) {
      toast.style.transition = 'opacity .4s';
      toast.style.opacity = '0';
      setTimeout(function () { toast.remove(); }, 400);
    }
  }, 4000);
}

document.addEventListener('DOMContentLoaded', function () {

  // --- Step 3: Date picker + dynamic slot loader ---
  var dateInput  = document.getElementById('appointment_date');
  var slotGrid   = document.getElementById('slotGrid');
  var slotHidden = document.getElementById('selected_slot');
  var doctorId   = document.getElementById('doctor_id_value');

  if (dateInput && slotGrid) {
    var today = new Date();
    var yyyy  = today.getFullYear();
    var mm    = String(today.getMonth() + 1).padStart(2, '0');
    var dd    = String(today.getDate()).padStart(2, '0');
    dateInput.setAttribute('min', yyyy + '-' + mm + '-' + dd);

    dateInput.addEventListener('change', function () {
      var date = dateInput.value;
      if (!date || !doctorId) return;

      slotGrid.innerHTML = '<p style="color:var(--text-muted);font-size:.85rem;"><i class="fa fa-spinner fa-spin"></i> Loading slots…</p>';
      if (slotHidden) slotHidden.value = '';

      var xhr = new XMLHttpRequest();
      xhr.open('GET', BASE_URL + '/patient/get-slots.php?doctor_id=' + doctorId.value + '&date=' + date);
      xhr.onload = function () {
        if (xhr.status === 200) {
          slotGrid.innerHTML = xhr.responseText;
          attachSlotClicks();
        }
      };
      xhr.send();
    });
  }

  function attachSlotClicks() {
    slotGrid.querySelectorAll('.slot-btn:not(.taken)').forEach(function (btn) {
      btn.addEventListener('click', function () {
        slotGrid.querySelectorAll('.slot-btn').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        if (slotHidden) slotHidden.value = btn.getAttribute('data-time');
      });
    });
  }

  // --- Step 3 form validation (styled toasts instead of browser alerts) ---
  var slotForm = document.getElementById('slotForm');
  if (slotForm) {
    slotForm.addEventListener('submit', function (e) {
      var hasDate = dateInput && dateInput.value;
      var hasSlot = slotHidden && slotHidden.value;

      if (!hasDate) {
        e.preventDefault();
        showToast('Please select a date first.', 'error');
        dateInput && dateInput.focus();
        return;
      }
      if (!hasSlot) {
        e.preventDefault();
        showToast('Please select a time slot before continuing.', 'error');
        return;
      }
    });
  }
});
