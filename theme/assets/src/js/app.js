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
  const dots = Array.from(slider.querySelectorAll('.hero-slider__thumb'));
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

function initMoreNews() {
  document.querySelectorAll('[data-more-news]').forEach((wrap) => {
    const btn = wrap.querySelector('[data-more-news-btn]');
    if (!btn) return;

    btn.addEventListener('click', () => {
      wrap.classList.add('is-expanded');
      btn.setAttribute('aria-expanded', 'true');
    });
  });
}

function loadScriptOnce(src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector('script[data-src="' + src + '"]')) { resolve(); return; }
    const s = document.createElement('script');
    s.src = src;
    s.dataset.src = src;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error('load ' + src));
    document.head.appendChild(s);
  });
}

// Visor de la edición impresa: renderiza el PDF con pdf.js dentro de la
// entrada, ajustado al ancho de la pantalla. pdf.js se descarga solo al
// pulsar «Leer en línea». Zoom con − / +.
function initEditionReader() {
  const btn = document.querySelector('[data-edition-reader-toggle]');
  const reader = document.querySelector('[data-edition-reader]');
  if (!btn || !reader) return;

  const pagesEl = reader.querySelector('[data-edition-pages]');
  const levelEl = reader.querySelector('[data-edition-level]');
  const labelShow = btn.textContent;
  const labelHide = btn.dataset.labelHide || labelShow;
  const t = reader.dataset;

  let started = false;
  let doc = null;
  let zoom = 1;
  let renderT;

  function setStatus(html) {
    pagesEl.innerHTML = '<p class="edition__reader-status">' + html + '</p>';
  }

  async function start() {
    setStatus(t.i18nLoading || 'Cargando…');
    try {
      if (!window.pdfjsLib) await loadScriptOnce(t.lib);
      const pdfjsLib = window.pdfjsLib;
      pdfjsLib.GlobalWorkerOptions.workerSrc = t.worker;
      doc = await pdfjsLib.getDocument(t.pdf).promise;
      build();
    } catch (e) {
      setStatus(
        (t.i18nError || 'No se pudo cargar el visor.') +
          ' <a href="' + t.pdf + '" target="_blank" rel="noopener">' + (t.i18nOpen || 'Abrir el PDF') + '</a>'
      );
    }
  }

  function build() {
    pagesEl.innerHTML = '';
    const dpr = Math.min(window.devicePixelRatio || 1, 2);

    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          const holder = entry.target;
          if (entry.isIntersecting) {
            renderPage(holder, dpr);
          } else if (holder.dataset.rendered && Math.abs(entry.boundingClientRect.top) > window.innerHeight * 4) {
            // Libera memoria en páginas muy lejos de la vista.
            holder.innerHTML = '';
            delete holder.dataset.rendered;
          }
        });
      },
      { rootMargin: '800px 0px' }
    );

    for (let n = 1; n <= doc.numPages; n++) {
      const holder = document.createElement('div');
      holder.className = 'edition__reader-page';
      holder.dataset.page = String(n);
      pagesEl.appendChild(holder);
      io.observe(holder);
    }

    reader._rerender = () => {
      clearTimeout(renderT);
      renderT = setTimeout(() => {
        pagesEl.querySelectorAll('.edition__reader-page').forEach((h) => {
          delete h.dataset.rendered;
          h.innerHTML = '';
          const r = h.getBoundingClientRect();
          if (r.bottom > -800 && r.top < window.innerHeight + 800) renderPage(h, dpr);
        });
      }, 250);
    };
  }

  async function renderPage(holder, dpr) {
    if (holder.dataset.rendered || !doc) return;
    holder.dataset.rendered = '1';
    const page = await doc.getPage(Number(holder.dataset.page));
    const cssWidth = pagesEl.clientWidth * zoom;
    const unit = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: (cssWidth / unit.width) * dpr });
    const canvas = document.createElement('canvas');
    canvas.width = Math.floor(viewport.width);
    canvas.height = Math.floor(viewport.height);
    canvas.style.width = cssWidth + 'px';
    canvas.style.height = 'auto';
    holder.innerHTML = '';
    holder.appendChild(canvas);
    try {
      await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    } catch (e) {
      delete holder.dataset.rendered;
    }
  }

  function setZoom(next) {
    zoom = Math.min(3, Math.max(1, Math.round(next * 4) / 4));
    if (levelEl) levelEl.textContent = Math.round(zoom * 100) + ' %';
    if (reader._rerender) reader._rerender();
  }

  reader.querySelectorAll('[data-edition-zoom]').forEach((b) => {
    b.addEventListener('click', () => setZoom(zoom + (b.dataset.editionZoom === 'in' ? 0.25 : -0.25)));
  });

  window.addEventListener('resize', () => {
    if (reader._rerender) reader._rerender();
  });

  btn.addEventListener('click', () => {
    const willOpen = reader.hidden;
    reader.hidden = !willOpen;
    btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    btn.textContent = willOpen ? labelHide : labelShow;
    if (willOpen) {
      reader.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (!started) {
        started = true;
        start();
      }
    }
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

function initBarPanels(barSelector, pairs) {
  const bar = document.querySelector(barSelector);
  if (!bar) return;

  const panels = pairs
    .map(([btnSel, panelId]) => ({
      btn: bar.querySelector(btnSel),
      panel: document.getElementById(panelId),
    }))
    .filter((p) => p.btn && p.panel);

  const setOpen = (pair, open) => {
    pair.panel.hidden = !open;
    pair.btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      const field = pair.panel.querySelector('input');
      if (field) field.focus({ preventScroll: true });
    }
  };
  const closeAll = () => panels.forEach((p) => setOpen(p, false));

  panels.forEach((pair) => {
    pair.btn.addEventListener('click', () => {
      const willOpen = pair.panel.hidden;
      closeAll();
      if (willOpen) setOpen(pair, true);
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAll();
  });
  document.addEventListener('click', (e) => {
    if (!bar.contains(e.target)) closeAll();
  });
}

function boot() {
  initSubmenu();
  initBarPanels('.topbar', [
    ['[data-drawer-toggle]', 'topbar-drawer'],
    ['[data-search-toggle]', 'topbar-search'],
  ]);
  initBarPanels('.masthead', [['[data-search-toggle]', 'masthead-search']]);
  initHeroSlider();
  initCardSliders();
  initMoreNews();
  initEditionReader();
  initShareCopy();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
