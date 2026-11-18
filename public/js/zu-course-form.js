/* ==========================================================================
   ZU Hub — Instructor "Create / Edit course" form (resources/views/instructor/course-form.blade.php)

   1. Cover image   drag & drop, preview, replace, remove (+ client-side checks; the server re-validates everything)
   2. Subject       College → Department → Subject searchable dropdowns, topic filtered by college
   3. Details       live preview of the title
   4. Duration      hours + minutes  → duration_hours (decimal hours, as the backend expects)
   5. Schedule      days + start time → "Days: Sunday, Tuesday; Time: 19:00" (the stored format)
   6. Validation    inline messages (no alert boxes)

   Form modes (data-mode): create | resubmit | edit.  In "edit" (approved course) the subject / title
   / topic are shown read-only, so only the cover, description, duration and schedule run here.
   ========================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('courseForm');
    if (!form) return;

    var $ = function (id) { return document.getElementById(id); };
    var locked = form.getAttribute('data-mode') === 'edit';

    /* ------------------------------------------------------------ helpers */
    function setError(id, message) {
        var el = $(id);
        if (!el) return;
        el.textContent = message || '';
        el.hidden = !message;
    }
    function mark(el, invalid) { if (el) el.classList.toggle('is-invalid', !!invalid); }
    function firstFocus(el) {
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(function () { try { el.focus({ preventScroll: true }); } catch (e) { } }, 250);
    }
    function formatSize(bytes) { return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB'; }

    /* ------------------------------------------------------- live preview */
    var previewTitle = $('previewTitle');
    var previewSubject = $('previewSubject');
    var previewImg = $('previewImg');
    var title = $('title');

    if (title && previewTitle) {
        title.addEventListener('input', function () {
            previewTitle.textContent = title.value.trim() || 'Your course title';
            mark(title, false); setError('titleError', '');
        });
    }

    /* ============================================================ 1 · cover */
    (function cover() {
        var drop = $('coverDrop'), input = $('coverInput');
        if (!drop || !input) return;

        var preview = $('coverPreview'), img = $('coverImg'), nameEl = $('coverName');
        var removedNote = $('coverRemoved'), removeFlag = $('removeCover');
        var limits = {
            maxBytes: Number(drop.getAttribute('data-max-kb')) * 1024,
            minW: Number(drop.getAttribute('data-min-w')), minH: Number(drop.getAttribute('data-min-h')),
            maxSide: Number(drop.getAttribute('data-max-side'))
        };
        var allowed = ['image/jpeg', 'image/png', 'image/webp'];
        var existing = drop.getAttribute('data-existing') || '';
        var objectUrl = null;

        function show(src, label) {
            img.src = src; nameEl.textContent = label;
            preview.hidden = false; drop.classList.add('has-image'); removedNote.hidden = true;
            if (previewImg) { previewImg.src = src; previewImg.hidden = false; }
        }
        function empty(removedExisting) {
            img.removeAttribute('src'); preview.hidden = true; drop.classList.remove('has-image');
            removedNote.hidden = !removedExisting;
            if (previewImg) { previewImg.hidden = true; previewImg.removeAttribute('src'); }
        }
        function fail(message) {
            setError('coverError', message);
            drop.classList.add('is-invalid');
            input.value = '';
        }
        function clearProblem() { setError('coverError', ''); drop.classList.remove('is-invalid'); }

        function accept(file) {
            clearProblem();
            if (!file) return;

            if (allowed.indexOf(file.type) === -1) { fail('That file is not a JPG, PNG or WebP image.'); return; }
            if (file.size > limits.maxBytes) { fail('This image is ' + formatSize(file.size) + '. Please choose one under ' + formatSize(limits.maxBytes) + '.'); return; }

            var url = URL.createObjectURL(file);
            var probe = new Image();
            probe.onload = function () {
                var w = probe.naturalWidth, h = probe.naturalHeight;
                if (w < limits.minW || h < limits.minH) {
                    URL.revokeObjectURL(url);
                    fail('This image is ' + w + '×' + h + ' px. Covers must be at least ' + limits.minW + '×' + limits.minH + ' px.');
                    return;
                }
                if (w > limits.maxSide || h > limits.maxSide) {
                    URL.revokeObjectURL(url);
                    fail('This image is very large (' + w + '×' + h + ' px). Please use one under ' + limits.maxSide + ' px.');
                    return;
                }
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = url;
                removeFlag.value = '0';
                show(url, file.name + ' · ' + formatSize(file.size) + ' · ' + w + '×' + h);
            };
            probe.onerror = function () { URL.revokeObjectURL(url); fail('We could not read that file as an image.'); };
            probe.src = url;
        }

        // choosing with the file dialog
        input.addEventListener('change', function () { accept(input.files && input.files[0]); });

        // drag & drop
        ['dragenter', 'dragover'].forEach(function (type) {
            drop.addEventListener(type, function (e) { e.preventDefault(); drop.classList.add('is-over'); });
        });
        ['dragleave', 'dragend'].forEach(function (type) {
            drop.addEventListener(type, function (e) { if (!drop.contains(e.relatedTarget)) drop.classList.remove('is-over'); });
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault(); drop.classList.remove('is-over');
            var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (!file) return;
            try { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; } catch (err) { /* very old browsers: preview only */ }
            accept(file);
        });
        // a file dropped next to the zone must not make the browser leave the page
        ['dragover', 'drop'].forEach(function (type) { window.addEventListener(type, function (e) { e.preventDefault(); }); });

        $('coverReplace').addEventListener('click', function () { input.click(); });
        $('coverRemove').addEventListener('click', function () {
            input.value = '';
            if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
            removeFlag.value = existing ? '1' : '0';
            clearProblem();
            empty(!!existing);
        });

        // initial state: the course's current cover, if it has one
        if (existing) show(existing, 'Current cover');
    })();

    /* ============================================================ 2 · subject */
    var dropdowns = null, topic = $('topic_id');

    if (!locked && $('subjectsData')) (function subject() {
        var subjects;
        try { subjects = JSON.parse($('subjectsData').textContent); } catch (e) { console.error('Cannot load subject data:', e); return; }

        var collegesMap = new Map(), departmentsMap = new Map();
        subjects.forEach(function (item) {
            var cc = String(item.college_code), dc = String(item.department_code);
            if (!collegesMap.has(cc)) collegesMap.set(cc, { value: cc, text: item.college_name });
            if (!departmentsMap.has(dc)) departmentsMap.set(dc, { value: dc, text: item.department_name, college: cc });
        });
        var sortByText = function (a, b) { return a.text.localeCompare(b.text); };
        var colleges = Array.from(collegesMap.values()).sort(sortByText);
        var departments = Array.from(departmentsMap.values()).sort(sortByText);
        var subjectItems = subjects.map(function (item) {
            return { value: String(item.course_id), text: item.course_name + ' (' + item.course_id + ')', name: item.course_name, department: String(item.department_code) };
        });

        function dd(type) {
            return {
                type: type, root: $(type + 'Dropdown'), toggle: $(type + 'Toggle'), label: $(type + 'Label'),
                panel: $(type + 'Panel'), search: $(type + 'Search'), options: $(type + 'Options'),
                field: $(type === 'subject' ? 'course_id' : type + '_id')
            };
        }
        dropdowns = { college: dd('college'), department: dd('department'), subject: dd('subject') };
        var placeholders = { college: 'Select a college', department: 'Select a college first', subject: 'Select a department first' };

        function closeAll() {
            Object.keys(dropdowns).forEach(function (k) {
                dropdowns[k].panel.hidden = true; dropdowns[k].toggle.setAttribute('aria-expanded', 'false');
            });
        }
        function items(type) {
            if (type === 'college') return colleges;
            if (type === 'department') return departments.filter(function (i) { return i.college === dropdowns.college.field.value; });
            return subjectItems.filter(function (i) { return i.department === dropdowns.department.field.value; });
        }
        function setLabel(d, text, isPlaceholder) {
            d.label.textContent = text; d.label.classList.toggle('is-placeholder', !!isPlaceholder);
        }
        function render(type) {
            var d = dropdowns[type], q = d.search.value.trim().toLowerCase();
            var list = items(type).filter(function (i) { return i.text.toLowerCase().indexOf(q) !== -1 || i.value.toLowerCase().indexOf(q) !== -1; });
            d.options.replaceChildren();
            if (!list.length) {
                var none = document.createElement('div'); none.className = 'iw-combo__empty'; none.textContent = 'No matching options found.';
                d.options.appendChild(none); return;
            }
            list.forEach(function (item) {
                var b = document.createElement('button');
                b.type = 'button'; b.setAttribute('role', 'option'); b.textContent = item.text;
                if (d.field.value === item.value) { b.classList.add('selected'); b.setAttribute('aria-selected', 'true'); }
                b.addEventListener('click', function () { choose(type, item); });
                d.options.appendChild(b);
            });
        }
        function availability() {
            var hasCollege = !!dropdowns.college.field.value, hasDept = !!dropdowns.department.field.value;
            dropdowns.department.toggle.disabled = !hasCollege;
            dropdowns.subject.toggle.disabled = !hasDept;
            if (!hasCollege) setLabel(dropdowns.department, placeholders.department, true);
            else if (!hasDept) setLabel(dropdowns.department, 'Select a department', true);
            if (!hasDept) setLabel(dropdowns.subject, placeholders.subject, true);
            else if (!dropdowns.subject.field.value) setLabel(dropdowns.subject, 'Select a subject', true);
            filterTopics();
        }
        function resetSubject() { var d = dropdowns.subject; d.field.value = ''; d.search.value = ''; setLabel(d, placeholders.subject, true); if (previewSubject) previewSubject.textContent = 'University subject'; }
        function resetDepartment() { var d = dropdowns.department; d.field.value = ''; d.search.value = ''; setLabel(d, placeholders.department, true); resetSubject(); }

        function choose(type, item) {
            var d = dropdowns[type], changed = d.field.value !== item.value;
            d.field.value = item.value; setLabel(d, item.text, false); d.search.value = '';
            if (type === 'college' && changed) resetDepartment();
            if (type === 'department' && changed) resetSubject();
            if (type === 'subject' && previewSubject) previewSubject.textContent = item.name;
            mark(d.toggle, false); setError(type + 'Error', '');
            availability(); closeAll(); d.toggle.focus();
        }

        Object.keys(dropdowns).forEach(function (type) {
            var d = dropdowns[type];
            d.toggle.addEventListener('click', function () {
                if (d.toggle.disabled) return;
                var opening = d.panel.hidden;
                closeAll();
                if (opening) {
                    d.panel.hidden = false; d.toggle.setAttribute('aria-expanded', 'true');
                    d.search.value = ''; render(type); d.search.focus();
                }
            });
            d.search.addEventListener('input', function () { render(type); });
            d.search.addEventListener('keydown', function (e) {
                var opts = Array.prototype.slice.call(d.options.querySelectorAll('button'));
                var current = d.options.querySelector('button.is-active');
                var i = opts.indexOf(current);
                if (e.key === 'Escape') { closeAll(); d.toggle.focus(); }
                else if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault(); if (!opts.length) return;
                    i = e.key === 'ArrowDown' ? Math.min(i + 1, opts.length - 1) : Math.max(i - 1, 0);
                    opts.forEach(function (o) { o.classList.remove('is-active'); });
                    opts[i].classList.add('is-active'); opts[i].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    e.preventDefault(); (current || opts[0]) && (current || opts[0]).click();
                }
            });
        });
        document.addEventListener('click', function (e) {
            var inside = Object.keys(dropdowns).some(function (k) { return dropdowns[k].root.contains(e.target); });
            if (!inside) closeAll();
        });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(); });

        // restore the saved subject (editing a request, or after a validation error)
        var saved = dropdowns.subject.field.value;
        if (saved) {
            var s = subjects.filter(function (i) { return String(i.course_id) === saved; })[0];
            if (s) {
                dropdowns.college.field.value = String(s.college_code); setLabel(dropdowns.college, s.college_name, false);
                dropdowns.department.field.value = String(s.department_code); setLabel(dropdowns.department, s.department_name, false);
                setLabel(dropdowns.subject, s.course_name + ' (' + s.course_id + ')', false);
                if (previewSubject) previewSubject.textContent = s.course_name;
            }
        } else {
            setLabel(dropdowns.college, placeholders.college, true);
        }
        availability();
    })();

    // topics: only those of the chosen college are offered
    function filterTopics() {
        if (!topic) return;
        var college = dropdowns ? dropdowns.college.field.value : '';
        Array.prototype.slice.call(topic.options).forEach(function (option) {
            if (!option.value) return;
            var ok = !!college && option.getAttribute('data-college') === college;
            option.hidden = !ok; option.disabled = !ok;
        });
        if (topic.selectedOptions[0] && topic.selectedOptions[0].disabled) topic.value = '';
        topic.disabled = !college;
    }
    if (topic && !dropdowns) topic.disabled = true;

    /* ============================================================ 3 · details */
    var description = $('description'), descCount = $('descCount');
    var previewDesc = $('previewDesc');
    if (description && descCount) {
        var count = function () {
            descCount.textContent = description.value.length;
            if (previewDesc) {   // the preview shows what students will read (same fallback as their cards)
                var text = description.value.trim();
                previewDesc.textContent = text || 'Explore this course and develop your skills.';
                previewDesc.classList.toggle('is-placeholder', !text);
            }
        };
        description.addEventListener('input', count); count();
    }

    /* ============================================================ 4 · duration */
    var hoursInput = $('durationHours'), minutesInput = $('durationMinutes');
    var durationField = $('duration_hours'), durationPreview = $('durationPreview');

    if (durationField.value !== '') {
        var stored = Number(durationField.value);
        if (isFinite(stored) && stored >= 0) {
            var totalMinutes = Math.round(stored * 60);
            hoursInput.value = Math.floor(totalMinutes / 60); minutesInput.value = totalMinutes % 60;
        }
    }

    function updateDuration() {
        var hours = Number(hoursInput.value || 0), minutes = Number(minutesInput.value || 0);
        var valid = Number.isInteger(hours) && Number.isInteger(minutes) && hours >= 0 && hours <= 9999 && minutes >= 0 && minutes <= 59;

        if (!valid) { durationField.value = ''; durationPreview.textContent = 'Please enter whole hours (0–9999) and minutes (0–59).'; return false; }
        if (hoursInput.value === '' && minutes === 0) { durationField.value = ''; durationPreview.textContent = 'Duration not specified.'; return true; }
        if (hours === 0 && minutes === 0) { durationField.value = ''; durationPreview.textContent = 'Duration must be greater than zero.'; return false; }

        var decimal = hours + minutes / 60;
        if (decimal > 9999.99) { durationField.value = ''; durationPreview.textContent = 'That duration is too long.'; return false; }

        durationField.value = decimal.toFixed(2);
        durationPreview.textContent = 'Total duration: ' + hours + ' hour' + (hours === 1 ? '' : 's') + (minutes ? ' and ' + minutes + ' minute' + (minutes === 1 ? '' : 's') : '');
        return true;
    }
    [hoursInput, minutesInput].forEach(function (i) { i.addEventListener('input', function () { setError('durationError', ''); updateDuration(); }); });
    updateDuration();

    /* ============================================================ 5 · schedule */
    var days = Array.prototype.slice.call(document.querySelectorAll('.lectureDay'));
    var lectureTime = $('lectureTime'), scheduleField = $('lecture_schedule'), schedulePreview = $('schedulePreview');
    var existingSchedule = scheduleField.value;
    var match = existingSchedule.match(/^Days: (.*); Time: (\d{2}:\d{2})$/);

    if (match) {
        var savedDays = match[1].split(',').map(function (d) { return d.trim(); });
        days.forEach(function (d) { d.checked = savedDays.indexOf(d.value) !== -1; });
        lectureTime.value = match[2];
    }

    function updateSchedule() {
        var selected = days.filter(function (d) { return d.checked; }).map(function (d) { return d.value; });
        var time = lectureTime.value;

        if (!selected.length && !time) { scheduleField.value = ''; schedulePreview.textContent = 'No lecture schedule selected.'; return true; }
        if (!selected.length || !time) { scheduleField.value = ''; schedulePreview.textContent = 'Choose at least one lecture day and a start time.'; return false; }

        scheduleField.value = 'Days: ' + selected.join(', ') + '; Time: ' + time;
        schedulePreview.textContent = selected.join(', ') + ' at ' + time;
        return true;
    }
    days.forEach(function (d) { d.addEventListener('change', function () { setError('scheduleError', ''); updateSchedule(); }); });
    lectureTime.addEventListener('change', function () { setError('scheduleError', ''); updateSchedule(); });

    if (match) updateSchedule();
    else if (existingSchedule) schedulePreview.textContent = 'Current schedule: ' + existingSchedule; // a free-text schedule is kept as it is

    /* ============================================================ 6 · submit */
    form.addEventListener('submit', function (event) {
        var firstBad = null;
        function bad(el, errorId, message, focusEl) { setError(errorId, message); mark(el, true); if (!firstBad) firstBad = focusEl || el; }

        if (!locked) {
            if (dropdowns) {
                if (!dropdowns.college.field.value) bad(dropdowns.college.toggle, 'collegeError', 'Please choose a college.');
                if (!dropdowns.department.field.value) bad(dropdowns.department.toggle, 'departmentError', 'Please choose a department.');
                if (!dropdowns.subject.field.value) bad(dropdowns.subject.toggle, 'subjectError', 'Please choose the university subject.');
            }
            if (title && !title.value.trim()) bad(title, 'titleError', 'Please give your course a title.');
        }
        if (!updateDuration()) bad(hoursInput, 'durationError', 'Please enter a valid duration.');
        // a free-text schedule saved earlier is preserved unless the user starts editing it
        if ((match || !existingSchedule) && !updateSchedule()) bad(lectureTime, 'scheduleError', 'Please choose at least one lecture day and a start time, or clear both.');
        if ($('coverDrop').classList.contains('is-invalid')) { if (!firstBad) firstBad = $('coverDrop'); }

        if (firstBad) { event.preventDefault(); firstFocus(firstBad); }
    });
})();
