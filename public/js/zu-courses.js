/* ==========================================================================
   ZU Hub — course discovery (/courses)

   Every approved course is already on the page (server-rendered, works without JavaScript). This script:
     · filters instantly (college -> topic -> status -> search) with the same ?college=&topic=&status=&q= address
       the server understands, so links, bookmarks, the back button and a no-JS page all agree
     · animates filtering with FLIP (cards glide to their new place, newcomers rise in) and keeps the active-filter chips
     · opens Details (description, weekly lectures, course facts) as a modal: the cover grows out of the card,
       previous / next step through the visible courses
     · switches English / Arabic (RTL), sharing the `zu-language` preference with My Courses
   Everything respects prefers-reduced-motion.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('cx');
    if (!root) return;
    root.classList.add('js');

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var EASE = 'cubic-bezier(.16, 1, .3, 1)';
    var $ = function (id) { return document.getElementById(id); };
    var qa = function (sel, ctx) { return [].slice.call((ctx || root).querySelectorAll(sel)); };
    var data = { topics: {}, colleges: {} };
    try { data = JSON.parse($('cxData').textContent); } catch (e) { /* the page still works from the server markup */ }
    if (!data.topics || typeof data.topics !== 'object') data.topics = {};

    var cards = qa('.cx-card');
    var baseUrl = root.getAttribute('data-url');

    /* ================================================================ language */
    var lang = 'en';
    try { lang = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en'; } catch (_) { }

    var DICT = {
        en: {
            allColleges: 'All colleges', allTopics: 'All topics', noTopics: 'No topics yet',
            college: 'College', topic: 'Topic', chooseCollege: 'Choose a college', chooseTopic: 'Choose a topic',
            searchTopics: 'Search topics', noTopicMatch: 'No topic matches your search.',
            hintOngoing: 'enroll to open the lessons', hintCompleted: 'watch any time, save to My Courses',
            tryAnother: 'Try another college or topic, or loosen the search.', clearSearch: 'Clear search', resetAll: 'Reset all',
            ongoing: 'Ongoing', completed: 'Completed', remove: 'Remove filter', langButton: 'العربية'
        },
        ar: {
            allColleges: 'كل الكليات', allTopics: 'كل المواضيع', noTopics: 'لا مواضيع بعد',
            college: 'الكلية', topic: 'الموضوع', chooseCollege: 'اختر كلية', chooseTopic: 'اختر موضوعاً',
            searchTopics: 'ابحث عن موضوع', noTopicMatch: 'لا يوجد موضوع مطابق لبحثك.',
            hintOngoing: 'سجّل لفتح الدروس', hintCompleted: 'شاهدها في أي وقت واحفظها في دوراتي',
            tryAnother: 'جرّب كلية أو موضوعاً آخر، أو خفّف البحث.', clearSearch: 'مسح البحث', resetAll: 'إعادة الضبط',
            ongoing: 'جارية', completed: 'مكتملة', remove: 'إزالة عامل التصفية', langButton: 'English'
        }
    };
    function T(key) { return (DICT[lang] && DICT[lang][key]) || DICT.en[key] || key; }
    function courses(n) {
        if (lang === 'en') return n === 1 ? 'course' : 'courses';
        return n === 1 ? 'دورة' : n === 2 ? 'دورتان' : (n >= 3 && n <= 10) ? 'دورات' : 'دورة';
    }

    function translate(scope) {
        [].forEach.call(scope.querySelectorAll('[data-en][data-ar]'), function (el) { el.textContent = el.getAttribute('data-' + lang); });
        [].forEach.call(scope.querySelectorAll('[data-aria-en]'), function (el) { el.setAttribute('aria-label', el.getAttribute('data-aria-' + lang) || el.getAttribute('data-aria-en')); });
        [].forEach.call(scope.querySelectorAll('[data-ph-en]'), function (el) { el.setAttribute('placeholder', el.getAttribute('data-ph-' + lang) || el.getAttribute('data-ph-en')); });
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
        var btn = $('languageButton'); if (btn) btn.textContent = T('langButton');
        if (roleEl && ROLES[role]) roleEl.textContent = ROLES[role][lang === 'ar' ? 1 : 0];
        root.classList.add('is-lang');
    }

    var langBtn = $('languageButton');
    if (langBtn) langBtn.addEventListener('click', function () {
        lang = lang === 'en' ? 'ar' : 'en';
        try { localStorage.setItem('zu-language', lang); } catch (_) { }
        var swap = function () {
            applyLanguage();
            shownCollege = null;                 // rebuild the topic picker in the new language
            render(true);
            if (current) fillPreview(current, false);
            placeThumb();
        };
        if (reduce || !root.animate) { swap(); return; }
        root.animate([{ opacity: 1 }, { opacity: 0, filter: 'blur(6px)' }], { duration: 160, easing: 'ease-in' }).onfinish = function () {
            swap();
            root.animate([{ opacity: 0, filter: 'blur(6px)', transform: 'translateY(6px)' }, { opacity: 1, filter: 'blur(0)', transform: 'none' }], { duration: 420, easing: EASE });
        };
    });

    /* =================================================================== state */
    function topicsOf(college) { return college ? (data.topics[college] || []) : []; }
    function read(college, topic, status, q) {
        college = college || ''; topic = topic || '';
        if (topic && !college) {                                           // an old topic-only link: the topic knows its college
            Object.keys(data.topics).forEach(function (code) { if ((data.topics[code] || []).some(function (t) { return t.id === topic; })) college = code; });
        }
        if (!college || !topicsOf(college).some(function (t) { return t.id === topic; })) topic = '';   // a topic only means something inside its college
        return { college: college, topic: topic, status: status === 'ongoing' || status === 'completed' ? status : '', q: (q || '').trim() };
    }
    var state = read(root.getAttribute('data-college'), root.getAttribute('data-topic'), root.getAttribute('data-status'), root.getAttribute('data-q'));

    function fromLocation() {
        var p = new URLSearchParams(location.search);
        return read(p.get('college'), p.get('topic'), p.get('status'), p.get('q'));
    }
    function urlFor(st) {
        var p = new URLSearchParams();
        ['college', 'topic', 'status', 'q'].forEach(function (k) { if (st[k]) p.set(k, st[k]); });
        var s = p.toString();
        return baseUrl + (s ? '?' + s : '');
    }
    function withState(over) {
        var s = {}, k; for (k in state) s[k] = state[k]; for (k in over) s[k] = over[k];
        return s;
    }
    function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    /* ---------------------------------------------------------------- counting */
    function matches(card, st, skip) {                                  // skip: a filter (or a list of them) to leave out
        var q = st.q.toLowerCase(), no = function (k) { return skip === k || (typeof skip !== 'string' && skip && skip.indexOf(k) > -1); };
        return (no('college') || !st.college || card.getAttribute('data-college') === st.college)
            && (no('topic') || !st.topic || card.getAttribute('data-topic') === st.topic)
            && (no('status') || !st.status || card.getAttribute('data-status') === st.status)
            && (!q || card.getAttribute('data-search').indexOf(q) > -1);
    }
    function count(st, skip, extra) {
        return cards.filter(function (c) { return matches(c, st, skip) && (!extra || extra(c)); }).length;
    }
    function visibleCards() { return cards.filter(function (c) { return !c.hidden; }); }

    /* =============================================================== rendering */
    var first = true, shownCollege = state.college, hideTimer = 0, shownChips = {};

    function render(quiet) {
        // 1) the cards — FLIP: remember where the visible ones were, apply, then glide them from there
        var animate = !first && !quiet && !reduce && !!root.animate;
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

        var cnt = $('cxCount'), old = parseInt(cnt.textContent, 10);
        cnt.textContent = shown;
        if (animate && old !== shown) cnt.animate([{ transform: 'translateY(' + (shown > old ? 50 : -50) + '%)', opacity: 0 }, { transform: 'none', opacity: 1 }], { duration: 380, easing: EASE });
        $('cxCountLabel').textContent = courses(shown);

        // 2) college picker (counts follow the status and the search)
        qa('#cxCollegeList [data-ccount]').forEach(function (b) {
            var code = b.getAttribute('data-ccount');
            var n = count(state, ['college', 'topic'], code === '' ? null : function (c) { return c.getAttribute('data-college') === code; });
            b.textContent = n;
            var a = b.closest('.cx-opt'), on = state.college === code;
            a.classList.toggle('is-zero', n === 0 && !on);
            a.classList.toggle('is-active', on);
            if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
        });
        var no = $('cxCollegeNo'), name = collegeName();
        if (state.college) { no.textContent = state.college; no.classList.add('is-set'); }
        else { no.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6"/></svg>'; no.classList.remove('is-set'); }
        var nameEl = $('cxCollegeName');
        if (nameEl.textContent !== name) {
            nameEl.textContent = name;
            if (animate) nameEl.animate([{ opacity: 0, transform: 'translateY(40%)' }, { opacity: 1, transform: 'none' }], { duration: 420, easing: EASE });
        }
        $('cxComboCollege').querySelector('summary').setAttribute('aria-label', T('college') + ': ' + name + '. ' + T('chooseCollege'));

        // 3) the topic picker appears only once a college is chosen
        syncTopics(animate);

        // 4) status switch
        qa('.cx-seg__item').forEach(function (a) {
            var key = a.getAttribute('data-status');
            a.querySelector('b').textContent = count(state, 'status', key ? function (c) { return c.getAttribute('data-status') === key; } : null);
            var on = state.status === key;
            a.classList.toggle('is-active', on);
            if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
        });
        placeThumb();

        // 5) hint + active-filter chips
        $('cxHint').textContent = state.status === 'ongoing' ? T('hintOngoing') : state.status === 'completed' ? T('hintCompleted') : '';
        renderChips(animate);

        // 6) search box
        var input = $('cxSearch');
        if (document.activeElement !== input) input.value = state.q;
        $('cxSearchClear').hidden = state.q === '';

        // 7) empty state
        $('cxEmpty').hidden = shown > 0;
        $('cxGrid').hidden = shown === 0;
        if (shown === 0) fillEmpty();
        if (current && current.hidden) closePreview();
        else syncSteps();

        first = false;
    }

    function collegeName() { return state.college ? (data.colleges[state.college] || state.college) : T('allColleges'); }
    function topicName() { var t = topicsOf(state.college).filter(function (x) { return x.id === state.topic; })[0]; return t ? t.name : ''; }

    var X = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';
    function renderChips(animate) {
        var box = $('cxChips'), reset = $('cxReset'), list = [];
        if (state.college) list.push(['college', '<bdi>' + esc(collegeName()) + '</bdi>']);
        if (state.topic) list.push(['topic', '<bdi>' + esc(topicName()) + '</bdi>']);
        if (state.status) list.push(['status', esc(T(state.status))]);
        if (state.q) list.push(['q', '“' + esc(state.q) + '”']);
        qa('.cx-chip', box).forEach(function (c) { c.remove(); });
        var next = {};
        list.forEach(function (it) {
            var key = it[0], a = document.createElement('a'), over = {};
            over[key] = ''; if (key === 'college') over.topic = '';
            a.className = 'cx-chip';
            a.setAttribute('data-chip', key);
            a.href = urlFor(withState(over));
            a.setAttribute('aria-label', T('remove') + ': ' + it[1].replace(/<[^>]+>/g, ''));
            a.innerHTML = it[1] + X;
            next[key] = it[1];
            if (animate && shownChips[key] !== it[1]) a.classList.add('is-new');
            box.insertBefore(a, reset);
        });
        shownChips = next;
        reset.hidden = list.length === 0;
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

    /* the topic picker: slides in when a college is chosen, slips away when the college is cleared */
    function syncTopics(animate) {
        var pick = $('cxPickTopic'), slot = $('cxTopicSlot'), changed = state.college !== shownCollege;
        if (state.college) {
            clearTimeout(hideTimer); pick.classList.remove('is-out');
            var wasHidden = pick.hidden, open = $('cxComboTopic') && $('cxComboTopic').open;
            if (changed || wasHidden || !open) slot.innerHTML = topicPicker(topicsOf(state.college));
            pick.hidden = false;
            if ((wasHidden || changed) && animate) { pick.classList.remove('is-in'); void pick.offsetWidth; pick.classList.add('is-in'); }
        } else if (!pick.hidden) {
            if (!animate) { pick.hidden = true; slot.innerHTML = ''; }
            else {
                pick.classList.remove('is-in'); pick.classList.add('is-out');
                hideTimer = setTimeout(function () { pick.hidden = true; pick.classList.remove('is-out'); slot.innerHTML = ''; placeThumb(); }, 180);
            }
        }
        shownCollege = state.college;
    }

    var ICON = {
        tag: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12V4h8l9 9-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/></svg>',
        chev: '<svg class="cx-combo__chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
        search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>'
    };
    function topicPicker(topics) {
        if (!topics.length) return '<p class="cx-picker__none">' + ICON.tag + '<span>' + esc(T('noTopics')) + '</span></p>';
        var cur = topics.filter(function (t) { return t.id === state.topic; })[0], label = cur ? cur.name : T('allTopics');
        var h = '<details class="cx-combo" id="cxComboTopic"><summary class="cx-combo__btn" aria-label="' + esc(T('topic') + ': ' + label + '. ' + T('chooseTopic')) + '">'
            + '<span class="cx-combo__no' + (cur ? ' is-set' : '') + '">' + ICON.tag + '</span><span class="cx-combo__txt"><small>' + esc(T('topic')) + '</small><strong dir="auto">' + esc(label) + '</strong></span>' + ICON.chev
            + '</summary><div class="cx-combo__panel">';
        if (topics.length > 8) h += '<label class="cx-combo__search">' + ICON.search + '<input type="search" placeholder="' + esc(T('searchTopics')) + '" aria-label="' + esc(T('searchTopics')) + '" autocomplete="off"></label>';
        h += '<ul class="cx-combo__list">' + opt('', T('allTopics'), count(state, 'topic'), state.topic === '');
        topics.forEach(function (t) { h += opt(t.id, t.name, count(state, 'topic', function (c) { return c.getAttribute('data-topic') === t.id; }), state.topic === t.id); });
        return h + '</ul><p class="cx-combo__none" hidden>' + esc(T('noTopicMatch')) + '</p></div></details>';
    }
    function opt(id, name, n, on) {
        return '<li><a href="' + esc(urlFor(withState({ topic: id }))) + '" class="cx-opt' + (on ? ' is-active' : '') + (n === 0 && !on ? ' is-zero' : '') + '" data-topic="' + esc(id) + '" data-name="' + esc(name.toLowerCase()) + '"' + (on ? ' aria-current="true"' : '') + '>'
            + '<span class="cx-opt__no">' + ICON.tag + '</span><span class="cx-opt__name" dir="auto">' + esc(name) + '</span><b>' + n + '</b></a></li>';
    }

    function fillEmpty() {
        if (!cards.length) return;                                        // nothing is published at all: the server text stands
        var title = $('cxEmptyTitle'), text = $('cxEmptyText'), actions = $('cxEmptyActions');
        var where = state.college ? collegeName() : '';
        var kind = state.status;
        if (lang === 'ar') {
            title.textContent = 'لا توجد دورات' + (kind ? ' ' + (kind === 'ongoing' ? 'جارية' : 'مكتملة') : '') + (where ? ' في ' + where : '') + (state.q ? ' تطابق «' + state.q + '»' : '') + ' بعد';
        } else {
            title.textContent = 'No ' + (kind ? kind + ' ' : '') + 'courses' + (where ? ' in ' + where : '') + (state.q ? ' for “' + state.q + '”' : '') + ' yet';
        }
        text.textContent = T('tryAnother');
        var html = '';
        if (state.status) {
            var other = state.status === 'ongoing' ? 'completed' : 'ongoing', n = count(withState({ status: other }));
            if (n > 0) html += '<a class="cx-cta" data-status="' + other + '" href="' + esc(urlFor(withState({ status: other }))) + '">'
                + esc(lang === 'ar' ? 'اعرض ' + n + ' ' + courses(n) + ' ' + (other === 'ongoing' ? 'جارية' : 'مكتملة') : 'Show ' + n + ' ' + other + ' ' + courses(n)) + '</a>';
        }
        if (state.q) html += '<a class="cx-ghost" data-clearq href="' + esc(urlFor(withState({ q: '' }))) + '">' + esc(T('clearSearch')) + '</a>';
        html += '<a class="cx-ghost" data-reset href="' + esc(baseUrl) + '">' + esc(T('resetAll')) + '</a>';
        actions.innerHTML = html;
    }

    /* the navy thumb behind the active status */
    var seg = $('cxSeg'), thumb = document.createElement('span');
    thumb.className = 'cx-seg__thumb'; thumb.setAttribute('aria-hidden', 'true'); seg.insertBefore(thumb, seg.firstChild);
    function placeThumb() {
        var on = seg.querySelector('.is-active');
        if (!on || !on.offsetWidth) return;
        var x = document.documentElement.dir === 'rtl' ? -(seg.clientWidth - on.offsetLeft - on.offsetWidth) : on.offsetLeft;
        thumb.style.width = on.offsetWidth + 'px';
        thumb.style.transform = 'translateX(' + x + 'px)';
    }

    /* ================================================================ preview */
    var dialog = $('cxPreview'), panel = $('cxPreviewPanel'), frame = $('cxStageFrame');
    var current = null, opener = null, swapTimer = 0, openTimer = 0;
    var canDialog = dialog && typeof dialog.showModal === 'function';
    if (!canDialog) qa('.cx-peek').forEach(function (b) { b.hidden = true; });   // very old browsers: the card already shows the essentials

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
        if (!current) return;
        var list = visibleCards(), i = list.indexOf(current);
        qa('[data-step]', dialog).forEach(function (b) {
            var to = list[i + parseInt(b.getAttribute('data-step'), 10)];
            b.disabled = !to;
        });
    }

    function openPreview(card, from) {
        if (!canDialog) return;
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

        // the cover grows out of the card it came from (a shared-element move), the panel rises around it
        panel.animate([{ opacity: 0, transform: 'translateY(18px) scale(.96)' }, { opacity: 1, transform: 'none' }], { duration: 560, easing: EASE });
        var src = card.querySelector('.cx-card__media'), dst = frame.querySelector('.cx-dt__media');
        if (src && dst) {
            var a = src.getBoundingClientRect(), b = dst.getBoundingClientRect();
            if (b.width && b.height) {
                dst.animate([
                    { transform: 'translate(' + (a.left - b.left) + 'px,' + (a.top - b.top) + 'px) scale(' + (a.width / b.width) + ',' + (a.height / b.height) + ')', transformOrigin: '0 0' },
                    { transform: 'none', transformOrigin: '0 0' }
                ], { duration: 620, easing: EASE });
            }
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
            if (panel.getAnimations) panel.getAnimations().forEach(function (an) { an.cancel(); });   // drop the faded-out end state
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
        opener = next.querySelector('.cx-peek');
        fillPreview(next, true);
        syncSteps();
    }

    if (canDialog) {
        root.addEventListener('click', function (e) {
            var peek = e.target.closest && e.target.closest('.cx-peek');
            if (peek) { e.preventDefault(); openPreview(peek.closest('.cx-card'), peek); }
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
            if (e.target.closest && e.target.closest('input, textarea')) return;
            var rtl = document.documentElement.dir === 'rtl', forward = (e.key === 'ArrowRight') !== rtl;
            e.preventDefault(); step(forward ? 1 : -1);
        });
        // the cover drifts a little with the pointer: depth without decoration
        panel.addEventListener('pointermove', function (e) {
            if (reduce) return;
            var r = panel.getBoundingClientRect();
            frame.style.setProperty('--px', ((e.clientX - r.left) / r.width - .5) * -12 + 'px');
            frame.style.setProperty('--py', ((e.clientY - r.top) / r.height - .5) * -10 + 'px');
        });
    }

    /* ======================================================== the entrance is one-time */
    function settle() { root.classList.add('is-ready'); }
    setTimeout(settle, 2200);
    root.addEventListener('pointerdown', settle, { once: true, capture: true });
    root.addEventListener('keydown', settle, { once: true, capture: true });

    // the tallies count up once (and never stay mid-count in a throttled tab)
    if (!reduce && window.requestAnimationFrame) {
        qa('[data-tick]').forEach(function (b, i) {
            var to = parseInt(b.getAttribute('data-tick'), 10) || 0, t0 = 0, delay = 250 + i * 90, done = false;
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

    /* ================================================================ pickers */
    function combos() { return qa('details.cx-combo'); }
    function visibleOpts(d) { return qa('.cx-opt', d).filter(function (a) { return !a.closest('li').hidden; }); }
    function closeCombo(d, refocus) { if (!d.open) return; d.open = false; if (refocus) d.querySelector('summary').focus(); }

    root.addEventListener('toggle', function (e) {                     // `toggle` does not bubble, so listen while it travels down
        var d = e.target;
        if (!d.matches || !d.matches('details.cx-combo') || !d.open) return;
        combos().forEach(function (o) { if (o !== d) o.open = false; });
        var input = d.querySelector('.cx-combo__search input'), on = d.querySelector('.cx-opt.is-active');
        if (on) { var list = d.querySelector('.cx-combo__list'); list.scrollTop = Math.max(0, on.closest('li').offsetTop - list.clientHeight / 2 + 22); }
        (input || on || d.querySelector('.cx-opt')).focus({ preventScroll: true });
    }, true);

    document.addEventListener('click', function (e) { combos().forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; }); });

    root.addEventListener('focusout', function (e) {                   // tabbing out of an open list closes it
        var d = e.target.closest && e.target.closest('details.cx-combo');
        if (d && d.open && e.relatedTarget && !d.contains(e.relatedTarget)) d.open = false;
    });

    root.addEventListener('input', function (e) {                      // type to filter a list
        var input = e.target.closest && e.target.closest('.cx-combo__search input'); if (!input) return;
        var d = input.closest('details'), needle = input.value.trim().toLowerCase(), any = false;
        qa('.cx-opt', d).forEach(function (a) {
            var hit = !needle || (a.getAttribute('data-name') || '').indexOf(needle) > -1;
            a.closest('li').hidden = !hit; if (hit) any = true;
        });
        d.querySelector('.cx-combo__none').hidden = any;
    });

    root.addEventListener('keydown', function (e) {                    // keyboard inside an open list
        var d = e.target.closest && e.target.closest('details.cx-combo'); if (!d || !d.open) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); closeCombo(d, true); return; }
        var opts = visibleOpts(d), i = opts.indexOf(document.activeElement), input = d.querySelector('.cx-combo__search input');
        if (e.key === 'ArrowDown') { e.preventDefault(); (opts[i + 1] || opts[0] || document.activeElement).focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (i <= 0 && input) input.focus(); else (opts[i - 1] || opts[opts.length - 1]).focus(); }
        else if (e.key === 'Home' && i > -1) { e.preventDefault(); opts[0].focus(); }
        else if (e.key === 'End' && i > -1) { e.preventDefault(); opts[opts.length - 1].focus(); }
        else if (input && e.target !== input && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) input.focus();   // start typing anywhere
    });

    /* ============================================================= navigation */
    function go(nextState, mode) {
        settle();
        var before = state.college;
        state = read(nextState.college, nextState.topic, nextState.status, nextState.q);
        if (state.college !== before && nextState.topic === undefined) state.topic = '';
        render();
        if (mode === 'push') history.pushState(state, '', urlFor(state));
        else if (mode === 'replace') history.replaceState(state, '', urlFor(state));
    }

    root.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest('a');
        if (!a || !root.contains(a) || a.closest('dialog')) return;

        var to = null, focusAfter = null;
        if (a.id === 'cxReset' || a.hasAttribute('data-reset')) to = read('', '', '', '');
        else if (a.hasAttribute('data-clearq')) { to = withState({ q: '' }); $('cxSearch').value = ''; }
        else if (a.hasAttribute('data-chip')) {
            var k = a.getAttribute('data-chip'), over = {}; over[k] = ''; if (k === 'college') over.topic = '';
            to = withState(over); if (k === 'q') $('cxSearch').value = '';
        }
        else if (a.classList.contains('cx-opt') && a.hasAttribute('data-college')) { to = withState({ college: a.getAttribute('data-college'), topic: '' }); focusAfter = '#cxComboCollege'; }
        else if (a.classList.contains('cx-opt') && a.hasAttribute('data-topic')) { to = withState({ topic: a.getAttribute('data-topic') }); focusAfter = '#cxComboTopic'; }
        else if (a.hasAttribute('data-status')) to = withState({ status: a.getAttribute('data-status') });
        if (!to) return;

        e.preventDefault();
        var d = a.closest('details.cx-combo');
        if (d) { d.open = false; var s = d.querySelector('.cx-combo__search input'); if (s && s.value) { s.value = ''; s.dispatchEvent(new Event('input', { bubbles: true })); } }
        go(to, 'push');
        if (focusAfter) { var sum = root.querySelector(focusAfter + ' summary'); if (sum) sum.focus({ preventScroll: true }); }
        else if (a.hasAttribute('data-chip') || a.id === 'cxReset') $('cxSearch').focus({ preventScroll: true });
    });

    window.addEventListener('popstate', function () { settle(); state = fromLocation(); render(); });

    /* ================================================================= search */
    var timer = 0, input = $('cxSearch');
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { go(withState({ q: input.value.trim() }), 'replace'); }, 120);
    });
    $('cxSearchForm').addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); go(withState({ q: input.value.trim() }), 'replace'); input.blur(); });
    $('cxSearchClear').addEventListener('click', function () { input.value = ''; go(withState({ q: '' }), 'replace'); input.focus(); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape' && input.value) { e.preventDefault(); input.value = ''; go(withState({ q: '' }), 'replace'); } });

    // "/" jumps to the search from anywhere on the page
    document.addEventListener('keydown', function (e) {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey || (dialog && dialog.open)) return;
        var t = e.target, tag = t && t.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (t && t.isContentEditable)) return;
        e.preventDefault();
        input.focus();
        input.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
    });

    /* ================================================================== start */
    applyLanguage();
    render();
    if (window.ResizeObserver) new ResizeObserver(placeThumb).observe(seg); else window.addEventListener('resize', placeThumb);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeThumb);
    history.replaceState(state, '', urlFor(state));
})();
