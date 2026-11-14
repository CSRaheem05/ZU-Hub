
document.addEventListener('DOMContentLoaded', function () {

    const search = document.getElementById('studentCourseSearch');
    const filter = document.getElementById('studentStatusFilter');

    const cards = Array.from(
        document.querySelectorAll('.student-course-card')
    );

    const noResults = document.getElementById('studentNoResults');

    function filterCourses() {
        const searchValue = (search?.value || '')
            .trim()
            .toLowerCase();

        const selectedStatus = filter?.value || 'all';

        let visibleCourses = 0;

        cards.forEach(card => {
            const courseText = (card.dataset.search || '')
                .toLowerCase();

            const courseStatus = card.dataset.status;

            const matchesSearch = courseText.includes(searchValue);

            const matchesStatus =
                selectedStatus === 'all' ||
                selectedStatus === courseStatus;

            const shouldShow = matchesSearch && matchesStatus;

            card.hidden = !shouldShow;

            if (shouldShow) {
                visibleCourses++;
            }
        });

        if (noResults) {
            noResults.hidden = visibleCourses !== 0;
        }
    }

    search?.addEventListener('input', filterCourses);
    filter?.addEventListener('change', filterCourses);

    filterCourses();
});
