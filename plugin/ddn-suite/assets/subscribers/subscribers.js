document.addEventListener('DOMContentLoaded', function () {
  // «Eliminar mi cuenta»: el botón solo se habilita al marcar la casilla
  // de confirmación, y además pide un diálogo nativo antes de enviar.
  var form = document.getElementById('ddn-delete-account-form');
  var box = document.getElementById('ddn-delete-account-confirm');
  var btn = document.getElementById('ddn-delete-account-btn');
  if (form && box && btn) {
    box.addEventListener('change', function () {
      btn.disabled = !box.checked;
    });

    form.addEventListener('submit', function (e) {
      var msg = form.dataset.confirm || '¿Seguro? Esta acción no se puede deshacer.';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  }

  // Pestañas de «Mi cuenta»: el servidor ya deja la pestaña correcta
  // visible en la carga (según venga o no un aviso de guardado/borrado);
  // esto solo añade el cambio sin recargar, con el hash de la URL como
  // marcador para poder enlazar directo a una pestaña.
  var tabs = document.querySelector('[data-ddn-tabs]');
  if (!tabs) return;

  var buttons = Array.prototype.slice.call(tabs.querySelectorAll('[data-ddn-tab]'));
  var panels = {};
  buttons.forEach(function (b) {
    panels[b.dataset.ddnTab] = document.getElementById(b.getAttribute('aria-controls'));
  });

  function activate(name, updateHash) {
    if (!panels[name]) return;
    buttons.forEach(function (b) {
      var active = b.dataset.ddnTab === name;
      b.classList.toggle('is-active', active);
      b.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    Object.keys(panels).forEach(function (key) {
      panels[key].hidden = key !== name;
    });
    if (updateHash && window.history && window.history.replaceState) {
      window.history.replaceState(null, '', '#' + name);
    }
  }

  buttons.forEach(function (b) {
    b.addEventListener('click', function () {
      activate(b.dataset.ddnTab, true);
    });
  });

  var fromHash = (window.location.hash || '').replace('#', '');
  if (fromHash && panels[fromHash]) {
    activate(fromHash, false);
  }
});
