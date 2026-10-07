/* ayudas.js — estado global ligero y utilidades de DOM / texto */
(function (A) {
  'use strict';
  A.state = { lang: 'es' };
  var U = A.utils;

  U.$ = function (sel, root) { return (root || document).querySelector(sel); };
  U.$$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* Evita XSS al insertar texto del usuario en HTML. */
  U.escapeHTML = function (s) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return map[c]; });
  };

  /* Traducción con respaldo en español. */
  U.t = function (key) {
    var d = A.data.i18n, v = d[A.state.lang][key];
    if (v !== undefined) return v;
    return d.es[key] !== undefined ? d.es[key] : key;
  };

  U.uid = function () { return 'p' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6); };

  /* Crea un elemento con texto seguro (textContent). */
  U.el = function (tag, cls, text) {
    var e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
  };

  /* Hash SHA-256 de la contraseña (nunca se guarda en texto plano). */
  U.hash = function (text) {
    if (window.crypto && crypto.subtle && window.TextEncoder) {
      return crypto.subtle.digest('SHA-256', new TextEncoder().encode(text)).then(function (buf) {
        return Array.prototype.map.call(new Uint8Array(buf), function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
      });
    }
    var h = 5381; // respaldo simple si el navegador no soporta SubtleCrypto
    for (var i = 0; i < text.length; i++) { h = ((h << 5) + h + text.charCodeAt(i)) | 0; }
    return Promise.resolve('d' + h);
  };
})(window.AyniHub);
