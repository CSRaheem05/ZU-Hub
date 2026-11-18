/* ==========================================================================
   ZU Hub — notification bell (header popover)
   Markup: resources/views/components/notification-bell.blade.php
   - toggles the glass popover (outside click / Escape close it, focus is restored)
   - opening an item shows it in place and marks it read (POST, own inbox only)
   - "Mark all as read" updates the badge without a reload
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

        function post(url) {
            return fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); });
        }

        function setAlerts(n) { root.classList.toggle('has-alert', n > 0); }

        function setUnread(n) {
            badge.hidden = n < 1;
            badge.textContent = n > 9 ? '9+' : String(n);
            if (summary) summary.textContent = n ? n + ' unread' : 'You are all caught up';
            if (readAllBtn) readAllBtn.hidden = n < 1;
            trigger.setAttribute('aria-label', 'Notifications' + (n ? ', ' + n + ' unread' : ''));
        }

        function showList() { detailView.hidden = true; listView.hidden = false; }

        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var closeTimer = 0;
        var isOpen = function () { return !panel.hidden && !panel.classList.contains('is-closing'); };

        /* Anchor the dropdown to the bell and keep it inside the viewport (works on phones, RTL, any header layout). */
        function place() {
            if (panel.hidden) return;
            var m = 12, vw = document.documentElement.clientWidth, vh = window.innerHeight;
            var t = trigger.getBoundingClientRect(), r = root.getBoundingClientRect();
            var w = panel.offsetWidth;
            var bellCentre = t.left + t.width / 2;
            var left = Math.min(Math.max(bellCentre - 36, m), Math.max(vw - w - m, m));   // starts just left of the bell centre, then clamped into view
            panel.style.left = (left - r.left) + 'px';
            panel.style.setProperty('--caret-x', Math.min(Math.max(bellCentre - left, 24), w - 24) + 'px'); // caret always points at the bell
            panel.style.setProperty('--panel-max', Math.max(vh - (r.bottom + 12) - m, 220) + 'px');         // lists scroll instead of leaving the screen
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

        /* While open, stay locked to the bell's live position: the header slides in on load, and layout can shift (fonts, resize). */
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
            closeTimer = setTimeout(function () { panel.hidden = true; panel.classList.remove('is-closing'); }, reduce ? 0 : 170);
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
        // the detail view is taller/shorter than the list: re-fit after switching
        new MutationObserver(place).observe(panel, { subtree: true, attributes: true, attributeFilter: ['hidden'] });

        // Open one notification: show its detail and mark it as read.
        root.addEventListener('click', function (e) {
            var item = e.target.closest('[data-zn-open]');
            if (!item) return;
            var id = item.getAttribute('data-zn-open');
            root.querySelectorAll('[data-zn-detail]').forEach(function (d) { d.hidden = d.getAttribute('data-zn-detail') !== id; });
            listView.hidden = true; detailView.hidden = false;
            detailView.querySelector('.zu-notify__details').scrollTop = 0;
            detailView.querySelector('[data-zn-back]').focus();

            if (item.classList.contains('is-unread')) {
                item.classList.remove('is-unread');     // optimistic; reverted if the server refuses
                post(readUrl.replace('__ID__', encodeURIComponent(id)))
                    .then(function (data) { setUnread(data.unread); setAlerts(data.alerts || 0); })
                    .catch(function () { item.classList.add('is-unread'); });
            }
        });

        root.querySelector('[data-zn-back]').addEventListener('click', function () {
            showList();
            var first = listView.querySelector('.zn-item'); if (first) first.focus();
        });

        if (readAllBtn) {
            readAllBtn.addEventListener('click', function () {
                readAllBtn.disabled = true;
                post(readAllUrl).then(function (data) {
                    root.querySelectorAll('.zn-item.is-unread').forEach(function (n) { n.classList.remove('is-unread'); });
                    setUnread(data.unread);
                    setAlerts(data.alerts || 0);
                }).catch(function () { /* keep state; user can retry */ }).then(function () { readAllBtn.disabled = false; });
            });
        }
    });
})();
