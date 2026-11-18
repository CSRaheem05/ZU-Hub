/* ==========================================================================
   ZU Hub — Student course page (and the instructor's Student preview)
   - Every section starts closed each time the page opens (the server renders them closed; nothing is remembered).
   - Sections open and close independently; Expand all / Collapse all act on every section.
   - Thumbnails load only once their section is opened (nothing is fetched for closed sections).
   - Videos never sit in the page: "Watch" opens ONE focused 16:9 player. Closing it (or closing its section) stops playback
     and drops the source, so only one player can ever exist and nothing keeps streaming in the background.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('clRoot');
    if (!root) return;

    var sections = [].slice.call(root.querySelectorAll('.cl-section'));
    var openAll = root.querySelector('[data-cl-all="open"]');
    var closeAll = root.querySelector('[data-cl-all="close"]');
    var YT = /^https:\/\/www\.youtube-nocookie\.com\/embed\/[A-Za-z0-9_-]{11}$/;

    // older versions remembered which sections were open; that is gone, so drop the leftovers
    try {
        Object.keys(localStorage).forEach(function (k) { if (k.indexOf('zu-cl-collapsed:') === 0) localStorage.removeItem(k); });
    } catch (e) { /* storage unavailable: nothing to clean up */ }

    /* ------------------------------------------------------------- thumbnails */
    function wake(sec) {
        [].forEach.call(sec.querySelectorAll('img[data-src]'), function (img) {
            img.addEventListener('load', function () { img.classList.add('is-loaded'); }, { once: true });
            img.src = img.getAttribute('data-src');
            img.removeAttribute('data-src');
        });
        clampDescriptions(sec);
    }

    /* long descriptions show two lines with a "Show more" toggle (only when they actually overflow) */
    function clampDescriptions(sec) {
        [].forEach.call(sec.querySelectorAll('[data-cl-desc]:not([data-cl-checked])'), function (p) {
            p.setAttribute('data-cl-checked', '');
            p.classList.add('is-clamped');
            if (p.scrollHeight <= p.clientHeight + 1) { p.classList.remove('is-clamped'); return; }
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'cl-more'; b.textContent = 'Show more'; b.setAttribute('aria-expanded', 'false');
            b.addEventListener('click', function () {
                var open = p.classList.toggle('is-clamped') === false;
                b.textContent = open ? 'Show less' : 'Show more'; b.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            p.insertAdjacentElement('afterend', b);
        });
    }

    /* ----------------------------------------------------------------- player */
    var stage = null, opener = null, activeHost = null;

    function build() {
        stage = document.createElement('div');
        stage.className = 'cl-stage'; stage.hidden = true;
        stage.setAttribute('role', 'dialog'); stage.setAttribute('aria-modal', 'true'); stage.setAttribute('aria-labelledby', 'clStageTitle');
        stage.innerHTML =
            '<div class="cl-stage__panel">' +
              '<div class="cl-stage__bar"><h2 class="cl-stage__title" id="clStageTitle"></h2>' +
              '<button type="button" class="cl-stage__close" aria-label="Close video"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>' +
              '<div class="cl-stage__media"><span class="cl-stage__spin" aria-hidden="true"></span><p class="cl-stage__msg" role="alert">This video could not be loaded.</p></div>' +
            '</div>';
        document.body.appendChild(stage);
        stage.addEventListener('click', function (e) { if (!e.target.closest('.cl-stage__panel') || e.target.closest('.cl-stage__close')) closePlayer(); });
    }

    function clearMedia() {
        var media = stage.querySelector('.cl-stage__media');
        [].forEach.call(media.querySelectorAll('video, iframe'), function (el) {
            if (el.tagName === 'VIDEO') { try { el.pause(); } catch (e) { /* ok */ } el.removeAttribute('src'); el.load(); }
            else { el.src = 'about:blank'; }
            el.remove();
        });
    }

    function openPlayer(btn) {
        var kind = btn.getAttribute('data-cl-play'), src = btn.getAttribute('data-src') || '', title = btn.getAttribute('data-title') || '';
        if (kind === 'youtube' && !YT.test(src)) return;                       // only a validated YouTube embed address is ever framed
        if (kind === 'video' && !/^(https?:\/\/|\/)/.test(src)) return;
        if (!stage) build();
        clearMedia();                                                          // never two players

        var media = stage.querySelector('.cl-stage__media'), el;
        stage.classList.remove('is-failed');
        stage.querySelector('.cl-stage__title').textContent = title;
        stage.querySelector('.cl-stage__spin').style.display = '';

        if (kind === 'youtube') {
            el = document.createElement('iframe');
            el.title = title || 'Video';
            el.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen');
            el.setAttribute('allowfullscreen', '');
            el.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            el.addEventListener('load', function () { stage.querySelector('.cl-stage__spin').style.display = 'none'; });
            el.src = src + '?autoplay=1&rel=0&playsinline=1';
        } else {
            el = document.createElement('video');
            el.controls = true; el.autoplay = true; el.playsInline = true; el.preload = 'auto';
            el.setAttribute('controlsList', 'nodownload');
            el.setAttribute('aria-label', title || 'Video');
            var done = function () { stage.querySelector('.cl-stage__spin').style.display = 'none'; };
            el.addEventListener('loadeddata', done); el.addEventListener('playing', done);
            el.addEventListener('error', function () { stage.classList.add('is-failed'); });
            el.src = src;
        }
        media.appendChild(el);

        opener = btn; activeHost = btn.closest('.cl-section');
        stage.hidden = false; document.body.style.overflow = 'hidden';
        stage.querySelector('.cl-stage__close').focus({ preventScroll: true });
        if (el.tagName === 'VIDEO') { var p = el.play(); if (p && p.catch) p.catch(function () { /* autoplay blocked: the controls are right there */ }); }
    }

    function closePlayer() {
        if (!stage || stage.hidden) return;
        clearMedia();
        stage.hidden = true; document.body.style.overflow = '';
        if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
        opener = null; activeHost = null;
    }

    document.addEventListener('keydown', function (e) {
        if (!stage || stage.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); closePlayer(); }
        else if (e.key === 'Tab') {                                            // keep focus inside the player while it is open
            var f = [].slice.call(stage.querySelectorAll('button, video, iframe, [tabindex]:not([tabindex="-1"])'));
            if (!f.length) return;
            var i = f.indexOf(document.activeElement), next = e.shiftKey ? (i <= 0 ? f.length - 1 : i - 1) : (i === f.length - 1 ? 0 : i + 1);
            if (i === -1 || (e.shiftKey && i === 0) || (!e.shiftKey && i === f.length - 1)) { e.preventDefault(); f[next < 0 ? 0 : next].focus(); }
        }
    }, true);

    /* --------------------------------------------------------------- sections */
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
        if (collapsed) { if (activeHost === sec) closePlayer(); } else wake(sec);
        syncTools();
    }

    // start: everything closed (the markup already is; this wires up aria and inert)
    sections.forEach(function (s) { set(s, true); });

    root.addEventListener('click', function (e) {
        var play = e.target.closest('[data-cl-play]');
        if (play) { openPlayer(play); return; }

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
