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

function initHeroSlider() {
  const slider = document.querySelector('[data-hero-slider]');
  if (!slider) return;

  const slides = Array.from(slider.querySelectorAll('.hero-slide'));
  const dots = Array.from(slider.querySelectorAll('.hero-slider__dot'));
  if (slides.length < 2) return;

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let index = 0;
  let timer = null;

  const show = (n) => {
    index = (n + slides.length) % slides.length;
    slides.forEach((s, i) => {
      const on = i === index;
      s.classList.toggle('is-active', on);
      if (on) { s.removeAttribute('aria-hidden'); } else { s.setAttribute('aria-hidden', 'true'); }
    });
    dots.forEach((d, i) => d.classList.toggle('is-active', i === index));
  };

  const start = () => {
    if (reduce || timer) return;
    timer = window.setInterval(() => show(index + 1), 6000);
  };
  const stop = () => {
    if (timer) { window.clearInterval(timer); timer = null; }
  };

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => { show(i); stop(); start(); });
  });

  slider.addEventListener('mouseenter', stop);
  slider.addEventListener('mouseleave', start);
  slider.addEventListener('focusin', stop);
  slider.addEventListener('focusout', start);

  start();
}

function initCardSliders() {
  document.querySelectorAll('[data-card-slider]').forEach((slider) => {
    const track = slider.querySelector('.card-slider__track');
    const prev = slider.querySelector('.card-slider__nav--prev');
    const next = slider.querySelector('.card-slider__nav--next');
    if (!track || !prev || !next) return;

    const update = () => {
      const max = track.scrollWidth - track.clientWidth - 2;
      prev.hidden = track.scrollLeft <= 2;
      next.hidden = track.scrollLeft >= max;
    };
    const step = () => Math.max(track.clientWidth * 0.85, 240);

    prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    track.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
  });
}

function initShareCopy() {
  const btn = document.querySelector('.article__share-copy');
  if (!btn) return;

  const url = btn.dataset.url || window.location.href;

  btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(url);
    } catch (err) {
      const tmp = document.createElement('input');
      tmp.value = url;
      document.body.appendChild(tmp);
      tmp.select();
      document.execCommand('copy');
      tmp.remove();
    }
    btn.classList.add('is-copied');
    window.setTimeout(() => btn.classList.remove('is-copied'), 1600);
  });
}

function boot() {
  initSubmenu();
  initHeroSlider();
  initCardSliders();
  initShareCopy();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
