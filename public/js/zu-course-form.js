
/* =====================================
   ZU HUB - LANGUAGE SWITCHING
   ===================================== */

(function () {
    const toggle = document.getElementById('languageButton');
    if (!toggle) return;

    let lang = 'en';

    try {
        lang = localStorage.getItem('zu-language') === 'ar'
            ? 'ar' : 'en';
    } catch (_) {}

    function render() {
        document.documentElement.lang = lang;
        document.documentElement.dir =
            lang === 'ar' ? 'rtl' : 'ltr';

        toggle.textContent =
            lang === 'ar' ? 'English' : 'العربية';

        document.querySelectorAll('[data-en][data-ar]')
            .forEach(element => {
                element.textContent =
                    element.getAttribute('data-' + lang);
            });
    }

    const role = document.querySelector('[data-role]');
    const initialRole = role
        ? role.textContent.trim().toLowerCase()
        : '';

    if (role) {
        role.dataset.originalRole = initialRole;
    }

    function paint() {
        render();

        if (role) {
            const labels = {
                student: ['Student', 'طالب'],
                instructor: ['Instructor', 'دكتور'],
                manager: ['Management', 'إدارة']
            };

            const values = labels[initialRole];

            if (values) {
                role.textContent =
                    lang === 'ar' ? values[1] : values[0];
            }
        }
    }

    toggle.addEventListener('click', function () {
        lang = lang === 'en' ? 'ar' : 'en';

        try {
            localStorage.setItem('zu-language', lang);
        } catch (_) {}

        paint();
    });

    paint();
})();


/* =====================================
   ZU HUB - CREATE / EDIT COURSE
   ===================================== */

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('courseForm');
    const dataElement = document.getElementById('subjectsData');

    if (!form || !dataElement) return;

    // 1. LOAD SUBJECT DATA

    let subjects = [];

    try {
        subjects = JSON.parse(dataElement.textContent);
    } catch (error) {
        console.error('Cannot load subject data:', error);
        return;
    }

    const collegesMap = new Map();
    const departmentsMap = new Map();

    subjects.forEach(item => {
        const collegeCode = String(item.college_code);
        const departmentCode = String(item.department_code);

        if (!collegesMap.has(collegeCode)) {
            collegesMap.set(collegeCode, {
                value: collegeCode,
                text: item.college_name
            });
        }

        if (!departmentsMap.has(departmentCode)) {
            departmentsMap.set(departmentCode, {
                value: departmentCode,
                text: item.department_name,
                college: collegeCode
            });
        }
    });

    const colleges = Array.from(collegesMap.values())
        .sort((a, b) => a.text.localeCompare(b.text));

    const departments = Array.from(departmentsMap.values())
        .sort((a, b) => a.text.localeCompare(b.text));

    const subjectItems = subjects.map(item => ({
        value: String(item.course_id),
        text: item.course_name + ' (' + item.course_id + ')',
        department: String(item.department_code)
    }));


    // 2. SEARCHABLE DROPDOWNS

    function dropdown(type) {
        return {
            root: document.getElementById(type + 'Dropdown'),
            toggle: document.getElementById(type + 'Toggle'),
            label: document.getElementById(type + 'Label'),
            panel: document.getElementById(type + 'Panel'),
            search: document.getElementById(type + 'Search'),
            options: document.getElementById(type + 'Options'),
            field: document.getElementById(
                type === 'subject' ? 'course_id' : type + '_id'
            )
        };
    }

    const dropdowns = {
        college: dropdown('college'),
        department: dropdown('department'),
        subject: dropdown('subject')
    };

    function closeAll() {
        Object.values(dropdowns).forEach(d => {
            d.panel.hidden = true;
            d.toggle.setAttribute('aria-expanded', 'false');
        });
    }

    function getItems(type) {
        if (type === 'college') {
            return colleges;
        }

        if (type === 'department') {
            return departments.filter(item =>
                item.college === dropdowns.college.field.value
            );
        }

        return subjectItems.filter(item =>
            item.department === dropdowns.department.field.value
        );
    }

    function renderOptions(type) {
        const d = dropdowns[type];
        const search = d.search.value.trim().toLowerCase();

        const items = getItems(type).filter(item =>
            item.text.toLowerCase().includes(search) ||
            item.value.toLowerCase().includes(search)
        );

        d.options.replaceChildren();

        if (items.length === 0) {
            const message = document.createElement('div');
            message.className = 'zc-no-results';
            message.textContent = 'No matching options found.';
            d.options.appendChild(message);
            return;
        }

        items.forEach(item => {
            const button = document.createElement('button');

            button.type = 'button';
            button.textContent = item.text;

            if (d.field.value === item.value) {
                button.classList.add('selected');
            }

            button.addEventListener('click', function () {
                selectOption(type, item);
            });

            d.options.appendChild(button);
        });
    }

    function resetSubject() {
        const d = dropdowns.subject;

        d.field.value = '';
        d.search.value = '';
        d.label.textContent = 'Select Department First';
    }

    function resetDepartment() {
        const d = dropdowns.department;

        d.field.value = '';
        d.search.value = '';
        d.label.textContent = 'Select College First';

        resetSubject();
    }

    function updateAvailability() {
        const hasCollege = !!dropdowns.college.field.value;
        const hasDepartment = !!dropdowns.department.field.value;

        dropdowns.department.toggle.disabled = !hasCollege;
        dropdowns.subject.toggle.disabled = !hasDepartment;

        if (!hasCollege) {
            dropdowns.department.label.textContent =
                'Select College First';
        } else if (!hasDepartment) {
            dropdowns.department.label.textContent =
                'Select Department';
        }

        if (!hasDepartment) {
            dropdowns.subject.label.textContent =
                'Select Department First';
        }
    }

    function selectOption(type, item) {
        const d = dropdowns[type];
        const changed = d.field.value !== item.value;

        d.field.value = item.value;
        d.label.textContent = item.text;
        d.search.value = '';

        if (type === 'college' && changed) {
            resetDepartment();
        }

        if (type === 'department' && changed) {
            resetSubject();
        }

        updateAvailability();
        closeAll();
    }

    Object.entries(dropdowns).forEach(([type, d]) => {

        d.toggle.addEventListener('click', function () {
            if (d.toggle.disabled) return;

            const opening = d.panel.hidden;

            closeAll();

            if (opening) {
                d.panel.hidden = false;
                d.toggle.setAttribute('aria-expanded', 'true');
                d.search.value = '';

                renderOptions(type);
                d.search.focus();
            }
        });

        d.search.addEventListener('input', function () {
            renderOptions(type);
        });

        d.search.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAll();
                d.toggle.focus();
            }

            if (event.key === 'Enter') {
                event.preventDefault();

                const first = d.options.querySelector('button');

                if (first) first.click();
            }
        });
    });

    document.addEventListener('click', function (event) {
        const inside = Object.values(dropdowns).some(d =>
            d.root.contains(event.target)
        );

        if (!inside) closeAll();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeAll();
    });


    // 3. RESTORE SELECTED SUBJECT

    const savedSubject = dropdowns.subject.field.value;

    if (savedSubject) {
        const selected = subjects.find(item =>
            String(item.course_id) === savedSubject
        );

        if (selected) {
            dropdowns.college.field.value =
                String(selected.college_code);

            dropdowns.college.label.textContent =
                selected.college_name;

            dropdowns.department.field.value =
                String(selected.department_code);

            dropdowns.department.label.textContent =
                selected.department_name;

            dropdowns.subject.label.textContent =
                selected.course_name + ' (' + selected.course_id + ')';
        }
    }

    updateAvailability();


    // 4. COURSE DURATION

    const hoursInput = document.getElementById('durationHours');
    const minutesInput = document.getElementById('durationMinutes');
    const durationField = document.getElementById('duration_hours');
    const durationPreview = document.getElementById('durationPreview');

    if (durationField.value !== '') {
        const stored = Number(durationField.value);

        if (Number.isFinite(stored) && stored >= 0) {
            const totalMinutes = Math.round(stored * 60);

            hoursInput.value = Math.floor(totalMinutes / 60);
            minutesInput.value = totalMinutes % 60;
        }
    }

    function updateDuration() {
        const hours = Number(hoursInput.value || 0);
        const minutes = Number(minutesInput.value || 0);

        const valid =
            Number.isInteger(hours) &&
            Number.isInteger(minutes) &&
            hours >= 0 &&
            hours <= 9999 &&
            minutes >= 0 &&
            minutes <= 59;

        if (!valid) {
            durationField.value = '';
            durationPreview.textContent =
                'Please enter valid hours and minutes.';
            return false;
        }

        if (hoursInput.value === '' && minutes === 0) {
            durationField.value = '';
            durationPreview.textContent = 'Duration not specified.';
            return true;
        }

        if (hours === 0 && minutes === 0) {
            durationField.value = '';
            durationPreview.textContent =
                'Duration must be greater than zero.';
            return false;
        }

        const decimalHours = hours + minutes / 60;

        if (decimalHours > 9999.99) {
            durationField.value = '';
            durationPreview.textContent =
                'Course duration is too large.';
            return false;
        }

        durationField.value = decimalHours.toFixed(2);

        durationPreview.textContent =
            'Total Duration: ' +
            hours + ' hour(s) and ' +
            minutes + ' minute(s)';

        return true;
    }

    hoursInput.addEventListener('input', updateDuration);
    minutesInput.addEventListener('input', updateDuration);

    updateDuration();


    // 5. LECTURE SCHEDULE

    const days = Array.from(
        document.querySelectorAll('.lectureDay')
    );

    const lectureTime = document.getElementById('lectureTime');
    const scheduleField = document.getElementById('lecture_schedule');
    const schedulePreview = document.getElementById('schedulePreview');

    const existingSchedule = scheduleField.value;

    const scheduleMatch = existingSchedule.match(
        /^Days: (.*); Time: (\d{2}:\d{2})$/
    );

    if (scheduleMatch) {
        const savedDays = scheduleMatch[1]
            .split(',')
            .map(day => day.trim());

        days.forEach(day => {
            day.checked = savedDays.includes(day.value);
        });

        lectureTime.value = scheduleMatch[2];
    }

    function updateSchedule() {
        const selectedDays = days
            .filter(day => day.checked)
            .map(day => day.value);

        const time = lectureTime.value;

        if (selectedDays.length === 0 && !time) {
            scheduleField.value = '';
            schedulePreview.textContent =
                'No lecture schedule selected.';
            return true;
        }

        if (selectedDays.length === 0 || !time) {
            scheduleField.value = '';
            schedulePreview.textContent =
                'Choose lecture days and a start time.';
            return false;
        }

        scheduleField.value =
            'Days: ' + selectedDays.join(', ') +
            '; Time: ' + time;

        schedulePreview.textContent =
            'Lecture Schedule: ' +
            selectedDays.join(', ') +
            ' at ' + time;

        return true;
    }

    days.forEach(day => {
        day.addEventListener('change', updateSchedule);
    });

    lectureTime.addEventListener('change', updateSchedule);

    if (scheduleMatch) {
        updateSchedule();
    } else if (existingSchedule) {
        schedulePreview.textContent =
            'Current schedule: ' + existingSchedule;
    }


    // 6. FORM VALIDATION

    form.addEventListener('submit', function (event) {

        if (
            !dropdowns.college.field.value ||
            !dropdowns.department.field.value ||
            !dropdowns.subject.field.value
        ) {
            event.preventDefault();
            alert('Please select a college, department and subject.');
            return;
        }

        if (!updateDuration()) {
            event.preventDefault();
            alert('Please enter a valid course duration.');
            return;
        }

        // Preserve any existing free-text schedule in Edit Course.
        if (scheduleMatch || !existingSchedule) {
            if (!updateSchedule()) {
                event.preventDefault();
                alert('Please complete the lecture schedule.');
            }
        }
    });

});
