(function (A) {
  'use strict';
  var U = A.utils, $ = U.$, UI = A.views.UI, Usr = A.models.User, V = U.validators;
  var roles = { login: 'comprador', reg: 'comprador' }, Auth = A.controllers.Auth = {};

  function refresh() { UI.renderUser(Usr.current()); A.controllers.Catalog.render(); }
  function entrar(user, toastKey) {
    refresh(); UI.toast(U.t(toastKey));
    A.controllers.App.navigate('Catalogo');
  }
  function fail(form, idField, err) {
    if (err && err.key) { var o = {}; o[idField] = err.key; UI.showErrors(form, o); } else { UI.toast(String(err)); }
  }

  Auth.init = function () {
    // Selector de rol (botones con aria-pressed)
    U.$$('[data-role]').forEach(function (b) {
      b.addEventListener('click', function () {
        var g = b.getAttribute('data-role-group'); roles[g] = b.getAttribute('data-role');
        U.$$('[data-role-group="' + g + '"]').forEach(function (x) {
          var on = x === b; x.classList.toggle('active', on); x.setAttribute('aria-pressed', String(on));
        });
        if (g === 'reg') $('#regUbicWrap').hidden = roles.reg === 'comprador';
      });
    });

    var fLogin = $('#formLogin'), fReg = $('#formRegistro');
    fLogin.addEventListener('submit', function (e) {
      e.preventDefault();
      var v = { loginUser: $('#loginUser').value, loginPass: $('#loginPass').value };
      if (!UI.showErrors(fLogin, U.validate(v, { loginUser: [V.required, V.contacto], loginPass: [V.required] }))) return;
      Usr.login(v.loginUser, v.loginPass, roles.login)
        .then(function (u) { fLogin.reset(); entrar(u, 'toastLogin'); })
        .catch(function (err) { fail(fLogin, 'loginPass', err); });
    });
    fReg.addEventListener('submit', function (e) {
      e.preventDefault();
      var v = { regNombre: $('#regNombre').value, regContacto: $('#regContacto').value, regUbicacion: $('#regUbicacion').value, regPass: $('#regPass').value };
      var rules = { regNombre: [V.required, V.minLen(3)], regContacto: [V.required, V.contacto], regPass: [V.password] };
      if (roles.reg !== 'comprador') rules.regUbicacion = [V.required, V.minLen(3)];
      if (!UI.showErrors(fReg, U.validate(v, rules))) return;
      Usr.register({ nombre: v.regNombre, contacto: v.regContacto, ubicacion: v.regUbicacion, password: v.regPass, rol: roles.reg })
        .then(function (u) { fReg.reset(); entrar(u, 'toastReg'); })
        .catch(function (err) { fail(fReg, 'regContacto', err); });
    });
    $('#btnLogout').addEventListener('click', function () {
      Usr.logout(); refresh(); UI.toast(U.t('toastSalir')); A.controllers.App.navigate('Inicio');
    });
    UI.renderUser(Usr.current());
  };
})(window.AyniHub);
