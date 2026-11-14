(() => {
  'use strict';
  const dict = {
    en: {
      back: 'Back to homepage', brandTagline: 'ZARQA UNIVERSITY · LEARNING PLATFORM',
      welcome: 'WELCOME BACK', title: 'Your learning starts here.',
      description: 'Sign in to access your courses and university learning space.',
      email: 'Email address', password: 'Password', signIn: 'Sign in',
      show: 'Show', hide: 'Hide',
      footer: 'One secure space for students, instructors and management.', copyright: 'Zarqa University',
      emailPlaceholder: 'name@example.com', passwordPlaceholder: 'Enter your password'
    },
    ar: {
      back: 'العودة إلى الرئيسية', brandTagline: 'جامعة الزرقاء · المنصة التعليمية',
      welcome: 'مرحبًا بعودتك', title: 'رحلتك التعليمية تبدأ هنا.',
      description: 'سجّل الدخول للوصول إلى دوراتك ومساحتك التعليمية الجامعية.',
      email: 'البريد الإلكتروني', password: 'كلمة المرور', signIn: 'تسجيل الدخول',
      show: 'إظهار', hide: 'إخفاء',
      footer: 'مساحة آمنة واحدة للطلاب وأعضاء الهيئة التدريسية والإدارة.', copyright: 'جامعة الزرقاء',
      emailPlaceholder: 'name@example.com', passwordPlaceholder: 'أدخل كلمة المرور'
    }
  };
  const button = document.getElementById('languageButton');
  const password = document.getElementById('password');
  const toggle = document.getElementById('passwordToggle');
  const logo = document.getElementById('universityLogo');
  let lang = 'en';
  try { lang = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en'; } catch (_) {}
  function render() {
    const t = dict[lang];
    document.documentElement.lang = lang;
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.querySelectorAll('[data-i18n]').forEach((el) => {
      const key = el.getAttribute('data-i18n');
      if (Object.hasOwn(t, key)) el.textContent = t[key];
    });
    document.getElementById('email').placeholder = t.emailPlaceholder;
    password.placeholder = t.passwordPlaceholder;
    toggle.textContent = password.type === 'password' ? t.show : t.hide;
    button.textContent = lang === 'ar' ? 'English' : 'العربية';
    logo.src = lang === 'ar' ? logo.dataset.arSrc : logo.dataset.enSrc;
  }
  button.addEventListener('click', () => {
    lang = lang === 'ar' ? 'en' : 'ar';
    try { localStorage.setItem('zu-language', lang); } catch (_) {}
    render();
  });
  toggle.addEventListener('click', () => {
    password.type = password.type === 'password' ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', password.type === 'text' ? 'true' : 'false');
    render();
  });
  document.getElementById('loginForm').addEventListener('submit', () => {
    const submit = document.getElementById('submitButton');
    submit.disabled = true;
  });
  render();
})();
