(function () {
    const toggle = document.getElementById('languageButton');
    if (!toggle) return;
    let lang = 'en';
    try { lang = localStorage.getItem('zu-language') === 'ar' ? 'ar' : 'en'; } catch (_) { }
    function render() {
        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
        toggle.textContent = lang === 'ar' ? 'English' : 'العربية';
        document.querySelectorAll('[data-en][data-ar]').forEach((element) => {
            element.textContent = element.getAttribute('data-' + lang);
        });
    }
    // Avoid translating a translated role on successive clicks.
    const role = document.querySelector('[data-role]');
    const initialRole = role ? role.textContent.trim().toLowerCase() : '';
    if (role) role.dataset.originalRole = initialRole;
    function paint() {
        render();
        if (role) {
            const labels = { student: ['Student', 'طالب'], instructor: ['Instructor', 'دكتور'], manager: ['Management', 'إدارة'] };
            const labelsForRole = labels[initialRole];
            if (labelsForRole) role.textContent = lang === 'ar' ? labelsForRole[1] : labelsForRole[0];
        }
    }
    toggle.addEventListener('click', function () {
        lang = lang === 'en' ? 'ar' : 'en';
        try { localStorage.setItem('zu-language', lang); } catch (_) { }
        paint();
    });
    paint();
})();
