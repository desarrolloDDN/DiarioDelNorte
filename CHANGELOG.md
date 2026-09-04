# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/).
Versionado conjunto: el tema y `ddn-suite` comparten número de versión.

## [Sin publicar]

### Añadido
- **Edición impresa pública.** El tipo de contenido `ddn_edition` pasa a ser
  público en `/edicion-impresa/`, con archivo de ediciones anteriores. Cada
  edición tiene su propia entrada (portada + nota de la redacción + botón para
  descargar el PDF); plantillas `single-ddn_edition.php` y
  `archive-ddn_edition.php` en el tema.

### Corregido
- Las URLs de cada edición (`/edicion-impresa/{fecha}/`) daban 404 en sitios
  migrados sin base de categoría: se añade una regla de reescritura explícita
  «arriba» y el refresco de reglas se reintenta hasta lograrlo.

### Quitado
- El bloque **«Boletín»** del lateral de la portada (escritorio y móvil) y el
  filtro `ddn/newsletter_action` que lo alimentaba.

### Cambiado
- El botón de «Edición impresa» del lateral de la portada se llama ahora
  **«Ver Edición Impresa»** y lleva a la entrada de la edición, no al PDF
  suelto de la biblioteca de medios.
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
- **Barra de secciones dibujada por el tema** (`Nav\SectionMenu`): 10 visibles
  + submenú «Más» con 9, resolviendo cada categoría por slug **o por nombre**
  (funciona con `judiciales-2` y demás slugs migrados). Ya no depende de un menú
  guardado en la base de datos. Para usar un menú propio de Apariencia → Menús,
  devolver `true` en el filtro `ddn/use_custom_nav`. El instalador solo siembra
  las 19 categorías y desasigna el menú «Secciones» autogenerado por versiones
  anteriores.
- Jerarquía de plantillas: portada, artículo, archivo, búsqueda, página, 404.
- `ddn-suite`: gestor de publicidad (campañas con vigencia, **prioridad y peso**,
  **segmentación por categoría**, rotación por sorteo ponderado — `CampaignSelector`),
  tracking de impresiones/clics, redirección de clic server-side; calendario editorial.
- `ddn-suite`: **conteo de páginas vistas** del lado del servidor (cubos por hora,
  sin PII, excluye personal y bots) y filtro `ddn/most_read` que alimenta el «Lo
  más leído» del tema con las noticias reales de las últimas 24 h. Limpieza diaria
  vía WP-Cron.
- `ddn-suite`: **módulo «Edición impresa»** (tipo de contenido propio bajo el menú
  DDN Suite): una entrada por fecha con portada (imagen destacada) y PDF (subida
  desde la biblioteca de medios, solo `application/pdf`). El tema toma la edición
  vigente por el filtro `ddn/print_edition`; si el plugin no está activo usa los
  campos del Personalizador.
- Tema: campos de autor (**foto de perfil** con selector de medios + **cargo para la
  firma**); la foto sustituye a Gravatar. Banda de **Opinión** en portada con tarjetas
  de columnista.
- Fuentes **totalmente autoalojadas**: Libre Franklin (variable, subconjunto latino)
  y «Sunlight Dreams»; sin ninguna petición a Google Fonts. Precarga de las dos
  fuentes críticas.
- Archivos `.pot` de traducción (`tools/make-pot.sh`).
- Herramientas: `phpcs` (WordPress-Extra), `phpstan`, Vite, CI de GitHub Actions,
  `tools/build/package.sh`.
