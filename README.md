# Diario del Norte

Tema editorial propio y suite de utilidades para el sitio de **Diario del Norte**
(*Periódico de la Región Caribe*), Riohacha, La Guajira, Colombia.
Una publicación de Sistema Cardenal S.A.S.

WordPress estándar, sin dependencias de plataformas externas.

## Contenido del repositorio

| Carpeta | Qué es |
|---|---|
| `theme/` | El tema de WordPress **Diario del Norte** (clásico, plantillas PHP + Vite). |
| `plugin/ddn-suite/` | Plugin **DDN Suite**: gestor de publicidad y calendario de noticias publicadas. |
| `tools/build/` | Script de empaquetado (`package.sh`) que genera los `.zip` instalables. |
| `docs/` | Documentación de arquitectura e identidad visual. |

El tema es de presentación; la lógica de negocio (campañas de anuncios con
tablas propias, calendario editorial) vive en el plugin `ddn-suite`, que se
activa por separado.

## Identidad visual

- **Color primario:** `#BF0202` (Rojo Norte, del logotipo). Sin modo oscuro.
- **Titulares:** «Sunlight Dreams» (fuente propia, autoalojada en el tema).
- **Resto de textos:** Libre Franklin.
- Propuesta navegable: `docs/identidad-visual.html`.

## Requisitos

- WordPress 6.5 o superior
- PHP 8.1 o superior
- Node 20+ y Composer (solo para desarrollo / empaquetado)

## Desarrollo

```bash
composer install
npm run build          # compila theme/assets/dist/
composer run check     # phpcs + phpstan
```

Modo watch de assets:

```bash
npm run dev
```

## Empaquetado

```bash
tools/build/package.sh
```

Genera en `tools/build/dist/`:

- `diario-del-norte-<versión>.zip` — subir en Apariencia → Temas → Añadir nuevo → Subir tema.
- `ddn-suite-<versión>.zip` — subir en Plugins → Añadir nuevo → Subir plugin.

## Licencia

GPL-2.0-or-later. Ver [LICENSE](LICENSE).
