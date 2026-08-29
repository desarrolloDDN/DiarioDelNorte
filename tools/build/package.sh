#!/usr/bin/env bash
#
# Genera los .zip instalables del tema y del plugin en tools/build/dist/.
#
# Uso:  tools/build/package.sh
# Requiere: node/npm y zip en el PATH.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
DIST_DIR="$ROOT_DIR/tools/build/dist"
STAGE_DIR="$DIST_DIR/.stage"

VERSION="$(grep -m1 -oE 'Version:[[:space:]]*[0-9.]+' "$ROOT_DIR/theme/style.css" | grep -oE '[0-9.]+')"
echo "==> Empaquetando Diario del Norte v${VERSION}"

# --- 1. Assets del tema -------------------------------------------------
echo "==> npm run build"
( cd "$ROOT_DIR" && npm run build --silent )

test -f "$ROOT_DIR/theme/assets/dist/app.css" || { echo "ERROR: falta app.css" >&2; exit 1; }
test -f "$ROOT_DIR/theme/assets/dist/app.js"  || { echo "ERROR: falta app.js" >&2; exit 1; }

# --- 2. Directorio limpio --------------------------------------------
rm -rf "$DIST_DIR"
mkdir -p "$STAGE_DIR/diario-del-norte" "$STAGE_DIR/ddn-suite"

# --- 3. Tema --------------------------------------------------------
rsync -a \
	--exclude='assets/src/' \
	--exclude='assets/fonts/' \
	--exclude='node_modules/' \
	--exclude='package.json' \
	--exclude='package-lock.json' \
	--exclude='vite.config.js' \
	--exclude='.DS_Store' \
	"$ROOT_DIR/theme/" "$STAGE_DIR/diario-del-norte/"

# --- 4. Plugin ------------------------------------------------------
rsync -a \
	--exclude='node_modules/' \
	--exclude='.DS_Store' \
	"$ROOT_DIR/plugin/ddn-suite/" "$STAGE_DIR/ddn-suite/"

# --- 5. Zips -------------------------------------------------------
echo "==> Generando zips"
( cd "$STAGE_DIR" \
	&& zip -rq "$DIST_DIR/diario-del-norte-${VERSION}.zip" diario-del-norte -x '*.DS_Store' \
	&& zip -rq "$DIST_DIR/ddn-suite-${VERSION}.zip" ddn-suite -x '*.DS_Store' )

rm -rf "$STAGE_DIR"

echo "==> Listo:"
echo "    $DIST_DIR/diario-del-norte-${VERSION}.zip"
echo "    $DIST_DIR/ddn-suite-${VERSION}.zip"
