#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ ! -f public/build/manifest.json ]]; then
  printf '%s\n' 'Build frontend assets before packaging.' >&2
  exit 1
fi
if [[ -n "$(git status --porcelain)" ]]; then
  printf '%s\n' 'Commit project changes before packaging a release.' >&2
  exit 1
fi
release_commit="$(git rev-parse HEAD)"
php -r '
$revision = json_decode(@file_get_contents("public/build/revision.json"), true);
if (!is_array($revision) || ($revision["commit"] ?? null) !== $argv[1] || ($revision["dirty"] ?? true)) {
    fwrite(STDERR, "Frontend assets must be rebuilt from this clean commit before packaging.\n");
    exit(1);
}
' "$release_commit"
stage="$(mktemp -d)"
trap 'rm -rf -- "$stage"' EXIT
git archive HEAD | tar -x -C "$stage"
mkdir -p "$stage/public/build"
cp -R public/build/. "$stage/public/build/"
(
  cd "$stage"
  composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader
  php artisan package:discover --no-ansi
)
# Only tracked source, production dependencies, and public build assets are included.
# Never package the local .env, customer database, uploads, or application logs.
mkdir -p dist
tar -czf dist/shoe-commerce.tar.gz -C "$stage" .
printf '%s\n' 'Release written to dist/shoe-commerce.tar.gz'
