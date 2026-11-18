/* Shared image lightbox. Any <button data-zu-lightbox="IMAGE_URL" data-caption="…"> opens it. */
(function () {
    'use strict';
    var box = null, opener = null;

    function build() {
        box = document.createElement('div');
        box.className = 'zu-lb'; box.hidden = true;
        box.setAttribute('role', 'dialog'); box.setAttribute('aria-modal', 'true'); box.setAttribute('aria-label', 'Image preview');
        box.innerHTML = '<button type="button" class="zu-lb__close" aria-label="Close image"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>' +
            '<figure class="zu-lb__fig"><img class="zu-lb__img" alt=""><figcaption class="zu-lb__cap"></figcaption></figure>';
        document.body.appendChild(box);
        box.addEventListener('click', function (e) { if (!e.target.closest('.zu-lb__img')) close(); });   // the backdrop or the close button
    }
    function open(btn) {
        if (!box) build();
        var img = box.querySelector('.zu-lb__img'), cap = box.querySelector('.zu-lb__cap'), text = btn.getAttribute('data-caption') || '';
        img.src = btn.getAttribute('data-zu-lightbox'); img.alt = text; cap.textContent = text; cap.hidden = !text;
        opener = btn; box.hidden = false; document.body.style.overflow = 'hidden';
        box.querySelector('.zu-lb__close').focus({ preventScroll: true });
    }
    function close() {
        if (!box || box.hidden) return;
        box.hidden = true; box.querySelector('.zu-lb__img').removeAttribute('src'); document.body.style.overflow = '';
        if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
        opener = null;
    }
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-zu-lightbox]');
        if (b) { e.preventDefault(); open(b); }
    });
    document.addEventListener('keydown', function (e) {
        if (!box || box.hidden) return;
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); }
        else if (e.key === 'Tab') { e.preventDefault(); box.querySelector('.zu-lb__close').focus(); }   // the close button is the only control
    }, true);
})();
