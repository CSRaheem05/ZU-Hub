(() => {
  'use strict';

  const dict = {
    en: {
      back: 'Back to homepage', welcome: 'Welcome back', title: 'Sign in',
      email: 'University email', password: 'Password', signIn: 'Sign in', signingIn: 'Signing in…',
      show: 'Show password', hide: 'Hide password', copyright: 'Zarqa University',
      emailPlaceholder: 'name@zu.edu.jo', passwordPlaceholder: 'Enter your password',
      switchTo: 'العربية', logoAlt: 'Zarqa University logo', capsOn: 'Caps Lock is on'
    },
    ar: {
      back: 'العودة إلى الرئيسية', welcome: 'مرحبًا بعودتك', title: 'تسجيل الدخول',
      email: 'البريد الإلكتروني الجامعي', password: 'كلمة المرور', signIn: 'تسجيل الدخول', signingIn: 'جارٍ تسجيل الدخول…',
      show: 'إظهار كلمة المرور', hide: 'إخفاء كلمة المرور', copyright: 'جامعة الزرقاء',
      emailPlaceholder: 'name@zu.edu.jo', passwordPlaceholder: 'أدخل كلمة المرور',
      switchTo: 'English', logoAlt: 'شعار جامعة الزرقاء', capsOn: 'مفتاح Caps Lock مفعّل'
    }
  };

  // Laravel's own (English) validation messages, so the page speaks one language.
  const serverAr = [
    [/email field is required/i, 'يرجى إدخال البريد الإلكتروني.'],
    [/email field must be a valid/i, 'يرجى إدخال بريد إلكتروني صالح.'],
    [/password field is required/i, 'يرجى إدخال كلمة المرور.'],
    [/too many/i, 'محاولات كثيرة. يرجى المحاولة لاحقًا.']
  ];

  const $ = (id) => document.getElementById(id);
  const html = document.documentElement;
  const form = $('loginForm'), email = $('email'), password = $('password');
  const toggle = $('passwordToggle'), submit = $('submitButton'), errorBox = $('loginError');
  const langBtn = $('languageButton'), logo = $('universityLogo'), hint = $('capsHint');
  const rawError = errorBox.textContent.trim();     // what Laravel rendered (bilingual for bad credentials)

  let lang = html.lang === 'ar' ? 'ar' : 'en';
  let busy = false;

  function localisedError(t) {
    if (!rawError) return '';
    const parts = rawError.split(/\s+\/\s+/);
    if (parts.length === 2) return lang === 'ar' ? parts[1] : parts[0];           // "English / العربية"
    if (lang === 'ar') for (const [re, ar] of serverAr) if (re.test(rawError)) return ar;
    return rawError;
  }

  function render() {
    const t = dict[lang];
    html.lang = lang;
    html.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      if (Object.hasOwn(t, key)) el.textContent = t[key];
    });
    email.placeholder = t.emailPlaceholder;
    password.placeholder = t.passwordPlaceholder;
    langBtn.querySelector('span').textContent = t.switchTo;
    langBtn.setAttribute('lang', lang === 'ar' ? 'en' : 'ar');
    langBtn.setAttribute('aria-label', lang === 'ar' ? 'Switch to English' : 'التبديل إلى العربية');
    logo.src = lang === 'ar' ? logo.dataset.arSrc : logo.dataset.enSrc;
    logo.alt = t.logoAlt;
    toggle.setAttribute('aria-label', password.type === 'password' ? t.show : t.hide);
    errorBox.textContent = localisedError(t);
    if (!hint.hidden) hint.textContent = t.capsOn;
    document.title = 'ZU Hub | ' + t.title;
  }

  langBtn.addEventListener('click', () => {
    lang = lang === 'ar' ? 'en' : 'ar';
    try { localStorage.setItem('zu-language', lang); } catch (_) { /* storage blocked */ }
    render();
  });

  toggle.addEventListener('click', () => {
    const show = password.type === 'password';
    password.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(show));
    render();
    password.focus({ preventScroll: true });
  });

  // mark the fields the server complained about, and clear the mark once the person starts fixing it
  const wraps = [email, password].map((i) => i.closest('.input-wrap'));
  if (rawError) {
    email.setAttribute('aria-invalid', 'true');
    email.setAttribute('aria-describedby', 'loginError');
    wraps[0].classList.add('is-invalid');
  }
  [email, password].forEach((input, i) => input.addEventListener('input', () => {
    wraps[i].classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');
  }));

  // Caps Lock hint on the password field
  function caps(e) {
    if (typeof e.getModifierState !== 'function') return;
    const on = e.getModifierState('CapsLock');
    hint.hidden = !on;
    hint.textContent = on ? dict[lang].capsOn : '';
  }
  password.addEventListener('keydown', caps);
  password.addEventListener('keyup', caps);
  password.addEventListener('blur', () => { hint.hidden = true; hint.textContent = '' });

  // submission feedback. The form posts normally (CSRF, validation and throttling stay server-side); we only
  // show progress and ignore a second press while the request is in flight.
  form.addEventListener('submit', (e) => {
    if (busy) { e.preventDefault(); return; }
    if (!form.checkValidity()) return;                 // native validation shows its own message first
    busy = true;
    submit.classList.add('is-loading');
    submit.setAttribute('aria-disabled', 'true');
    submit.setAttribute('aria-busy', 'true');
    submit.querySelector('[data-i18n="signIn"]').textContent = dict[lang].signingIn;
  });

  // coming back via the browser's back button must not leave the form stuck in its loading state
  window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    busy = false;
    submit.classList.remove('is-loading');
    submit.removeAttribute('aria-disabled'); submit.removeAttribute('aria-busy');
    submit.querySelector('[data-i18n="signIn"]').textContent = dict[lang].signIn;
  });

  render();

  // ---- micro-interactions (purely decorative; skipped when the visitor prefers reduced motion) ----
  const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const panel = document.querySelector('.panel');

  // a soft light that follows the pointer across the glass card (mouse/trackpad only)
  if (!still && panel && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    let frame = 0;
    panel.addEventListener('pointermove', (e) => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        const r = panel.getBoundingClientRect();
        panel.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        panel.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });
    panel.addEventListener('pointerenter', () => panel.classList.add('is-lit'));
    panel.addEventListener('pointerleave', () => panel.classList.remove('is-lit'));
  }

  // press ripple on the sign-in button, starting where it was pressed
  submit.addEventListener('pointerdown', (e) => {
    if (still || busy) return;
    const r = submit.getBoundingClientRect();
    const dot = document.createElement('span');
    dot.className = 'ripple';
    dot.setAttribute('aria-hidden', 'true');
    dot.style.left = (e.clientX - r.left) + 'px';
    dot.style.top = (e.clientY - r.top) + 'px';
    submit.appendChild(dot);
    dot.addEventListener('animationend', () => dot.remove(), { once: true });
  });

  // focus the email field on roomy, hover-capable screens only (on phones it would pop the keyboard and scroll the page)
  if (window.matchMedia('(hover: hover) and (min-height: 600px)').matches && !email.value) email.focus({ preventScroll: true });
  else if (email.value && rawError) password.focus({ preventScroll: true });
})();
