/* controladorApp.js — CONTROLADOR raíz: enrutador por hash (#Catalogo), idioma, menú móvil y carrusel */
(function (A) {
  'use strict';
  var U = A.utils, $ = U.$, UI = A.views.UI;
  var ROUTES = ['Inicio', 'Entrevista', 'ComoFunciona', 'Catalogo', 'Login', 'Registro'];
  var first = true, slide = 0, timer, App = A.controllers.App = {};

  function route() {
    var name = location.hash.replace('#', '');
    if (ROUTES.indexOf(name) < 0) name = 'Inicio';
    UI.showSection(name, !first); first = false;
    $('#navLinks').classList.remove('open'); $('#menuToggle').setAttribute('aria-expanded', 'false');
  }
  App.navigate = function (name) { if (location.hash === '#' + name) route(); else location.hash = '#' + name; };

  App.setLang = function (lang) {
    A.state.lang = lang; document.documentElement.lang = lang;
    ['es', 'qu'].forEach(function (l) {
      var b = $('#btn-' + l); b.classList.toggle('active', l === lang); b.setAttribute('aria-pressed', String(l === lang));
    });
    UI.applyLang(document); UI.renderUser(A.models.User.current()); A.controllers.Catalog.render();
  };

  function showSlide(i) {
    slide = i;
    U.$$('#heroCarousel .carousel-img').forEach(function (img, k) { img.classList.toggle('show', k === i); });
    U.$$('#heroCarousel .dot').forEach(function (d, k) { d.classList.toggle('active', k === i); });
  }

  App.init = function () {
    window.addEventListener('hashchange', route); route();
    U.$$('[data-lang]').forEach(function (b) { b.addEventListener('click', function () { App.setLang(b.getAttribute('data-lang')); }); });
    $('#menuToggle').addEventListener('click', function () {
      var open = $('#navLinks').classList.toggle('open'); this.setAttribute('aria-expanded', String(open));
    });
    U.$$('#heroCarousel .dot').forEach(function (d) { d.addEventListener('click', function () { showSlide(Number(d.getAttribute('data-slide'))); }); });
    if (!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
      timer = setInterval(function () { showSlide((slide + 1) % 3); }, 4500);
    }
  };
})(window.AyniHub);
