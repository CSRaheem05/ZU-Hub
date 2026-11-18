/* ==========================================================================
   ZU Hub — course discovery (/courses)

   Every approved course is already on the page. This script only decides which ones are visible:
   college -> topic -> status -> search, all instant, with the same ?college=&topic=&status=&q= address the
   server understands (so links, bookmarks, the back button and a page without JavaScript all keep working).

   Progressive discovery: the topic picker does not exist until a college is chosen, and it only lists that
   college's real topics (sent by the server). Changing the college clears the topic.

   The page entrance (staggered fade-up) is pure CSS; this script only marks it finished (`is-ready`) so a filter
   change never replays it — filtering uses a small, fast transition instead.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('cx');
    if (!root) return;
    root.classList.add('js');

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var $ = function (id) { return document.getElementById(id); };
    var qa = function (sel, ctx) { return [].slice.call((ctx || root).querySelectorAll(sel)); };
    var data = { topics: {}, colleges: {} };
    try { data = JSON.parse($('cxData').textContent); } catch (e) { /* the page still works from the server markup */ }

    var cards = qa('.cx-card');
    var baseUrl = root.getAttribute('data-url');
    var HINTS = { ongoing: 'Ongoing classes: enroll to open the lessons.', completed: 'Completed courses: watch any time, and save them to My Courses.' };
    var ICON = {
        tag: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12V4h8l9 9-8 8z"/><circle cx="7.5" cy="8.5" r="1.2"/></svg>',
        chev: '<svg class="cx-combo__chev" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>',
        grid: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.6"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.6"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.6"/></svg>',
        search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>'
    };

    /* ----------------------------------------------------------------- state */
    if (!data.topics || typeof data.topics !== 'object') data.topics = {};
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
        var s = {}; for (var k in state) s[k] = state[k]; for (var o in over) s[o] = over[o];
        return s;
    }
    function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    function plural(n, one, many) { return n === 1 ? one : many; }

    /* --------------------------------------------------------------- counting */
    function matches(card, st, skip) {                                   // skip: a filter (or a list of them) to leave out
        var q = st.q.toLowerCase(), no = function (k) { return skip === k || (typeof skip !== 'string' && skip && skip.indexOf(k) > -1); };
        return (no('college') || !st.college || card.getAttribute('data-college') === st.college)
            && (no('topic') || !st.topic || card.getAttribute('data-topic') === st.topic)
            && (no('status') || !st.status || card.getAttribute('data-status') === st.status)
            && (!q || card.getAttribute('data-search').indexOf(q) > -1);
    }
    function count(st, skip, extra) {
        return cards.filter(function (c) { return matches(c, st, skip) && (!extra || extra(c)); }).length;
    }

    /* -------------------------------------------------------------- rendering */
    var first = true, shownCollege = state.college, hideTimer = 0;

    function render() {
        // 1) the cards: newly visible ones rise in quickly (the page entrance is a separate, one-time thing)
        var shown = 0, entering = 0;
        cards.forEach(function (card) {
            var show = matches(card, state), was = !card.hidden;
            card.hidden = !show;
            card.classList.remove('is-entering');
            if (show) {
                shown++;
                if (!was && !first && !reduce) {
                    card.style.setProperty('--d', Math.min(entering++, 8) * 24 + 'ms');
                    void card.offsetWidth;
                    card.classList.add('is-entering');
                }
            }
        });
        $('cxCount').textContent = shown;
        $('cxCountLabel').textContent = plural(shown, 'course', 'courses');

        // 2) step 1: the college picker (counts follow the status and the search)
        qa('#cxCollegeList [data-ccount]').forEach(function (b) {
            var code = b.getAttribute('data-ccount');
            var n = count(state, ['college', 'topic'], code === '' ? null : function (c) { return c.getAttribute('data-college') === code; });
            b.textContent = n;
            var a = b.closest('.cx-opt'), on = state.college === code;
            a.classList.toggle('is-zero', n === 0 && !on);
            a.classList.toggle('is-active', on);
            if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
        });
        var no = $('cxCollegeNo'), name = state.college ? (data.colleges[state.college] || state.college) : 'All colleges';
        if (state.college) { no.textContent = state.college; no.classList.add('is-set'); } else { no.innerHTML = ICON.grid; no.classList.remove('is-set'); }
        $('cxCollegeName').textContent = name;
        $('cxComboCollege').querySelector('summary').setAttribute('aria-label', 'College: ' + name + '. Choose a college');

        // 3) step 2: the topic picker appears only once a college is chosen
        syncTopics();

        // 4) the status switch
        qa('.cx-seg__item').forEach(function (a) {
            var key = a.getAttribute('data-status');
            a.querySelector('b').textContent = count(state, 'status', key ? function (c) { return c.getAttribute('data-status') === key; } : null);
            var on = state.status === key;
            a.classList.toggle('is-active', on);
            if (on) a.setAttribute('aria-current', 'true'); else a.removeAttribute('aria-current');
        });
        placeThumb();

        // 5) result heading, reset, hint
        $('cxResult').innerHTML = resultTitle();
        $('cxReset').hidden = !(state.college || state.topic || state.status || state.q);
        var hint = $('cxHint'); hint.textContent = HINTS[state.status] || ''; hint.hidden = !state.status;

        // 6) search box
        var input = $('cxSearch');
        if (document.activeElement !== input) input.value = state.q;
        $('cxSearchClear').hidden = state.q === '';

        // 7) empty state
        $('cxEmpty').hidden = shown > 0;
        $('cxGrid').hidden = shown === 0;
        if (shown === 0) fillEmpty();

        first = false;
    }

    function resultTitle() {
        var word = state.status === 'ongoing' ? 'Ongoing ' : state.status === 'completed' ? 'Completed ' : '';
        var college = state.college ? '<bdi>' + esc(data.colleges[state.college] || state.college) + '</bdi>' : '';
        var topic = topicsOf(state.college).filter(function (t) { return t.id === state.topic; })[0];
        if (topic) return '<bdi>' + esc(topic.name) + '</bdi> in ' + college;
        if (college) return (word ? word + 'courses' : 'Courses') + ' in ' + college;
        return word ? word + 'courses' : 'All courses';
    }

    /* the topic picker: fades up when a college is chosen, fades out when the college is cleared */
    function syncTopics() {
        var pick = $('cxPickTopic'), slot = $('cxTopicSlot'), changed = state.college !== shownCollege;
        if (state.college) {
            clearTimeout(hideTimer); pick.classList.remove('is-out');
            var wasHidden = pick.hidden, open = $('cxComboTopic') && $('cxComboTopic').open;
            if (changed || wasHidden || !open) slot.innerHTML = topicPicker(topicsOf(state.college));
            pick.hidden = false;
            if ((wasHidden || changed) && !first && !reduce) { pick.classList.remove('is-in'); void pick.offsetWidth; pick.classList.add('is-in'); }
        } else if (!pick.hidden) {
            if (reduce || first) { pick.hidden = true; slot.innerHTML = ''; }
            else {
                pick.classList.remove('is-in'); pick.classList.add('is-out');
                hideTimer = setTimeout(function () { pick.hidden = true; pick.classList.remove('is-out'); slot.innerHTML = ''; }, 170);
            }
        }
        shownCollege = state.college;
    }

    function topicPicker(topics) {
        if (!topics.length) return '<p class="cx-picker__none">' + ICON.tag + '<span>No topics for this college yet. Showing all of its courses.</span></p>';
        var cur = topics.filter(function (t) { return t.id === state.topic; })[0];
        var h = '<details class="cx-combo" id="cxComboTopic"><summary class="cx-combo__btn" aria-label="Topic: ' + esc(cur ? cur.name : 'All topics') + '. Choose a topic">'
            + '<span class="cx-combo__no' + (cur ? ' is-set' : '') + '">' + ICON.tag + '</span><span class="cx-combo__txt"><small>Topic</small><strong dir="auto">' + esc(cur ? cur.name : 'All topics') + '</strong></span>' + ICON.chev
            + '</summary><div class="cx-combo__panel">';
        if (topics.length > 8) h += '<label class="cx-combo__search">' + ICON.search + '<input type="search" placeholder="Search topics" aria-label="Search topics" autocomplete="off"></label>';
        h += '<ul class="cx-combo__list">' + opt('', 'All topics', count(state, 'topic'), state.topic === '');
        topics.forEach(function (t) { h += opt(t.id, t.name, count(state, 'topic', function (c) { return c.getAttribute('data-topic') === t.id; }), state.topic === t.id); });
        return h + '</ul><p class="cx-combo__none" hidden>No topic matches your search.</p></div></details>';
    }
    function opt(id, name, n, on) {
        return '<li><a href="' + esc(urlFor(withState({ topic: id }))) + '" class="cx-opt' + (on ? ' is-active' : '') + (n === 0 && !on ? ' is-zero' : '') + '" data-topic="' + esc(id) + '" data-name="' + esc(name.toLowerCase()) + '"' + (on ? ' aria-current="true"' : '') + '>'
            + '<span class="cx-opt__no">' + ICON.tag + '</span><span class="cx-opt__name" dir="auto">' + esc(name) + '</span><b>' + n + '</b></a></li>';
    }

    function fillEmpty() {
        var title = $('cxEmptyTitle'), text = $('cxEmptyText'), actions = $('cxEmptyActions');
        if (!cards.length) return;                                        // nothing is published at all: the server text stands
        var where = state.college ? (data.colleges[state.college] || 'this college') : '';
        var kind = state.status === 'ongoing' ? 'ongoing' : state.status === 'completed' ? 'completed' : '';
        title.textContent = 'No ' + (kind ? kind + ' ' : '') + 'courses' + (where ? ' in ' + where : '') + (state.q ? ' for “' + state.q + '”' : '') + ' yet';
        text.textContent = 'Try another college or topic, or loosen the search.';
        var html = '';
        if (state.status) {
            var other = state.status === 'ongoing' ? 'completed' : 'ongoing', n = count(withState({ status: other }));
            if (n > 0) html += '<a class="cx-cta" data-status="' + other + '" href="' + esc(urlFor(withState({ status: other }))) + '">Show ' + n + ' ' + other + ' ' + plural(n, 'course', 'courses') + '</a>';
        }
        if (state.q) html += '<a class="cx-ghost" data-clearq href="' + esc(urlFor(withState({ q: '' }))) + '">Clear search</a>';
        html += '<a class="cx-ghost" data-reset href="' + esc(baseUrl) + '">Reset all</a>';
        actions.innerHTML = html;
    }

    /* the sliding thumb behind the active status */
    function placeThumb() {
        var seg = $('cxSeg'), on = seg.querySelector('.is-active'), thumb = seg.querySelector('.cx-seg__thumb');
        if (!on || !on.offsetWidth) return;
        thumb.style.width = on.offsetWidth + 'px';
        thumb.style.transform = 'translateX(' + on.offsetLeft + 'px)';
    }

    /* ------------------------------------------------ the entrance is one-time */
    function settle() { root.classList.add('is-ready'); }      // from now on the entrance animation can never replay
    setTimeout(settle, 1900);
    root.addEventListener('pointerdown', settle, { once: true, capture: true });
    root.addEventListener('keydown', settle, { once: true, capture: true });

    /* ----------------------------------------------------- searchable pickers */
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
        var input = e.target.closest('.cx-combo__search input'); if (!input) return;
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

    /* ------------------------------------------------------------ navigation */
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
        if (!a || !root.contains(a)) return;

        var to = null, focusAfter = null;
        if (a.id === 'cxReset' || a.hasAttribute('data-reset')) to = read('', '', '', '');
        else if (a.hasAttribute('data-clearq')) { to = withState({ q: '' }); $('cxSearch').value = ''; }
        else if (a.classList.contains('cx-opt') && a.hasAttribute('data-college')) { to = withState({ college: a.getAttribute('data-college'), topic: '' }); focusAfter = '#cxComboCollege'; }
        else if (a.classList.contains('cx-opt') && a.hasAttribute('data-topic')) { to = withState({ topic: a.getAttribute('data-topic') }); focusAfter = '#cxComboTopic'; }
        else if (a.hasAttribute('data-status')) to = withState({ status: a.getAttribute('data-status') });
        if (!to) return;

        e.preventDefault();
        var d = a.closest('details.cx-combo');
        if (d) { d.open = false; var s = d.querySelector('.cx-combo__search input'); if (s && s.value) { s.value = ''; s.dispatchEvent(new Event('input', { bubbles: true })); } }
        go(to, 'push');
        if (focusAfter) { var sum = root.querySelector(focusAfter + ' summary'); if (sum) sum.focus({ preventScroll: true }); }
    });

    window.addEventListener('popstate', function () { settle(); state = fromLocation(); render(); });

    /* ---------------------------------------------------------------- search */
    var timer = 0, input = $('cxSearch');
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { go(withState({ q: input.value.trim() }), 'replace'); }, 110);
    });
    $('cxSearchForm').addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); go(withState({ q: input.value.trim() }), 'replace'); input.blur(); });
    $('cxSearchClear').addEventListener('click', function () { input.value = ''; go(withState({ q: '' }), 'replace'); input.focus(); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape' && input.value) { e.preventDefault(); input.value = ''; go(withState({ q: '' }), 'replace'); } });

    /* ----------------------------------------------------------------- start */
    render();
    if (window.ResizeObserver) new ResizeObserver(placeThumb).observe($('cxSeg')); else window.addEventListener('resize', placeThumb);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(placeThumb);
    history.replaceState(state, '', urlFor(state));
})();
