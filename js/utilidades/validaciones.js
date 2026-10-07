(function (A) {
  'use strict';
  var V = A.utils.validators = {
    required: function (v) { return String(v == null ? '' : v).trim() ? '' : 'errRequired'; },
    minLen: function (n) { return function (v) { return String(v || '').trim().length >= n ? '' : 'errMin'; }; },
    /* Correo válido o celular peruano (9 dígitos, empieza con 9). */
    contacto: function (v) {
      v = String(v || '').trim();
      var email = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/, cel = /^9\d{8}$/;
      return (email.test(v) || cel.test(v.replace(/[\s-]/g, ''))) ? '' : 'errContacto';
    },
    password: function (v) { return /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(String(v || '')) ? '' : 'errPass'; },
    precio: function (v) { var n = Number(v); return (isFinite(n) && n > 0 && n <= 10000) ? '' : 'errPrecio'; },
    /* Hace opcional otra regla: si el campo está vacío, es válido. */
    optional: function (rule) { return function (v) { return String(v || '').trim() ? rule(v) : ''; }; }
  };

  /* validate(valores, reglas) → { campo: claveError }  (objeto vacío = formulario válido) */
  A.utils.validate = function (values, rules) {
    var errors = {};
    Object.keys(rules).forEach(function (field) {
      for (var i = 0; i < rules[field].length; i++) {
        var e = rules[field][i](values[field]);
        if (e) { errors[field] = e; break; }
      }
    });
    return errors;
  };
})(window.AyniHub);
