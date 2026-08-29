import '../scss/app.scss';

/**
 * Interacciones de front-end de Diario del Norte.
 * El bundle se sirve como <script type="module"> (diferido), así que en
 * lugar de escuchar DOMContentLoaded se comprueba readyState.
 */

function initSubmenu() {
  const more = document.querySelector('.mainnav__menu .menu-item--more');
  if (!more) return;

  const link = more.querySelector(':scope > a');
  const panel = more.querySelector(':scope > .sub-menu');
  if (!link || !panel) return;

  link.setAttribute('aria-haspopup', 'true');
  link.setAttribute('aria-expanded', 'false');

  const open = () => {
    more.classList.add('is-open');
    link.setAttribute('aria-expanded', 'true');
  };
  const close = () => {
    more.classList.remove('is-open');
    link.setAttribute('aria-expanded', 'false');
  };

  link.addEventListener('click', (e) => {
    // El href es "#": en pantallas táctiles el primer toque abre el panel.
    e.preventDefault();
    more.classList.contains('is-open') ? close() : open();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });

  document.addEventListener('click', (e) => {
    if (!more.contains(e.target)) close();
  });

  more.addEventListener('focusout', (e) => {
    if (!more.contains(e.relatedTarget)) close();
  });
}

function boot() {
  initSubmenu();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
