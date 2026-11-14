
document.addEventListener('DOMContentLoaded', function () {

    const search = document.getElementById('availableSearch');
    const cards = Array.from(
        document.querySelectorAll('.available-card')
    );

    const counter = document.getElementById('availableCount');
    const noResults = document.getElementById('availableNoResults');

    function filterCourses() {
        const term = (search?.value || '')
            .trim()
            .toLowerCase();

        let visibleCount = 0;

        cards.forEach(card => {
            const text = (card.dataset.search || '').toLowerCase();
            const matches = text.includes(term);

            card.hidden = !matches;

            if (matches) {
                visibleCount++;
            }
        });

        if (counter) {
            counter.textContent = visibleCount;
        }

        if (noResults) {
            noResults.hidden = visibleCount !== 0;
        }
    }

    search?.addEventListener('input', filterCourses);

    function applyLanguage() {
        let lang = 'en';

        try {
            lang = localStorage.getItem('zu-language') === 'ar'
                ? 'ar'
                : 'en';
        } catch (_) {}

        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar'
            ? 'rtl'
            : 'ltr';

        document.querySelectorAll('[data-en][data-ar]')
            .forEach(element => {
                element.textContent =
                    element.getAttribute('data-' + lang);
            });

        if (search) {
            search.placeholder = lang === 'ar'
                ? 'ابحث عن دورة أو مدرس...'
                : 'Search by course or instructor...';
        }
    }

    applyLanguage();
    filterCourses();
});
