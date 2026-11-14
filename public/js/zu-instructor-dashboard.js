
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('courseSearch');
    const status = document.getElementById('courseStatus');
    const cards = Array.from(
        document.querySelectorAll('.dash-course')
    );

    const resultCount = document.getElementById('courseResultCount');
    const empty = document.getElementById('noSearchResults');

    function filterCourses() {
        const term = (search?.value || '').trim().toLowerCase();
        const selectedStatus = status?.value || 'all';

        let visibleCount = 0;

        cards.forEach(card => {
            const text = (card.dataset.search || '').toLowerCase();
            const courseStatus = card.dataset.status;

            const matchesSearch = text.includes(term);
            const matchesStatus = selectedStatus === 'all' ||
                selectedStatus === courseStatus;

            const visible = matchesSearch && matchesStatus;

            card.hidden = !visible;

            if (visible) visibleCount++;
        });

        if (resultCount) {
            resultCount.textContent =
                visibleCount + ' course(s) found';
        }

        if (empty) {
            empty.hidden = visibleCount !== 0;
        }
    }

    if (search) {
        search.addEventListener('input', filterCourses);
    }

    if (status) {
        status.addEventListener('change', filterCourses);
    }

    filterCourses();

    document.querySelectorAll('.dash-delete-form')
        .forEach(form => {
            form.addEventListener('submit', function (event) {
                const title = form.dataset.courseTitle || 'this course';

                const confirmed = confirm(
                    'Are you sure you want to delete "' +
                    title +
                    '"?\n\nThis action cannot be undone.'
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            });
        });
});
