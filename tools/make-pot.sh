#!/usr/bin/env bash
#
# Regenera los archivos .pot del tema y del plugin.
# Requiere wp-cli en el PATH (o wp-cli.phar; ajusta WP_CLI abajo).

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_CLI="${WP_CLI:-wp}"
BUGS="https://github.com/desarrolloDDN/DiarioDelNorte/issues"

cd "$ROOT_DIR"

"$WP_CLI" i18n make-pot theme theme/languages/diario-del-norte.pot \
	--domain=diario-del-norte \
	--exclude=assets/dist,assets/src,node_modules \
	--package-name="Diario del Norte" \
	--headers="{\"Report-Msgid-Bugs-To\":\"${BUGS}\"}" \
	--skip-audit

"$WP_CLI" i18n make-pot plugin/ddn-suite plugin/ddn-suite/languages/ddn-suite.pot \
	--domain=ddn-suite \
	--exclude=node_modules \
	--package-name="DDN Suite" \
	--headers="{\"Report-Msgid-Bugs-To\":\"${BUGS}\"}" \
	--skip-audit

echo "OK: .pot regenerados."
