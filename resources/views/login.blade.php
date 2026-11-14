<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#edf6ff">
    <title>ZU Hub | Sign in</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <style>
        /* Overrides for the original login design */
        .login-card .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 25px;
            text-align: center;
        }
        .login-card .brand img,
        .login-card .brand #universityLogo {
            display: block;
            width: min(100%, 300px);
            max-width: 300px;
            height: auto;
            object-fit: contain;
            flex: none;
            margin: 0 auto;
        }
        .login-card .heading { text-align: center; }
        .login-card .heading h1 {
            margin-top: 12px;
            letter-spacing: -.045em;
        }
        html[dir="rtl"] body { font-family: 'Cairo', Arial, sans-serif; }
        html[dir="rtl"] .login-card .heading h1 { letter-spacing: 0; }
        html[dir="rtl"] .form-group label { text-align: right; }
        html[dir="rtl"] .input-wrap input { text-align: right; }
        html[dir="rtl"] .submit-arrow { transform: rotate(180deg); }
        html[dir="rtl"] .back-arrow { transform: rotate(180deg); }
        .login-card .brand-divider,
        .login-card .brand-name,
        .login-card .card-footer { display: none; }
        .error-box:empty { display: none; }
        /* Always display the login form and both fields */
        #loginForm, #loginForm .form-group, #loginForm .input-wrap, #loginForm input,
        #loginForm .submit-button { visibility: visible; opacity: 1; }
        #loginForm { display: block; width: 100%; }
        #loginForm .form-group { display: block; }
        #loginForm .input-wrap { display: flex; }
        #loginForm input { display: block; }
        #loginForm .submit-button { display: flex; }
        @@media (max-width: 540px) {
            .login-card .brand img,
            .login-card .brand #universityLogo {
                width: min(100%, 260px);
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="back-link" href="{{ route('home') }}">
            <span class="back-arrow" aria-hidden="true">←</span>
            <span data-i18n="back">Back to homepage</span>
        </a>
        <button class="language-button" id="languageButton" type="button" aria-label="Switch language">العربية</button>
    </header>

    <main class="page">
        <section class="login-card" aria-labelledby="loginTitle">
            <div class="brand">
                <img id="universityLogo"
                     src="{{ asset('assets/zarqa-en-edge.png') }}"
                     data-en-src="{{ asset('assets/zarqa-en-edge.png') }}"
                     data-ar-src="{{ asset('assets/zarqa-ar-edge.png') }}"
                     alt="Zarqa University logo">
            </div>

            <div class="heading">
                <div class="eyebrow">
                    <span class="eyebrow-dot"></span>
                    <span data-i18n="welcome">WELCOME BACK</span>
                </div>
                <h1 id="loginTitle">ZU Hub</h1>
                <p data-i18n="description">Sign in with your university email and password.</p>
            </div>

            <div class="error-box" id="loginError" role="alert">{{ $errors->first() }}</div>

            <form action="{{ route('login.store') }}" method="POST" id="loginForm">
                @csrf
                <div class="form-group">
                    <label for="email" data-i18n="email">University email</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="3"/>
                            <path d="m4 7 8 6 8-6"/>
                        </svg>
                        <input type="email" name="email" id="email"
                               value="{{ old('email') }}" maxlength="255"
                               autocomplete="username" placeholder="name@zu.edu.jo"
                               required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" data-i18n="password">Password</label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <input type="password" name="password" id="password"
                               autocomplete="current-password" placeholder="Enter your password" required>
                        <button type="button" id="passwordToggle"
                                class="show-password" aria-controls="password"
                                aria-pressed="false" data-i18n="show">Show</button>
                    </div>
                </div>

                <button type="submit" class="submit-button" id="submitButton">
                    <span data-i18n="signIn">Sign in</span>
                    <span aria-hidden="true" class="submit-arrow">→</span>
                </button>
            </form>
        </section>
        <p class="copyright">© {{ date('Y') }} ZU Hub · <span data-i18n="copyright">Zarqa University</span></p>
    </main>

    <script>
        (() => {
            const translations = {
                en: {
                    back: 'Back to homepage', welcome: 'WELCOME BACK',
                    description: 'Sign in with your university email and password.',
                    email: 'University email', password: 'Password',
                    show: 'Show', hide: 'Hide', signIn: 'Sign in',
                    copyright: 'Zarqa University',
                    emailPlaceholder: 'name@zu.edu.jo',
                    passwordPlaceholder: 'Enter your password'
                },
                ar: {
                    back: 'العودة إلى الرئيسية', welcome: 'مرحبًا بعودتك',
                    description: 'سجّل الدخول باستخدام بريدك الإلكتروني الجامعي وكلمة المرور.',
                    email: 'البريد الإلكتروني الجامعي', password: 'كلمة المرور',
                    show: 'إظهار', hide: 'إخفاء', signIn: 'تسجيل الدخول',
                    copyright: 'جامعة الزرقاء',
                    emailPlaceholder: 'name@zu.edu.jo',
                    passwordPlaceholder: 'أدخل كلمة المرور'
                }
            };

            const languageButton = document.getElementById('languageButton');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const emailInput = document.getElementById('email');
            const universityLogo = document.getElementById('universityLogo');
            let language = 'en';
            try {
                language = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en';
            } catch (error) { /* Storage may be blocked */ }

            function render() {
                const t = translations[language];
                document.documentElement.lang = language;
                document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
                document.querySelectorAll('[data-i18n]').forEach(element => {
                    const key = element.getAttribute('data-i18n');
                    if (Object.prototype.hasOwnProperty.call(t, key)) {
                        element.textContent = t[key];
                    }
                });
                document.getElementById('loginTitle').textContent = 'ZU Hub';
                languageButton.textContent = language === 'ar' ? 'English' : 'العربية';
                emailInput.placeholder = t.emailPlaceholder;
                passwordInput.placeholder = t.passwordPlaceholder;
                passwordToggle.textContent = passwordInput.type === 'password' ? t.show : t.hide;
                universityLogo.src = language === 'ar' ? universityLogo.dataset.arSrc : universityLogo.dataset.enSrc;
            }

            languageButton.addEventListener('click', () => {
                language = language === 'ar' ? 'en' : 'ar';
                try { localStorage.setItem('zu-language', language); } catch (error) {}
                render();
            });
            passwordToggle.addEventListener('click', () => {
                const show = passwordInput.type === 'password';
                passwordInput.type = show ? 'text' : 'password';
                passwordToggle.setAttribute('aria-pressed', String(show));
                render();
            });
            render();
        })();
    </script>
</body>
</html>
