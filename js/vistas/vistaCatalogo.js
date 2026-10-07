
(function (A) {
  'use strict';
  var U = A.utils, $ = U.$, UI = A.views.UI, P = A.models.Product;
  var V = A.views.Catalog = {};
  var PLACEHOLDER = '<svg aria-hidden="true" class="prod-icon" viewBox="0 0 24 24" fill="none" stroke="#E8A33D" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>';

  
  V.render = function (list, user, h) {
    var grid = $('#catGrid'); grid.innerHTML = '';
    if (!list.length) { grid.appendChild(U.el('p', 'empty-msg', U.t('sinProductos'))); return; }
    list.forEach(function (p) {
      var card = U.el('article', 'prod-card show');
      if (p.img) {
        var b = U.el('button', 'prod-img-wrap'); b.type = 'button';
        b.style.cssText = 'padding:0;border:0;background:none;width:100%;display:block;';
        b.setAttribute('aria-label', p.nombre);
        var img = U.el('img'); img.src = p.img; img.alt = p.nombre; img.loading = 'lazy'; b.appendChild(img);
        b.addEventListener('click', function () { h.onInfo(p); }); card.appendChild(b);
      } else {
        var top = U.el('div', 'prod-top'); top.innerHTML = PLACEHOLDER;
        top.appendChild(U.el('span', 'badge', U.t('badge'))); card.appendChild(top);
      }
      card.appendChild(U.el('h3', null, p.nombre));
      card.appendChild(U.el('p', 'who', p.productor));
      card.appendChild(U.el('p', 'loc', '📍 ' + p.ubicacion));
      card.appendChild(U.el('p', 'price', P.summary(p)));
      if (P.canEdit(user, p)) {
        var act = U.el('div', 'card-actions');
        var ed = U.el('button', 'btn btn-ghost btn-small', U.t('editar')); ed.type = 'button';
        var del = U.el('button', 'btn btn-ghost btn-small btn-danger', U.t('eliminar')); del.type = 'button';
        ed.addEventListener('click', function () { h.onEdit(p); });
        del.addEventListener('click', function () { h.onDelete(p); });
        act.appendChild(ed); act.appendChild(del); card.appendChild(act);
      }
      grid.appendChild(card);
    });
  };

  V.setFilter = function (cat) {
    U.$$('.cat-tab[data-cat]').forEach(function (t) { t.setAttribute('aria-pressed', String(t.getAttribute('data-cat') === cat)); });
  };

  V.form = function () { return $('#formProducto'); };
  V.formValues = function () {
    return { id: $('#fpId').value, nombre: $('#fpNombre').value, cat: $('#fpCat').value, productor: $('#fpProductor').value,
      precio: $('#fpPrecio').value, unidad: $('#fpUnidad').value, ubicacion: $('#fpUbicacion').value, desc: $('#fpDesc').value };
  };
  
  V.openForm = function (p, defaultProductor) {
    var f = V.form(); f.reset(); UI.clearErrors(f);
    $('#fpId').value = p ? p.id : '';
    if (p) {
      $('#fpNombre').value = p.nombre; $('#fpCat').value = p.cat; $('#fpProductor').value = p.productor;
      $('#fpPrecio').value = p.precio; $('#fpUnidad').value = p.unidad; $('#fpUbicacion').value = p.ubicacion; $('#fpDesc').value = p.desc || '';
    } else { $('#fpProductor').value = defaultProductor || ''; }
    $('#fpTitulo').textContent = U.t(p ? 'editFormTitle' : 'addFormTitle');
    f.hidden = false; $('#fpNombre').focus();
  };
  V.closeForm = function () { V.form().hidden = true; };

  V.showInfo = function (p) {
    $('#infoImg').src = p.img || ''; $('#infoImg').alt = p.nombre; $('#infoImg').hidden = !p.img;
    $('#infoNombre').textContent = p.nombre; $('#infoDesc').textContent = p.desc || '';
    var box = $('#infoPrecios'); box.innerHTML = '';
    p.precios.forEach(function (r) { var d = U.el('div'); d.appendChild(U.el('b', null, r[0] + ': ')); d.appendChild(document.createTextNode(r[1])); box.appendChild(d); });
    UI.openModal('infoOverlay');
  };

})(window.AyniHub);
