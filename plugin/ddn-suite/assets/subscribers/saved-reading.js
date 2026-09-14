document.addEventListener('DOMContentLoaded', function () {
  function postToRest(btn, onDone) {
    if (btn.disabled) return;
    btn.disabled = true;

    fetch(btn.dataset.restUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': btn.dataset.nonce },
      body: JSON.stringify({ post_id: Number(btn.dataset.postId) }),
    })
      .then(function (res) { return res.json(); })
      .then(onDone)
      .catch(function () {})
      .finally(function () {
        btn.disabled = false;
      });
  }

  // Botón «Guardar» / «Guardado»: en la nota y en la lista de Mi cuenta.
  document.querySelectorAll('[data-ddn-save]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      postToRest(btn, function (data) {
        var saved = !!data.saved;
        btn.classList.toggle('is-saved', saved);
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');

        var label = btn.querySelector('span');
        if (label) {
          label.textContent = saved ? (btn.dataset.labelSaved || 'Guardado') : (btn.dataset.labelSave || 'Guardar');
        }

        // Lista de «Guardados» en Mi cuenta: al quitarlo, se saca la fila.
        var row = btn.closest('[data-ddn-reading-item]');
        if (!saved && row && row.dataset.ddnReadingItem === 'saved') {
          row.remove();
        }
      });
    });
  });

  // «Quitar» una nota del historial de lectura (por privacidad), en Mi cuenta.
  document.querySelectorAll('[data-ddn-history-remove]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      postToRest(btn, function (data) {
        if (!data.removed) return;
        var row = btn.closest('[data-ddn-reading-item]');
        if (row) row.remove();
      });
    });
  });
});
