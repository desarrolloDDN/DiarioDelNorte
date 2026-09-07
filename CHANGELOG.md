# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/).
Versionado conjunto: el tema y `ddn-suite` comparten número de versión.

## [Sin publicar]

## [0.1.12] — 2026-09-07

### Cambiado
- **Buscador flotante, colgado del icono.** El rectángulo de búsqueda ya no
  aparece fijo en la esquina de la pantalla: se ancla justo debajo del icono
  de la lupa (bajo la fila de redes en el cabezote; bajo la barra roja en la
  nota).

## [0.1.11] — 2026-09-07

### Cambiado
- **Buscador flotante.** Al pulsar la lupa (en el cabezote y en la barra de
  la nota) la búsqueda ya no despliega una franja que empuja el contenido:
  aparece como un rectángulo blanco que flota arriba a la derecha, sobre la
  página. Se cierra con Esc o pulsando fuera.

## [0.1.10] — 2026-09-07

### Cambiado
- **Portada de sección, tarjetas con titular sobre la imagen.** El kicker de
  sección y el titular ya no van pegados al borde izquierdo de la foto; se les
  deja aire por los cuatro lados (`.cat-overlay__body`).

## [0.1.9] — 2026-09-07

### Cambiado
- El rótulo **«Espacio publicitario»** de cada anuncio va ahora **debajo**
  del banner, no encima (`AdRenderer` lo emite después de la creatividad y
  `.ddn-ad__label` lo fija abajo con `order`).

## [0.1.8] — 2026-09-07

### Quitado
- El **filete negro de 3 px** del cabezote (`.masthead`), el que quedaba
  entre la publicidad de cabecera y la barra superior de redes/buscador.

## [0.1.7] — 2026-09-06

### Cambiado
- **Pie de página reorganizado.** El menú de secciones ahora es plegable tras
  un botón «Secciones» (mismo estilo que «Términos y políticas»); al abrirlo se
  ven todas las categorías. Si no hay un menú asignado en Apariencia → Menús,
  el pie usa el menú principal o, en su defecto, la lista completa de
  secciones (incluidas las de «Más») con «Inicio» y «Contacto».

### Quitado
- El **filete negro** que separaba el pie del resto de la web; el cambio de
  fondo (arena) ya lo distingue.

## [0.1.6] — 2026-09-06

### Corregido
- **Cuadro gris en el informe de campaña impreso / en PDF.** El fondo gris de
  wp-admin asomaba bajo la hoja del informe cuando el contenido no llegaba al
  final de la página; ahora al imprimir se fuerza fondo blanco en toda la
  cadena de contenedores.

## [0.1.5] — 2026-09-06

### Corregido
- **Línea negra bajo la publicidad de cabecera.** Muchas creatividades traen un
  filete oscuro de 1 px en el borde (o lo genera el navegador al reescalar el
  banner); ahora el contenedor de anuncios recorta 1 px arriba y abajo, así que
  ya no aparece una raya entre el anuncio y la barra de secciones / redes.
- **«Subir evidencia» en el gestor de publicidad.** El botón «Añadir imagen» no
  hacía nada: el script se ejecutaba antes de que WordPress cargara el selector
  de medios (`wp.media`). Ahora espera a que el selector esté disponible.

## [0.1.4] — 2026-09-06

### Añadido
- **Imagen de presentación del tema** (`screenshot.png`): ya no sale el recuadro
  a cuadros en Apariencia → Temas.

## [0.1.3] — 2026-09-06

### Añadido
- **«Comprobar actualizaciones»** en la fila de DDN Suite (Plugins): fuerza una
  consulta inmediata a GitHub —vacía las cachés y vuelve a mirar el tema y el
  plugin— y avisa si hay una versión nueva o si ya estás al día.

## [0.1.2] — 2026-09-06

### Cambiado
- **Zonas de publicidad, colocación real:**
  - *Cabecera*: al principio de todas las páginas (home, notas, secciones…),
    por encima de la fecha y las redes sociales.
  - *Portada*: bajo el menú principal — en el home y también en las notas,
    justo después de la cinta «Lo último».
  - *Inicio de la nota*: bajo la firma del autor.
  - *Dentro de la nota*: tras el tercer párrafo (sin cambios).
  - *Final de la nota*: al terminar el texto (sin cambios).

## [0.1.1] — 2026-09-06

### Añadido
- **Actualización automática desde GitHub.** El tema y `ddn-suite` comprueban los
  *releases* del repositorio (público, sin token) y muestran «Hay una
  actualización disponible» en Apariencia / Plugins de WordPress; se actualiza
  con un clic. A partir de esta versión ya no hace falta subir zips a mano.
- **Membrete de Sistema Cardenal** en el informe de campaña (logo arriba, pie con
  teléfono y dirección; en impresión el pie se repite en cada página).
- **Gestor de publicidad rediseñado.** Tabla con pestañas Campañas / Historial,
  ficha de alta/edición en tarjeta con vista previa en vivo, y por campaña:
  activar/desactivar, **subir evidencia** (pantallazos del anuncio publicado) y
  **generar informe** imprimible para el anunciante (impresiones, clics, CTR,
  detalle por día y evidencia). Tipos nuevos: `gam`, `video`, `sponsored`.
  Una campaña puede aparecer en **varias zonas** a la vez. Campos de AdSense
  (client ID + slot). Migración de base de datos a v6 (conserva las campañas
  existentes; su zona única pasa a la lista de zonas).
- **Edición impresa pública.** El tipo de contenido `ddn_edition` pasa a ser
  público en `/edicion-impresa/`, con archivo de ediciones anteriores. Cada
  edición tiene su propia entrada (portada + nota de la redacción + botón para
  descargar el PDF); plantillas `single-ddn_edition.php` y
  `archive-ddn_edition.php` en el tema.

### Corregido
- Las URLs de cada edición (`/edicion-impresa/{fecha}/`) daban 404 en sitios
  migrados sin base de categoría: se añade una regla de reescritura explícita
  «arriba» y el refresco de reglas se reintenta hasta lograrlo.
- El mismo 404 volvía a aparecer cada vez que se reinstalaba el plugin con el
  mismo zip (sin pasar por el hook de activación), porque el refresco de
  reglas dependía de un cambio de versión. Ahora se comprueba en cada carga
  si la regla de la edición sigue guardada y, si falta, se refresca sola —
  sin tener que entrar a Ajustes → Enlaces permanentes a mano.

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
