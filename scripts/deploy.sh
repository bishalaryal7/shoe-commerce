#!/usr/bin/env bash
set -euo pipefail
artifact="${1:?Pass a verified release archive}"
: "${DEPLOY_HOST:?Configure DEPLOY_HOST}"
: "${DEPLOY_USER:?Configure DEPLOY_USER}"
: "${DEPLOY_PATH:?Configure DEPLOY_PATH}"
: "${DEPLOY_URL:?Configure DEPLOY_URL}"
: "${DEPLOY_SSH_KEY:?Configure DEPLOY_SSH_KEY}"
: "${DEPLOY_KNOWN_HOSTS:?Configure pinned SSH known-hosts entries}"
[[ -f "$artifact" ]] || exit 1
[[ "$DEPLOY_HOST" =~ ^[a-zA-Z0-9._-]+$ ]] || exit 1
[[ "$DEPLOY_USER" =~ ^[a-zA-Z0-9_-]+$ ]] || exit 1
[[ "$DEPLOY_PATH" =~ ^/[a-zA-Z0-9_./-]+$ && "$DEPLOY_PATH" != / && "$DEPLOY_PATH" != *'/../'* ]] || exit 1
[[ "$DEPLOY_URL" == https://* ]] || exit 1
umask 077
task_ssh_directory="$(mktemp -d)"
trap 'rm -rf -- "$task_ssh_directory"' EXIT
printf '%s\n' "$DEPLOY_SSH_KEY" > "$task_ssh_directory/key"
printf '%s\n' "$DEPLOY_KNOWN_HOSTS" > "$task_ssh_directory/known_hosts"
ssh_options=(-i "$task_ssh_directory/key" -o BatchMode=yes -o StrictHostKeyChecking=yes -o "UserKnownHostsFile=$task_ssh_directory/known_hosts")
release_id="$(date -u +%Y%m%d%H%M%S)-${GITHUB_SHA:-local}"
release_id="${release_id:0:24}"
remote="${DEPLOY_USER}@${DEPLOY_HOST}"
printf -v prepare_command 'bash -s -- %q %q' "$DEPLOY_PATH" "$release_id"
ssh "${ssh_options[@]}" "$remote" "$prepare_command" <<'REMOTE'
set -euo pipefail
base="$1"
id="$2"
test -f "$base/shared/.env" || { echo 'Prepare shared/.env on the server first.' >&2; exit 1; }
mkdir -p "$base/releases"
test ! -e "$base/releases/$id" || exit 1
mkdir "$base/releases/$id"
REMOTE
scp "${ssh_options[@]}" "$artifact" "$remote:$DEPLOY_PATH/releases/$release_id/release.tar.gz"
printf -v deploy_command 'bash -s -- %q %q %q' "$DEPLOY_PATH" "$release_id" "$DEPLOY_URL"
ssh "${ssh_options[@]}" "$remote" "$deploy_command" <<'REMOTE'
set -euo pipefail
base="$1"
id="$2"
url="$3"
release="$base/releases/$id"
old="$(readlink -f "$base/current" 2>/dev/null || true)"
switched=0
recover() {
  if [[ "$switched" == 1 && -n "$old" ]]; then
    ln -s "$old" "$base/.rollback-$id"
    mv -Tf "$base/.rollback-$id" "$base/current"
    php "$base/current/artisan" up || true
  elif [[ "$switched" == 1 ]]; then
    rm -- "$base/current"
  fi
}
trap recover ERR
tar -xzf "$release/release.tar.gz" -C "$release"
rm "$release/release.tar.gz"
mkdir -p "$base/shared/storage/app/public" "$base/shared/storage/framework/cache/data" "$base/shared/storage/framework/sessions" "$base/shared/storage/framework/views" "$base/shared/storage/logs"
rm -rf -- "$release/storage"
ln -s "$base/shared/storage" "$release/storage"
ln -s "$base/shared/.env" "$release/.env"
cd "$release"
php artisan storage:link
php artisan migrate --force
php artisan optimize
ln -s "$release" "$base/.current-$id"
mv -Tf "$base/.current-$id" "$base/current"
switched=1
php artisan queue:restart
php artisan up
curl --fail --silent --show-error --retry 3 --retry-delay 3 "${url%/}/ready" >/dev/null
printf '%s\n' 'Deployment passed application and database readiness checks.'
REMOTE
