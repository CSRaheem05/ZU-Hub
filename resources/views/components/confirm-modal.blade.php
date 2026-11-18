{{--
  Reusable glass confirmation modal. Render ONCE per page with the x-confirm-modal component.
  Styles: css/zu-glass.css + css/zu-modal.css. Behaviour: js/zu-modal.js.

  The same dialog is re-filled for every action (icon, tone, title, message, labels).

  From JS:
    ZUConfirm.open({ tone, icon, title, message, confirmLabel, note }).then(ok => ...)

  Declaratively, on a submit button inside a form:
    data-zu-confirm                      turns the button into a confirm-first button
    data-confirm-tone                    green | yellow | red
    data-confirm-icon                    check | edit | warning
    data-confirm-title / -message / -label
    data-confirm-note                    optional selector: echo that field's text in the dialog
    data-confirm-requires-note           optional selector: block until that field is filled
  (Note: never nest Blade comments inside this comment, the first closing marker would end it.)
--}}
<div class="zu-modal" id="zuConfirm" data-tone="blue" hidden>
  <div class="zu-modal__backdrop" data-zu-modal-dismiss></div>
  <div class="zu-modal__panel lg" role="alertdialog" aria-modal="true" aria-labelledby="zuConfirmTitle" aria-describedby="zuConfirmMessage" tabindex="-1">
    <span class="zu-modal__glow" aria-hidden="true"></span>
    <span class="zu-modal__icon" id="zuConfirmIcon" aria-hidden="true"></span>
    <h2 class="zu-modal__title" id="zuConfirmTitle"></h2>
    <p class="zu-modal__message" id="zuConfirmMessage"></p>
    <blockquote class="zu-modal__note" id="zuConfirmNote" hidden>
      <span id="zuConfirmNoteLabel">Your note</span>
      <p id="zuConfirmNoteText"></p>
    </blockquote>
    <div class="zu-modal__actions">
      <button type="button" class="zu-modal__btn zu-modal__btn--cancel" id="zuConfirmCancel">Cancel</button>
      <button type="button" class="zu-modal__btn zu-modal__btn--confirm" id="zuConfirmOk">Confirm</button>
    </div>
  </div>
</div>
