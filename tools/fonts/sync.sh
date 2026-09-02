#!/usr/bin/env bash
#
# Copia los .woff2 (subconjunto latino) de los paquetes @fontsource/*
# a theme/assets/fonts/. Ejecutar tras `npm --prefix theme install`
# cuando cambie alguna versión de fuente.
#
#   Titulares .... Majerit Headline Medium (archivo propio; NO se
#                  sincroniza aquí, se copia a mano a assets/fonts/).
#                  Newsreader queda de reserva (sí se sincroniza).
#   Cuerpo ....... Libre Franklin  (variable, redonda + itálica)

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SRC="$ROOT_DIR/theme/node_modules/@fontsource-variable"
DST="$ROOT_DIR/theme/assets/fonts"

test -d "$SRC" || { echo "Falta node_modules: corre 'npm --prefix theme install'." >&2; exit 1; }

cp "$SRC/newsreader/files/newsreader-latin-wght-normal.woff2"          "$DST/newsreader.woff2"
cp "$SRC/newsreader/files/newsreader-latin-wght-italic.woff2"          "$DST/newsreader-italic.woff2"
cp "$SRC/libre-franklin/files/libre-franklin-latin-wght-normal.woff2"  "$DST/libre-franklin.woff2"
cp "$SRC/libre-franklin/files/libre-franklin-latin-wght-italic.woff2"  "$DST/libre-franklin-italic.woff2"

echo "OK: 4 fuentes copiadas a theme/assets/fonts/"
