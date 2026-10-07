(function (A) {
  'use strict';
  var U = A.utils, $ = U.$, UI = A.views.UI, View = A.views.Catalog, P = A.models.Product, Usr = A.models.User;
  var cat = 'todos', C = A.controllers.Catalog = {};

  var RULES = {
    fpNombre: [U.validators.required, U.validators.minLen(3)],
    fpProductor: [U.validators.required, U.validators.minLen(3)],
    fpPrecio: [U.validators.precio],
    fpUbicacion: [U.validators.required, U.validators.minLen(3)],
    fpDesc: [U.validators.optional(U.validators.minLen(3))]
  };

  C.render = function () {
    View.setFilter(cat);
    View.render(P.all(cat), Usr.current(), {
      onInfo: function (p) { View.showInfo(p); },
      onEdit: function (p) {
        if (!P.canEdit(Usr.current(), p)) { UI.toast(U.t('errNoEditar')); return; }
        View.openForm(p);
      },
      onDelete: function (p) {
        if (!P.canEdit(Usr.current(), p)) { UI.toast(U.t('errNoEditar')); return; }
        if (window.confirm(U.t('confirmEliminar'))) { P.remove(p.id); C.render(); UI.toast(U.t('toastEliminado')); }
      }
    });
  };

  /* Alta / edición: valida antes de tocar el modelo. */
  function guardar(e) {
    e.preventDefault();
    var v = View.formValues(), user = Usr.current();
    var values = { fpNombre: v.nombre, fpProductor: v.productor, fpPrecio: v.precio, fpUbicacion: v.ubicacion, fpDesc: v.desc };
    if (!UI.showErrors(View.form(), U.validate(values, RULES))) return;
    if (v.id) {
      var p = P.get(v.id);
      if (!p || !P.canEdit(user, p)) { UI.toast(U.t('errNoEditar')); return; }
      P.update(v.id, v); UI.toast(U.t('toastActualizado'));
    } else {
      if (!P.canCreate(user)) { UI.toast(U.t('errPermiso')); return; }
      P.create(v, user.contacto); UI.toast(U.t('toastGuardado'));
    }
    View.closeForm(); C.render();
  }

  C.init = function () {
    U.$$('.cat-tab[data-cat]').forEach(function (b) {
      b.addEventListener('click', function () { cat = b.getAttribute('data-cat'); C.render(); });
    });
    $('#btnNuevoProducto').addEventListener('click', function () {
      var user = Usr.current();
      if (!P.canCreate(user)) { UI.toast(U.t('errPermiso')); if (!user) A.controllers.App.navigate('Login'); return; }
      View.openForm(null, user.nombre);
    });
    $('#btnCancelarProducto').addEventListener('click', View.closeForm);
    View.form().addEventListener('submit', guardar);
    C.render();
  };
})(window.AyniHub);
