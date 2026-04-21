/* calendar.js — Doctor availability calendar */

document.addEventListener('DOMContentLoaded', function () {
  var calendarEl = document.getElementById('availCalendar');
  if (!calendarEl) return;

  var unavailDates = JSON.parse(calendarEl.getAttribute('data-unavail') || '[]');
  var doctorId     = calendarEl.getAttribute('data-doctor');

  var today    = new Date();
  var viewYear = today.getFullYear();
  var viewMonth = today.getMonth(); // 0-indexed

  renderCalendar(viewYear, viewMonth);

  document.getElementById('calPrev').addEventListener('click', function () {
    viewMonth--;
    if (viewMonth < 0) { viewMonth = 11; viewYear--; }
    renderCalendar(viewYear, viewMonth);
  });
  document.getElementById('calNext').addEventListener('click', function () {
    viewMonth++;
    if (viewMonth > 11) { viewMonth = 0; viewYear++; }
    renderCalendar(viewYear, viewMonth);
  });

  function renderCalendar(year, month) {
    var monthNames = ['January','February','March','April','May','June',
                      'July','August','September','October','November','December'];
    document.getElementById('calMonthLabel').textContent = monthNames[month] + ' ' + year;

    var grid    = document.getElementById('calGrid');
    grid.innerHTML = '';

    var firstDay = new Date(year, month, 1).getDay(); // 0=Sun
    var daysInMonth = new Date(year, month + 1, 0).getDate();

    // empty cells before first day
    for (var i = 0; i < firstDay; i++) {
      var empty = document.createElement('div');
      empty.className = 'avail-day empty';
      grid.appendChild(empty);
    }

    for (var d = 1; d <= daysInMonth; d++) {
      var mm  = String(month + 1).padStart(2, '0');
      var dd  = String(d).padStart(2, '0');
      var dateStr = year + '-' + mm + '-' + dd;

      var cell = document.createElement('div');
      cell.className = 'avail-day';
      cell.textContent = d;

      var cellDate = new Date(year, month, d);
      var todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());

      if (cellDate < todayDate) {
        cell.classList.add('past');
      } else if (cellDate.getTime() === todayDate.getTime()) {
        cell.classList.add('today');
      }

      if (unavailDates.indexOf(dateStr) !== -1) {
        cell.classList.add('unavail');
      }

      if (!cell.classList.contains('past')) {
        cell.addEventListener('click', function (dateStr, cell) {
          return function () { toggleUnavail(dateStr, cell); };
        }(dateStr, cell));
      }

      grid.appendChild(cell);
    }
  }

  function toggleUnavail(dateStr, cell) {
    var idx = unavailDates.indexOf(dateStr);
    var isRemoving = idx !== -1;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', BASE_URL + '/doctor/availability-toggle.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      if (xhr.status === 200) {
        var res = JSON.parse(xhr.responseText);
        if (res.success) {
          if (isRemoving) {
            unavailDates.splice(idx, 1);
            cell.classList.remove('unavail');
          } else {
            unavailDates.push(dateStr);
            cell.classList.add('unavail');
          }
        }
      }
    };
    var csrfToken = document.getElementById('csrf_token_cal').value;
    xhr.send('date=' + encodeURIComponent(dateStr) +
             '&action=' + (isRemoving ? 'remove' : 'add') +
             '&csrf_token=' + encodeURIComponent(csrfToken));
  }
});
