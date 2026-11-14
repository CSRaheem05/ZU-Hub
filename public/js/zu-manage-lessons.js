
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.lesson-type').forEach(select => {

        const form = select.closest('form');
        const videoFields = form.querySelector('.lesson-video-fields');
        const liveFields = form.querySelector('.lesson-live-fields');

        function updateFields() {
            const isLive = select.value === 'live';

            videoFields.hidden = isLive;
            liveFields.hidden = !isLive;

            videoFields.querySelectorAll('input').forEach(input => {
                input.disabled = isLive;
                input.required = !isLive;
            });

            liveFields.querySelectorAll('input').forEach(input => {
                input.disabled = !isLive;
                input.required = isLive;
            });
        }

        select.addEventListener('change', updateFields);
        updateFields();
    });

    document.querySelectorAll('.lesson-edit-toggle')
        .forEach(button => {

            button.addEventListener('click', function () {
                const panel = document.getElementById(
                    button.getAttribute('aria-controls')
                );

                const opening = panel.hidden;

                panel.hidden = !opening;
                button.setAttribute(
                    'aria-expanded',
                    opening ? 'true' : 'false'
                );

                button.textContent = opening
                    ? 'Close Editor'
                    : 'Edit Lesson';
            });
        });

    document.querySelectorAll('.lesson-delete-form')
        .forEach(form => {
            form.addEventListener('submit', function (event) {
                if (!confirm(
                    'Delete this lesson permanently? This cannot be undone.'
                )) {
                    event.preventDefault();
                }
            });
        });

});
