(function (A) {
  'use strict';
  A.services.Storage = {
    get: function (key, fallback) {
      try { var raw = localStorage.getItem(key); return raw === null ? fallback : JSON.parse(raw); }
      catch (e) { return fallback; }
    },
    set: function (key, value) {
      try { localStorage.setItem(key, JSON.stringify(value)); return true; }
      catch (e) { return false; }
    },
    remove: function (key) { try { localStorage.removeItem(key); } catch (e) {} }
  };
})(window.AyniHub);
