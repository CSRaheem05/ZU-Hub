/* ==========================================================================
   ZU Hub — shared liquid-glass runtime (ported from the homepage)
   1) Chromium: builds a per-element edge-refraction displacement map for .lg
   2) pointer-tracked specular glow on .lg
   3) sliding glass lens inside every [data-zu-nav] (click, press-and-drag, keyboard)

   Markup contract:
     <nav class="zu-nav lg" data-zu-nav>
       <a class="zu-nav-link is-active" href="...">…</a>
     </nav>
   ========================================================================== */
(function () {
    'use strict';

    // SVG filters inside backdrop-filter only work in Chromium. Others keep blur + saturate.
    var ua = navigator.userAgent;
    var REFRACT = /Chrome\/\d+/.test(ua) && !/Firefox\//.test(ua);
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var SVGNS = 'http://www.w3.org/2000/svg';
    var defs = null, uid = 0, ro = null;

    var clamp = function (v, a, b) { return Math.min(Math.max(v, a), b); };

    /* ---- refraction ---- */
    if (REFRACT) {
        var holder = document.createElementNS(SVGNS, 'svg');
        holder.setAttribute('width', '0'); holder.setAttribute('height', '0'); holder.setAttribute('aria-hidden', 'true');
        holder.style.cssText = 'position:absolute;width:0;height:0;pointer-events:none';
        defs = document.createElementNS(SVGNS, 'defs'); holder.appendChild(defs);
        document.body.appendChild(holder);
    }

    function makeMap(w, h, r, T) {
        var c = document.createElement('canvas'); c.width = w; c.height = h;
        var ctx = c.getContext('2d'), img = ctx.createImageData(w, h), d = img.data;
        var hw = w / 2, hh = h / 2;
        for (var y = 0; y < h; y++) for (var x = 0; x < w; x++) {
            var px = x + .5 - hw, py = y + .5 - hh;
            var qx = Math.abs(px) - (hw - r), qy = Math.abs(py) - (hh - r);
            var ox = Math.max(qx, 0), oy = Math.max(qy, 0);
            var depth = -(Math.hypot(ox, oy) + Math.min(Math.max(qx, qy), 0) - r); // distance from the edge, inwards
            var nx = 0, ny = 0;
            if (ox > 0 || oy > 0) { var L = Math.hypot(ox, oy); nx = Math.sign(px) * ox / L; ny = Math.sign(py) * oy / L; }
            else if (qx > qy) nx = Math.sign(px); else ny = Math.sign(py);
            var t = Math.min(Math.max(depth / T, 0), 1);
            var m = (1 - t) * (1 - t); // strongest right at the rim
            var i = (y * w + x) * 4;
            d[i] = Math.round(127.5 * (1 - nx * m)); d[i + 1] = Math.round(127.5 * (1 - ny * m)); d[i + 2] = 128; d[i + 3] = 255;
        }
        ctx.putImageData(img, 0, 0);
        return c.toDataURL();
    }

    function build(el, W, H) {
        if (!REFRACT) return;
        var w = Math.round(W || el.offsetWidth), h = Math.round(H || el.offsetHeight);
        if (!w || !h || (el.__lgW === w && el.__lgH === h)) return;
        el.__lgW = w; el.__lgH = h;
        var cs = getComputedStyle(el), rs = cs.borderTopLeftRadius, r = parseFloat(rs) || 0;
        if (rs.indexOf('%') > -1) r = w * r / 100;
        r = Math.min(r, w / 2, h / 2);
        var T = Math.max(5, Math.min(Math.min(w, h) * .3, 14)), S = T * 2.3;
        if (!el.__lgId) {
            el.__lgId = 'lgf' + (uid++);
            var f = document.createElementNS(SVGNS, 'filter');
            f.id = el.__lgId; f.setAttribute('x', '0'); f.setAttribute('y', '0');
            f.setAttribute('width', '1'); f.setAttribute('height', '1');
            f.setAttribute('color-interpolation-filters', 'sRGB');
            var im = document.createElementNS(SVGNS, 'feImage');
            im.setAttribute('x', '0'); im.setAttribute('y', '0'); im.setAttribute('result', 'map');
            im.setAttribute('preserveAspectRatio', 'none');
            var dm = document.createElementNS(SVGNS, 'feDisplacementMap');
            dm.setAttribute('in', 'SourceGraphic'); dm.setAttribute('in2', 'map');
            dm.setAttribute('xChannelSelector', 'R'); dm.setAttribute('yChannelSelector', 'G');
            f.appendChild(im); f.appendChild(dm); defs.appendChild(f);
            el.__lgImg = im; el.__lgMap = dm;
            el.style.setProperty('--lg-bf', 'url(#' + el.__lgId + ') blur(var(--lg-blur)) saturate(var(--lg-sat)) brightness(var(--lg-bright))');
        }
        el.__lgImg.setAttribute('width', w); el.__lgImg.setAttribute('height', h);
        el.__lgImg.setAttribute('href', makeMap(w, h, r, T));
        el.__lgMap.setAttribute('scale', S);
    }

    if (REFRACT && 'ResizeObserver' in window) {
        ro = new ResizeObserver(function (entries) {
            entries.forEach(function (e) { if (!e.target.classList.contains('zu-lens')) build(e.target); });
        });
        document.querySelectorAll('.lg').forEach(function (el) { if (!el.classList.contains('zu-lens')) ro.observe(el); });
    }

    /* ---- pointer-tracked specular glow ---- */
    document.addEventListener('pointermove', function (e) {
        var el = e.target.closest && e.target.closest('.lg'); if (!el) return;
        var r = el.getBoundingClientRect();
        el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        el.style.setProperty('--my', (e.clientY - r.top) + 'px');
    }, { passive: true });

    /* ---- sliding lens ---- */
    function initNav(nav) {
        var links = Array.prototype.slice.call(nav.querySelectorAll('.zu-nav-link'));
        if (!links.length) return;

        var ind = document.createElement('span');
        ind.className = 'zu-lens lg is-empty'; ind.setAttribute('aria-hidden', 'true');
        nav.insertBefore(ind, nav.firstChild);

        var current = null, placed = false;
        var activeLink = function () { return nav.querySelector('.zu-nav-link.is-active'); };

        function stretch() {
            if (reducedMotion) return;
            ind.classList.remove('stretch'); void ind.offsetWidth; ind.classList.add('stretch');
        }
        function moveTo(link, animate) {
            if (!link) { ind.classList.add('is-empty'); current = null; return; }
            if (!link.offsetWidth) return;
            var first = !placed;
            if (first || animate === false) ind.classList.add('no-anim');
            ind.classList.remove('is-empty');
            ind.style.width = link.offsetWidth + 'px'; ind.style.height = link.offsetHeight + 'px';
            ind.style.setProperty('--ix', link.offsetLeft + 'px'); ind.style.setProperty('--iy', link.offsetTop + 'px');
            build(ind, link.offsetWidth, link.offsetHeight);
            if (link !== current && !first) stretch();
            current = link;
            if (first || animate === false) requestAnimationFrame(function () { requestAnimationFrame(function () { ind.classList.remove('no-anim'); }); });
            placed = true;
        }
        function settle(animate) { moveTo(activeLink(), animate); }

        if ('ResizeObserver' in window) {
            new ResizeObserver(function () { settle(false); }).observe(nav);
            links.forEach(function (l) { new ResizeObserver(function () { settle(false); }).observe(l); });
        }
        window.addEventListener('resize', function () { settle(false); }, { passive: true });
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { settle(false); });
        settle();
        var lastActive = activeLink();
        new MutationObserver(function () { var a = activeLink(); if (a !== lastActive) { lastActive = a; settle(); } })
            .observe(nav, { subtree: true, attributes: true, attributeFilter: ['class'] });

        /* press + drag (mouse / pen): the lens lifts, follows the pointer and snaps to the nearest item */
        var pressed = null, moved = false, startX = 0, peek = null, swallowClick = false;
        function setPeek(l) { if (peek === l) return; if (peek) peek.classList.remove('peek'); peek = l; if (l) l.classList.add('peek'); }

        nav.addEventListener('pointerdown', function (e) {
            var link = e.target.closest('.zu-nav-link'); if (!link || e.button > 0) return;
            pressed = link; moved = false; startX = e.clientX;
            ind.classList.add('is-pressed');
            moveTo(link);
            if (e.pointerType !== 'touch') window.addEventListener('pointermove', onMove);
            window.addEventListener('pointerup', onUp, { once: true });
            window.addEventListener('pointercancel', onUp, { once: true });
        });

        function onMove(e) {
            if (!moved && Math.abs(e.clientX - startX) < 5) return;
            moved = true; ind.classList.add('is-dragging');
            var nr = nav.getBoundingClientRect();
            var x = e.clientX - nr.left - nav.clientLeft + nav.scrollLeft;
            var lo = Math.min.apply(null, links.map(function (l) { return l.offsetLeft; }));
            var hi = Math.max.apply(null, links.map(function (l) { return l.offsetLeft + l.offsetWidth; }));
            var w = ind.offsetWidth;
            ind.style.setProperty('--ix', clamp(x - w / 2, lo, hi - w) + 'px');
            var best = null, bd = 1e9;
            links.forEach(function (l) { var d = Math.abs(l.offsetLeft + l.offsetWidth / 2 - x); if (d < bd) { bd = d; best = l; } });
            if (best && best !== current) {
                ind.style.width = best.offsetWidth + 'px'; ind.style.height = best.offsetHeight + 'px';
                ind.style.setProperty('--iy', best.offsetTop + 'px');
                build(ind, best.offsetWidth, best.offsetHeight); current = best;
            }
            setPeek(best);
        }

        function onUp() {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp); window.removeEventListener('pointercancel', onUp);
            ind.classList.remove('is-pressed', 'is-dragging'); setPeek(null);
            if (moved) {
                swallowClick = true; setTimeout(function () { swallowClick = false; }, 0);
                if (current && current !== activeLink()) {
                    if (current.href) { window.location.href = current.href; return; }
                    current.click(); // button-style tabs (client-side filters)
                }
                settle();
            } else {
                setTimeout(settle, 80); // plain click: the link navigates; if nothing changed, glide back
            }
            pressed = null; moved = false;
        }
        nav.addEventListener('click', function (e) { if (swallowClick) { e.stopPropagation(); e.preventDefault(); } }, true);
    }

    document.querySelectorAll('[data-zu-nav]').forEach(initNav);
})();
