/* ==========================================================================
   ZU Hub — Student course page
   Every section starts closed each time the page opens (the server renders them closed; nothing is remembered).
   Sections open and close independently, several can be open together, and Expand all / Collapse all act on every section.
   A closed section also stops any video playing inside it and does not load its embedded players until it is opened.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('clRoot');
    if (!root) return;

    var sections = [].slice.call(root.querySelectorAll('.cl-section'));
    var openAll = root.querySelector('[data-cl-all="open"]');
    var closeAll = root.querySelector('[data-cl-all="close"]');

    // older versions remembered which sections were open; that is gone, so drop the leftovers
    try {
        Object.keys(localStorage).forEach(function (k) { if (k.indexOf('zu-cl-collapsed:') === 0) localStorage.removeItem(k); });
    } catch (e) { /* storage unavailable: nothing to clean up */ }

    // a closed section must not keep playing, and its embeds wait until it is opened
    function silence(sec) {
        [].forEach.call(sec.querySelectorAll('video'), function (v) { try { v.pause(); } catch (e) { /* ok */ } });
        [].forEach.call(sec.querySelectorAll('iframe'), function (f) {
            if (f.src && f.src !== 'about:blank') { f.setAttribute('data-src', f.src); f.src = 'about:blank'; }
        });
    }
    function wake(sec) {
        [].forEach.call(sec.querySelectorAll('iframe[data-src]'), function (f) { f.src = f.getAttribute('data-src'); f.removeAttribute('data-src'); });
    }

    function syncTools() {
        var closed = sections.filter(function (s) { return s.classList.contains('is-collapsed'); }).length;
        if (openAll) openAll.disabled = closed === 0;                     // everything is already open
        if (closeAll) closeAll.disabled = closed === sections.length;     // everything is already closed
    }

    function set(sec, collapsed) {
        sec.classList.toggle('is-collapsed', collapsed);
        sec.querySelector('.cl-section__toggle').setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        var panel = sec.querySelector('.cl-section__panel');
        if (panel) panel.inert = collapsed;                               // keeps closed content out of the tab order and away from screen readers
        if (collapsed) silence(sec); else wake(sec);
        syncTools();
    }

    // start: everything closed (the markup already is; this wires up aria, inert and the embeds)
    sections.forEach(function (s) { set(s, true); });

    root.addEventListener('click', function (e) {
        var toggle = e.target.closest('.cl-section__toggle');
        if (toggle) { var s = toggle.closest('.cl-section'); set(s, !s.classList.contains('is-collapsed')); return; }

        var all = e.target.closest('[data-cl-all]');
        if (all && !all.disabled) {
            var close = all.getAttribute('data-cl-all') === 'close';
            sections.forEach(function (s) { set(s, close); });
        }
    });

    // arriving on a link to one item (#lesson-...) opens just its section, without animating, so the scroll lands right
    if (/^#lesson-/.test(location.hash)) {
        var target = document.getElementById(location.hash.slice(1));
        var host = target && target.closest('.cl-section');
        if (host && host.classList.contains('is-collapsed')) {
            host.classList.add('is-instant');
            set(host, false);
            target.scrollIntoView();
            requestAnimationFrame(function () { requestAnimationFrame(function () { host.classList.remove('is-instant'); }); });
        }
    }
})();
