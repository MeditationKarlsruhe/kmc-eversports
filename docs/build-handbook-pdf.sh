#!/usr/bin/env bash
# Builds docs/recovery-handbook.pdf from docs/recovery-handbook.md.
#
# The PDF states the plugin version from the header of kmc-eversports.php.
#
# Requires pandoc, WeasyPrint and the fonts named in handbook.css. The GitHub Action
# .github/actions/build-handbook installs them and then calls this script.
set -euo pipefail

cd "$(dirname "$0")"

plugin_version="$(grep -oP '(?<=Version:\s)[0-9][0-9A-Za-z.\-]*' ../kmc-eversports.php)"

pandoc recovery-handbook.md \
  --from gfm \
  --to html5 \
  --standalone \
  --metadata lang=de \
  --metadata pagetitle="KMC Eversports – Betriebs- und Recovery-Handbuch" \
  --metadata plugin-version="$plugin_version" \
  --lua-filter handbook.lua \
  --css "$PWD/handbook.css" \
  --pdf-engine weasyprint \
  --output recovery-handbook.pdf

echo "Created $PWD/recovery-handbook.pdf"
