/* ==========================================================================
   ZU Hub — Student "My courses"

   The whole library is already on the page; this script only decides what is visible (view + search), keeps the
   address (?view=&q=) in step, runs the small card menus, and keeps the Arabic text of the placeholders and
   confirmation dialogs in step with the language button. Without it the page still shows everything and every link works.
   The entrance (staggered fade-up) is pure CSS; this marks it finished (`is-ready`) so filtering never replays it.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('mc');
    if (!root) return;
    root.classList.add('js');

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var $ = function (id) { return document.getElementById(id); };
    var qa = function (sel, ctx) { return [].slice.call((ctx || root).querySelectorAll(sel)); };

    /* ------------------------------------------------ the entrance is one-time */
    function settle() { root.classList.add('is-ready'); }
    setTimeout(settle, 1900);
    root.addEventListener('pointerdown', settle, { once: true, capture: true });
    root.addEventListener('keydown', settle, { once: true, capture: true });

    /* ----------------------------------------------------- language details */
    function paintLanguage() {
        var ar = document.documentElement.lang === 'ar', lang = ar ? 'ar' : 'en';
        qa('[data-ph-en]').forEach(function (el) { el.placeholder = el.getAttribute('data-ph-' + lang); });
        qa('[data-zu-confirm][data-en-title]').forEach(function (b) {          // the confirmation dialog reads these
            b.setAttribute('data-confirm-title', b.getAttribute('data-' + lang + '-title'));
            b.setAttribute('data-confirm-message', b.getAttribute('data-' + lang + '-message'));
            b.setAttribute('data-confirm-label', b.getAttribute('data-' + lang + '-label'));
        });
    }
    paintLanguage();
    new MutationObserver(paintLanguage).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });

    var list = $('mcOngoing');
    if (!list) return;          // the empty library has nothing to filter

    /* ----------------------------------------------------------------- state */
    var items = qa('.mc-row, .mc-card');
    var baseUrl = root.getAttribute('data-url');
    var state = read(root.getAttribute('data-view'), root.getAttribute('data-q'));
    var first = true;

    function read(view, q) { return { view: view === 'ongoing' || view === 'completed' ? view : '', q: (q || '').trim() }; }
    function fromLocation() { var p = new URLSearchParams(location.search); return read(p.get('view'), p.get('q')); }
    function urlFor(st) {
        var p = new URLSearchParams();
        if (st.view) p.set('view', st.view);
        if (st.q) p.set('q', st.q);
        var s = p.toString();
        return baseUrl + (s ? '?' + s : '');
    }
    function withState(over) { var s = {}; for (var k in state) s[k] = state[k]; for (var o in over) s[o] = over[o]; return s; }
    function hit(el, q) { return !q || el.getAttribute('data-search').indexOf(q.toLowerCase()) > -1; }

    function render() {
        var counts = { ongoing: 0, completed: 0 }, entering = 0;
        items.forEach(function (el) {
            var show = hit(el, state.q), kind = el.getAttribute('data-kind'), was = !el.hidden;
            el.hidden = !show;
            el.classList.remove('is-entering');
            if (show) {
                counts[kind]++;
                if (!was && !first && !reduce) {
                    el.style.setProperty('--d', Math.min(entering++, 8) * 24 + 'ms');
                    void el.offsetWidth;
                    el.classList.add('is-entering');
                }
            }
        });

        var showOngoing = state.view !== 'completed' && !(state.q && counts.ongoing === 0);
        var showDone = state.view !== 'ongoing' && !(state.q && counts.completed === 0);
        $('mcOngoing').hidden = !showOngoing; $('mcDone').hidden = !showDone;
        $('mcOngoingCount').textContent = counts.ongoing; $('mcDoneCount').textContent = counts.completed;
        $('mcOngoingNone').hidden = counts.ongoing > 0; $('mcDoneNone').hidden = counts.completed > 0;
        $('mcEmpty').hidden = showOngoing || showDone;

        qa('.mc-seg__item').forEach(function (a) {
            var key = a.getAttribute('data-view');
            a.querySelector('b').textContent = key === 'ongoing' ? counts.ongoing : key === 'completed' ? counts.completed : counts.ongoing + counts.completed;
            var on = state.view === key;
            a.classList.toggle('is-active', on);
            if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
        });
        placeThumb();

        var input = $('mcSearch');
        if (document.activeElement !== input) input.value = state.q;
        $('mcSearchClear').hidden = state.q === '';
        first = false;
    }

    function placeThumb() {
        var seg = $('mcSeg'), on = seg.querySelector('.is-active'), thumb = seg.querySelector('.mc-seg__thumb');
        if (!on || !on.offsetWidth) return;
        thumb.style.width = on.offsetWidth + 'px';
        thumb.style.transform = 'translateX(' + on.offsetLeft + 'px)';
    }

    function go(next, mode) {
        settle();
        state = read(next.view, next.q);
        render();
        if (mode === 'push') history.pushState(state, '', urlFor(state));
        else if (mode === 'replace') history.replaceState(state, '', urlFor(state));
    }

    root.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest('a');
        if (!a || !root.contains(a)) return;
        if (a.classList.contains('mc-seg__item')) { e.preventDefault(); go(withState({ view: a.getAttribute('data-view') }), 'push'); }
        else if (a.id === 'mcClear') { e.preventDefault(); $('mcSearch').value = ''; go(withState({ q: '' }), 'replace'); }
    });
    window.addEventListener('popstate', function () { settle(); state = fromLocation(); render(); });

    /* ---------------------------------------------------------------- search */
    var timer = 0, input = $('mcSearch');
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { go(withState({ q: input.value.trim() }), 'replace'); }, 110);
    });
    $('mcSearchForm').addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); go(withState({ q: input.value.trim() }), 'replace'); input.blur(); });
    $('mcSearchClear').addEventListener('click', function () { input.value = ''; go(withState({ q: '' }), 'replace'); input.focus(); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape' && input.value) { e.preventDefault(); input.value = ''; go(withState({ q: '' }), 'replace'); } });

    /* ------------------------------------------------------ the card menus */
    function menus() { return qa('details.mc-more'); }
    root.addEventListener('toggle', function (e) {                       // `toggle` does not bubble: catch it on the way down
        var d = e.target;
        if (d.matches && d.matches('details.mc-more') && d.open) menus().forEach(function (o) { if (o !== d) o.open = false; });
    }, true);
    document.addEventListener('click', function (e) { menus().forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; }); });
    root.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var d = e.target.closest && e.target.closest('details.mc-more');
        if (d && d.open) { e.preventDefault(); d.open = false; d.querySelector('summary').focus(); }
    });
    root.addEventListener('focusout', function (e) {
        var d = e.target.closest && e.target.closest('details.mc-more');
        if (d && d.open && e.relatedTarget && !d.contains(e.relatedTarget)) d.open = false;
    });

    /* ----------------------------------------------------------------- start */
    render();
    if (window.ResizeObserver) new ResizeObserver(placeThumb).observe($('mcSeg')); else window.addEventListener('resize', placeThumb);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeThumb);
    history.replaceState(state, '', urlFor(state));
})();
