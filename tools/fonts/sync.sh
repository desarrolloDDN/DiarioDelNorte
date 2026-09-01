#!/usr/bin/env bash
#
# Copia los .woff2 (subconjunto latino) de los paquetes @fontsource/*
# a theme/assets/fonts/. Ejecutar tras `npm --prefix theme install`
# cuando cambie alguna versión de fuente.
#
#   Titulares ...... DM Serif Display  (400 + itálica)
#   Subtítulos ..... Libre Baskerville (400 / 400i / 700)
#   Cuerpo ......... Source Sans 3     (variable, redonda + itálica)

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SRC="$ROOT_DIR/theme/node_modules"
DST="$ROOT_DIR/theme/assets/fonts"

test -d "$SRC/@fontsource" || { echo "Falta node_modules: corre 'npm --prefix theme install'." >&2; exit 1; }

cp "$SRC/@fontsource/dm-serif-display/files/dm-serif-display-latin-400-normal.woff2"   "$DST/dm-serif-display.woff2"
cp "$SRC/@fontsource/dm-serif-display/files/dm-serif-display-latin-400-italic.woff2"   "$DST/dm-serif-display-italic.woff2"
cp "$SRC/@fontsource/libre-baskerville/files/libre-baskerville-latin-400-normal.woff2" "$DST/libre-baskerville.woff2"
cp "$SRC/@fontsource/libre-baskerville/files/libre-baskerville-latin-400-italic.woff2" "$DST/libre-baskerville-italic.woff2"
cp "$SRC/@fontsource/libre-baskerville/files/libre-baskerville-latin-700-normal.woff2" "$DST/libre-baskerville-700.woff2"
cp "$SRC/@fontsource-variable/source-sans-3/files/source-sans-3-latin-wght-normal.woff2" "$DST/source-sans-3.woff2"
cp "$SRC/@fontsource-variable/source-sans-3/files/source-sans-3-latin-wght-italic.woff2" "$DST/source-sans-3-italic.woff2"

echo "OK: 7 fuentes copiadas a theme/assets/fonts/"
