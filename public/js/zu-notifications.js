/* ==========================================================================
   ZU Hub — notification bell (header panel)
   Markup: resources/views/components/notification-bell.blade.php
   - the bell toggles the panel; outside click and Escape close it (focus returns to the bell)
   - opening a notification shows it in place and marks it read (POST, own inbox only)
   - the dot on an unread row marks just that one as read; "Mark all as read" clears the rest
   - badge, colour (normal / important / urgent) and summary follow the server's answer
   ========================================================================== */
(function () {
    'use strict';

    document.querySelectorAll('[data-zu-notify]').forEach(function (root) {
        var trigger = root.querySelector('.zu-notify__trigger');
        var panel = root.querySelector('.zu-notify__panel');
        var badge = root.querySelector('.zu-notify__badge');
        var summary = root.querySelector('[data-zn-summary]');
        var readAllBtn = root.querySelector('[data-zn-read-all]');
        var listView = root.querySelector('[data-view="list"]');
        var detailView = root.querySelector('[data-view="detail"]');
        var token = root.getAttribute('data-token');
        var readUrl = root.getAttribute('data-read-url');
        var readAllUrl = root.getAttribute('data-read-all-url');
        var summaryText = '', statusTimer = 0;

        function post(url) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
        }

        function setAlerts(n, level) {
            root.classList.toggle('has-alert', n > 0);
            if (n > 0 && level) root.setAttribute('data-level', level); else root.removeAttribute('data-level');
        }

        function setUnread(n) {
            badge.hidden = n < 1;
            badge.textContent = n > 9 ? '9+' : String(n);
            summaryText = n ? n + ' unread' : 'You are all caught up';
            if (summary) summary.textContent = summaryText;
            if (readAllBtn) readAllBtn.hidden = n < 1;
            trigger.setAttribute('aria-label', 'Notifications' + (n ? ', ' + n + ' unread' : ''));
        }
        function applyServer(data) { setUnread(data.unread); setAlerts(data.alerts || 0, data.level); }

        // a short, honest message in the header when something could not be saved
        function fail() {
            if (!summary) return;
            summary.textContent = 'Couldn’t update. Please try again.';
            clearTimeout(statusTimer);
            statusTimer = setTimeout(function () { summary.textContent = summaryText; }, 3500);
        }

        function markRowRead(item) {
            item.classList.remove('is-unread');
            var mark = item.querySelector('[data-zn-mark]');
            if (mark) { var dot = document.createElement('i'); dot.className = 'zn-dot'; mark.replaceWith(dot); }
        }

        function showList() { detailView.hidden = true; listView.hidden = false; }

        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var closeTimer = 0;
        var isOpen = function () { return !panel.hidden && !panel.classList.contains('is-closing'); };

        /* Anchor the panel to the bell and keep it inside the viewport (phones, RTL, any header layout). */
        function place() {
            if (panel.hidden) return;
            var m = 12, vw = document.documentElement.clientWidth, vh = window.innerHeight;
            var t = trigger.getBoundingClientRect(), r = root.getBoundingClientRect();
            var w = panel.offsetWidth;
            var bellCentre = t.left + t.width / 2;
            var left = Math.min(Math.max(bellCentre - 36, m), Math.max(vw - w - m, m));
            panel.style.left = (left - r.left) + 'px';
            panel.style.setProperty('--caret-x', Math.min(Math.max(bellCentre - left, 24), w - 24) + 'px');
            panel.style.setProperty('--panel-max', Math.max(vh - (r.bottom + 12) - m, 220) + 'px');
        }

        function openPanel() {
            clearTimeout(closeTimer);
            panel.classList.remove('is-closing');
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            document.documentElement.classList.add('zu-notify-open');
            showList();
            place();
            track();
        }

        /* While open, stay locked to the bell's live position (the header slides in on load; layout can shift). */
        var raf = 0, lastKey = '';
        function track() {
            cancelAnimationFrame(raf);
            var step = function () {
                if (panel.hidden) return;
                var b = trigger.getBoundingClientRect();
                var key = [b.left, b.top, b.width, b.height, window.innerWidth, window.innerHeight].map(Math.round).join();
                if (key !== lastKey) { lastKey = key; place(); }
                raf = requestAnimationFrame(step);
            };
            lastKey = ''; step();
        }
        function closePanel(returnFocus) {
            if (!isOpen()) return;
            trigger.setAttribute('aria-expanded', 'false');
            document.documentElement.classList.remove('zu-notify-open');
            panel.classList.add('is-closing');
            clearTimeout(closeTimer);
            closeTimer = setTimeout(function () { panel.hidden = true; panel.classList.remove('is-closing'); }, reduce ? 0 : 160);
            if (returnFocus) trigger.focus();
        }

        trigger.addEventListener('click', function () { isOpen() ? closePanel(false) : openPanel(); });
        document.addEventListener('pointerdown', function (e) { if (isOpen() && !root.contains(e.target)) closePanel(false); });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !isOpen()) return;
            if (!detailView.hidden) { showList(); return; }   // Escape first steps back, then closes
            closePanel(true);
        });
        window.addEventListener('resize', place);
        window.addEventListener('orientationchange', place);
        new MutationObserver(place).observe(panel, { subtree: true, attributes: true, attributeFilter: ['hidden'] });   // detail and list differ in height

        // mark one notification as read (the dot on the row)
        root.addEventListener('click', function (e) {
            var mark = e.target.closest('[data-zn-mark]');
            if (!mark) return;
            e.stopPropagation();
            var item = mark.closest('.zn-item');
            var id = mark.getAttribute('data-zn-mark');
            markRowRead(item);                                     // optimistic; restored if the server refuses
            post(readUrl.replace('__ID__', encodeURIComponent(id))).then(applyServer).catch(function () {
                item.classList.add('is-unread');
                var dot = item.querySelector('.zn-state .zn-dot');
                if (dot) { var b = document.createElement('button'); b.type = 'button'; b.className = 'zn-mark'; b.setAttribute('data-zn-mark', id); b.setAttribute('aria-label', 'Mark as read'); b.title = 'Mark as read'; b.innerHTML = '<i class="zn-dot"></i>'; dot.replaceWith(b); }
                fail();
            });
        });

        // open one notification: show its detail and mark it read
        root.addEventListener('click', function (e) {
            var open = e.target.closest('[data-zn-open]');
            if (!open) return;
            var item = open.closest('.zn-item');
            var id = open.getAttribute('data-zn-open');
            root.querySelectorAll('[data-zn-detail]').forEach(function (d) { d.hidden = d.getAttribute('data-zn-detail') !== id; });
            listView.hidden = true; detailView.hidden = false;
            detailView.querySelector('.zu-notify__details').scrollTop = 0;
            detailView.querySelector('[data-zn-back]').focus();

            if (item.classList.contains('is-unread')) {
                markRowRead(item);
                post(readUrl.replace('__ID__', encodeURIComponent(id))).then(applyServer).catch(function () { item.classList.add('is-unread'); fail(); });
            }
        });

        root.querySelector('[data-zn-back]').addEventListener('click', function () {
            showList();
            var first = listView.querySelector('.zn-main'); if (first) first.focus();
        });

        if (readAllBtn) {
            readAllBtn.addEventListener('click', function () {
                readAllBtn.disabled = true;
                post(readAllUrl).then(function (data) {
                    root.querySelectorAll('.zn-item.is-unread').forEach(markRowRead);
                    applyServer(data);
                }).catch(fail).then(function () { readAllBtn.disabled = false; });
            });
        }

        summaryText = summary ? summary.textContent : '';
    });
})();
