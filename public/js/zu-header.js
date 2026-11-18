/* ZU Hub — shared header behaviour: the account menu closes on outside click and Escape. */
(function () {
    'use strict';
    var menu = document.querySelector('.zu-account');
    if (!menu) return;

    document.addEventListener('pointerdown', function (e) {
        if (menu.open && !menu.contains(e.target)) menu.open = false;
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && menu.open) { menu.open = false; menu.querySelector('summary').focus(); }
    });
})();
