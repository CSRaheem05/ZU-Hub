/* ==========================================================================
   ZU Hub — automatic announcement banner (see components/announcement-banner.blade.php)
   - tells the server which alerts were just shown, so they are announced only once per user
     (navigating to another page does not pop them up again; the bell keeps the reminder)
   - dismiss button, Escape key, and a quiet auto-hide for "Important" (Urgent stays)
   ========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('[data-zu-alerts]');
    if (!root) return;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

    /* 2) leaving */
    function dismiss(alert) {
        if (!alert || alert.classList.contains('is-leaving')) return;
        alert.classList.add('is-leaving');
        setTimeout(function () {
            alert.remove();
            if (!root.querySelector('.zu-alert')) root.remove();
        }, reduce ? 0 : 480);
    }

    root.addEventListener('click', function (e) {
        var close = e.target.closest('.zu-alert__close');
        if (close) dismiss(close.closest('.zu-alert'));
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var first = root.querySelector('.zu-alert:not(.is-leaving)');
        if (first) dismiss(first);
    });

    /* 3) "Important" fades away on its own, but waits while the user is hovering or reading it */
    root.querySelectorAll('[data-autohide]').forEach(function (alert) {
        var ms = parseInt(alert.getAttribute('data-autohide'), 10) || 16000;
        var timer = alert.querySelector('.zu-alert__timer');
        if (timer) timer.style.setProperty('--za-ms', ms + 'ms');
        var left = ms + 1000, started = 0, handle = null;
        function start() { started = Date.now(); handle = setTimeout(function () { dismiss(alert); }, left); }
        function pause() { if (handle) { clearTimeout(handle); handle = null; left -= Date.now() - started; } }
        function resume() { if (!handle && left > 0) start(); }
        alert.addEventListener('mouseenter', pause);
        alert.addEventListener('mouseleave', resume);
        alert.addEventListener('focusin', pause);
        alert.addEventListener('focusout', resume);
        start();
    });
})();
