document.addEventListener('DOMContentLoaded', function () {
  var C = AyniHub.controllers;
  AyniHub.views.UI.init();
  C.Catalog.init();   
  C.Auth.init();      
  C.App.init();      
  AyniHub.views.UI.applyLang(document);
});
