/* ZU Hub — admin page behaviour: soft card light and metric count-up. */
(function () {
    'use strict';
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Soft light that follows the pointer across glass cards. */
    if (!reduce) {
        document.addEventListener('pointermove', function (e) {
            var card = e.target.closest && e.target.closest('.glass-card');
            if (!card) return;
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            card.style.setProperty('--my', (e.clientY - r.top) + 'px');
        }, { passive: true });
    }

    /* Count-up for metric numbers: <strong data-count="12">12</strong> */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduce || target < 2) { el.textContent = target.toLocaleString(); return; }
        var start = null, dur = 900;
        function step(t) {
            if (start === null) start = t;
            var p = Math.min((t - start) / dur, 1), eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString();
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });

    /* Times are stored in UTC; show them in the visitor's own timezone: <time data-local datetime="ISO"> */
    document.querySelectorAll('time[data-local]').forEach(function (t) {
        var d = new Date(t.getAttribute('datetime'));
        if (isNaN(d)) return;
        t.textContent = d.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    });
})();
