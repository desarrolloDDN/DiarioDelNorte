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
| `Install/Installer.php` | Crea `wp_ddn_ad_campaigns` y `wp_ddn_ad_events` (dbDelta), versión en `ddn_suite_db_version`. |
| `Ads/AdZone`, `Ads/CampaignType` | Enums; `AdZone` debe coincidir con `DiarioDelNorte\Support\Ads::ZONES` del tema. |
| `Ads/CampaignRepository`, `Ads/StatsRepository` | Acceso a datos (consultas preparadas con `%i`). |
| `Ads/CampaignSelector`, `Ads/AdRenderer` | Selección (prioridad → sorteo ponderado por peso, filtrado por categoría) y render de la creatividad. Puros. |
| `Ads/ZoneController` | Puente `ddn/ad_zone` + inserción tras el 3.er párrafo vía `the_content`; registra impresión. |
| `Ads/ClickController` | `/ddn-anuncio/clic/{id}` → redirección resuelta en el servidor por ID (sin open-redirect). |
| `Ads/Admin/CampaignsPage` | Alta/edición/borrado de campañas + impresiones/clics/CTR. Slug sin las palabras «ad»/«campaign» (bloqueadores). |
| `Calendar/CalendarRepository` | Noticias por día de un mes (publicadas, programadas, borradores). |
| `Calendar/Admin/CalendarPage` | Retícula mensual en wp-admin con enlace a editar cada nota. |
| `Analytics/PageviewRecorder` | Cuenta páginas vistas de noticias en cubos por hora (`ddn_pageviews`); sin PII, excluye editores y bots; poda diaria (WP-Cron). |
| `Analytics/PageviewRepository` | Filtro `ddn/most_read` → IDs de las noticias más vistas en 24 h. |
| `PrintEdition/EditionPostType` | Tipo de contenido público `ddn_edition` (slug `/edicion-impresa/`, con archivo): portada (imagen destacada) + PDF + nota, por fecha. Plantillas en el tema: `single-ddn_edition.php` y `archive-ddn_edition.php`. |
| `PrintEdition/EditionRepository` | Filtros `ddn/print_edition` (edición vigente) y `ddn/edition_pdf_url` (URL del PDF de una edición dada). |
| `Admin/Menu` | Menú «DDN Suite» (Calendario + Publicidad + Edición impresa). |

### Contratos tema ↔ plugin (filtros)

| Filtro | Lo emite | Lo consume | Devuelve |
|---|---|---|---|
| `ddn/ad_zone` (acción) | tema (plantillas) | `Ads/ZoneController` | — (imprime el anuncio) |
| `ddn/most_read` | `front-page.php` | `Analytics/PageviewRepository` | `int[]` IDs |
| `ddn/print_edition` | `front-page.php` | `PrintEdition/EditionRepository` | `array{date,title,permalink,cover_id,pdf_url,edit_link}` o `null` |
| `ddn/edition_pdf_url` | `single-ddn_edition.php` | `PrintEdition/EditionRepository` | `string` URL del PDF (`$url, $post_id`) |
| `ddn/home_sections` | `front-page.php` | — (personalizable) | `string[]` slugs |
| `ddn/newsletter_action` | `front-page.php` | — (integración externa) | `string` URL |

## Versionado

El tema (`style.css`), el plugin (`ddn-suite.php`) y `package.json`
comparten número. El job `version-consistency` de CI lo verifica.

## Pendiente para v0.1

- Verificación end-to-end contra un WordPress real.
- Reporte de campañas en PDF y panel de estadísticas por rango de fechas.
- Traducción `es_CO` (`.po`/`.mo`) a partir del `.pot`.
- Patrones de bloque para páginas.
