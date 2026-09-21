# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/).
Versionado conjunto: el tema y `ddn-suite` comparten número de versión.

## [Sin publicar]

## [0.1.51] — 2026-09-21

### Añadido
- **Estadísticas de lectura (DDN Suite → Estadísticas, solo Administrador).**
  Para el rango de fechas elegido (Hoy, 7, 30, 90 días, 12 meses o fechas
  a mano): lecturas totales, notas leídas, lecturas por nota, **autor más
  leído**, gráfico de lecturas por día, las **20 notas más leídas** (con
  autor, sección y fecha) y el **ranking de autores** (notas, lecturas y
  promedio por nota). Cuenta lectores reales: no incluye al personal de
  redacción ni a robots, y solo notas publicadas.

### Cambiado
- El registro de lecturas por hora ahora se conserva **400 días** (antes 8)
  para que existan estadísticas de meses. Los datos de antes de esta
  versión no se recuperan: el histórico empieza a acumularse desde ahora
  (de lo anterior solo quedan los últimos días).

## [0.1.50] — 2026-09-20

### Cambiado
- **Contacto**: el formulario ahora llega a `gerenciageneral@gamezeditores.com`
  y ese mismo correo es el que se muestra en «Datos de contacto» (antes
  llegaba al correo de redacción). Sigue siendo editable en Personalizar →
  Contacto → «Correo del formulario de contacto»; en blanco usa el de
  gerencia. El correo de redacción del pie de página no cambia.

## [0.1.49] — 2026-09-19

### Añadido
- **Botón «Suscríbete» / «Mi cuenta» también en la cabecera de la nota**
  (la barra roja compacta), entre «DIARIO DEL NORTE» y la lupa. Mismo
  comportamiento que en el home: sin sesión lleva al registro; con sesión
  abre «Ver perfil / Cerrar sesión». En celular queda solo el icono de
  persona, como los demás iconos de la barra, y cabe hasta en pantallas de
  320 px.

## [0.1.48] — 2026-09-19

### Cambiado
- **El acceso a la cuenta vive solo en la barra roja de secciones.** Se
  eliminó el enlace «Mi cuenta» de la barra superior. Sin sesión, el botón
  de la barra roja dice «Suscríbete» y lleva al registro; con sesión
  cambia a «Mi cuenta» y abre el menú «Ver perfil / Cerrar sesión».
- En pantallas anchas (≥ 1360 px) el botón flota fuera del flujo del menú
  y las secciones conservan su centrado; en pantallas más angostas
  comparte la fila con el menú (en móvil, el menú se desplaza en
  horizontal y el botón queda fijo a la derecha), así ya no se pierde el
  acceso a la cuenta en tabletas y celulares.

## [0.1.47] — 2026-09-19

### Añadido
- **Botón «Suscríbete» en la barra roja de secciones** (solo sin sesión,
  a partir de 1360 px de ancho): blanco con texto rojo, en el extremo
  derecho. Va fuera del flujo del menú, así que las secciones conservan
  exactamente su centrado y espaciado (medido: mismos márgenes a ambos
  lados con y sin el botón). En pantallas más angostas no se muestra y
  queda el enlace de la barra superior.

## [0.1.46] — 2026-09-19

### Cambiado
- **«Lo último» al final de la nota**: en móvil (cuadrícula de 2
  columnas, hasta 760 px) muestra cuatro noticias en vez de tres, para
  cerrar en dos filas completas; en escritorio siguen siendo tres.

## [0.1.45] — 2026-09-19

### Cambiado
- **Llamado a suscribirse**: rediseñado sobre fondo claro (gris casi
  blanco con borde y filete superior en Rojo Norte), kicker con el
  cuadrito rojo del periódico, titular en Lora oscuro, «Continuar con
  Google» en botón blanco con borde y «Crear cuenta gratis» en rojo.

## [0.1.44] — 2026-09-19

### Añadido
- **Llamado a suscribirse al final de cada nota**, solo para visitantes
  sin sesión: «Suscríbete gratis», botones «Continuar con Google» (solo
  si Google está configurado) y «Crear cuenta gratis», y «¿Ya tienes
  cuenta? Inicia sesión». Con la identidad del periódico (fondo tinta,
  Rojo Norte, titular en Lora). En notas exclusivas para suscriptores no
  se repite: ya sale el aviso propio del muro.

## [0.1.43] — 2026-09-16

### Corregido
- **Error crítico al actualizar a la v0.1.42** (`ddn-suite` quedaba
  inutilizable en todo el sitio, no solo en el panel de Actividad). La
  tabla de la bitácora se construía demasiado pronto — al arrancar el
  plugin, antes de que WordPress cargara `convert_to_screen()` (una
  función que `WP_List_Table` necesita y que solo existe una vez que
  WordPress está de verdad dibujando una pantalla de administración).
  Ahora se construye, como el resto de tablas del plugin, solo dentro de
  su propia pantalla.

## [0.1.42] — 2026-09-16

### Añadido
- **Actividad (DDN Suite → Actividad, solo Administrador).** Bitácora de
  eventos clave de todos los usuarios de la web — personal (redacción,
  autores, administradores) y suscriptores por igual, porque todos pasan
  por los mismos mecanismos nativos de WordPress: inicio y cierre de
  sesión, notas publicadas/editadas/enviadas a la papelera/borradas, y
  alta, cambio de rol o baja de un usuario. Cada fila guarda una foto de
  quién hizo qué (usuario, rol, IP, fecha) y sobre qué (nota o usuario),
  filtrable por evento, usuario y rango de fechas; se conserva 180 días
  y se poda sola.
- Segunda pestaña **«Publicaciones por autor»**: cuántas notas publicó
  cada autor en un rango de fechas, con acceso directo al listado
  filtrado de sus notas.

Medir con precisión cuánto tiempo pasa cada visitante en la web queda
fuera de este alcance — requeriría un mecanismo de «heartbeat» aparte;
esta bitácora registra hechos puntuales (cuándo entra y sale cada
quien), no la duración de la sesión.

## [0.1.41] — 2026-09-16

### Corregido
- **La casilla «Exclusiva para suscriptores» no se guardaba.** El campo
  oculto del nonce y la casilla usaban el mismo `name` en el formulario;
  el navegador mandaba los dos bajo esa única clave y el valor de la
  casilla pisaba el del nonce, así que la verificación fallaba siempre y
  la entrada nunca llegaba a guardarse. De paso se encontró y corrigió el
  mismo problema en «Créditos de la foto» (bajo la imagen principal),
  que llevaba el mismo defecto desde su creación — ahí pasaba
  inadvertido porque un crédito que no se guarda simplemente se ve en
  blanco, sin ningún aviso.

## [0.1.40] — 2026-09-15

### Seguridad
- **Radio, atada al rol Administrador.** El módulo de Radio (activar el
  reproductor, emisoras, resumen de escuchas) deja de depender de
  `manage_options` y pasa a exigir una capacidad propia
  (`ddn_manage_radio`) que solo tiene el rol Administrador — el mismo
  tratamiento que ya tenían Publicidad y, desde su creación, el panel de
  Suscriptores.

## [0.1.39] — 2026-09-15

### Añadido
- **Notas exclusivas para suscriptores.** Casilla «Exclusiva para
  suscriptores» en el editor de la entrada (cuadro «Acceso»). Marcada,
  el visitante sin sesión ve la bajada y una invitación a iniciar sesión
  o crear cuenta gratis; con sesión iniciada (suscriptor o redacción) ve
  la nota completa, igual que siempre. Un distintivo «Suscriptores» avisa
  en la propia nota y en las tarjetas de portada, sección y listados.

### Seguridad
- **Publicidad, atada al rol Administrador.** Los módulos de Publicidad
  (campañas, informes, evidencia) dejan de depender de `manage_options`
  y pasan a exigir una capacidad propia (`ddn_manage_ads`) que solo tiene
  el rol Administrador — así ninguna capacidad que otro plugin llegue a
  conceder a otro rol abre por accidente el acceso a Publicidad.

## [0.1.38] — 2026-09-14

### Corregido
- **`/wp-admin/` mandaba a cualquiera (administradores incluidos) al login
  de suscriptores** en vez del login nativo de WordPress. WordPress arma
  internamente su propia URL de login apuntando de vuelta a wp-admin
  cuando alguien sin sesión entra ahí; el módulo de suscriptores la
  reescribía igual que cualquier otra. Ahora se deja intacta cuando el
  destino es wp-admin — el acceso de redacción vuelve a ser el de
  siempre, sin tocar el login de lectores.

## [0.1.37] — 2026-09-14

### Cambiado
- **Mi cuenta**: reorganizada en tres pestañas — Mi cuenta, Noticias
  guardadas, Noticias leídas — sin recargar la página al cambiar entre
  ellas. El servidor abre la pestaña que corresponde al aviso mostrado
  (p. ej. «Borrar historial» abre «Noticias leídas»).

## [0.1.36] — 2026-09-14

### Añadido
- **Suscriptores: solo para la web, nunca wp-admin.** Cualquier intento de
  entrar a una pantalla de wp-admin (fuera de admin-post.php/admin-ajax.php,
  que usa el propio módulo) rebota a «Mi cuenta»; sin barra de
  administración en la web. Al personal de redacción no le afecta.
- **Guardar artículos**: botón «Guardar»/«Guardado» en cada nota, sin
  recargar la página; listado en Mi cuenta.
- **Historial de lectura**: cada nota que lee un suscriptor con sesión
  iniciada queda registrada (excluye a redacción); en Mi cuenta puede
  quitar una nota suelta o borrar todo el historial, por privacidad.

## [0.1.35] — 2026-09-14

### Añadido
- **Cabecera — menú de «Mi cuenta»**: con sesión iniciada, el enlace se
  convierte en un menú desplegable con «Ver perfil» y «Cerrar sesión»
  (mismo mecanismo accesible que la lupa de búsqueda).

## [0.1.34] — 2026-09-13

### Cambiado
- **Registro e Ingresar (suscriptores)**: formulario rediseñado como
  tarjeta centrada (título, bajada, botones sociales con ícono real de
  Google/Facebook, divisor, campos en pareja) y nuevo campo «Confirmar
  contraseña» en el registro directo (validado). El enlace «Mi cuenta»
  de la cabecera lleva a Registro si no hay sesión iniciada.

## [0.1.33] — 2026-09-13

### Añadido
- **Cabecera — «Mi cuenta»**: enlace junto al botón de buscar hacia la
  cuenta del suscriptor (contrato `ddn/account_url` con el módulo de
  suscriptores de DDN Suite; sin el plugin activo, no se imprime nada).

## [0.1.32] — 2026-09-13

### Añadido
- **Google Analytics**: etiqueta `gtag.js` encolada en el `<head>` (async).
  ID de medición editable en Personalizar → Analítica (Google); en blanco
  no se imprime nada.

## [0.1.31] — 2026-09-13

### Añadido
- **Cuentas de suscriptor (nuevo módulo, DDN Suite)**: registro directo
  (correo + contraseña, con departamento/ciudad/dirección/identificación
  opcionales), inicio de sesión propio del sitio (ya no wp-login.php),
  inicio de sesión con Google y Facebook, «Mi cuenta» (editar datos,
  cambiar contraseña, eliminar la cuenta) y un panel «Suscriptores» en
  wp-admin con búsqueda, filtros y exportación a CSV/Excel de todo lo que
  cumpla el filtro activo. Las páginas (Registro, Ingresar, Mi cuenta) se
  crean solas, igual que las secciones.
  - Límite de 5 intentos fallidos de inicio de sesión en 15 minutos por
    IP real de la conexión (nunca por usuario ni por cabeceras como
    X-Forwarded-For), aplicado a cualquier forma de intentar entrar al
    sitio, no solo al formulario propio.
  - El registro social exige aceptar Términos y Política antes de crear
    la cuenta, igual que el registro directo.
  - El número de identificación se guarda cifrado.
  - Requiere configurar las credenciales de Google/Facebook en
    **DDN Suite → Suscriptores → Inicio social** para que aparezcan esos
    botones.

## [0.1.30] — 2026-09-13

### Añadido
- **Nota individual — «Lo último»**: bajo las etiquetas de la nota, las 3
  noticias más recientes del sitio (foto y titular; nunca repite la que se
  está leyendo). Las etiquetas ahora llevan la leyenda «Temas
  relacionados».
- **Página de Contacto**: se crea sola (como las secciones) con formulario
  —nombre, correo, asunto y mensaje, sin plugin ni servicio externo— y los
  teléfonos de Gerencia General, Comercial Riohacha y Comercial
  Barranquilla, editables en Personalizar → Contacto. El botón
  «Contáctenos» del pie enlaza aquí automáticamente si no tiene una URL
  propia configurada.

## [0.1.29] — 2026-09-13

### Cambiado
- **Portada de sección — nota destacada**: el titular de la nota principal
  ya no compite en tamaño con el título de la sección; ahora es
  proporcional a que comparte espacio con la lista lateral de 6 notas.
- **Portada de sección — «Más leídas»**: número de orden y titular más
  grandes, con más presencia visual.
- **Portada de sección — nota grande + dos tarjetas**: las dos fotos de la
  derecha ahora se estiran para ocupar, entre las dos, el mismo alto que
  la foto principal (antes la columna quedaba más alta y dejaba un hueco
  en blanco bajo la nota grande).
- **Portada de sección — «Más noticias»**: en vez de la paginación
  numerada, un botón «Cargar más noticias» trae el siguiente lote sin
  recargar la página (REST propio del tema); el lector puede seguir
  cargando hasta agotar las noticias de la sección.

## [0.1.28] — 2026-09-09

### Corregido
- **La foto del autor se quedaba en la genérica** aunque se subiera la real.
  Si la foto venía de otra fuente (un plugin de autores, un avatar local), el
  tema la pisaba con la silueta por defecto. Ahora el orden es: foto del campo
  del tema → cualquier foto real que ya haya puesto otro plugin → silueta por
  defecto. El filtro corre con prioridad alta para no dejar volver a Gravatar.

## [0.1.27] — 2026-09-09

### Quitado
- **La caja de perfil del autor** (foto + biografía) al final de la nota.

## [0.1.26] — 2026-09-09

### Corregido
- **Autores sin foto: salía el icono de imagen rota** (firma de la nota y
  tarjetas de Opinión). El avatar de iniciales usaba un `data:` URI que
  WordPress descarta al escapar la URL. Ahora todo autor sin foto propia usa
  una **imagen genérica**: la que se configure en **Personalizar → Autores**,
  o una silueta neutra incluida en el tema si no se configura ninguna. Aplica
  automáticamente a los autores nuevos.

## [0.1.25] — 2026-09-08

### Corregido
- **Pie de página: en «Secciones» salían los enlaces legales.** Ocurría cuando
  el menú legal estaba asignado a la ubicación «Menú de secciones del pie» en
  Apariencia → Menús. Ahora «Secciones» muestra **siempre las mismas categorías
  que la barra principal** (lista propia del tema); ya no depende de un menú
  guardado. Se retira la ubicación de menú «footer» (era la fuente del enredo).

## [0.1.24] — 2026-09-07

### Corregido
- **La foto de perfil del autor se deformaba** si no era cuadrada (firma de la
  nota, caja del autor, archivo de autor). Ahora se recorta al círculo con
  `object-fit: cover`, sea cual sea su proporción, y se sirve un tamaño
  intermedio (`medium`/`thumbnail`) en vez del archivo original.

## [0.1.23] — 2026-09-07

### Corregido
- **Las campañas publicitarias se borraban al actualizar.** `uninstall.php`
  eliminaba las tablas de campañas y estadísticas, y algunos gestores de
  plugins «actualizan» borrando y reinstalando (lo que ejecuta ese archivo).
  Ahora, al desinstalar, **los datos se conservan por defecto**; para vaciarlos
  a propósito hay que poner la opción `ddn_suite_purge` en `1` antes. Las
  campañas ya perdidas hay que volver a crearlas una última vez.

## [0.1.22] — 2026-09-07

### Añadido
- **Previsualización al compartir (Open Graph / Twitter Card).** Al pegar el
  enlace de una nota en WhatsApp o redes ahora se ve la **imagen destacada**,
  el titular y el resumen (antes salía el logotipo del sitio). Nuevo tamaño de
  imagen `ddn-og` (1200 × 630) y opción en el Personalizador → «Compartir en
  redes» para la imagen por defecto de la portada y las secciones. Si hay un
  plugin de SEO activo (Yoast, Rank Math, Jetpack…) se cede el control.

## [0.1.21] — 2026-09-07

### Corregido
- **Opinión: el título de la columna ya no se recorta.** Se quita el límite de
  una línea; ahora se lee entero. Las tarjetas del carrusel mantienen la misma
  altura y en escritorio **todos los botones «Leer columna» quedan alineados**
  (el botón se ancla abajo).

## [0.1.20] — 2026-09-07

### Corregido
- **Edición impresa: el visor ya no alarga la página.** «Leer en línea» abría
  todas las páginas apiladas y la entrada se volvía interminable. Ahora el
  visor es una **ventana de alto fijo** (≈80 % de la pantalla) con su propio
  desplazamiento: se lee la edición completa dentro de esa caja y el contenido
  que va debajo queda donde debe. Además solo mantiene en memoria las páginas
  cercanas a la que se está viendo (da igual cuántas tenga el PDF).

## [0.1.19] — 2026-09-07

### Cambiado
- **Edición impresa: visor propio de PDF.** «Leer en línea» ya no usa el visor
  del navegador (que no ajustaba el ancho en el móvil): el PDF se pinta con
  **pdf.js** dentro de la propia entrada, **ajustado al ancho de la pantalla**,
  página por página, con botones de zoom (−/+). El lector sigue pudiendo
  descargarlo. pdf.js (Apache-2.0) va empaquetado en `assets/vendor/pdfjs/` y
  solo se descarga al pulsar «Leer en línea».

## [0.1.18] — 2026-09-07

### Corregido
- **Edición impresa en el móvil: el PDF no se ajustaba a la pantalla.** El
  visor embebido (`<iframe>`) no encaja el ancho en el móvil (iOS ignora
  `#view=FitH`). Ahora, en pantallas pequeñas, «Leer» abre el PDF **a pantalla
  completa en el visor del propio navegador**, que sí lo ajusta y permite
  ampliar. En escritorio sigue igual (visor embebido).

## [0.1.17] — 2026-09-07

### Corregido
- **Pie de página: «Secciones» mostraba los enlaces legales.** Si la ubicación
  de menú «footer»/«primary» quedaba apuntando a un menú borrado, WordPress
  volcaba ahí la lista de páginas (términos, cookies…). Ahora, si no hay un
  menú real asignado, el pie usa su propia lista de secciones (Inicio + las 19
  categorías + Contacto).

## [0.1.16] — 2026-09-07

### Cambiado
- **Radio: nombre en la cabecera.** Ya no dice «Radio en vivo» fijo: al
  reproducir una emisora muestra su **marca** (Riohacha → «Cardenal Stereo»,
  Valledupar → «Sistema Cardenal»); en pausa muestra «Sistema Cardenal». La
  marca de cada emisora y el texto en pausa se editan en DDN Suite → Radio.
- Logos por defecto de Cardenal Stereo (Riohacha) y Sistema Cardenal
  (Valledupar). Para verlos nítidos, súbelos en alta resolución con «Elegir».

## [0.1.15] — 2026-09-07

### Añadido
- **Reproductor de radio, mejoras:**
  - **Estados de conexión.** «Conectando…» / «Cargando…» mientras arranca; si
    el stream se cae, «Sin señal, reintentando…» con reintento automático
    (espera creciente).
  - **Volumen y silencio**, recordados en el navegador.
  - **«Sonando ahora».** Muestra la canción en curso (y el nº de oyentes) si el
    panel de la emisora lo publica. Lo consulta WordPress y lo cachea, porque
    esos paneles no permiten pedirlo desde el navegador.
  - **Controles del sistema** (Media Session): pantalla de bloqueo del móvil y
    botones de auriculares/volante.
  - **Escuchas.** DDN Suite → Radio muestra, por emisora y sin datos
    personales, cuántas veces se pulsó play y las horas escuchadas (30 días).
  - **Ajustes nuevos:** empezar minimizado, emisora por defecto; y por emisora,
    botón «Probar», aviso si la URL es `http://` en una web `https://`, y una
    URL de metadatos opcional.
  - Fundido al cambiar de emisora; burbuja minimizada manejable con el teclado
    y con animación de ecualizador cuando hay emisión; `preconnect` a los
    servidores de streaming para que arranque antes.

## [0.1.14] — 2026-09-07

### Añadido
- **Reproductor de radio en vivo** (`ddn-suite`). Cinta flotante abajo a la
  derecha en toda la web con las emisoras que se configuren. El visitante
  elige emisora, puede minimizarla (queda como una burbuja) o cerrarla; el
  estado (emisora, pausa, minimizado) se recuerda al cambiar de página.
  Nueva página **DDN Suite → Radio**: activar/desactivar y gestionar las
  emisoras (nombre, URL del stream, logo desde la biblioteca de medios).
  Viene con Cardenal Stereo Riohacha y Valledupar precargadas (desactivado
  hasta que se marque «Mostrarlo en la web»).

## [0.1.13] — 2026-09-07

### Cambiado
- **Cabezote.** La fila superior (redes + buscador) lleva ahora un filete fino
  arriba, igual al que ya tenía debajo.

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
