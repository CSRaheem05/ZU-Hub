/* ==========================================================================
   ZU Hub — reusable glass confirmation modal (see components/confirm-modal.blade.php)

   1) window.ZUConfirm.open(options) -> Promise<boolean>
        options: { tone: 'green'|'yellow'|'red'|'blue', icon: 'check'|'edit'|'warning',
                   title, message, confirmLabel, cancelLabel, note, noteLabel }
   2) Declarative: any submit button with [data-zu-confirm] asks first, then submits its
      form exactly once (name/value of the button are preserved).
   3) Forms with [data-submit-once] cannot be submitted twice.
   ========================================================================== */
(function () {
    'use strict';

    var modal = document.getElementById('zuConfirm');
    if (!modal) return;

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var root = document.documentElement;
    var panel = modal.querySelector('.zu-modal__panel');
    var el = {
        icon: document.getElementById('zuConfirmIcon'),
        title: document.getElementById('zuConfirmTitle'),
        message: document.getElementById('zuConfirmMessage'),
        note: document.getElementById('zuConfirmNote'),
        noteLabel: document.getElementById('zuConfirmNoteLabel'),
        noteText: document.getElementById('zuConfirmNoteText'),
        cancel: document.getElementById('zuConfirmCancel'),
        ok: document.getElementById('zuConfirmOk')
    };

    var ICONS = {
        check: '<svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
        edit: '<svg viewBox="0 0 24 24"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/></svg>',
        warning: '<svg viewBox="0 0 24 24"><path d="M12 3.5 2.8 19.5a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5z"/><path d="M12 10v4.5M12 17.6v.4"/></svg>',
        info: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.4"/></svg>'
    };

    var state = null;      // { resolve, opener } while open
    var closeTimer = null;

    /* ---------------- open / close ---------------- */
    function open(opts) {
        opts = opts || {};
        if (state) return Promise.resolve(false);
        clearTimeout(closeTimer);
        modal.classList.remove('is-closing');

        modal.setAttribute('data-tone', opts.tone || 'blue');
        el.icon.innerHTML = ICONS[opts.icon] || ICONS.info;
        el.title.textContent = opts.title || 'Are you sure?';
        el.message.textContent = opts.message || '';
        el.message.hidden = !opts.message;
        el.cancel.textContent = opts.cancelLabel || 'Cancel';
        el.ok.textContent = opts.confirmLabel || 'Confirm';
        var note = (opts.note || '').trim();
        el.note.hidden = !note;
        el.noteText.textContent = note;
        el.noteLabel.textContent = opts.noteLabel || 'Your note';

        return new Promise(function (resolve) {
            state = { resolve: resolve, opener: document.activeElement };
            modal.hidden = false;
            root.classList.add('zu-modal-open');
            // Safe default: focus Cancel so a stray Enter never confirms a destructive action.
            el.cancel.focus({ preventScroll: true });
        });
    }

    function close(result) {
        if (!state) return;
        var s = state; state = null;
        modal.classList.add('is-closing');
        closeTimer = setTimeout(function () {
            modal.hidden = true;
            modal.classList.remove('is-closing');
            root.classList.remove('zu-modal-open');
            if (s.opener && document.contains(s.opener) && typeof s.opener.focus === 'function') s.opener.focus({ preventScroll: true });
        }, reduce ? 0 : 220);
        s.resolve(result);
    }

    el.cancel.addEventListener('click', function () { close(false); });
    el.ok.addEventListener('click', function () { close(true); });
    modal.addEventListener('click', function (e) { if (e.target.hasAttribute('data-zu-modal-dismiss')) close(false); });

    /* Escape closes; Tab / Shift+Tab stay inside the dialog. */
    document.addEventListener('keydown', function (e) {
        if (!state) return;
        if (e.key === 'Escape') { e.preventDefault(); close(false); return; }
        if (e.key !== 'Tab') return;
        var items = [el.cancel, el.ok];
        var first = items[0], last = items[items.length - 1];
        if (!panel.contains(document.activeElement)) { e.preventDefault(); first.focus(); }
        else if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    window.ZUConfirm = { open: open };

    /* ---------------- form wiring ---------------- */
    function unlock(form) {
        delete form.dataset.locked;
        form.classList.remove('is-submitting');
        form.querySelectorAll('[data-zu-hidden]').forEach(function (n) { n.remove(); });
        form.querySelectorAll('button').forEach(function (b) { b.disabled = false; b.classList.remove('is-loading'); });
    }

    function lockAndSubmit(form, button) {
        if (form.dataset.locked) return;
        form.dataset.locked = '1';
        form.classList.add('is-submitting');
        if (button && button.name) {            // a disabled button is not submitted, so carry its value
            var hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = button.name; hidden.value = button.value;
            hidden.setAttribute('data-zu-hidden', '');
            form.appendChild(hidden);
        }
        if (button) button.classList.add('is-loading');
        form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
        form.submit();
    }

    function flagMissing(field) {
        var msg = document.getElementById(field.id + '-error');
        field.setAttribute('aria-invalid', 'true');
        if (msg) { msg.hidden = false; field.setAttribute('aria-describedby', msg.id); }
        field.classList.remove('is-shaking'); void field.offsetWidth; field.classList.add('is-shaking');
        field.focus();
        if (!msg && field.reportValidity) { field.setCustomValidity('Please fill in this field.'); field.reportValidity(); field.setCustomValidity(''); }
    }

    document.addEventListener('input', function (e) {
        var f = e.target;
        if (f.getAttribute && f.getAttribute('aria-invalid') === 'true' && f.value.trim()) {
            f.removeAttribute('aria-invalid');
            var msg = document.getElementById(f.id + '-error'); if (msg) msg.hidden = true;
        }
    });

    function pick(selector) { return selector ? document.querySelector(selector) : null; }

    document.addEventListener('click', function (e) {
        var button = e.target.closest && e.target.closest('button[data-zu-confirm]');
        if (!button) return;
        e.preventDefault();
        var form = button.form;
        if (!form || form.dataset.locked) return;

        var needNote = pick(button.getAttribute('data-confirm-requires-note'));
        if (needNote && !needNote.value.trim()) { flagMissing(needNote); return; }
        // Native constraints (required, maxlength…) are checked first, so the dialog never hides a form error.
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) { form.reportValidity(); return; }
        var noteField = pick(button.getAttribute('data-confirm-note')) || needNote;

        open({
            tone: button.getAttribute('data-confirm-tone'),
            icon: button.getAttribute('data-confirm-icon'),
            title: button.getAttribute('data-confirm-title'),
            message: button.getAttribute('data-confirm-message'),
            confirmLabel: button.getAttribute('data-confirm-label'),
            note: noteField ? noteField.value : '',
            noteLabel: button.getAttribute('data-confirm-note-label')
        }).then(function (ok) { if (ok) lockAndSubmit(form, button); });
    });

    /* Any other way of submitting a confirm-guarded form (e.g. Enter key) must not skip the dialog;
       [data-submit-once] forms can only be submitted once. */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented) return;   // another handler (e.g. inline validation) already cancelled this submit: do not lock the form
        var submitter = e.submitter;
        // Only confirm-guarded buttons are intercepted; other buttons of the same form submit normally.
        if (submitter ? submitter.hasAttribute('data-zu-confirm') : form.querySelector('[data-zu-confirm]')) { e.preventDefault(); return; }
        if (form.hasAttribute('data-submit-once')) {
            if (form.dataset.locked) { e.preventDefault(); return; }
            form.dataset.locked = '1';
            form.classList.add('is-submitting');
            if (e.submitter) e.submitter.classList.add('is-loading');
            setTimeout(function () { form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; }); }, 0);
        }
    });

    /* Coming back via the browser's back button must not leave forms locked. */
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) document.querySelectorAll('form[data-locked], form.is-submitting').forEach(unlock);
    });
})();
