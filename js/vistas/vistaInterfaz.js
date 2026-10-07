
(function (A) {
  'use strict';
  var U = A.utils, $ = U.$, $$ = U.$$, UI = A.views.UI = {}, lastFocus = null, toastTimer;

  UI.toast = function (msg) {
    var t = $('#toastGlobal'); t.textContent = msg; t.classList.add('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.classList.remove('show'); }, 3200);
  };

  UI.applyLang = function (root) {
    $$('[data-i18n]', root).forEach(function (el) { el.innerHTML = U.t(el.getAttribute('data-i18n')); }); // textos propios, no del usuario
    $$('[data-i18n-placeholder]', root).forEach(function (el) { el.placeholder = U.t(el.getAttribute('data-i18n-placeholder')); });
  };

  UI.showSection = function (name, moveFocus) {
    $$('.section').forEach(function (s) { s.classList.toggle('active', s.id === 'sec-' + name); });
    $$('[data-route]').forEach(function (a) {
      if (a.getAttribute('data-route') === name) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
    });
    window.scrollTo(0, 0);
    var sec = $('#sec-' + name); if (moveFocus && sec) sec.focus({ preventScroll: true });
  };

  UI.renderUser = function (user) {
    var pill = $('#userPill');
    pill.style.display = user ? 'inline-flex' : 'none';
    pill.textContent = user ? user.nombre + ' · ' + U.t(user.rol === 'productor' ? 'rolProductor' : user.rol === 'gestor' ? 'rolGestor' : 'rolComprador') : '';
    $('#navLogin').hidden = $('#navRegister').hidden = !!user;
    $('#btnLogout').hidden = !user;
  };

 
  UI.openModal = function (id) {
    lastFocus = document.activeElement;
    var o = $('#' + id); o.classList.add('open');
    var f = $('button, [href], input, select', o.querySelector('.modal')); if (f) f.focus();
  };
  UI.closeModal = function (id) {
    $('#' + id).classList.remove('open');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  };

  UI.showErrors = function (form, errors) {
    var first = null;
    $$('input[id], select[id], textarea[id]', form).forEach(function (el) {
      var key = errors[el.id], out = $('#' + el.id + '-error');
      el.setAttribute('aria-invalid', key ? 'true' : 'false');
      if (out) out.textContent = key ? U.t(key) : '';
      if (key && !first) first = el;
    });
    if (first) first.focus();
    return !first;
  };
  UI.clearErrors = function (form) { UI.showErrors(form, {}); };

  UI.init = function () {
    document.addEventListener('keydown', function (e) {
      var open = $('.modal-overlay.open');
      if (e.key === 'Escape' && open) UI.closeModal(open.id);
      if (e.key === 'Tab' && open) { // mantiene el foco dentro del modal
        var f = $$('button, [href], input, select', open), a = f[0], z = f[f.length - 1];
        if (e.shiftKey && document.activeElement === a) { e.preventDefault(); z.focus(); }
        else if (!e.shiftKey && document.activeElement === z) { e.preventDefault(); a.focus(); }
      }
    });
    $$('.modal-overlay').forEach(function (o) {
      o.addEventListener('click', function (e) { if (e.target === o) UI.closeModal(o.id); });
    });
    $$('[data-close-modal]').forEach(function (b) {
      b.addEventListener('click', function () { UI.closeModal(b.closest('.modal-overlay').id); });
    });
  };
})(window.AyniHub);
