document.addEventListener('DOMContentLoaded', function () {
  // «Eliminar mi cuenta»: el botón solo se habilita al marcar la casilla
  // de confirmación, y además pide un diálogo nativo antes de enviar.
  var form = document.getElementById('ddn-delete-account-form');
  var box = document.getElementById('ddn-delete-account-confirm');
  var btn = document.getElementById('ddn-delete-account-btn');
  if (!form || !box || !btn) return;

  box.addEventListener('change', function () {
    btn.disabled = !box.checked;
  });

  form.addEventListener('submit', function (e) {
    var msg = form.dataset.confirm || '¿Seguro? Esta acción no se puede deshacer.';
    if (!window.confirm(msg)) {
      e.preventDefault();
    }
  });
});
