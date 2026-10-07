(function (A) {
  'use strict';
  var Store = A.services.Storage, USERS = 'ayni.usuarios', SESSION = 'ayni.sesion';

  function norm(c) {
    c = String(c || '').trim().toLowerCase();
    return /^[\d\s-]+$/.test(c) ? c.replace(/[\s-]/g, '') : c;
  }
  function pub(u) { return { nombre: u.nombre, contacto: u.contacto, rol: u.rol, ubicacion: u.ubicacion }; }

  A.models.User = {
    current: function () { return Store.get(SESSION, null); },
    register: function (d) {
      var users = Store.get(USERS, []), contacto = norm(d.contacto);
      if (users.some(function (u) { return u.contacto === contacto; })) return Promise.reject({ key: 'errExiste' });
      return A.utils.hash(d.password).then(function (h) {
        var u = { nombre: d.nombre.trim(), contacto: contacto, rol: d.rol, ubicacion: (d.ubicacion || '').trim(), hash: h };
        users.push(u); Store.set(USERS, users); Store.set(SESSION, pub(u)); return pub(u);
      });
    },
    login: function (contacto, password, rol) {
      var c = norm(contacto), u = Store.get(USERS, []).filter(function (x) { return x.contacto === c && x.rol === rol; })[0];
      if (!u) return Promise.reject({ key: 'errCredenciales' });
      return A.utils.hash(password).then(function (h) {
        if (h !== u.hash) return Promise.reject({ key: 'errCredenciales' });
        Store.set(SESSION, pub(u)); return pub(u);
      });
    },
    logout: function () { Store.remove(SESSION); }
  };
})(window.AyniHub);
