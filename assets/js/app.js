/**
 * app.js
 * Shared front-end behaviour: sidebar toggle, live clock,
 * delete confirmations, and live SLA countdown timers.
 */
(function () {
  'use strict';

  // ---- Sidebar toggle (mobile) ----------------------------------------
  var sidebar = document.getElementById('appSidebar');
  var toggleBtn = document.getElementById('sidebarToggle');
  var backdrop = document.getElementById('sidebarBackdrop');

  function closeSidebar() {
    if (sidebar) sidebar.classList.remove('show');
    if (backdrop) backdrop.classList.remove('show');
  }

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      if (backdrop) backdrop.classList.toggle('show');
    });
  }
  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  // ---- Live clock -------------------------------------------------------
  var clockEl = document.getElementById('liveClock');
  function tickClock() {
    if (!clockEl) return;
    var now = new Date();
    clockEl.textContent = now.toLocaleString();
  }
  tickClock();
  setInterval(tickClock, 1000);

  // ---- Delete confirmation dialogs --------------------------------------
  document.querySelectorAll('.confirm-delete').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var message = form.getAttribute('data-message') || 'Are you sure you want to delete this item?';
      if (!window.confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // ---- Live SLA countdown timers -----------------------------------------
  function formatDuration(totalSeconds) {
    totalSeconds = Math.floor(Math.abs(totalSeconds));
    var days = Math.floor(totalSeconds / 86400);
    totalSeconds %= 86400;
    var hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    var minutes = Math.floor(totalSeconds / 60);

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    if (days > 0) {
      return days + 'd ' + pad(hours) + 'h ' + pad(minutes) + 'm';
    }
    return pad(hours) + 'h ' + pad(minutes) + 'm';
  }

  var COLOR_CLASSES = ['success', 'warning', 'danger', 'secondary'];

  function updateTimers() {
    document.querySelectorAll('.sla-timer').forEach(function (el) {
      var due = parseInt(el.getAttribute('data-due'), 10) * 1000;
      var warning = parseInt(el.getAttribute('data-warning') || '0', 10) * 1000;
      if (!due) return;

      var now = Date.now();
      var diff = due - now;
      var color, label;

      if (diff < 0) {
        color = 'danger';
        label = 'Overdue ' + formatDuration(diff / 1000);
      } else if (warning > 0 && diff <= warning) {
        color = 'warning';
        label = 'Remaining ' + formatDuration(diff / 1000);
      } else {
        color = 'success';
        label = 'Remaining ' + formatDuration(diff / 1000);
      }

      el.textContent = label;

      var isBadge = el.classList.contains('badge');
      COLOR_CLASSES.forEach(function (c) {
        el.classList.remove(isBadge ? 'bg-' + c : 'text-' + c);
      });
      el.classList.add(isBadge ? 'bg-' + color : 'text-' + color);
    });
  }

  if (document.querySelector('.sla-timer')) {
    updateTimers();
    setInterval(updateTimers, 1000);
  }
})();
