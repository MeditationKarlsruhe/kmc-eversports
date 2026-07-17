#!/usr/bin/env bash
# Prints the body of one CHANGELOG.md section (Keep a Changelog format).
#
# Usage: changelog-section.sh <heading>
#   <heading> is either "Unreleased" or a bare version number (e.g. "2.1.0"),
#   without the surrounding "## [...]" markdown syntax.
set -euo pipefail

heading="${1:?Usage: changelog-section.sh <heading>}"
changelog_file="$(dirname "$0")/../CHANGELOG.md"

awk -v heading="$heading" '
  /^## \[/ {
    if (found) exit
    if ($0 ~ "^## \\[" heading "\\]") { found=1; next }
    next
  }
  found { print }
' "$changelog_file"
