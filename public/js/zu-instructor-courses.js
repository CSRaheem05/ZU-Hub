/* ==========================================================================
   ZU Hub — Instructor "My Courses"
   - live search (title / subject) + course-status filter (All · Ongoing · Completed)
   - the per-card "more" menu: one open at a time, closes on outside click / Escape
   Deleting a course uses the shared confirm dialog (zu-modal.js); the "mark completed"
   forms are protected against double submits by [data-submit-once].
   ========================================================================== */
(function () {
    'use strict';

    /* ---------------------------------------------------------- search + filter */
    var search = document.getElementById('courseSearch');
    var clear = document.getElementById('courseSearchClear');
    var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));
    var cards = Array.prototype.slice.call(document.querySelectorAll('#courseCards .iw-card'));
    var count = document.getElementById('courseResultCount');
    var empty = document.getElementById('noSearchResults');
    var status = 'all';

    function apply() {
        if (!search) return;
        var term = search.value.trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
            var show = (card.dataset.search || '').toLowerCase().indexOf(term) !== -1
                && (status === 'all' || card.dataset.status === status);
            card.hidden = !show;
            if (show) visible++;
        });

        if (count) count.textContent = visible + (visible === 1 ? ' course' : ' courses');
        if (empty) empty.hidden = visible !== 0;
        if (clear) clear.hidden = term === '';
    }

    if (search && cards.length) {
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                status = tab.getAttribute('data-filter');
                tabs.forEach(function (t) {
                    var on = t === tab;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                apply();
            });
        });

        search.addEventListener('input', apply);
        search.addEventListener('keydown', function (e) { if (e.key === 'Escape' && search.value) { search.value = ''; apply(); } });
        if (clear) clear.addEventListener('click', function () { search.value = ''; apply(); search.focus(); });

        // "/" jumps to the search box unless the user is already typing somewhere
        document.addEventListener('keydown', function (e) {
            if (e.key !== '/' || e.metaKey || e.ctrlKey || e.altKey) return;
            var t = e.target;
            if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
            e.preventDefault();
            search.focus();
        });

        apply();
    }

    /* ------------------------------------------------------------- more menus */
    var menus = Array.prototype.slice.call(document.querySelectorAll('.iw-more'));

    menus.forEach(function (menu) {
        menu.addEventListener('toggle', function () {
            if (!menu.open) return;
            menus.forEach(function (other) { if (other !== menu) other.open = false; });
            var first = menu.querySelector('.iw-more__item');
            if (first) first.focus({ preventScroll: true });
        });
    });

    document.addEventListener('pointerdown', function (e) {
        menus.forEach(function (menu) { if (menu.open && !menu.contains(e.target)) menu.open = false; });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        menus.forEach(function (menu) {
            if (!menu.open) return;
            menu.open = false;
            var summary = menu.querySelector('summary');
            if (summary) summary.focus();
        });
    });

    // arrow keys move through the items of an open menu
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        var menu = e.target.closest && e.target.closest('.iw-more[open]');
        if (!menu) return;
        e.preventDefault();
        var items = Array.prototype.slice.call(menu.querySelectorAll('.iw-more__item'));
        var i = items.indexOf(document.activeElement);
        var next = e.key === 'ArrowDown' ? (i + 1) % items.length : (i - 1 + items.length) % items.length;
        items[next].focus();
    });
})();
