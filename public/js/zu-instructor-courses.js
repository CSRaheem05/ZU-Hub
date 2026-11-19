/* ==========================================================================
   ZU Hub — Instructor "My Courses" (/instructor/my-courses)

   Every course is server-rendered and fully usable without JavaScript. This script adds:
     · live search (title / subject / description / topic / college) + status switch, with FLIP filtering
     · the Details modal (cover grows out of its card, previous / next step through the visible courses)
     · the per-card "more" menu: one open at a time, closes on outside click / Escape, arrow keys inside
     · English / Arabic (RTL), sharing the `zu-language` preference with the course catalog
   Deleting a course uses the shared confirm dialog (zu-modal.js); [data-submit-once] guards double submits.
   Everything respects prefers-reduced-motion.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('iwx');
    if (!root) return;
    root.classList.add('js');

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var EASE = 'cubic-bezier(.16, 1, .3, 1)';
    var $ = function (id) { return document.getElementById(id); };
    var qa = function (sel, ctx) { return [].slice.call((ctx || root).querySelectorAll(sel)); };
    var esc = function (t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; };

    var cards = qa('.iwx-card');

    /* ================================================================ language */
    var lang = 'en';
    try { lang = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en'; } catch (_) { }
    var LANG_BUTTON = { en: 'العربية', ar: 'English' };

    function courses(n) {
        if (lang === 'en') return n === 1 ? 'course' : 'courses';
        return n === 1 ? 'دورة' : n === 2 ? 'دورتان' : (n >= 3 && n <= 10) ? 'دورات' : 'دورة';
    }

    function translate(scope) {
        [].forEach.call(scope.querySelectorAll('[data-en][data-ar]'), function (el) { el.textContent = el.getAttribute('data-' + lang); });
        [].forEach.call(scope.querySelectorAll('[data-aria-en]'), function (el) { el.setAttribute('aria-label', el.getAttribute('data-aria-' + lang) || el.getAttribute('data-aria-en')); });
        [].forEach.call(scope.querySelectorAll('[data-ph-en]'), function (el) { el.setAttribute('placeholder', el.getAttribute('data-ph-' + lang) || el.getAttribute('data-ph-en')); });
        // the shared confirm dialog reads data-confirm-*: keep the English originals, swap in the Arabic text
        [].forEach.call(scope.querySelectorAll('[data-confirm-ar]'), function (b) {
            if (!b.hasAttribute('data-confirm-en')) b.setAttribute('data-confirm-en', [b.getAttribute('data-confirm-title'), b.getAttribute('data-confirm-message'), b.getAttribute('data-confirm-label')].join('|'));
            var parts = b.getAttribute('data-confirm-' + lang).split('|');
            b.setAttribute('data-confirm-title', parts[0]);
            b.setAttribute('data-confirm-message', parts[1]);
            b.setAttribute('data-confirm-label', parts[2]);
        });
    }

    var roleEl = document.querySelector('[data-role]');
    var role = roleEl ? (roleEl.getAttribute('data-original-role') || roleEl.textContent.trim().toLowerCase()) : '';
    if (roleEl) roleEl.setAttribute('data-original-role', role);
    var ROLES = { student: ['Student', 'طالب'], instructor: ['Instructor', 'دكتور'], manager: ['Management', 'إدارة'] };

    function applyLanguage() {
        var h = document.documentElement;
        h.lang = lang; h.dir = lang === 'ar' ? 'rtl' : 'ltr';
        h.classList.toggle('cx-ar', lang === 'ar');
        translate(root);
        var btn = $('languageButton'); if (btn) btn.textContent = LANG_BUTTON[lang];
        if (roleEl && ROLES[role]) roleEl.textContent = ROLES[role][lang === 'ar' ? 1 : 0];
        if ($('iwxCountLabel')) $('iwxCountLabel').textContent = courses(parseInt($('iwxCount').textContent, 10) || 0);
        root.classList.add('is-lang');
    }

    var langBtn = $('languageButton');
    if (langBtn) langBtn.addEventListener('click', function () {
        lang = lang === 'en' ? 'ar' : 'en';
        try { localStorage.setItem('zu-language', lang); } catch (_) { }
        var swap = function () { applyLanguage(); if (current) fillPreview(current, false); placeThumb(); };
        if (reduce || !root.animate) { swap(); return; }
        var done = false, out = null, run = function () {
            if (done) return; done = true;
            if (out) out.cancel();                                              // drop the faded-out end state
            swap();
            root.animate([{ opacity: 0, filter: 'blur(6px)', transform: 'translateY(6px)' }, { opacity: 1, filter: 'blur(0)', transform: 'none' }], { duration: 420, easing: EASE });
        };
        out = root.animate([{ opacity: 1 }, { opacity: 0, filter: 'blur(6px)' }], { duration: 160, easing: 'ease-in', fill: 'forwards' });
        out.onfinish = run;
        setTimeout(run, 220);                                                   // a throttled tab never stays blank
    });

    /* ================================================================== filter */
    var input = $('iwxSearch'), seg = $('iwxSeg');
    var state = { status: '', q: '' }, first = true;

    function matches(card, st) {
        return (!st.status || card.getAttribute('data-status') === st.status)
            && (!st.q || (card.getAttribute('data-search') || '').indexOf(st.q.toLowerCase()) > -1);
    }

    /* search highlight in titles (text only, never HTML from the page) */
    function highlight(el) {
        if (!el) return;
        var text = el.getAttribute('data-text');
        if (text === null) { text = el.textContent; el.setAttribute('data-text', text); }
        var q = state.q.toLowerCase(), i = q ? text.toLowerCase().indexOf(q) : -1;
        if (i < 0) { if (el.querySelector('mark')) el.textContent = text; return; }
        el.innerHTML = esc(text.slice(0, i)) + '<mark>' + esc(text.slice(i, i + q.length)) + '</mark>' + esc(text.slice(i + q.length));
    }

    function render() {
        if (!input) return;
        // FLIP: remember where the visible cards were, apply, then glide them from there; newcomers rise in
        var animate = !first && !reduce && !!root.animate;
        var before = new Map();
        if (animate) cards.forEach(function (c) { if (!c.hidden) before.set(c, c.getBoundingClientRect()); });

        var shown = 0, entering = 0;
        cards.forEach(function (card) {
            var show = matches(card, state), was = !card.hidden;
            card.hidden = !show;
            card.classList.remove('is-entering');
            if (show) {
                shown++;
                if (!was && animate) {
                    card.style.setProperty('--d', Math.min(entering++, 8) * 50 + 'ms');
                    void card.offsetWidth;
                    card.classList.add('is-entering');
                }
            }
            highlight(card.querySelector('[data-title]'));
        });
        if (animate) before.forEach(function (r, card) {
            if (card.hidden) return;
            var now = card.getBoundingClientRect(), dx = r.left - now.left, dy = r.top - now.top;
            if (Math.abs(dx) > 1 || Math.abs(dy) > 1) card.animate([{ transform: 'translate(' + dx + 'px,' + dy + 'px)' }, { transform: 'none' }], { duration: 650, easing: EASE });
        });

        var cnt = $('iwxCount'), old = parseInt(cnt.textContent, 10);
        cnt.textContent = shown;
        if (animate && old !== shown) cnt.animate([{ transform: 'translateY(' + (shown > old ? 50 : -50) + '%)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 380, easing: EASE });
        $('iwxCountLabel').textContent = courses(shown);

        // status counts follow the search
        qa('.cx-seg__item', seg).forEach(function (b) {
            var key = b.getAttribute('data-status');
            b.querySelector('b').textContent = cards.filter(function (c) { return matches(c, { status: key, q: state.q }); }).length;
            var on = state.status === key;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        placeThumb();

        $('iwxSearchClear').hidden = state.q === '';
        $('iwxReset').hidden = !state.q && !state.status;
        $('iwxEmpty').hidden = shown > 0;
        $('iwxGrid').hidden = shown === 0;
        if (current && current.hidden) closePreview(); else syncSteps();
        first = false;
    }

    function go(over) {
        settle();
        for (var k in over) state[k] = over[k];
        render();
    }

    /* the white thumb behind the active status */
    var thumb = null;
    if (seg) { thumb = document.createElement('span'); thumb.className = 'cx-seg__thumb'; thumb.setAttribute('aria-hidden', 'true'); seg.insertBefore(thumb, seg.firstChild); }
    function placeThumb() {
        if (!thumb) return;
        var on = seg.querySelector('.is-active');
        if (!on || !on.offsetWidth) return;
        var x = document.documentElement.dir === 'rtl' ? -(seg.clientWidth - on.offsetLeft - on.offsetWidth) : on.offsetLeft;
        thumb.style.width = on.offsetWidth + 'px';
        thumb.style.transform = 'translateX(' + x + 'px)';
    }

    if (input) {
        var timer = 0;
        input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { go({ q: input.value.trim() }); }, 100); });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && input.value) { e.preventDefault(); input.value = ''; go({ q: '' }); }
            if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); go({ q: input.value.trim() }); }
        });
        $('iwxSearchClear').addEventListener('click', function () { input.value = ''; go({ q: '' }); input.focus(); });
        seg.addEventListener('click', function (e) { var b = e.target.closest('[data-status]'); if (b) go({ status: b.getAttribute('data-status') }); });
        var reset = function () { input.value = ''; go({ q: '', status: '' }); input.focus({ preventScroll: true }); };
        $('iwxReset').addEventListener('click', reset);
        root.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('[data-reset]')) reset(); });

        // "/" jumps to the search from anywhere on the page
        document.addEventListener('keydown', function (e) {
            if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey || (dialog && dialog.open)) return;
            var t = e.target, tag = t && t.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (t && t.isContentEditable)) return;
            e.preventDefault();
            input.focus();
            input.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
        });
    }

    /* ================================================================ Details */
    var dialog = $('iwxPreview'), panel = $('iwxPreviewPanel'), frame = $('iwxStageFrame');
    var current = null, opener = null, swapTimer = 0, openTimer = 0;
    var canDialog = !!dialog && typeof dialog.showModal === 'function';
    if (!canDialog) qa('.iwx-peek').forEach(function (b) { b.hidden = true; });

    function visibleCards() { return cards.filter(function (c) { return !c.hidden; }); }
    function detailFor(card) {
        var frag = card.querySelector('template.cx-card__detail').content.cloneNode(true);
        translate(frag);
        var title = frag.querySelector('.cx-dt__title'); if (title) title.setAttribute('data-text', title.textContent);
        return frag;
    }
    function fillPreview(card, animate) {
        clearTimeout(swapTimer);
        var put = function () {
            frame.classList.remove('is-swap-out', 'is-swap-in');
            frame.innerHTML = '';
            frame.appendChild(detailFor(card));
            highlight(frame.querySelector('.cx-dt__title'));
            panel.scrollTop = 0;
            if (animate) { void frame.offsetWidth; frame.classList.add('is-swap-in'); swapTimer = setTimeout(function () { frame.classList.remove('is-swap-in'); }, 1000); }
        };
        if (!animate || reduce || !frame.firstChild) { put(); return; }
        frame.classList.remove('is-swap-in');
        frame.classList.add('is-swap-out');
        swapTimer = setTimeout(put, 150);
    }
    function syncSteps() {
        if (!current || !dialog) return;
        var list = visibleCards(), i = list.indexOf(current);
        qa('[data-step]', dialog).forEach(function (b) { b.disabled = !list[i + parseInt(b.getAttribute('data-step'), 10)]; });
    }

    function openPreview(card, from) {
        if (!canDialog) return;
        closeMenus();
        current = card; opener = from || null;
        fillPreview(card, false);
        document.documentElement.classList.add('cx-lock');
        dialog.showModal();
        syncSteps();
        var close = dialog.querySelector('[data-close]'); if (close) close.focus({ preventScroll: true });
        if (reduce || !panel.animate) return;
        clearTimeout(openTimer);
        dialog.classList.remove('is-closing');
        dialog.classList.add('is-opening');                                     // CSS staggers the information in
        openTimer = setTimeout(function () { dialog.classList.remove('is-opening'); }, 1100);

        // the panel rises, the cover grows out of the card it came from
        panel.animate([{ opacity: 0, transform: 'translateY(18px) scale(.96)' }, { opacity: 1, transform: 'none' }], { duration: 560, easing: EASE });
        var src = card.querySelector('.cx-card__media'), dst = frame.querySelector('.cx-dt__media');
        if (src && dst) {
            var a = src.getBoundingClientRect(), b = dst.getBoundingClientRect();
            if (b.width && b.height) dst.animate([
                { transform: 'translate(' + (a.left - b.left) + 'px,' + (a.top - b.top) + 'px) scale(' + (a.width / b.width) + ',' + (a.height / b.height) + ')', transformOrigin: '0 0' },
                { transform: 'none', transformOrigin: '0 0' }
            ], { duration: 620, easing: EASE });
        }
    }

    function closePreview() {
        if (!canDialog || !dialog.open) { current = null; return; }
        var closed = false;
        var done = function () {
            if (closed) return;
            closed = true;
            dialog.close();
            dialog.classList.remove('is-closing', 'is-opening');
            if (panel.getAnimations) panel.getAnimations().forEach(function (an) { an.cancel(); });
            document.documentElement.classList.remove('cx-lock');
            current = null;
            if (opener && document.contains(opener) && !opener.closest('[hidden]')) opener.focus({ preventScroll: true });
        };
        if (reduce || !panel.animate) { done(); return; }
        dialog.classList.add('is-closing');
        panel.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateY(12px) scale(.97)' }], { duration: 220, easing: 'cubic-bezier(.4, 0, 1, 1)', fill: 'forwards' }).onfinish = done;
        setTimeout(done, 260);                                                  // never left open if the animation is throttled
    }

    function step(dir) {
        var list = visibleCards(), next = list[list.indexOf(current) + dir];
        if (!next) return;
        current = next;
        opener = next.querySelector('.iwx-peek');
        fillPreview(next, true);
        syncSteps();
    }

    if (canDialog) {
        root.addEventListener('click', function (e) {
            var peek = e.target.closest && e.target.closest('.iwx-peek');
            if (peek) { e.preventDefault(); openPreview(peek.closest('.iwx-card'), peek); }
        });
        dialog.addEventListener('click', function (e) {
            if (e.target === dialog) { closePreview(); return; }                 // the backdrop
            var b = e.target.closest('[data-close], [data-step]');
            if (!b || b.disabled) return;
            if (b.hasAttribute('data-close')) closePreview();
            else step(parseInt(b.getAttribute('data-step'), 10));
        });
        dialog.addEventListener('cancel', function (e) { e.preventDefault(); closePreview(); });   // Esc
        dialog.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            var rtl = document.documentElement.dir === 'rtl', forward = (e.key === 'ArrowRight') !== rtl;
            e.preventDefault(); step(forward ? 1 : -1);
        });
        panel.addEventListener('pointermove', function (e) {                    // the cover drifts a little with the pointer
            if (reduce) return;
            var r = panel.getBoundingClientRect();
            frame.style.setProperty('--px', ((e.clientX - r.left) / r.width - .5) * -12 + 'px');
            frame.style.setProperty('--py', ((e.clientY - r.top) / r.height - .5) * -10 + 'px');
        });
    }

    /* ============================================================= more menus */
    var menus = qa('.iwx-more');
    function closeMenus(except) { menus.forEach(function (m) { if (m !== except) m.open = false; }); }

    menus.forEach(function (menu) {
        menu.addEventListener('toggle', function () {
            if (!menu.open) return;
            closeMenus(menu);
            var firstItem = menu.querySelector('.iwx-more__item');
            if (firstItem) firstItem.focus({ preventScroll: true });
        });
    });
    document.addEventListener('pointerdown', function (e) {
        menus.forEach(function (m) { if (m.open && !m.contains(e.target)) m.open = false; });
    });
    root.addEventListener('focusout', function (e) {                           // tabbing out of an open menu closes it
        var m = e.target.closest && e.target.closest('.iwx-more');
        if (m && m.open && e.relatedTarget && !m.contains(e.relatedTarget)) m.open = false;
    });
    document.addEventListener('keydown', function (e) {
        var m = e.target.closest && e.target.closest('.iwx-more[open]');
        if (!m) return;
        if (e.key === 'Escape') { e.preventDefault(); m.open = false; m.querySelector('summary').focus(); return; }
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        e.preventDefault();
        var items = qa('.iwx-more__item', m), i = items.indexOf(document.activeElement);
        items[e.key === 'ArrowDown' ? (i + 1) % items.length : (i - 1 + items.length) % items.length].focus();
    });

    /* ======================================================== the entrance is one-time */
    function settle() { root.classList.add('is-ready'); }
    setTimeout(settle, 2200);
    root.addEventListener('pointerdown', settle, { once: true, capture: true });
    root.addEventListener('keydown', settle, { once: true, capture: true });

    // the header numbers count up once (and never stay mid-count in a throttled tab)
    if (!reduce && window.requestAnimationFrame) {
        qa('[data-tick]').forEach(function (b, i) {
            var to = parseInt(b.getAttribute('data-tick'), 10) || 0, t0 = 0, delay = 200 + i * 90, done = false;
            var tick = function (t) {
                if (done) return;
                if (!t0) t0 = t;
                var p = Math.min(1, Math.max(0, (t - t0 - delay) / 900)), k = 1 - Math.pow(1 - p, 4);
                b.textContent = Math.round(to * k);
                if (p < 1) requestAnimationFrame(tick); else done = true;
            };
            requestAnimationFrame(tick);
            setTimeout(function () { done = true; b.textContent = to; }, 1800);
        });
    }

    /* ================================================================== start */
    applyLanguage();
    render();
    if (seg) {
        if (window.ResizeObserver) new ResizeObserver(placeThumb).observe(seg); else window.addEventListener('resize', placeThumb);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeThumb);
    }
})();
