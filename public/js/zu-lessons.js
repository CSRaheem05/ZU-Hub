/* ==========================================================================
   ZU Hub — Instructor "Course content" page

   1) sections: add, rename, delete (empty only), collapse
   2) drag & drop: sections and content, between sections, with a floating preview and a highlighted drop slot;
      the same moves by keyboard (arrow keys)
   3) the add / edit slide-over panel (type picker -> details): videos (MP4 upload or link), materials, links, Teams sessions
   4) every structural change is saved at once (one request with the whole layout) and put back if the server refuses

   Everything the server must decide (permissions, file checks, the order itself) is checked again there.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('lmRoot');
    if (!root) return;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var $ = function (id) { return document.getElementById(id); };
    var csrf = root.getAttribute('data-csrf');
    var course = root.getAttribute('data-course');
    var ds = root.dataset;

    /* ---------------------------------------------------------------- feedback */
    var toastEl = $('lmToast'), toastTimer = null;
    function toast(message, kind) {
        toastEl.textContent = message;
        toastEl.className = 'lm-toast' + (kind === 'error' ? ' is-error' : kind === 'saving' ? ' is-saving' : '');
        toastEl.hidden = false;
        toastEl.style.animation = 'none'; void toastEl.offsetWidth; toastEl.style.animation = '';
        clearTimeout(toastTimer);
        if (kind !== 'saving') toastTimer = setTimeout(function () { toastEl.hidden = true; }, kind === 'error' ? 5600 : 2600);
    }
    function announce(message) { $('lmAnnounce').textContent = message; }

    function json(method, url, body) {
        return fetch(url, {
            method: method, credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: body === undefined ? undefined : JSON.stringify(body)
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) { return { ok: r.ok, status: r.status, data: data }; });
        });
    }
    function firstError(data) {
        if (data && data.errors) { var k = Object.keys(data.errors)[0]; if (k) return data.errors[k][0]; }
        return (data && data.message) || '';
    }

    var sectionsEl = $('lmSections'), panel = $('lmPanel');
    if (!sectionsEl || !panel) return;      // the "update pending" page has nothing to manage

    /* ----------------------------------------------------------- reading the DOM */
    function q(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qa(sel, ctx) { return [].slice.call((ctx || document).querySelectorAll(sel)); }
    function isSource(n) { return n.classList.contains('is-drag-source'); }
    function sectionEls() { return qa('.lm-section', sectionsEl).filter(function (s) { return !isSource(s); }); }
    function listOf(sec) { return q('[data-section-list]', sec); }
    function itemsOf(sec) { return [].slice.call(listOf(sec).children).filter(function (n) { return n.classList.contains('lm-item') && !isSource(n); }); }
    function titleOf(sec) { return q('.lm-section__title', sec).textContent.trim(); }
    function sectionById(id) { return q('.lm-section[data-section-id="' + id + '"]', sectionsEl); }
    function lessonById(id) { return q('.lm-item[data-id="' + id + '"]', sectionsEl); }
    function plural(n, one, many) { return n + ' ' + (n === 1 ? one : many); }

    function readLayout() {
        return sectionEls().map(function (s) { return { id: s.getAttribute('data-section-id'), lessons: itemsOf(s).map(function (li) { return li.getAttribute('data-id'); }) }; });
    }
    function sameLayout(a, b) { return JSON.stringify(a) === JSON.stringify(b); }
    function applyLayoutToDom(layout) {
        layout.forEach(function (s) {
            var sec = sectionById(s.id); if (!sec) return;
            sectionsEl.appendChild(sec);
            s.lessons.forEach(function (id) { var li = lessonById(id); if (li) listOf(sec).appendChild(li); });
        });
        refresh();
    }

    /* ----------------------------------------------------------- collapsed state */
    var collapsedKey = 'zu-lm-collapsed:' + course;
    function loadCollapsed() { try { return JSON.parse(localStorage.getItem(collapsedKey) || '[]'); } catch (e) { return []; } }
    function saveCollapsed() { try { localStorage.setItem(collapsedKey, JSON.stringify(sectionEls().filter(function (s) { return s.classList.contains('is-collapsed'); }).map(function (s) { return s.getAttribute('data-section-id'); }))); } catch (e) { /* private mode */ } }
    function setCollapsed(sec, collapsed, persist) {
        sec.classList.toggle('is-collapsed', collapsed);
        q('.lm-section__toggle', sec).setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        if (persist !== false) saveCollapsed();
    }

    /* ------------------------------------------------- counters, labels, empty states */
    function labelSection(sec) {
        var t = titleOf(sec);
        q('.lm-shandle', sec).setAttribute('aria-label', 'Reorder section “' + t + '”. Drag it, or focus it and use the up and down arrow keys.');
        qa('[data-lm-add-in]', sec).forEach(function (b) { if (b.hasAttribute('aria-label')) b.setAttribute('aria-label', 'Add content to “' + t + '”'); });
        q('[data-lm-section-menu]', sec).setAttribute('aria-label', 'Section options for “' + t + '”');
    }

    function refresh() {
        var secs = sectionEls(), total = 0;
        secs.forEach(function (sec) {
            var items = itemsOf(sec); total += items.length;
            q('.lm-section__count', sec).textContent = items.length === 0 ? 'Empty' : plural(items.length, 'item', 'items');
            sec.classList.toggle('is-empty', items.length === 0);
        });
        $('lmSummary').textContent = plural(secs.length, 'section', 'sections') + ' · ' + plural(total, 'item', 'items');
        sectionsEl.hidden = secs.length === 0;
        $('lmEmpty').hidden = secs.length > 0;
        var tip = $('lmTip'), tipHidden = false; try { tipHidden = localStorage.getItem('zu-lm-tip') === 'hidden'; } catch (e) { /* ok */ }
        if (tip) tip.hidden = tipHidden || total < 2;
    }
    if ($('lmTipClose')) $('lmTipClose').addEventListener('click', function () { $('lmTip').hidden = true; try { localStorage.setItem('zu-lm-tip', 'hidden'); } catch (e) { /* ok */ } });

    /* ----------------------------------------------------------- saving the layout */
    var lastSaved = readLayout(), saving = false, queued = false, saveTimer = null;

    function saveLayout() {
        var now = readLayout();
        if (sameLayout(now, lastSaved)) return;
        if (saving) { queued = true; return; }
        saving = true;
        toast('Saving…', 'saving');
        json('POST', ds.layoutUrl, { sections: now }).then(function (res) {
            if (!res.ok) throw res;
            lastSaved = now; toast('Saved');
        }).catch(function (res) {
            applyLayoutToDom(lastSaved);
            var why = res && res.data ? firstError(res.data) : '';
            toast('Could not save. The previous order was restored.' + (why && res.status === 422 ? ' ' + why : ''), 'error');
        }).then(function () { saving = false; if (queued) { queued = false; saveLayout(); } });
    }
    function saveSoon() { clearTimeout(saveTimer); saveTimer = setTimeout(saveLayout, 650); }

    /* ---------------------------------------------------- FLIP: glide to new places */
    function flip(mutate, what) {
        var sel = what === 'sections' ? '.lm-section' : '.lm-item';
        var els = qa(sel, sectionsEl).filter(function (e) { return !isSource(e); });
        var before = new Map(els.map(function (e) { return [e, e.getBoundingClientRect()]; }));
        mutate();
        if (reduce || !Element.prototype.animate) return;
        els.forEach(function (e) {
            if (!e.isConnected) return;
            var a = before.get(e), b = e.getBoundingClientRect(), dx = a.left - b.left, dy = a.top - b.top;
            if (dx || dy) e.animate([{ transform: 'translate(' + dx + 'px,' + dy + 'px)' }, { transform: 'none' }], { duration: 220, easing: 'cubic-bezier(.2,.8,.2,1)' });
        });
    }

    /* -------------------------------------------------------------- the moves */
    function neighbourSection(sec, dir) {
        var all = sectionEls(), i = all.indexOf(sec) + dir;
        return all[i] || null;
    }

    function moveItem(li, dir) {                        // one place up (-1) or down (+1); crosses into the next section at the ends
        var sec = li.closest('.lm-section'), list = li.parentElement, sib = dir < 0 ? li.previousElementSibling : li.nextElementSibling;
        if (sib && !sib.classList.contains('lm-item')) sib = null;
        var target = null;
        if (!sib) { target = neighbourSection(sec, dir); if (!target) return false; }
        flip(function () {
            if (sib) { if (dir < 0) list.insertBefore(li, sib); else list.insertBefore(sib, li); }
            else { setCollapsed(target, false, false); var tl = listOf(target); if (dir < 0) tl.appendChild(li); else tl.insertBefore(li, tl.firstElementChild); }
            refresh();
        });
        return true;
    }

    function moveSection(sec, dir) {
        var other = neighbourSection(sec, dir); if (!other) return false;
        flip(function () { if (dir < 0) sectionsEl.insertBefore(sec, other); else sectionsEl.insertBefore(other, sec); refresh(); }, 'sections');
        return true;
    }

    /* ------------------------------------------------------------ popover menus */
    var pop = $('lmPop'), popAnchor = null;
    function closeMenu(restoreFocus) {
        if (pop.hidden) return;
        pop.hidden = true; pop.innerHTML = '';
        if (popAnchor) { popAnchor.setAttribute('aria-expanded', 'false'); if (restoreFocus && popAnchor.isConnected) popAnchor.focus({ preventScroll: true }); }
        popAnchor = null;
    }
    function icon(name) {
        var paths = {
            edit: '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
            trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-12M9 7V4h6v3"/>'
        };
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' + (paths[name] || '') + '</svg>';
    }
    function openMenu(anchor, items) {
        closeMenu();
        pop.innerHTML = '';
        items.forEach(function (it) {
            if (it.sep) { pop.appendChild(document.createElement('hr')); return; }
            if (it.label) { var l = document.createElement('div'); l.className = 'lm-pop__label'; l.textContent = it.label; pop.appendChild(l); return; }
            var b = document.createElement('button'); b.type = 'button'; b.className = 'lm-pop__item' + (it.danger ? ' is-danger' : '') + (it.accent ? ' is-accent' : '') + (it.current ? ' is-current' : ''); b.setAttribute('role', 'menuitem');
            b.innerHTML = (it.icon ? icon(it.icon) : '') + '<span class="lm-pop__text"></span>';
            var text = q('.lm-pop__text', b); text.textContent = it.text;
            if (it.hint) { var sm = document.createElement('small'); sm.textContent = it.hint; text.appendChild(sm); }
            if (it.disabled) b.setAttribute('aria-disabled', 'true');
            b.addEventListener('click', function () { closeMenu(true); if (!it.current && it.run) it.run(); });
            pop.appendChild(b);
        });
        popAnchor = anchor; anchor.setAttribute('aria-expanded', 'true'); menuY = window.pageYOffset;
        pop.hidden = false; pop.style.visibility = 'hidden';
        var r = anchor.getBoundingClientRect(), w = pop.offsetWidth, h = pop.offsetHeight, vw = window.innerWidth, vh = window.innerHeight;
        var left = Math.max(8, Math.min(r.right - w, vw - w - 8)), top = r.bottom + 8;
        if (top + h > vh - 8) top = Math.max(8, r.top - h - 8);
        pop.style.left = left + 'px'; pop.style.top = top + 'px'; pop.style.visibility = '';
        var first = q('.lm-pop__item', pop); if (first) first.focus({ preventScroll: true });
    }
    document.addEventListener('click', function (e) { if (!pop.hidden && !pop.contains(e.target) && !e.target.closest('[aria-haspopup="menu"]')) closeMenu(); });
    // Close when the layout really changes: a different width (not the phone address bar sliding away) or a real scroll.
    var menuW = window.innerWidth, menuY = window.pageYOffset;
    window.addEventListener('resize', function () { if (window.innerWidth !== menuW) closeMenu(); menuW = window.innerWidth; });
    window.addEventListener('scroll', function () { if (!pop.hidden && Math.abs(window.pageYOffset - menuY) > 24) closeMenu(); }, { passive: true });
    pop.addEventListener('keydown', function (e) {
        var items = qa('.lm-pop__item', pop), i = items.indexOf(document.activeElement);
        if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length].focus(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
        else if (e.key === 'Home') { e.preventDefault(); items[0].focus(); }
        else if (e.key === 'End') { e.preventDefault(); items[items.length - 1].focus(); }
        else if (e.key === 'Tab') { closeMenu(); }
    });

    function sectionMenu(anchor, sec) {
        var n = itemsOf(sec).length;
        openMenu(anchor, [
            { text: 'Rename', icon: 'edit', accent: true, run: function () { startRename(sec); } },
            {
                text: 'Delete Section', icon: 'trash', danger: true, disabled: n > 0,
                hint: n > 0 ? 'Move or remove its ' + (n === 1 ? 'item' : plural(n, 'item', 'items').replace(/^\d+ /, '')) + ' first' : '',
                run: function () { deleteSection(sec); }
            }
        ]);
    }

    /* ------------------------------------------------------------------ sections */
    function addSection() {
        toast('Adding section…', 'saving');
        return json('POST', ds.sectionsUrl, { title: 'New section' }).then(function (res) {
            if (!res.ok) { toast(firstError(res.data) || 'The section could not be added.', 'error'); return null; }
            var html = $('lmSectionTpl').innerHTML.replace(/__ID__/g, res.data.section.id).replace(/__TITLE__/g, 'New section');
            var wrap = document.createElement('div'); wrap.innerHTML = html.trim();
            var sec = wrap.firstElementChild; sec.classList.add('is-empty', 'is-new');
            sectionsEl.appendChild(sec); refresh();
            lastSaved = readLayout();
            toastEl.hidden = true;
            sec.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
            startRename(sec);
            return sec;
        }).catch(function () { toast('Could not reach the server. Nothing was added.', 'error'); return null; });
    }

    function startRename(sec) {
        var toggle = q('.lm-section__toggle', sec), current = titleOf(sec), done = false;
        setCollapsed(sec, false);
        var input = document.createElement('input');
        input.type = 'text'; input.className = 'lm-rename'; input.value = current; input.maxLength = 255; input.setAttribute('aria-label', 'Section name');
        toggle.hidden = true; toggle.after(input); input.focus(); input.select();
        function finish(commit) {
            if (done) return; done = true;
            var value = input.value.trim().replace(/\s+/g, ' ');
            input.remove(); toggle.hidden = false; toggle.focus({ preventScroll: true });
            if (!commit || value === current) return;
            if (!value) { toast('Give the section a name.', 'error'); return; }
            q('.lm-section__title', sec).textContent = value; labelSection(sec);
            json('PUT', ds.sectionsUrl + '/' + sec.getAttribute('data-section-id'), { title: value }).then(function (res) {
                if (!res.ok) throw res;
                toast('Section renamed');
            }).catch(function (res) {
                q('.lm-section__title', sec).textContent = current; labelSection(sec);
                toast((res && res.data && firstError(res.data)) || 'The section could not be renamed.', 'error');
            });
        }
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); finish(true); } else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); finish(false); } });
        input.addEventListener('blur', function () { finish(true); });
    }

    function deleteSection(sec) {
        var n = itemsOf(sec).length;
        if (n > 0) { toast('This section still has ' + plural(n, 'item', 'items') + '. Move or remove ' + (n === 1 ? 'it' : 'them') + ' first.', 'error'); return; }
        var title = titleOf(sec);
        var ask = window.ZUConfirm ? window.ZUConfirm.open({ tone: 'red', icon: 'warning', title: 'Delete this section?', message: '“' + title + '” is empty and will be deleted.', confirmLabel: 'Yes, delete' }) : Promise.resolve(true);
        ask.then(function (ok) {
            if (!ok) return;
            json('DELETE', ds.sectionsUrl + '/' + sec.getAttribute('data-section-id')).then(function (res) {
                if (!res.ok) { toast(firstError(res.data) || 'The section could not be deleted.', 'error'); return; }
                sec.remove(); refresh(); lastSaved = readLayout(); saveCollapsed(); toast('Section deleted');
            }).catch(function () { toast('Could not reach the server. Nothing was deleted.', 'error'); });
        });
    }

    /* --------------------------------------------------- drag & drop (pointer) */
    var drag = null;

    function place(ph, parent, ref, what) {
        var next = ph.nextElementSibling; while (next && isSource(next)) next = next.nextElementSibling;
        if (ph.parentNode === parent && next === (ref || null)) return;
        flip(function () { parent.insertBefore(ph, ref || null); }, what);
    }

    function beginDrag(e, grip) {
        var kind = grip.classList.contains('lm-grip') ? 'item' : 'section';
        var node = grip.closest(kind === 'item' ? '.lm-item' : '.lm-section');
        if (!node) return;
        closeMenu();
        qa('.lm-item, .lm-section', sectionsEl).forEach(function (e) { if (e.getAnimations) e.getAnimations().forEach(function (a) { a.cancel(); }); });   // positions must be the real ones
        grip.focus({ preventScroll: true });
        sectionsEl.classList.add(kind === 'item' ? 'is-dragging-item' : 'is-dragging-section');
        document.body.classList.add('lm-dragging');

        var rect = node.getBoundingClientRect();          // measured after a section collapsed to its header
        var ph = document.createElement(kind === 'item' ? 'li' : 'div');
        ph.className = 'lm-placeholder' + (kind === 'section' ? ' lm-placeholder--section' : '');
        ph.style.height = rect.height + 'px';
        var ghost = node.cloneNode(true);
        qa('[id]', ghost).forEach(function (n) { n.removeAttribute('id'); }); ghost.removeAttribute('id');
        qa('a, button, input, [tabindex]', ghost).forEach(function (n) { n.setAttribute('tabindex', '-1'); });
        ghost.classList.add('lm-ghost'); ghost.style.width = rect.width + 'px'; ghost.style.height = rect.height + 'px';
        var offX = Math.max(0, Math.min(e.clientX - rect.left, rect.width)), offY = Math.max(0, Math.min(e.clientY - rect.top, rect.height));
        document.body.appendChild(ghost);
        node.before(ph); node.classList.add('is-drag-source');

        drag = { kind: kind, node: node, grip: grip, ph: ph, ghost: ghost, offX: offX, offY: offY, left: rect.left, startX: e.clientX, x: e.clientX, y: e.clientY, raf: 0, hoverSec: null, hoverTimer: 0, id: e.pointerId };
        try { grip.setPointerCapture(e.pointerId); } catch (err) { /* synthetic events have no active pointer */ }
        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);
        window.addEventListener('pointercancel', onCancel);
        document.addEventListener('keydown', onDragKey, true);
        paintGhost();
        loop();
    }

    function paintGhost() {
        var d = drag;
        // pure translation: the card stays perfectly horizontal and locked to its column
        d.ghost.style.transform = 'translate3d(' + d.left + 'px,' + (d.y - d.offY) + 'px,0)';
    }
    function onMove(e) { if (drag) { drag.x = e.clientX; drag.y = e.clientY; } }
    function onUp() { endDrag(true); }
    function onCancel() { endDrag(false); }
    function onDragKey(e) { if (drag && e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); endDrag(false); } }

    function loop() {
        var d = drag; if (!d) return;
        if (d.y < 90) window.scrollBy(0, -Math.ceil((90 - d.y) / 5));
        else if (d.y > window.innerHeight - 90) window.scrollBy(0, Math.ceil((d.y - (window.innerHeight - 90)) / 5));
        paintGhost();
        if (d.kind === 'item') targetForItem(d); else targetForSection(d);
        d.raf = requestAnimationFrame(loop);
    }

    function targetForItem(d) {
        var secs = sectionEls(), best = null, bestDist = Infinity;
        secs.forEach(function (s) {
            var r = s.getBoundingClientRect(), dist = d.y < r.top ? r.top - d.y : d.y > r.bottom ? d.y - r.bottom : 0;
            if (dist < bestDist) { bestDist = dist; best = s; }
        });
        secs.forEach(function (s) { s.classList.toggle('is-drop-target', s === best); });
        if (!best) return;

        if (best.classList.contains('is-collapsed') && d.hoverSec !== best) {       // hovering a closed section opens it
            clearTimeout(d.hoverTimer); d.hoverSec = best;
            d.hoverTimer = setTimeout(function () { if (drag === d) setCollapsed(best, false, false); }, 450);
        } else if (!best.classList.contains('is-collapsed')) { clearTimeout(d.hoverTimer); d.hoverSec = null; }

        var list = listOf(best), ref = null;
        itemsOf(best).some(function (li) { var r = li.getBoundingClientRect(); if (r.top + r.height / 2 > d.y) { ref = li; return true; } return false; });
        place(d.ph, list, ref, 'items');
    }

    function targetForSection(d) {
        var ref = null;
        sectionEls().some(function (s) { var r = s.getBoundingClientRect(); if (r.top + r.height / 2 > d.y) { ref = s; return true; } return false; });
        place(d.ph, sectionsEl, ref, 'sections');
    }

    function endDrag(commit) {
        var d = drag; if (!d) return;
        drag = null;
        cancelAnimationFrame(d.raf); clearTimeout(d.hoverTimer);
        window.removeEventListener('pointermove', onMove); window.removeEventListener('pointerup', onUp); window.removeEventListener('pointercancel', onCancel);
        document.removeEventListener('keydown', onDragKey, true);
        try { d.grip.releasePointerCapture(d.id); } catch (err) { /* ok */ }

        function cleanup() {
            d.ghost.remove();
            sectionsEl.classList.remove('is-dragging-item', 'is-dragging-section');
            document.body.classList.remove('lm-dragging');
            qa('.is-drop-target', sectionsEl).forEach(function (s) { s.classList.remove('is-drop-target'); });
        }
        function finish() {
            if (commit) {
                var target = d.ph.closest('.lm-section');
                d.ph.replaceWith(d.node);
                d.node.classList.remove('is-drag-source');
                if (d.kind === 'item' && target && target.classList.contains('is-collapsed')) setCollapsed(target, false);
            } else {
                d.ph.remove(); d.node.classList.remove('is-drag-source');
            }
            cleanup(); refresh();
            if (commit) {
                var now = readLayout();
                if (!sameLayout(now, lastSaved)) {
                    var where = d.kind === 'item' ? 'Moved to ' + titleOf(d.node.closest('.lm-section')) + ', position ' + (itemsOf(d.node.closest('.lm-section')).indexOf(d.node) + 1) + '.' : 'Section moved.';
                    announce(where);
                    saveLayout();
                }
            }
            d.grip.focus({ preventScroll: true });
        }

        if (!commit || reduce) { finish(); return; }
        var t = d.ph.getBoundingClientRect();           // glide the floating preview into the slot, then drop it in
        d.ghost.style.transition = 'transform .2s cubic-bezier(.3,1.2,.5,1)';
        d.ghost.style.transform = 'translate(' + t.left + 'px,' + t.top + 'px) scale(1)';
        setTimeout(finish, 210);
    }

    sectionsEl.addEventListener('pointerdown', function (e) {
        var grip = e.target.closest('.lm-grip, .lm-shandle');
        if (!grip || drag || (e.pointerType === 'mouse' && e.button !== 0)) return;
        e.preventDefault();
        beginDrag(e, grip);
    });

    /* keyboard: focus a handle, then the up / down arrow keys move it (across sections too) */
    sectionsEl.addEventListener('keydown', function (e) {
        var grip = e.target.closest('.lm-grip, .lm-shandle');
        if (!grip) return;
        var isItem = grip.classList.contains('lm-grip'), node = grip.closest(isItem ? '.lm-item' : '.lm-section');

        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') return;
        e.preventDefault();

        var moved = isItem ? moveItem(node, e.key === 'ArrowUp' ? -1 : 1) : moveSection(node, e.key === 'ArrowUp' ? -1 : 1);
        grip.focus({ preventScroll: true });
        if (!moved) return;
        node.classList.remove('is-new'); void node.offsetWidth; node.classList.add('is-new');
        announce(isItem ? 'Moved to ' + titleOf(node.closest('.lm-section')) + ', position ' + (itemsOf(node.closest('.lm-section')).indexOf(node) + 1) + ' of ' + itemsOf(node.closest('.lm-section')).length + '.' : 'Section moved to position ' + (sectionEls().indexOf(node) + 1) + ' of ' + sectionEls().length + '.');
        saveSoon();
    });

    /* ----------------------------------------------------- clicks on the page */
    document.addEventListener('click', function (e) {
        var t = e.target;
        var addSec = t.closest('[data-lm-add-section]');
        if (addSec) { e.preventDefault(); addSection(); return; }

        var toggle = t.closest('.lm-section__toggle');
        if (toggle) { var s = toggle.closest('.lm-section'); setCollapsed(s, !s.classList.contains('is-collapsed')); return; }

        var secMenu = t.closest('[data-lm-section-menu]');
        if (secMenu) { e.preventDefault(); if (popAnchor === secMenu) closeMenu(); else sectionMenu(secMenu, secMenu.closest('.lm-section')); return; }

        var addIn = t.closest('[data-lm-add-in]');
        if (addIn) { e.preventDefault(); openPanel('add', '', null, addIn.closest('.lm-section').getAttribute('data-section-id')); return; }

        var add = t.closest('[data-lm-add]');
        if (add) {
            e.preventDefault();
            var type = add.getAttribute('data-lm-add');
            if (state.open) { if (state.busy) return; if (type) { showForm(type, null, state.preset); el.name.focus(); } else showPick(); }
            else openPanel('add', type, null, null);
            return;
        }

        var edit = t.closest('[data-lm-edit]');
        if (edit) { e.preventDefault(); var data; try { data = JSON.parse(edit.getAttribute('data-lm-edit')); } catch (err) { return; } openPanel('edit', data.type, data, data.section_id); return; }

        if (t.closest('[data-lm-close]')) {
            var onBackdrop = t.classList.contains('lm-panel__backdrop');
            if (onBackdrop && isDirty()) { toast('Your changes are not saved yet. Use Cancel to discard them.', 'error'); return; }
            closePanel();
        }
    });

    /* =================================================================== the add / edit panel */
    var sheet = panel.querySelector('.lm-sheet'), pick = $('lmPick'), form = $('lmForm');
    var el = {
        title: $('lmPanelTitle'), back: $('lmPanelBack'), type: $('lmType'), method: $('lmMethod'),
        badge: $('lmTypeBadge'), badgeIcon: $('lmTypeIcon'), badgeName: $('lmTypeName'),
        section: $('lmSection'), sectionField: $('lmSectionField'),
        name: $('lmTitle'), desc: $('lmDesc'),
        sourceField: $('lmSourceField'), url: $('lmUrl'), urlField: $('lmUrlField'), urlLabel: $('lmUrlLabel'), urlHelp: $('lmUrlHelp'),
        liveUrl: $('lmLiveUrl'), liveUrlField: $('lmLiveUrlField'), when: $('lmWhen'), whenField: $('lmWhenField'),
        file: $('lmFile'), fileField: $('lmFileField'), fileLabelText: $('lmFileLabelText'), fileHelp: $('lmFileHelp'), dropTitle: $('lmDropTitle'),
        drop: $('lmDrop'), picked: $('lmPicked'), pickedName: $('lmPickedName'), pickedMeta: $('lmPickedMeta'),
        pickedIcon: $('lmPickedIcon'), pickedRemove: $('lmPickedRemove'), fileStar: $('lmFileStar'),
        formError: $('lmFormError'), progress: $('lmProgress'), bar: $('lmProgressBar'),
        submit: $('lmSubmit'), submitLabel: $('lmSubmitLabel')
    };
    var radios = qa('input[name="video_source"]', form);
    var KINDS = {
        video: { name: 'Video', cls: 'lm-k--video' },
        file: { name: 'Material', cls: 'lm-k--file-file' },
        link: { name: 'External resource', cls: 'lm-k--link' },
        live: { name: 'Live session', cls: 'lm-k--live' }
    };
    var TEAMS = /^https:\/\/(teams\.microsoft\.com|teams\.live\.com|teams\.microsoft\.us|gov\.teams\.microsoft\.us|dod\.teams\.microsoft\.us)(\/|$|\?)/i;

    var state = { open: false, mode: 'add', type: '', source: 'upload', busy: false, opener: null, picked: null, current: null, original: null, snapshot: '', preset: null, lastSection: null };

    function isVisible(node) { return !!(node.offsetWidth || node.offsetHeight || node.getClientRects().length); }
    function focusables() {
        return qa('button, [href], input, select, textarea, label.lm-mini, [tabindex]:not([tabindex="-1"])', sheet)
            .filter(function (n) { return !n.disabled && !n.hidden && isVisible(n) && n.getAttribute('tabindex') !== '-1' && !n.classList.contains('lm-drop__input'); });
    }

    function openPanel(mode, type, data, sectionId) {
        if (state.open) return;
        closeMenu();
        state.open = true; state.mode = mode; state.opener = document.activeElement; state.preset = sectionId || state.lastSection;
        panel.classList.remove('is-closing'); panel.hidden = false;
        document.body.classList.add('lm-lock');
        if (mode === 'add' && !type) showPick(); else showForm(type, mode === 'edit' ? data : null, state.preset);
        requestAnimationFrame(function () {
            var target = !pick.hidden ? q('.lm-kind', pick) : el.name;
            (target || sheet).focus({ preventScroll: true });
        });
    }

    function closePanel(force) {
        if (!state.open || (state.busy && !force)) return;
        state.open = false;
        panel.classList.add('is-closing');
        var done = function () {
            panel.hidden = true; panel.classList.remove('is-closing');
            document.body.classList.remove('lm-lock');
            if (state.opener && document.contains(state.opener) && state.opener.focus) state.opener.focus({ preventScroll: true });
            state.opener = null;
        };
        if (reduce) done(); else setTimeout(done, 260);
    }

    function showPick() {
        state.mode = 'add'; state.type = '';
        el.title.textContent = 'Add content'; el.back.hidden = true;
        pick.hidden = false; form.hidden = true;
    }

    function fillSections(selected) {
        var secs = sectionEls();
        el.section.innerHTML = '';
        secs.forEach(function (s) {
            var o = document.createElement('option'); o.value = s.getAttribute('data-section-id'); o.textContent = titleOf(s); el.section.appendChild(o);
        });
        var has = secs.length > 0;
        el.sectionField.hidden = !has; el.section.disabled = !has;        // no sections yet: the server creates "Getting Started"
        if (has) el.section.value = secs.some(function (s) { return s.getAttribute('data-section-id') === selected; }) ? selected : secs[secs.length - 1].getAttribute('data-section-id');
    }

    function fileKind() { return state.type === 'video' ? 'video' : 'document'; }

    function applyFields() {
        var type = state.type, source = state.source;
        var show = {
            source: type === 'video',
            url: type === 'link' || (type === 'video' && source === 'link'),
            file: type === 'file' || (type === 'video' && source === 'upload'),
            live: type === 'live'
        };
        [[el.sourceField, show.source], [el.urlField, show.url], [el.fileField, show.file], [el.liveUrlField, show.live], [el.whenField, show.live]].forEach(function (p) {
            if (!p[0]) return;      // the video-source switch no longer exists: a video is always an upload
            p[0].hidden = !p[1];
            qa('input, textarea, select', p[0]).forEach(function (f) { f.disabled = !p[1]; });
        });
        radios.forEach(function (r) { r.checked = r.value === source; });

        if (type === 'video') { el.urlLabel.innerHTML = 'Video link <em>*</em>'; el.urlHelp.textContent = 'YouTube links play right inside the course.'; }
        else { el.urlLabel.innerHTML = 'Link <em>*</em>'; el.urlHelp.textContent = 'Students open it in a new tab.'; }

        var kind = fileKind(), video = kind === 'video';
        el.file.accept = video ? ds.videoAccept : ds.docAccept;
        el.fileLabelText.textContent = video ? 'Video file' : 'File';
        el.dropTitle.innerHTML = video ? 'Drop an MP4 video here, or <u>browse</u>' : 'Drop a file here, or <u>browse</u>';
        el.fileHelp.textContent = video ? 'MP4 video · up to ' + ds.videoLabel : 'PDF, Word, PowerPoint, Excel or text · up to ' + ds.docLabel;
        el.fileStar.hidden = state.mode === 'edit' && !!state.current;      // replacing an existing file is optional
        renderFile();
    }

    function showForm(type, data, sectionId) {
        var edit = !!data;
        state.mode = edit ? 'edit' : 'add'; state.type = type; state.picked = null; state.current = null; state.original = data || null;
        state.source = type === 'video' ? 'upload' : 'link';

        form.reset(); clearErrors(); setBusy(false);
        form.action = edit ? data.update_url : ds.storeUrl;
        el.method.disabled = !edit;
        el.type.value = type;

        el.title.textContent = edit ? 'Edit content' : 'Add content';
        el.back.hidden = edit;
        el.submitLabel.textContent = edit ? 'Save changes' : 'Add content';

        var tile = q('.lm-kind[data-lm-add="' + type + '"] .lm-tile', pick);        // the icon lives in one place: the picker
        el.badgeIcon.innerHTML = tile ? tile.innerHTML : ''; el.badgeIcon.className = 'lm-tile';
        el.badge.className = 'lm-typebadge ' + KINDS[type].cls; el.badgeName.textContent = KINDS[type].name;

        fillSections(sectionId);

        if (edit) {
            el.name.value = data.title || ''; el.desc.value = data.description || '';
            el.url.value = data.url || ''; el.liveUrl.value = data.live_url || ''; el.when.value = data.scheduled_at || '';
            if (data.file_name && (type === 'file' || state.source === 'upload')) state.current = { name: data.file_name, size: data.file_size || '' };
        }

        applyFields();
        pick.hidden = true; form.hidden = false; form.scrollTop = 0;
        state.snapshot = snapshot();
    }

    function snapshot() { return [el.name.value, el.desc.value, el.url.value, el.liveUrl.value, el.when.value, el.section.value, state.source, state.picked ? state.picked.name : ''].join('\u0001'); }
    function isDirty() { return !form.hidden && snapshot() !== state.snapshot; }

    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (!r.checked) return;
            state.source = r.value; state.picked = null; el.file.value = '';
            // an edit that goes back to "upload" only has a file to keep when the video already was an upload
            state.current = (state.original && state.original.video_source === 'upload' && state.original.file_name && r.value === 'upload') ? { name: state.original.file_name, size: state.original.file_size || '' } : null;
            clearFieldError('file'); clearFieldError('lesson_url'); applyFields();
        });
    });

    el.back.addEventListener('click', function () { if (!state.busy) { showPick(); var first = q('.lm-kind', pick); if (first) first.focus(); } });

    document.addEventListener('keydown', function (e) {
        if (!state.open || drag) return;
        if (e.key === 'Escape') { if (!pop.hidden) return; e.preventDefault(); closePanel(); return; }
        if (e.key !== 'Tab') return;
        var items = focusables(); if (!items.length) return;
        var first = items[0], last = items[items.length - 1];
        if (e.shiftKey && (document.activeElement === first || document.activeElement === sheet)) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !pop.hidden) { e.preventDefault(); closeMenu(true); }
    });

    /* ------------------------------------------------------------------- errors */
    function errorNode(name) { return q('[data-error-for="' + name + '"]', form); }
    function fieldNode(name) { return name === 'file' ? el.drop : form.elements[name]; }
    function setError(name, message) {
        var node = errorNode(name), field = fieldNode(name);
        if (node) { node.textContent = message; node.hidden = false; } else { showFormError(message); }
        if (field && field.classList) field.classList.add('is-invalid');
        if (field && field.setAttribute) field.setAttribute('aria-invalid', 'true');
    }
    function showFormError(message) { el.formError.textContent = message; el.formError.hidden = false; }
    function clearErrors() {
        qa('[data-error-for]', form).forEach(function (n) { n.hidden = true; n.textContent = ''; });
        qa('.is-invalid', form).forEach(function (n) { n.classList.remove('is-invalid'); n.removeAttribute('aria-invalid'); });
        el.formError.hidden = true; el.formError.textContent = '';
    }
    function clearFieldError(name) {
        var node = errorNode(name), field = fieldNode(name);
        if (node) { node.hidden = true; node.textContent = ''; }
        if (field && field.classList) { field.classList.remove('is-invalid'); field.removeAttribute('aria-invalid'); }
    }
    form.addEventListener('input', function (e) { if (e.target.name) clearFieldError(e.target.name); });

    function validUrl(value) { try { var u = new URL(value); return u.protocol === 'http:' || u.protocol === 'https:'; } catch (err) { return false; } }

    function validate() {
        var errors = {}, type = state.type;
        if (!el.name.value.trim()) errors.lesson_title = 'Give this content a title.';
        if (type === 'link' || (type === 'video' && state.source === 'link')) {
            var u = el.url.value.trim();
            if (!u) errors.lesson_url = type === 'video' ? 'Paste the link to your video.' : 'Paste the link to the resource.';
            else if (!validUrl(u)) errors.lesson_url = 'Enter a full web address that starts with http:// or https://.';
        }
        if (type === 'live') {
            var l = el.liveUrl.value.trim(), unchanged = state.original && state.original.live_url === l;
            if (!l) errors.live_url = 'Paste the Microsoft Teams meeting link.';
            else if (!unchanged && !TEAMS.test(l)) errors.live_url = 'Paste the Microsoft Teams meeting link (it starts with https://teams.microsoft.com/).';
            if (!el.when.value) errors.scheduled_at = 'Choose the date and time of the session.';
        }
        if ((type === 'file' || (type === 'video' && state.source === 'upload')) && !state.picked && !state.current) {
            errors.file = type === 'video' ? 'Choose an MP4 video to upload.' : 'Choose a file to upload.';
        }
        return errors;
    }

    /* --------------------------------------------------------------------- files */
    function extOf(name) { var i = name.lastIndexOf('.'); return i > -1 ? name.slice(i + 1).toLowerCase() : ''; }
    function human(bytes) {
        if (bytes >= 1048576) return (Math.round(bytes / 104857.6) / 10) + ' MB';
        if (bytes >= 1024) return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        return bytes + ' B';
    }
    function fileProblem(file) {
        var video = fileKind() === 'video', max = parseInt(video ? ds.videoBytes : ds.docBytes, 10), label = video ? ds.videoLabel : ds.docLabel;
        var allowed = (video ? ds.videoTypes : ds.docTypes).split(',');
        if (allowed.indexOf(extOf(file.name)) < 0) return video ? 'This file type is not supported. Upload an MP4 video.' : 'This file type is not supported. Upload PDF, Word, PowerPoint, Excel or text files.';
        if (file.size === 0) return 'This file is empty.';
        if (file.size > max) { var size = human(file.size); return size === label ? 'This file is just over the ' + label + ' limit.' : 'This file is ' + size + '. The limit is ' + label + '.'; }
        return '';
    }
    function takeFile(file) {
        clearFieldError('file');
        var problem = fileProblem(file);
        if (problem) { el.file.value = ''; setError('file', problem); return; }
        state.picked = file;
        if (!el.name.value.trim()) el.name.value = file.name.replace(/\.[^.]+$/, '').replace(/[_]+/g, ' ').replace(/\s+/g, ' ').trim();
        renderFile();
    }
    function renderFile() {
        var info = state.picked ? { name: state.picked.name, meta: human(state.picked.size) } : (state.current ? { name: state.current.name, meta: (state.current.size ? state.current.size + ' · ' : '') + 'current file' } : null);
        el.drop.classList.toggle('has-file', !!info);
        el.picked.hidden = !info;
        if (!info) return;
        el.pickedName.textContent = info.name; el.pickedMeta.textContent = info.meta;
        var ext = extOf(info.name);
        el.pickedIcon.className = 'lm-tile ' + (ext === 'mp4' ? 'lm-k--video' : 'lm-k--file-' + (ext || 'file'));
        el.pickedIcon.innerHTML = '<b>' + (ext ? ext.toUpperCase() : 'FILE') + '</b>';
        el.pickedRemove.hidden = !state.picked;          // the current file can only be replaced, never removed
    }
    el.file.addEventListener('change', function () { if (el.file.files && el.file.files[0]) takeFile(el.file.files[0]); });
    el.pickedRemove.addEventListener('click', function () { state.picked = null; el.file.value = ''; clearFieldError('file'); renderFile(); });
    ['dragenter', 'dragover'].forEach(function (n) { el.drop.addEventListener(n, function (e) { e.preventDefault(); el.drop.classList.add('is-over'); }); });
    ['dragleave', 'dragend'].forEach(function (n) { el.drop.addEventListener(n, function () { el.drop.classList.remove('is-over'); }); });
    el.drop.addEventListener('drop', function (e) {
        e.preventDefault(); el.drop.classList.remove('is-over');
        var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]; if (!f) return;
        try { var dt = new DataTransfer(); dt.items.add(f); el.file.files = dt.files; } catch (err) { /* the file is also kept in state.picked */ }
        takeFile(f);
    });
    window.addEventListener('dragover', function (e) { if (state.open && e.dataTransfer && [].indexOf.call(e.dataTransfer.types || [], 'Files') > -1) e.preventDefault(); });
    window.addEventListener('drop', function (e) { if (state.open && !e.target.closest('#lmDrop')) e.preventDefault(); });

    /* -------------------------------------------------------------------- submit */
    function setBusy(on) {
        state.busy = on;
        el.submit.disabled = on; el.submit.classList.toggle('is-loading', on);
        qa('.lm-form__foot .btn', form).forEach(function (b) { if (b.id !== 'lmSubmit') b.disabled = on; });
        if (!on) { el.progress.hidden = true; el.progress.classList.remove('is-busy'); el.bar.style.width = '0'; }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (state.busy) return;
        clearErrors();

        var errors = validate(), names = Object.keys(errors);
        if (names.length) {
            names.forEach(function (n) { setError(n, errors[n]); });
            var first = fieldNode(names[0]);
            if (first && first.scrollIntoView) first.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
            if (names[0] !== 'file' && first && first.focus) first.focus({ preventScroll: true });
            return;
        }

        var fd = new FormData(form);
        fd.delete('file');
        var hasFile = !!state.picked;
        if (hasFile) fd.set('file', state.picked, state.picked.name);
        if (!el.section.disabled && el.section.value) state.lastSection = el.section.value;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        setBusy(true);
        if (hasFile) {
            el.progress.hidden = false;
            xhr.upload.onprogress = function (ev) {
                if (!ev.lengthComputable) return;
                var pct = Math.min(100, Math.round(ev.loaded / ev.total * 100));
                el.bar.style.width = pct + '%';
                if (pct >= 100) el.progress.classList.add('is-busy');       // sent; the server is saving it
            };
        }

        xhr.onerror = function () { setBusy(false); showFormError('Could not reach the server. Check your connection and try again.'); };
        xhr.onload = function () {
            var body = {}; try { body = JSON.parse(xhr.responseText); } catch (err) { /* not JSON */ }

            if (xhr.status >= 200 && xhr.status < 300 && body.redirect) {
                var url = body.redirect, m = /#lesson-([\w-]+)$/.exec(url);
                if (m) { try { sessionStorage.setItem('zu-lm-focus', m[1]); } catch (err) { /* ok */ } url = url.replace(/#.*$/, ''); }
                window.location.assign(url);
                return;
            }

            setBusy(false);
            if (xhr.status === 422 && body.errors) {
                var keys = Object.keys(body.errors);
                keys.forEach(function (k) { setError(k, body.errors[k][0]); });
                var f = fieldNode(keys[0]);
                if (f && f.scrollIntoView) f.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' });
                return;
            }
            if (xhr.status === 419) { showFormError(hasFile ? 'The upload was rejected. The file may be larger than this server accepts, or your session expired. Reload the page and try again.' : 'Your session expired. Reload the page and try again.'); return; }
            if (xhr.status === 413) { showFormError('This file is larger than the server accepts. Choose a smaller one.'); return; }
            if (xhr.status === 403 || xhr.status === 404) { showFormError('You can no longer manage this content. Reload the page.'); return; }
            showFormError('Something went wrong and nothing was saved. Please try again.');
        };
        xhr.send(fd);
    });

    /* ------------------------------------------------------------------ start up */
    var collapsed = loadCollapsed();
    sectionEls().forEach(function (s) { if (collapsed.indexOf(s.getAttribute('data-section-id')) > -1) setCollapsed(s, true, false); });
    refresh();

    // after adding / editing content, scroll to it and highlight it
    var focusId = null;
    try { focusId = sessionStorage.getItem('zu-lm-focus'); sessionStorage.removeItem('zu-lm-focus'); } catch (e) { /* ok */ }
    if (!focusId && /^#lesson-/.test(location.hash)) focusId = location.hash.slice(8);
    var target = focusId && lessonById(focusId);
    if (target) {
        var host = target.closest('.lm-section'); if (host) setCollapsed(host, false, false);
        requestAnimationFrame(function () { target.scrollIntoView({ block: 'center', behavior: reduce ? 'auto' : 'smooth' }); target.classList.add('is-new'); });
    }
})();
