# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/).
Versionado conjunto: el tema y `ddn-suite` comparten número de versión.

## [Sin publicar]

### Cambiado
- **Portada, cuerpo a dos columnas** (principal + lateral). Columna principal:
  La Guajira (3 destacadas + 4 en lista), Judiciales y Opinión en carrusel
  horizontal con flechas, y «Más noticias» — un cajón que recoge todo lo que no
  salió en ningún otro bloque de la portada (sin repeticiones). Columna lateral:
  Editorial, Edición impresa (portada + enlace a la edición digital), «Lo más
  leído» (24 h) y boletín (opcional, se activa con el filtro `ddn/newsletter_action`).
- Se retira la tira de «Última hora» de la portada.
- **Portada, sección de apertura** a tres columnas (1/2 – 1/4 – 1/4):
  carrusel de 6 noticias con la etiqueta «Destacado» (autorrotación 6 s, pausa
  al pasar el ratón / enfocar, respeta `prefers-reduced-motion`, puntos de
  navegación); columna central con una nota de «Judiciales» (imagen, titular,
  extracto); columna derecha con una de «Caribe» y una de «Nación» (titular e
  imagen). La tira de última hora / lo más leído / edición impresa pasa a una
  fila propia bajo la apertura.

### Añadido
- Esqueleto del repositorio: tema `diario-del-norte` + plugin `ddn-suite`.
- Sistema de diseño (identidad): paleta Rojo Norte `#BF0202`, sin modo oscuro,
  titulares «Sunlight Dreams» (autoalojada) y Libre Franklin para el resto.
- Instalador del menú principal (19 secciones + submenú «Más»): resuelve
  cada sección por slug **o por nombre**, así funciona en un sitio ya
  migrado donde la categoría tenga otro slug (p. ej. `judiciales-2`); al
  subir de versión reconstruye el menú entero.
- Jerarquía de plantillas: portada, artículo, archivo, búsqueda, página, 404.
- `ddn-suite`: gestor de publicidad (campañas con vigencia, **prioridad y peso**,
  **segmentación por categoría**, rotación por sorteo ponderado — `CampaignSelector`),
  tracking de impresiones/clics, redirección de clic server-side; calendario editorial.
- `ddn-suite`: **conteo de páginas vistas** del lado del servidor (cubos por hora,
  sin PII, excluye personal y bots) y filtro `ddn/most_read` que alimenta el «Lo
  más leído» del tema con las noticias reales de las últimas 24 h. Limpieza diaria
  vía WP-Cron.
- Tema: campos de autor (**foto de perfil** con selector de medios + **cargo para la
  firma**); la foto sustituye a Gravatar. Banda de **Opinión** en portada con tarjetas
  de columnista.
- Fuentes **totalmente autoalojadas**: Libre Franklin (variable, subconjunto latino)
  y «Sunlight Dreams»; sin ninguna petición a Google Fonts. Precarga de las dos
  fuentes críticas.
- Archivos `.pot` de traducción (`tools/make-pot.sh`).
- Herramientas: `phpcs` (WordPress-Extra), `phpstan`, Vite, CI de GitHub Actions,
  `tools/build/package.sh`.
