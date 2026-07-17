#!/usr/bin/env bash
# Interactive release flow: promotes CHANGELOG.md's [Unreleased] section into a
# versioned one, bumps the Version header, commits, tags, and pushes.
# Run from inside the Dev Container. Requires GNU sed (available there).
set -euo pipefail

cd "$(dirname "$0")/.."

plugin_file="kmc-eversports.php"
changelog_file="CHANGELOG.md"

echo "== Vorbedingungen =="

branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "$branch" != "main" ]; then
    echo "Fehler: bitte von main releasen (aktuell: $branch)." >&2
    exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
    echo "Fehler: Arbeitsbaum ist nicht sauber. Bitte erst committen oder stashen." >&2
    exit 1
fi

git fetch origin main --quiet
local_head="$(git rev-parse HEAD)"
remote_head="$(git rev-parse origin/main)"
if [ "$local_head" != "$remote_head" ]; then
    ahead_behind="$(git rev-list --left-right --count origin/main...HEAD)"
    behind="$(echo "$ahead_behind" | cut -f1)"
    ahead="$(echo "$ahead_behind" | cut -f2)"
    echo "Fehler: main ist nicht synchron mit origin/main ($ahead Commit(s) voraus, $behind hinterher)." >&2
    echo "Bitte erst push/pull." >&2
    exit 1
fi

echo "OK: main, sauber, synchron mit origin/main."
echo

echo "== Aktuellen Stand lesen =="

current_version="$(grep -oP '(?<=Version:\s)[0-9][0-9A-Za-z.\-]*' "$plugin_file")"
unreleased="$(bin/changelog-section.sh Unreleased)"

if [ -z "$(echo "$unreleased" | tr -d '[:space:]')" ]; then
    echo "Fehler: Nichts zu releasen — [Unreleased] in $changelog_file ist leer." >&2
    exit 1
fi

echo "Aktuelle Version: $current_version"
echo
echo "-- Unreleased --"
echo "$unreleased"
echo "----------------"
echo

echo "== Versionsvorschlag =="

if echo "$unreleased" | grep -q '^### Added'; then
    bump="minor"
else
    bump="patch"
fi

IFS='.' read -r major minor patch <<<"$current_version"
case "$bump" in
minor) suggested="$major.$((minor + 1)).0" ;;
patch) suggested="$major.$minor.$((patch + 1))" ;;
esac

read -rp "Vorschlag: $suggested ($bump-Bump). Enter zum Übernehmen, oder eigene Version eingeben: " input
new_version="${input:-$suggested}"

if ! [[ "$new_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Fehler: '$new_version' ist keine gültige Version (erwartet: X.Y.Z)." >&2
    exit 1
fi

highest="$(printf '%s\n%s\n' "$current_version" "$new_version" | sort -V | tail -n1)"
if [ "$highest" != "$new_version" ] || [ "$new_version" = "$current_version" ]; then
    echo "Fehler: '$new_version' muss größer als die aktuelle Version ($current_version) sein." >&2
    exit 1
fi

release_date="$(date +%F)"

echo
echo "== Vorschau =="
echo "Neue Version: $new_version"
echo "Release-Datum: $release_date"
echo
echo "-- wird zu CHANGELOG.md-Abschnitt [$new_version] - $release_date --"
echo "$unreleased"
echo "-------------------------------------------------------------------"
echo

read -rp "CHANGELOG.md + $plugin_file schreiben, committen und v$new_version lokal taggen? [y/N] " confirm_local
if [ "${confirm_local,,}" != "y" ]; then
    echo "Abgebrochen, nichts verändert."
    exit 0
fi

# Renames the [Unreleased] heading to the new version and inserts a fresh
# empty [Unreleased] above it — the body underneath stays untouched and now
# belongs to the new heading.
sed -i "s/^## \[Unreleased\]\$/## [Unreleased]\n\n## [$new_version] - $release_date/" "$changelog_file"
sed -i "s/^ \* Version:.*/ * Version: $new_version/" "$plugin_file"

git add "$changelog_file" "$plugin_file"
git commit -m "chore: release v$new_version" --quiet
git tag -a "v$new_version" --cleanup=verbatim -m "$(bin/changelog-section.sh "$new_version")"

recovery_hint() {
    echo
    echo "Lokal committet und getaggt, noch nichts gepusht."
    echo "Manuell nachholen:  git push origin main && git push origin v$new_version"
    echo "Tag verwerfen:      git tag -d v$new_version"
}
recovery_hint

echo
read -rp "Commit + Tag v$new_version jetzt nach origin/main pushen? Das löst den Release-Workflow aus. [y/N] " confirm_push
if [ "${confirm_push,,}" != "y" ]; then
    echo "Nicht gepusht."
    recovery_hint
    exit 0
fi

if ! git push origin main; then
    echo "Fehler: Push von main fehlgeschlagen, nichts wurde gepusht." >&2
    recovery_hint
    exit 1
fi

if ! git push origin "v$new_version"; then
    echo "Fehler: main wurde bereits gepusht, aber der Tag-Push ist fehlgeschlagen." >&2
    echo "Der Release-Workflow läuft erst beim Tag-Push, also bisher nichts ausgelöst." >&2
    echo "Erneut versuchen mit: git push origin v$new_version" >&2
    exit 1
fi

repo_url="$(git remote get-url origin | sed -e 's/\.git$//' -e 's#^git@github.com:#https://github.com/#')"
echo
echo "Fertig. Workflow verfolgen: $repo_url/actions"
