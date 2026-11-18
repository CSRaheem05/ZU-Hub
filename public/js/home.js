'use strict';
// Public asset URLs (served from Laravel public/).

const MEDIA = {
    video: '/videos/Robot.mp4',
    poster: ''
};

const $ = id => document.getElementById(id);
const video = $('heroVideo'), poster = $('poster');
video.querySelector('source').src = MEDIA.video;
if (MEDIA.poster) {
    video.poster = MEDIA.poster;
    poster.src = MEDIA.poster;
}
video.load();
const DURATION = 2100;
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const mobile = () => window.matchMedia('(max-width: 720px)').matches;
let animations = [], busy = false;
const timing = { duration: DURATION, fill: 'both', easing: 'linear' };
function animate(id, frames) { return $(id).animate(frames, timing) }
function createReveal() {
    animations.forEach(a => a.cancel()); animations = [];
    const onPhone = mobile();
    if (onPhone) {
        const height = Math.max(80, (window.innerWidth - 40) / 1.85);
        animations.push(animate('pill', [
            { height: '1.5px', clipPath: 'inset(0 50%)', offset: 0 },
            { height: '1.5px', clipPath: 'inset(0 0%)', offset: .14 },
            { height: (height * .10) + 'px', clipPath: 'inset(0 0%)', offset: .30 },
            { height: (height * .55) + 'px', clipPath: 'inset(0 0%)', offset: .58 },
            { height: height + 'px', clipPath: 'inset(0 0%)', offset: 1 }
        ]));
        animations.push(animate('wordmark', [
            { rowGap: 'calc(var(--fs) * .03)', offset: 0 },
            { rowGap: 'calc(var(--fs) * .03)', offset: .14 },
            { rowGap: 'calc(var(--fs) * .065)', offset: .58 },
            { rowGap: 'calc(var(--fs) * .10)', offset: 1 }
        ]));
    } else {
        animations.push(animate('pill', [
            { width: '.012em', clipPath: 'inset(50% 0 50% 0)', easing: 'cubic-bezier(.5,0,.3,1)', offset: 0 },
            { width: '.012em', clipPath: 'inset(0 0 0 0)', easing: 'cubic-bezier(.5,0,.3,1)', offset: .14 },
            { width: '.16em', clipPath: 'inset(0 0 0 0)', easing: 'cubic-bezier(.45,0,.3,1)', offset: .30 },
            { width: '1.81em', clipPath: 'inset(0 0 0 0)', easing: 'cubic-bezier(.4,0,.2,1)', offset: .58 },
            { width: '3.62em', clipPath: 'inset(0 0 0 0)', offset: 1 }
        ]));
        animations.push(animate('wordmark', [
            { columnGap: 'calc(var(--fs) * .026)', offset: 0 },
            { columnGap: 'calc(var(--fs) * .026)', offset: .14 },
            { columnGap: 'calc(var(--fs) * .05)', offset: .58 },
            { columnGap: 'calc(var(--fs) * .085)', offset: 1 }
        ]));
    }
    animations.push(animate('hairline', [{ opacity: 1, offset: 0 }, { opacity: 1, offset: .14 }, { opacity: 0, offset: .30 }, { opacity: 0, offset: 1 }]));
    animations.push(animate('paper', [{ opacity: 0, offset: 0 }, { opacity: 0, offset: .45 }, { opacity: 1, offset: .78 }, { opacity: 1, offset: 1 }]));
    animations.push(animate('nav', [{ transform: 'translateY(-140%)', offset: 0 }, { transform: 'translateY(-140%)', easing: 'cubic-bezier(.2,.8,.2,1)', offset: .5 }, { transform: 'translateY(0)', offset: .86 }, { transform: 'translateY(0)', offset: 1 }]));
    animations.push(animate('microcopy', [{ opacity: 0, transform: 'translateY(14px)', offset: 0 }, { opacity: 0, transform: 'translateY(14px)', offset: .72 }, { opacity: 1, transform: 'translateY(0)', offset: 1 }]));
    animations.forEach(a => a.pause());
}
function seekAll(ms) { animations.forEach(a => { a.playbackRate = 1; a.currentTime = ms; a.pause() }) }
function playForward() { animations.forEach(a => { a.playbackRate = 1; a.play() }) }
async function replay() {
    if (busy || reduced) return;
    busy = true; $('replay') && ($('replay').disabled = true);
    try {
        animations.forEach(a => { a.currentTime = DURATION; a.playbackRate = -1; a.play() });
        await animations[0].finished.catch(() => { });
        seekAll(0);
        playForward();
        await animations[0].finished.catch(() => { });
    } finally { busy = false; $('replay') && ($('replay').disabled = false) }
}
function init() { createReveal(); if (reduced) { seekAll(DURATION); video.pause() } else { seekAll(0); playForward(); video.play().catch(() => { }) } }
let resizeId = 0;
window.addEventListener('resize', () => { cancelAnimationFrame(resizeId); resizeId = requestAnimationFrame(() => { createReveal(); seekAll(DURATION); busy = false; $('replay') && ($('replay').disabled = false) }) }, { passive: true });
$('replay')?.addEventListener('click', () => { if (reduced) { seekAll(DURATION) } else replay() });
document.querySelectorAll('[data-info]').forEach(link => link.addEventListener('click', e => { e.preventDefault(); const str = link.dataset.info; if (str) $('microcopy').textContent = str }));
document.addEventListener('keydown', e => { if (e.key === 'r' && !e.ctrlKey && !e.metaKey) replay() });
const rec = new URLSearchParams(location.search).get('rec') === '1';
window.__recVideo = (t) => { const dur = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 4.25; const at = ((t % dur) + dur) % dur + 1 / 120; try { video.currentTime = at } catch { } return at };
document.fonts.ready.then(() => { init(); if (rec && !reduced) { setTimeout(() => { animations.forEach(a => { a.currentTime = DURATION; a.playbackRate = -1; a.play() }) }, 4900) } });

// Functional bilingual UI. Demo notifications/panels are sample content, not connected to Laravel.
const TEXT = {
    en: {
        home: 'Home', features: 'Features', courses: 'Courses', contact: 'Contact', notifications: 'Notifications', demoNotices: 'Preview only', markRead: 'Mark all as read', tagline: 'Learn beyond the classroom', intro: '', explore: 'Explore Courses', unread: 'New', empty: 'You are all caught up.',
        notices: [['Welcome to ZU Hub', 'Your learning space is getting ready.', 'Just now'], ['A better way to learn', 'Explore courses, resources, and updates in one place.', 'Preview']]
    },
    ar: {
        home: 'الرئيسية', features: 'المميزات', courses: 'الدورات', contact: 'تواصل معنا', notifications: 'التنبيهات', demoNotices: 'تنبيهات تجريبية', markRead: 'تحديد الكل كمقروء', tagline: 'تعلّم بلا حدود', intro: 'اكتشف تجربة تعلم أكثر ذكاءً. استكشف الدورات، وتواصل مع مجتمعك الجامعي، وحوّل شغفك بالمعرفة إلى إنجاز.', explore: 'استكشف الدورات', unread: 'جديد', empty: 'لا توجد تنبيهات غير مقروءة.',
        notices: [['مرحبًا بك في ZU Hub', 'مساحتك التعليمية الجديدة قيد التجهيز.', 'الآن'], ['تعلّم بطريقة أفضل', 'استكشف الدورات والمصادر والتنبيهات في مكان واحد.', 'تجريبي']]
    }
};
let language = 'en'; try { language = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en' } catch { }
let noticesRead = false;
const escapeHTML = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
function applyLanguage() { const t = TEXT[language]; document.documentElement.lang = language; document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr'; document.querySelectorAll('[data-i18n]').forEach(n => { n.textContent = t[n.dataset.i18n] || n.textContent }); $('langLabel').textContent = language === 'ar' ? 'English' : 'العربية'; $('langBtn').setAttribute('aria-label', language === 'ar' ? 'Switch to English' : 'التبديل إلى العربية'); $('notifBtn').setAttribute('aria-label', t.notifications); renderNotices(); }
function renderNotices() { const t = TEXT[language]; $('notifDot').classList.toggle('hidden', noticesRead); $('noticeList').innerHTML = noticesRead ? '<div class="notice"><div class="notice-icon">✓</div><div><strong>' + escapeHTML(t.empty) + '</strong></div></div>' : t.notices.map(([h, p, time]) => '<article class="notice"><div class="notice-icon"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/></svg></div><div><strong>' + escapeHTML(h) + '</strong><p>' + escapeHTML(p) + '</p><small>' + escapeHTML(time) + '</small></div></article>').join(''); $('markRead').hidden = noticesRead }
/* Notification dropdown: anchored to the bell, kept inside the viewport, animated open/closed. */
let noticeTimer = 0;
function placeNotices() {
    const panel = $('notifPanel'), btn = $('notifBtn'); if (panel.hidden) return;
    const m = 12, gap = 12, vw = document.documentElement.clientWidth, vh = window.innerHeight;
    const b = btn.getBoundingClientRect();
    panel.style.position = 'fixed'; panel.style.right = 'auto'; panel.style.bottom = 'auto';
    const w = Math.min(panel.offsetWidth || 370, vw - 2 * m);
    const left = Math.min(Math.max(b.left + b.width / 2 - 36, m), Math.max(vw - w - m, m));   // starts just left of the bell centre, then clamped into view
    const top = b.bottom + gap;
    panel.style.left = left + 'px'; panel.style.top = top + 'px';
    panel.style.setProperty('--caret-x', Math.min(Math.max(b.left + b.width / 2 - left, 24), w - 24) + 'px');   // the caret always points at the bell
    panel.style.setProperty('--list-max', Math.max(vh - top - m - 150, 120) + 'px');                            // the list scrolls instead of overflowing the screen
}
function showNotices() {
    const panel = $('notifPanel'); clearTimeout(noticeTimer);
    panel.classList.remove('is-closing'); panel.hidden = false; placeNotices();
    $('notifBtn').setAttribute('aria-expanded', 'true');
    document.documentElement.classList.add('zu-notify-open');   // the slide-down banner steps aside while the bell is open
    trackNotices();
}
/* While open, stay locked to the bell's live position: the header slides in on load, and layout can shift (fonts, language, resize). */
let noticeRaf = 0, noticeKey = '';
function trackNotices() {
    cancelAnimationFrame(noticeRaf);
    const step = () => {
        if ($('notifPanel').hidden) return;
        const b = $('notifBtn').getBoundingClientRect();
        const key = [b.left, b.top, b.width, b.height, innerWidth, innerHeight].map(Math.round).join();
        if (key !== noticeKey) { noticeKey = key; placeNotices() }
        noticeRaf = requestAnimationFrame(step);
    };
    noticeKey = ''; step();
}
function hideNotices() {
    const panel = $('notifPanel'); if (panel.hidden || panel.classList.contains('is-closing')) return;
    $('notifBtn').setAttribute('aria-expanded', 'false'); panel.classList.add('is-closing'); clearTimeout(noticeTimer);
    document.documentElement.classList.remove('zu-notify-open');
    noticeTimer = setTimeout(() => { panel.hidden = true; panel.classList.remove('is-closing') }, matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 170);
}
(() => { const caret = document.createElement('span'); caret.className = 'zu-caret'; caret.setAttribute('aria-hidden', 'true'); $('notifPanel').prepend(caret) })();
window.addEventListener('resize', placeNotices); window.addEventListener('orientationchange', placeNotices);
new MutationObserver(() => requestAnimationFrame(placeNotices)).observe(document.documentElement, { attributes: true, attributeFilter: ['dir', 'lang'] });
$('langBtn').addEventListener('click', () => { language = language === 'en' ? 'ar' : 'en'; try { localStorage.setItem('zu-language', language) } catch { } applyLanguage() });
$('notifBtn').addEventListener('click', () => { const open = !$('notifPanel').hidden && !$('notifPanel').classList.contains('is-closing'); open ? hideNotices() : showNotices() });
$('markRead').addEventListener('click', () => { noticesRead = true; renderNotices() });
document.addEventListener('pointerdown', e => { if (!$('notifPanel').hidden && !$('notifPanel').contains(e.target) && !$('notifBtn').contains(e.target)) hideNotices() });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { const wasOpen = !$('notifPanel').hidden; hideNotices(); if (wasOpen) $('notifBtn').focus() } });
applyLanguage();


/* =====================================================================
   iOS 26 LIQUID GLASS — runtime
   1) tags every glass control with .lg
   2) (Chromium) builds a per-element edge-refraction displacement map
   3) sliding glass lens in the nav: click, press-and-drag, keyboard
   ===================================================================== */
(function () {
    const ua = navigator.userAgent;
    // SVG filters inside backdrop-filter only work in Chromium. Others keep blur+saturate.
    const REFRACT = /Chrome\/\d+/.test(ua) && !/Firefox\//.test(ua);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const GLASS = '.nav-links,.glass-btn,.panel-action,.popover-panel,.notice';
    const nav = document.getElementById('mainNav');
    const links = [...nav.querySelectorAll('.nav-link')];

    /* ---- sliding lens element ---- */
    const ind = document.createElement('span');
    ind.className = 'lg-indicator lg'; ind.setAttribute('aria-hidden', 'true');
    nav.prepend(ind);

    document.querySelectorAll(GLASS).forEach(el => el.classList.add('lg'));

    /* ---- refraction map ---- */
    const SVGNS = 'http://www.w3.org/2000/svg';
    let defs = null, uid = 0;
    if (REFRACT) {
        const svg = document.createElementNS(SVGNS, 'svg');
        svg.setAttribute('width', '0'); svg.setAttribute('height', '0');
        svg.setAttribute('aria-hidden', 'true');
        svg.style.cssText = 'position:absolute;width:0;height:0;pointer-events:none';
        defs = document.createElementNS(SVGNS, 'defs'); svg.appendChild(defs);
        document.body.appendChild(svg);
    }
    function makeMap(w, h, r, T) {
        const c = document.createElement('canvas'); c.width = w; c.height = h;
        const ctx = c.getContext('2d'), img = ctx.createImageData(w, h), d = img.data;
        const hw = w / 2, hh = h / 2;
        for (let y = 0; y < h; y++)for (let x = 0; x < w; x++) {
            const px = x + .5 - hw, py = y + .5 - hh;
            const qx = Math.abs(px) - (hw - r), qy = Math.abs(py) - (hh - r);
            const ox = Math.max(qx, 0), oy = Math.max(qy, 0);
            const depth = -(Math.hypot(ox, oy) + Math.min(Math.max(qx, qy), 0) - r);   // distance from the edge, inwards
            let nx = 0, ny = 0;
            if (ox > 0 || oy > 0) { const L = Math.hypot(ox, oy); nx = Math.sign(px) * ox / L; ny = Math.sign(py) * oy / L }
            else if (qx > qy) nx = Math.sign(px); else ny = Math.sign(py);
            const t = Math.min(Math.max(depth / T, 0), 1);
            const m = (1 - t) * (1 - t);                                               // strongest right at the rim
            const i = (y * w + x) * 4;
            d[i] = Math.round(127.5 * (1 - nx * m)); d[i + 1] = Math.round(127.5 * (1 - ny * m)); d[i + 2] = 128; d[i + 3] = 255;
        }
        ctx.putImageData(img, 0, 0);
        return c.toDataURL();
    }
    function build(el, W, H) {
        if (!REFRACT) return;
        const w = Math.round(W || el.offsetWidth), h = Math.round(H || el.offsetHeight);
        if (!w || !h || (el.__lgW === w && el.__lgH === h)) return;
        el.__lgW = w; el.__lgH = h;
        const cs = getComputedStyle(el); let rs = cs.borderTopLeftRadius, r = parseFloat(rs) || 0;
        if (rs.indexOf('%') > -1) r = w * r / 100;
        r = Math.min(r, w / 2, h / 2);
        const T = Math.max(5, Math.min(Math.min(w, h) * .3, 14)), S = T * 2.3;
        if (!el.__lgId) {
            el.__lgId = 'lgf' + (uid++);
            const f = document.createElementNS(SVGNS, 'filter');
            f.id = el.__lgId; f.setAttribute('x', '0'); f.setAttribute('y', '0');
            f.setAttribute('width', '1'); f.setAttribute('height', '1');
            f.setAttribute('color-interpolation-filters', 'sRGB');
            const im = document.createElementNS(SVGNS, 'feImage');
            im.setAttribute('x', '0'); im.setAttribute('y', '0'); im.setAttribute('result', 'map');
            im.setAttribute('preserveAspectRatio', 'none');
            const dm = document.createElementNS(SVGNS, 'feDisplacementMap');
            dm.setAttribute('in', 'SourceGraphic'); dm.setAttribute('in2', 'map');
            dm.setAttribute('xChannelSelector', 'R'); dm.setAttribute('yChannelSelector', 'G');
            f.append(im, dm); defs.appendChild(f);
            el.__lgImg = im; el.__lgMap = dm;
            el.style.setProperty('--lg-bf', 'url(#' + el.__lgId + ') blur(var(--lg-blur)) saturate(var(--lg-sat)) brightness(var(--lg-bright))');
        }
        el.__lgImg.setAttribute('width', w); el.__lgImg.setAttribute('height', h);
        el.__lgImg.setAttribute('href', makeMap(w, h, r, T));
        el.__lgMap.setAttribute('scale', S);
    }
    let ro = null;
    if (REFRACT) {
        ro = new ResizeObserver(es => es.forEach(e => { if (e.target !== ind) build(e.target) }));
        document.querySelectorAll('.lg').forEach(el => { if (el !== ind) ro.observe(el) });
    }
    /* notices are re-rendered by the page's own code: give every new one glass, free the old filters */
    function adopt(root) {
        root.querySelectorAll('.notice').forEach(el => {
            if (el.classList.contains('lg')) return;
            el.classList.add('lg'); if (ro) ro.observe(el);
        });
    }
    function drop(n) {
        if (n.nodeType !== 1) return;
        [n, ...n.querySelectorAll('.lg')].forEach(e => {
            if (e.__lgId) { const f = document.getElementById(e.__lgId); if (f) f.remove(); e.__lgId = null }
        });
    }
    ['noticeList'].forEach(id => {
        const host = document.getElementById(id); if (!host) return;
        adopt(host);
        new MutationObserver(ms => ms.forEach(m => { m.removedNodes.forEach(drop); adopt(host) })).observe(host, { childList: true });
    });

    /* ---- pointer-tracked specular glow ---- */
    document.addEventListener('pointermove', e => {
        const el = e.target.closest && e.target.closest('.lg'); if (!el) return;
        const r = el.getBoundingClientRect();
        el.style.setProperty('--mx', (e.clientX - r.left) + 'px'); el.style.setProperty('--my', (e.clientY - r.top) + 'px');
    }, { passive: true });

    /* ---- sliding lens ---- */
    let current = null, placed = false;
    const clamp = (v, a, b) => Math.min(Math.max(v, a), b);
    function activeLink() { return nav.querySelector('.nav-link.active') || links[0] }
    function stretch() {
        if (reducedMotion) return;
        ind.classList.remove('stretch'); void ind.offsetWidth; ind.classList.add('stretch');
    }
    function moveTo(link, animate) {
        if (!link || !link.offsetWidth) return;
        const first = !placed;
        if (first || animate === false) { ind.classList.add('no-anim') }
        ind.style.width = link.offsetWidth + 'px'; ind.style.height = link.offsetHeight + 'px';
        ind.style.setProperty('--ix', link.offsetLeft + 'px'); ind.style.setProperty('--iy', link.offsetTop + 'px');
        build(ind, link.offsetWidth, link.offsetHeight);
        if (link !== current && !first) stretch();
        current = link;
        if (first || animate === false) { requestAnimationFrame(() => requestAnimationFrame(() => ind.classList.remove('no-anim'))) }
        placed = true;
    }
    function settle(animate) { moveTo(activeLink(), animate) }

    // follow the *active* button only (ignore our own .is-pressed / .peek class changes)
    let lastActive = null;
    new MutationObserver(() => { const a = activeLink(); if (a !== lastActive) { lastActive = a; settle() } }).observe(nav, { subtree: true, attributes: true, attributeFilter: ['class'] });
    new ResizeObserver(() => settle(false)).observe(nav);
    links.forEach(l => new ResizeObserver(() => settle(false)).observe(l));
    window.addEventListener('resize', () => settle(false), { passive: true });
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => settle(false));
    lastActive = activeLink(); settle();

    /* press + drag (mouse / pen): the lens lifts, follows the pointer, and snaps to the nearest button */
    let pressed = null, moved = false, startX = 0, peek = null, swallowClick = false;
    const setPeek = l => { if (peek === l) return; peek && peek.classList.remove('peek'); peek = l; l && l.classList.add('peek') };
    nav.addEventListener('pointerdown', e => {
        const link = e.target.closest('.nav-link'); if (!link || e.button > 0) return;
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
        const nr = nav.getBoundingClientRect();
        const x = e.clientX - nr.left - nav.clientLeft + nav.scrollLeft;
        const lo = Math.min(...links.map(l => l.offsetLeft)), hi = Math.max(...links.map(l => l.offsetLeft + l.offsetWidth));
        const w = ind.offsetWidth;
        ind.style.setProperty('--ix', clamp(x - w / 2, lo, hi - w) + 'px');
        let best = null, bd = 1e9;
        links.forEach(l => { const d = Math.abs(l.offsetLeft + l.offsetWidth / 2 - x); if (d < bd) { bd = d; best = l } });
        if (best && best !== current) {
            // lens size follows the button it's hovering
            ind.style.width = best.offsetWidth + 'px'; ind.style.height = best.offsetHeight + 'px';
            ind.style.setProperty('--iy', best.offsetTop + 'px');
            build(ind, best.offsetWidth, best.offsetHeight); current = best;
        }
        setPeek(best);
    }
    function onUp(e) {
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp); window.removeEventListener('pointercancel', onUp);
        ind.classList.remove('is-pressed', 'is-dragging'); setPeek(null);
        const target = current;
        // commit only when the pointer is let go over the nav itself (not cancelled, not dropped somewhere else)
        const r = nav.getBoundingClientRect(), pad = 8;
        const over = e && e.type === 'pointerup' && e.clientX >= r.left - pad && e.clientX <= r.right + pad && e.clientY >= r.top - pad && e.clientY <= r.bottom + pad;
        if (moved) { swallowClick = true; setTimeout(() => { swallowClick = false }, 0) }   // the drag was the gesture: no second, accidental click
        if (moved && over && target && target.tagName === 'A' && target.href && !target.classList.contains('active')) {
            settle(); window.location.assign(target.href);
        } else if (moved) {
            current = null; settle();                                                          // snap the lens back to the current page
        } else {
            setTimeout(settle, 80);                                                            // a plain press: the click itself navigates
        }
        pressed = null; moved = false;
    }
    nav.addEventListener('dragstart', e => e.preventDefault());           // a pressed link must not turn into a browser drag-and-drop
    nav.addEventListener('click', e => { if (swallowClick) { e.stopPropagation(); e.preventDefault() } }, true);
})();

'use strict';


(function () {
    const label = document.querySelector('[data-login-label]');
    function refreshLogin() { const ar = document.documentElement.lang === 'ar'; if (label) label.textContent = ar ? 'تسجيل الدخول' : 'Log in'; document.getElementById('loginLink')?.setAttribute('aria-label', ar ? 'تسجيل الدخول' : 'Log in'); }
    const languageButton = document.getElementById('langBtn');
    languageButton?.addEventListener('click', () => queueMicrotask(refreshLogin));
    refreshLogin();
    // The identity is a visual university-inspired treatment, using the provided crest.
})();

/* Sync the logo's accessibility state whenever the bilingual UI changes. */
(function () {
    const arLogo = document.querySelector('.campus-brand.brand-ar');
    const enLogo = document.querySelector('.campus-brand.brand-en');
    const tools = document.getElementById('headerTools');
    const left = document.getElementById('headerLeft');
    const right = document.getElementById('headerRight');
    function syncLogos() {
        const ar = document.documentElement.lang === 'ar';
        if (arLogo) { arLogo.hidden = !ar; arLogo.setAttribute('aria-hidden', String(!ar)); }
        if (enLogo) { enLogo.hidden = ar; enLogo.setAttribute('aria-hidden', String(ar)); }
        // Relocate the actual button group: English = left tools/right crest;
        // Arabic = left crest/right tools. Moving the node keeps handlers intact.
        const target = ar ? right : left;
        if (tools && target && tools.parentElement !== target) target.appendChild(tools);
    }
    syncLogos();
    new MutationObserver(syncLogos).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
})();


/* ZU Hub role-aware navbar labels and guest course access. */
(function () {
    const translations = {
        en: { courses: 'Courses', myCourses: 'My Courses', management: 'Management', logout: 'Log out', student: 'Student', instructor: 'Instructor', manager: 'Management' },
        ar: { courses: 'الدورات', myCourses: 'دوراتي', management: 'الإدارة', logout: 'تسجيل الخروج', student: 'طالب', instructor: 'دكتور', manager: 'إدارة' }
    };
    function translateZuNav() {
        const lang = document.documentElement.lang === 'ar' ? 'ar' : 'en';
        const t = translations[lang];
        document.querySelectorAll('[data-nav-label]').forEach(el => { el.textContent = t[el.dataset.navLabel] || el.textContent; });
        document.querySelectorAll('[data-role]').forEach(el => { el.textContent = t[el.dataset.role] || el.textContent; });
        document.querySelector('.zu-home-link')?.setAttribute('aria-label', lang === 'ar' ? 'الرئيسية' : 'Home');
    }
    document.getElementById('langBtn')?.addEventListener('click', () => queueMicrotask(translateZuNav));
    translateZuNav();
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.zu-account[open]').forEach(menu => { if (!menu.contains(e.target)) menu.removeAttribute('open') });
    });
})();

/* Real notifications for signed-in users (replaces the preview notices). Data comes from Laravel via window.ZU_NOTIFS. */
(function () {
    const data = window.ZU_NOTIFS; if (!data) return;
    const items = data.items;
    let alerts = data.alerts || 0;
    const label = document.querySelector('#notifPanel .panel-count');
    if (label) label.removeAttribute('data-i18n');
    const bell = '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>';
    const shield = '<svg viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.5 3.1 8.2 7.5 9.5 4.4-1.3 7.5-5 7.5-9.5V6z"/></svg>';
    renderNotices = function () {
        const unread = items.filter(n => !n.read).length;
        $('notifDot').classList.toggle('hidden', unread === 0);
        $('notifBtn').classList.toggle('zu-ring', alerts > 0);
        $('markRead').hidden = unread === 0;
        if (label) label.textContent = unread ? unread + ' new' : 'All caught up';
        $('noticeList').innerHTML = (items.length ? items.map(n =>
            '<a class="notice zu-notice' + (n.read ? ' is-read' : '') + (n.expired ? ' is-expired' : '') + (n.alert ? ' is-alert' : '') + (n.official ? '' : ' is-instructor') + '" href="' + escapeHTML(n.url) + '">' +
            '<div class="notice-icon">' + (n.official ? shield : bell) + '</div><div><strong>' + escapeHTML(n.title) + '</strong>' +
            (n.excerpt ? '<p>' + escapeHTML(n.excerpt) + '</p>' : '') +
            '<small>' + escapeHTML(n.sender) + ' · ' + escapeHTML(n.time || '') + '</small></div></a>'
        ).join('') : '<div class="notice"><div class="notice-icon">✓</div><div><strong>No notifications yet</strong></div></div>') +
            '<a class="zu-notice-all" href="' + escapeHTML(data.inboxUrl) + '">View all notifications →</a>';
    };
    $('markRead').addEventListener('click', () => {
        fetch(data.readAllUrl, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': window.ZU_CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => { if (!r.ok) throw 0; items.forEach(n => { n.read = true; n.alert = false }); alerts = 0; renderNotices() }).catch(() => { });
    });
    renderNotices();
})();
