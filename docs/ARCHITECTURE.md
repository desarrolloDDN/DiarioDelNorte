# Arquitectura

## Principios

1. **El tema es presentación.** `theme/` recibe datos ya resueltos por
   WordPress y los maqueta. No crea tablas ni gestiona lógica de negocio.
2. **La lógica de negocio vive en `plugin/ddn-suite/`**: gestor de
   publicidad (tablas propias, campañas, tracking, redirección de clic) y
   calendario editorial. Se activa por separado del tema.
3. **Acoplamiento por contrato, no por código.** El tema marca las zonas
   de anuncio con `do_action( 'ddn/ad_zone', $zone )`; el plugin se
   engancha ahí. Si el plugin no está activo, no se imprime nada y no hay
   error.
4. **Sin dependencias externas de plataforma.** WordPress estándar. Cada
   paquete trae un autoloader PSR-4 propio de ~30 líneas; el `.zip`
   instalable no lleva `vendor/`.

## Tema (`theme/`)

| Área | Archivo |
|---|---|
| Bootstrap | `functions.php` → `inc/Autoloader.php` → `inc/Theme.php` |
| Assets (Vite) | `inc/Assets.php`; fuentes/estilos en `assets/src/`, compilado en `assets/dist/` |
| Secciones + menú | `inc/Sections/DefaultSectionsInstaller.php` (19 categorías + submenú «Más») |
| Personalizador | `inc/Customizer/SiteOptions.php` (pie, contacto, redes, edición impresa) |
| Perfil de autor | `inc/Users/AuthorProfile.php` (foto + cargo para la firma; filtra `get_avatar_data`) |
| Compartir en redes | `inc/Content/SocialMeta.php` (Open Graph + Twitter Card en `wp_head`: imagen destacada, titular, resumen; tamaño `ddn-og` 1200×630; se inhibe si hay plugin SEO). Filtro `ddn/social_meta`. |
| Helpers de plantilla | `inc/Support/Format.php`, `inc/Support/Ads.php` |
| Fuentes | Autoalojadas en `assets/fonts/`, declaradas en `assets/src/scss/_fonts.scss`. Sin Google Fonts. |
| Plantillas | `front-page.php`, `single.php`, `archive.php`, `search.php`, `page.php`, `404.php`, `index.php` |
| Parciales | `template-parts/entry-*.php` |

### Jerarquía de plantillas

`archive.php` cubre categoría, etiqueta, autor y fecha (WordPress lo usa
como *fallback* de `category.php` / `tag.php` / `author.php`), así que no
se duplican esos archivos.

## Plugin (`plugin/ddn-suite/`)

| Módulo | Qué hace |
|---|---|
| `Install/Installer.php` | Crea las tablas propias (dbDelta), versión en `ddn_suite_db_version` (v6: campaña con zonas múltiples + AdSense + evidencia; v7: `wp_ddn_radio_plays`). |
| `uninstall.php` | **No** borra tablas ni opciones salvo que `get_option('ddn_suite_purge') === '1'` — así una «actualización» que borre+reinstale el plugin no se lleva las campañas. |
| `Ads/AdZone`, `Ads/CampaignType` | Enums; `AdZone` debe coincidir con `DiarioDelNorte\Support\Ads::ZONES` del tema. `CampaignType`: adsense, gam, html, image, video, sponsored. |
| `Ads/CampaignRepository`, `Ads/StatsRepository` | Acceso a datos (consultas preparadas con `%i`). `StatsRepository::daily($id)` = impresiones/clics por día para el informe. |
| `Ads/CampaignSelector`, `Ads/AdRenderer` | Selección (prioridad → sorteo ponderado por peso, filtrado por categoría) y render (`render($campaign, $zone)`; adsense = `<ins class="adsbygoogle">` con client/slot, image/video enlazados, html/gam/sponsored = creativa cruda). Puros. |
| `Ads/ZoneController` | Puente `ddn/ad_zone` + inserción tras el 3.er párrafo vía `the_content`; registra impresión. Campaña vale para una zona si esa zona está en `$campaign->zones`. |
| `Ads/ClickController` | `/ddn-anuncio/clic/{id}` → redirección resuelta en el servidor por ID (sin open-redirect). |
| `Ads/Admin/CampaignsPage` | Gestor: tabla + pestañas Campañas/Historial, ficha con vista previa en vivo, subir evidencia (imágenes del anuncio publicado, media modal), informe imprimible del anunciante (impresiones/clics/CTR + detalle por día + evidencia). Slug sin las palabras «ad»/«campaign» (bloqueadores). |
| `Calendar/CalendarRepository` | Noticias por día de un mes (publicadas, programadas, borradores). |
| `Calendar/Admin/CalendarPage` | Retícula mensual en wp-admin con enlace a editar cada nota. |
| `Analytics/PageviewRecorder` | Cuenta páginas vistas de noticias en cubos por hora (`ddn_pageviews`); sin PII, excluye editores y bots; poda diaria (WP-Cron). |
| `Analytics/PageviewRepository` | Filtro `ddn/most_read` → IDs de las noticias más vistas en 24 h. |
| `PrintEdition/EditionPostType` | Tipo de contenido público `ddn_edition` (slug `/edicion-impresa/`, con archivo): portada (imagen destacada) + PDF + nota, por fecha. Plantillas en el tema: `single-ddn_edition.php` y `archive-ddn_edition.php`. El visor «Leer en línea» del tema renderiza el PDF con **pdf.js** (`theme/assets/vendor/pdfjs/`, Apache-2.0, carga bajo demanda), ajustado al ancho de la pantalla + zoom. |
| `PrintEdition/EditionRepository` | Filtros `ddn/print_edition` (edición vigente) y `ddn/edition_pdf_url` (URL del PDF de una edición dada). |
| `Radio/RadioSettings` | Opción `ddn_suite_radio`: `enabled`, `start_minimized`, `default_station` + emisoras (`name`, `stream`, `logo_id`, `meta_url` opcional). Sanea al leer y al guardar. |
| `Radio/RadioPlayer` | Reproductor flotante en `wp_footer` (solo si está activo): marcado + `assets/radio/radio.{css,js}` + config por `wp_add_inline_script` + `preconnect` a los hosts de streaming. Estado (emisora, pausa, volumen, minimizado) en `localStorage`. JS: estados de conexión/reintento con backoff, volumen/silencio, Media Session, fundido al cambiar de emisora, burbuja accesible por teclado. |
| `Radio/RadioMeta` | «Sonando ahora» de una emisora: consulta el panel Shoutcast/Icecast desde el servidor (esos paneles no permiten CORS), cachea 20 s. Devuelve `{title, listeners}`. |
| `Radio/RadioStats` | Tabla `wp_ddn_radio_plays` (día × emisora: `starts`, `seconds`). Sin PII. Poda >120 días en el cron `ddn_suite_prune_pageviews`. |
| `Radio/RadioController` | REST `ddn-suite/v1`: `GET /radio/nowplaying?station=N` (público, proxy con caché) y `POST /radio/tick` (nonce `wp_rest`; `kind` = `start`\|`beat`). |
| `Radio/Admin/RadioPage` | Página «Radio»: on/off, arranque, emisora por defecto, filas de emisora repetibles (logo por media modal, botón «Probar», aviso de contenido mixto http/https, metadatos opcionales) y resumen de escuchas de 30 días. |
| `Admin/Menu` | Menú «DDN Suite» (Calendario + Publicidad + Radio + Edición impresa). |

### Contratos tema ↔ plugin (filtros)

| Filtro | Lo emite | Lo consume | Devuelve |
|---|---|---|---|
| `ddn/ad_zone` (acción) | tema (plantillas) | `Ads/ZoneController` | — (imprime el anuncio) |
| `ddn/most_read` | `front-page.php` | `Analytics/PageviewRepository` | `int[]` IDs |
| `ddn/print_edition` | `front-page.php` | `PrintEdition/EditionRepository` | `array{date,title,permalink,cover_id,pdf_url,edit_link}` o `null` |
| `ddn/edition_pdf_url` | `single-ddn_edition.php` | `PrintEdition/EditionRepository` | `string` URL del PDF (`$url, $post_id`) |
| `ddn/home_sections` | `front-page.php` | — (personalizable) | `string[]` slugs |

## Versionado y publicación

El tema (`style.css` + `DDN_THEME_VERSION`), el plugin (`ddn-suite.php` +
`DDN_SUITE_VERSION`) y `package.json` comparten número. El job
`version-consistency` de CI verifica que `style.css`, `ddn-suite.php` y
`package.json` coincidan.

**Publicar una versión nueva** (dispara la actualización automática en los
sitios):

1. Subir el número en `theme/style.css`, `theme/functions.php`,
   `plugin/ddn-suite/ddn-suite.php` (cabecera **y** constante) y
   `package.json`. Anotar los cambios en `CHANGELOG.md`.
2. `git commit` + `git push` (CI en verde).
3. `bash tools/build/package.sh` → genera los dos zip en `tools/build/dist/`.
4. `gh release create vX.Y.Z tools/build/dist/diario-del-norte-X.Y.Z.zip tools/build/dist/ddn-suite-X.Y.Z.zip --title "vX.Y.Z" --notes-file <(...)`.

`Updater\GitHubUpdater` (tema e `inc/Updater` del plugin) consulta
`releases/latest` de la API de GitHub (repo público, sin token; cacheado
12 h en el transient compartido `ddn_gh_release`), compara con la versión
instalada y, si hay una mayor, la inyecta en el aviso de actualizaciones
de WordPress apuntando al adjunto `diario-del-norte-*.zip` /
`ddn-suite-*.zip` del *release*.

## Pendiente para v0.1

- Verificación end-to-end contra un WordPress real.
- Reporte de campañas en PDF y panel de estadísticas por rango de fechas.
- Traducción `es_CO` (`.po`/`.mo`) a partir del `.pot`.
- Patrones de bloque para páginas.
