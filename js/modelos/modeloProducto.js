(function (A) {
  'use strict';
  var Store = A.services.Storage, KEY = 'ayni.productos';
  var LABEL = { kilo: 'Kilo', unidad: 'Unidad', arroba: 'Arroba (11.5 kg)' };

  function load() {
    var list = Store.get(KEY, null);
    if (!list) { list = JSON.parse(JSON.stringify(A.data.seedProductos)); Store.set(KEY, list); }
    return list;
  }
  function row(precio, unidad) { return [LABEL[unidad] || 'Unidad', 'S/ ' + Number(precio).toFixed(2)]; }

  var M = A.models.Product = {
    
    all: function (cat) { return load().filter(function (p) { return !cat || cat === 'todos' || p.cat === cat; }); },
    get: function (id) { return load().filter(function (p) { return p.id === id; })[0] || null; },
   
    create: function (d, owner) {
      var list = load();
      var p = { id: A.utils.uid(), cat: d.cat, nombre: d.nombre.trim(), productor: d.productor.trim(), owner: owner,
        img: null, ubicacion: d.ubicacion.trim(), precio: Number(d.precio), unidad: d.unidad,
        precios: [row(d.precio, d.unidad)], desc: (d.desc || '').trim() };
      list.push(p); Store.set(KEY, list); return p;
    },
   
    update: function (id, d) {
      var list = load(), p = list.filter(function (x) { return x.id === id; })[0];
      if (!p) return null;
      p.cat = d.cat; p.nombre = d.nombre.trim(); p.productor = d.productor.trim(); p.ubicacion = d.ubicacion.trim();
      p.precio = Number(d.precio); p.unidad = d.unidad; p.desc = (d.desc || '').trim();
      p.precios[0] = row(d.precio, d.unidad); // conserva otras escalas de precio (mayorista, etc.)
      Store.set(KEY, list); return p;
    },
    
    remove: function (id) {
      var list = load(), n = list.filter(function (p) { return p.id !== id; });
      Store.set(KEY, n); return n.length !== list.length;
    },
   
    canEdit: function (user, p) {
      if (!user) return false;
      return user.rol === 'gestor' || (user.rol === 'productor' && p.owner === user.contacto);
    },
    canCreate: function (user) { return !!user && user.rol !== 'comprador'; },
    summary: function (p) { return p.precios && p.precios.length ? p.precios[0][1] + ' / ' + p.precios[0][0].toLowerCase() : ''; }
  };
})(window.AyniHub);
