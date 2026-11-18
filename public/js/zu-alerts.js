/* ==========================================================================
   ZU Hub — official announcement banner (see components/announcement-banner.blade.php)
   - shows ONE announcement at a time (most urgent first); dismissing it reveals the next
   - tells the server which alerts were shown, so each is announced once per user: a refresh or
     another page does not pop it up again, while the bell keeps the reminder until it is read
   - dismissing only hides the banner; it never marks anything as read (opening it does)
   - "Important" quietly leaves after 20 s (paused while hovered or focused); "Urgent" stays until dismissed
   ========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('[data-zu-alerts]');
    if (!root) return;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var extra = parseInt(root.getAttribute('data-more'), 10) || 0;      // qualified but not rendered (beyond the first three)
    var alerts = Array.prototype.slice.call(root.querySelectorAll('.zu-alert'));

    root.classList.add('is-stacked');

    /* 1) report as seen, once the banner is actually on screen */
    function reportSeen() {
        var ids = [];
        try { ids = JSON.parse(root.getAttribute('data-ids') || '[]'); } catch (e) { ids = []; }
        if (!ids.length) return;
        fetch(root.getAttribute('data-seen-url'), {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'X-CSRF-TOKEN': root.getAttribute('data-token'), 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ ids: ids })
        }).catch(function () { /* it will simply be offered again next time */ });
    }
    setTimeout(reportSeen, 900);

    /* 2) one at a time */
    var current = null, timer = null, left = 0, started = 0;

    function waiting() { return alerts.length - 1 + extra; }

    function refreshMore() {
        if (!current) return;
        var chip = current.querySelector('[data-zu-more]');
        if (!chip) return;
        var n = waiting();
        chip.hidden = n < 1;
        if (n > 0) chip.textContent = '+' + n + ' more';
    }

    function startTimer() {
        if (!current || !current.hasAttribute('data-autohide') || left <= 0 || timer) return;
        started = Date.now();
        timer = setTimeout(function () { dismiss(current); }, left);
    }
    function pauseTimer() { if (timer) { clearTimeout(timer); timer = null; left -= Date.now() - started; } }

    function show(alert) {
        current = alert;
        alerts.forEach(function (a) { a.hidden = a !== alert; });
        left = parseInt(alert.getAttribute('data-autohide'), 10) || 0;
        timer = null;
        refreshMore();
        startTimer();
    }

    function dismiss(alert) {
        if (!alert || alert.classList.contains('is-leaving')) return;
        pauseTimer();
        alert.classList.add('is-leaving');
        setTimeout(function () {
            alerts = alerts.filter(function (a) { return a !== alert; });
            alert.remove();
            if (alerts.length) show(alerts[0]);
            else { current = null; root.remove(); document.documentElement.style.removeProperty('--zu-alert-space'); }
        }, reduce ? 0 : 260);
    }

    root.addEventListener('click', function (e) {
        var close = e.target.closest('.zu-alert__close');
        if (close) dismiss(close.closest('.zu-alert'));
    });
    root.addEventListener('mouseover', function (e) { if (current && current.contains(e.target)) pauseTimer(); });
    root.addEventListener('mouseout', function (e) { if (current && !current.contains(e.relatedTarget)) startTimer(); });
    root.addEventListener('focusin', pauseTimer);
    root.addEventListener('focusout', function (e) { if (!root.contains(e.relatedTarget)) startTimer(); });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && current && !document.documentElement.classList.contains('zu-notify-open')) dismiss(current);
    });

    if (alerts.length) show(alerts[0]);
})();
