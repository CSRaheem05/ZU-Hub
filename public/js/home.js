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
    animations.push(animate('footer', [{ transform: 'translateY(160%)', offset: 0 }, { transform: 'translateY(160%)', easing: 'cubic-bezier(.2,.8,.2,1)', offset: .52 }, { transform: 'translateY(0)', offset: .90 }, { transform: 'translateY(0)', offset: 1 }]));
    animations.push(animate('footerRule', [{ transform: 'scaleX(0)', offset: 0 }, { transform: 'scaleX(0)', offset: .4 }, { transform: 'scaleX(1)', offset: .95 }, { transform: 'scaleX(1)', offset: 1 }]));
    animations.push(animate('microcopy', [{ opacity: 0, transform: 'translateY(14px)', offset: 0 }, { opacity: 0, transform: 'translateY(14px)', offset: .72 }, { opacity: 1, transform: 'translateY(0)', offset: 1 }]));
    animations.push(animate('footerSmall', [{ opacity: 0, offset: 0 }, { opacity: 0, offset: .85 }, { opacity: 1, offset: 1 }]));
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
        home: 'Home', features: 'Features', courses: 'Courses', contact: 'Contact', notifications: 'Notifications', demoNotices: 'Preview only', markRead: 'Mark all as read', tagline: 'Learn beyond the classroom', intro: '', explore: 'Explore Courses', unread: 'New', empty: 'You are all caught up.', note: 'Preview interface only — connect these sections to Laravel routes and your database when integrating ZU Hub.', eyebrow: 'THE ZU HUB EXPERIENCE',
        panels: { features: { title: 'Everything you need, beautifully connected.', lead: 'A focused digital learning experience for students, instructors, and administrators.', cards: [['✦', 'Your learning space', 'One place for lessons, resources, and progress.'], ['▤', 'Course materials', 'Find the files and content you need.'], ['◷', 'Stay in the loop', 'Follow important course updates.'], ['◎', 'Built for everyone', 'Thoughtful bilingual support and clear navigation.']] }, courses: { title: 'Your next skill starts here.', lead: 'Explore a selection of course areas. Live course listings will appear when connected to the ZU Hub database.', tags: ['Laravel & PHP', 'Python', 'Databases', 'Artificial Intelligence', 'Web Development', 'Data Structures', 'Cybersecurity'] }, contact: { title: 'Let’s stay connected.', lead: 'ZU Hub is a university learning project. Official contact information and support forms will be added when the platform is connected.', cards: [['✉', 'Student support', 'Contact details will be provided by the project team.'], ['◇', 'Course questions', 'Instructors and students can connect through the platform.']] } },
        notices: [['Welcome to ZU Hub', 'Your learning space is getting ready.', 'Just now'], ['A better way to learn', 'Explore courses, resources, and updates in one place.', 'Preview']]
    },
    ar: {
        home: 'الرئيسية', features: 'المميزات', courses: 'الدورات', contact: 'تواصل معنا', notifications: 'التنبيهات', demoNotices: 'تنبيهات تجريبية', markRead: 'تحديد الكل كمقروء', tagline: 'تعلّم بلا حدود', intro: 'اكتشف تجربة تعلم أكثر ذكاءً. استكشف الدورات، وتواصل مع مجتمعك الجامعي، وحوّل شغفك بالمعرفة إلى إنجاز.', explore: 'استكشف الدورات', unread: 'جديد', empty: 'لا توجد تنبيهات غير مقروءة.', note: 'هذه واجهة تجريبية؛ سيتم ربط الأقسام والتنبيهات بمشروع Laravel وقاعدة بيانات ZU Hub لاحقًا.', eyebrow: 'تجربة ZU HUB',
        panels: { features: { title: 'كل ما تحتاجه في مكان واحد.', lead: 'تجربة تعليمية متكاملة للطلاب والمدرسين والإدارة، بتصميم واضح وعصري.', cards: [['✦', 'مساحة تعلم خاصة بك', 'دروسك وملفاتك وتقدمك في مكان واحد.'], ['▤', 'المواد التعليمية', 'الوصول إلى الملفات والمحتوى بسهولة.'], ['◷', 'ابقَ على اطلاع', 'تابع آخر التحديثات المهمة لدوراتك.'], ['◎', 'مصمم للجميع', 'دعم العربية والإنجليزية وتنقل سلس.']] }, courses: { title: 'مهارتك القادمة تبدأ هنا.', lead: 'استكشف مجالات الدورات المقترحة. ستظهر الدورات الحقيقية بعد ربط الواجهة بقاعدة البيانات.', tags: ['Laravel و PHP', 'Python', 'قواعد البيانات', 'الذكاء الاصطناعي', 'تطوير الويب', 'تراكيب البيانات', 'الأمن السيبراني'] }, contact: { title: 'خلّينا على تواصل.', lead: 'ZU Hub مشروع تعليمي جامعي. ستُضاف بيانات التواصل الرسمية ونموذج الدعم عند ربط المنصة.', cards: [['✉', 'دعم الطلاب', 'سيتم توفير بيانات التواصل من فريق المشروع.'], ['◇', 'استفسارات الدورات', 'تواصل الطلاب والمدرسين من داخل المنصة.']] } },
        notices: [['مرحبًا بك في ZU Hub', 'مساحتك التعليمية الجديدة قيد التجهيز.', 'الآن'], ['تعلّم بطريقة أفضل', 'استكشف الدورات والمصادر والتنبيهات في مكان واحد.', 'تجريبي']]
    }
};
let language = 'en'; try { language = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en' } catch { }
let noticesRead = false; let currentView = 'home';
const escapeHTML = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
function applyLanguage() { const t = TEXT[language]; document.documentElement.lang = language; document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr'; document.querySelectorAll('[data-i18n]').forEach(n => { n.textContent = t[n.dataset.i18n] || n.textContent }); $('langLabel').textContent = language === 'ar' ? 'English' : 'العربية'; $('langBtn').setAttribute('aria-label', language === 'ar' ? 'Switch to English' : 'التبديل إلى العربية'); $('notifBtn').setAttribute('aria-label', t.notifications); renderNotices(); if (!$('overlay').hidden) populateSheet(currentView) }
function renderNotices() { const t = TEXT[language]; $('notifDot').classList.toggle('hidden', noticesRead); $('noticeList').innerHTML = noticesRead ? '<div class="notice"><div class="notice-icon">✓</div><div><strong>' + escapeHTML(t.empty) + '</strong></div></div>' : t.notices.map(([h, p, time]) => '<article class="notice"><div class="notice-icon"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/></svg></div><div><strong>' + escapeHTML(h) + '</strong><p>' + escapeHTML(p) + '</p><small>' + escapeHTML(time) + '</small></div></article>').join(''); $('markRead').hidden = noticesRead }
function hideNotices() { $('notifPanel').hidden = true; $('notifBtn').setAttribute('aria-expanded', 'false') }
function populateSheet(view) { const t = TEXT[language], p = t.panels[view]; if (!p) return; $('sheetEyebrow').textContent = t.eyebrow; $('sheetTitle').textContent = p.title; $('sheetLead').textContent = p.lead; $('sheetNote').textContent = t.note; $('sheetContent').innerHTML = p.cards ? '<div class="card-grid">' + p.cards.map(([i, h, body]) => '<article class="feature-card"><div class="card-icon">' + escapeHTML(i) + '</div><h3>' + escapeHTML(h) + '</h3><p>' + escapeHTML(body) + '</p></article>').join('') + '</div>' : '<div class="course-tags">' + p.tags.map(x => '<span class="course-tag"><i class="tag-dot"></i>' + escapeHTML(x) + '</span>').join('') + '</div>' }
function openSheet(view) { if (view === 'home') { closeSheet(); return } hideNotices(); currentView = view; populateSheet(view); $('overlay').hidden = false; document.querySelectorAll('.nav-link').forEach(b => b.classList.toggle('active', b.dataset.view === view)); $('sheet').focus({ preventScroll: true }) }
function closeSheet() { $('overlay').hidden = true; currentView = 'home'; document.querySelectorAll('.nav-link').forEach(b => b.classList.toggle('active', b.dataset.view === 'home')) }
$('langBtn').addEventListener('click', () => { language = language === 'en' ? 'ar' : 'en'; try { localStorage.setItem('zu-language', language) } catch { } applyLanguage() });
$('notifBtn').addEventListener('click', () => { const willOpen = $('notifPanel').hidden; $('notifPanel').hidden = !willOpen; $('notifBtn').setAttribute('aria-expanded', String(willOpen)) });
$('markRead').addEventListener('click', () => { noticesRead = true; renderNotices() });
document.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => openSheet(b.dataset.view)));
$('explore')?.addEventListener('click', () => openSheet('courses'));
$('closeSheet').addEventListener('click', closeSheet); $('overlay').addEventListener('click', e => { if (e.target === $('overlay')) closeSheet() });
document.addEventListener('pointerdown', e => { if (!$('notifPanel').hidden && !$('notifPanel').contains(e.target) && !$('notifBtn').contains(e.target)) hideNotices() });
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeSheet(); hideNotices() } });
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
    const GLASS = '.nav-links,.glass-btn,.cta,.pill-icon,.panel-action,.popover-panel,.sheet,.feature-card,.course-tag,.notice';
    const nav = document.getElementById('mainNav');
    const links = [...nav.querySelectorAll('.nav-link')];

    /* ---- sliding lens element ---- */
    const ind = document.createElement('span');
    ind.className = 'lg-indicator lg'; ind.setAttribute('aria-hidden', 'true');
    nav.prepend(ind);

    /* the sheet scrolls inside its own wrapper, so the glass layers on the sheet itself never scroll away */
    const sheetEl = document.getElementById('sheet');
    if (sheetEl && !sheetEl.querySelector('.sheet-scroll')) {
        const wrap = document.createElement('div'); wrap.className = 'sheet-scroll';
        while (sheetEl.firstChild) wrap.appendChild(sheetEl.firstChild);
        sheetEl.appendChild(wrap); sheetEl.setAttribute('tabindex', '-1');
    }
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
    /* cards, chips and notices are re-rendered by the page's own code: give every new one glass, free the old filters */
    function adopt(root) {
        root.querySelectorAll('.feature-card,.course-tag,.notice').forEach(el => {
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
    ['sheetContent', 'noticeList'].forEach(id => {
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
    function onUp() {
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp); window.removeEventListener('pointercancel', onUp);
        ind.classList.remove('is-pressed', 'is-dragging'); setPeek(null);
        if (moved && current) {
            swallowClick = true; setTimeout(() => { swallowClick = false }, 0);
            openSheet(current.dataset.view);        // existing function: toggles .active, opens/closes the sheet
            settle();
        } else {
            setTimeout(settle, 80);                  // if nothing changed, glide back to the active button
        }
        pressed = null; moved = false;
    }
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
    // Demo rule: guests must sign in before accessing course listings or course cards.
    if (document.body.dataset.zuGuest === '1') {
        const login = document.body.dataset.loginUrl || '/login';
        document.addEventListener('click', function (e) {
            const target = e.target.closest('#explore, #finalExplore, .course-card, [data-jump="courses"], [data-jump="contact"]');
            if (!target) return;
            e.preventDefault(); e.stopImmediatePropagation(); window.location.assign(login);
        }, true);
    }
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.zu-account[open]').forEach(menu => { if (!menu.contains(e.target)) menu.removeAttribute('open') });
    });
})();

// Translate the compact bottom section when the existing language control changes.
(function () {
    function syncBottomLanguage() {
        const ar = document.documentElement.lang === 'ar';
        document.querySelectorAll('[data-zu-en][data-zu-ar]').forEach(el => {
            el.textContent = el.getAttribute(ar ? 'data-zu-ar' : 'data-zu-en');
        });
    }
    syncBottomLanguage();
    document.getElementById('langBtn')?.addEventListener('click', () => queueMicrotask(syncBottomLanguage));
    document.querySelector('.zu-backtop')?.addEventListener('click', function (e) {
        e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
