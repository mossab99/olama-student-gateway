#!/usr/bin/env bash

# Repair the olama.online cleanup service worker directly on the production
# server. Run as root on the Contabo host:
#
#   sudo bash tools/fix-production-service-worker.sh
#
# Optional Cloudflare purge:
#
#   export CLOUDFLARE_ZONE_ID='...'
#   export CLOUDFLARE_API_TOKEN='...'
#   sudo --preserve-env=CLOUDFLARE_ZONE_ID,CLOUDFLARE_API_TOKEN \
#     bash tools/fix-production-service-worker.sh

set -Eeuo pipefail

readonly DOCROOT='/home/olama/htdocs/olama.online'
readonly WORKER_PATH="${DOCROOT}/nochain-sw.js"
readonly MU_PLUGIN_DIR="${DOCROOT}/wp-content/mu-plugins"
readonly MU_PLUGIN_PATH="${MU_PLUGIN_DIR}/olama-security-cleanup.php"
readonly PUBLIC_WORKER_URL='https://olama.online/nochain-sw.js'
readonly PUBLIC_HOME_URL='https://olama.online/'

if [[ ${EUID} -ne 0 ]]; then
    echo 'ERROR: Run this script as root (for example: sudo bash fix-production-service-worker.sh).' >&2
    exit 1
fi

resolved_docroot="$(readlink -f -- "${DOCROOT}")"
if [[ "${resolved_docroot}" != "${DOCROOT}" || ! -f "${DOCROOT}/wp-load.php" ]]; then
    echo "ERROR: Expected WordPress document root was not found at ${DOCROOT}." >&2
    exit 1
fi

if [[ -L "${WORKER_PATH}" || -L "${MU_PLUGIN_PATH}" ]]; then
    echo 'ERROR: Refusing to replace a symbolic-link target.' >&2
    exit 1
fi

command -v curl >/dev/null || { echo 'ERROR: curl is required.' >&2; exit 1; }
command -v sha256sum >/dev/null || { echo 'ERROR: sha256sum is required.' >&2; exit 1; }
command -v php >/dev/null || { echo 'ERROR: PHP CLI is required.' >&2; exit 1; }

timestamp="$(date -u +'%Y%m%dT%H%M%SZ')"
backup_dir="/home/olama/backups/service-worker-refresh-fix-${timestamp}"
install -d -m 0700 "${backup_dir}"

if [[ -f "${WORKER_PATH}" ]]; then
    cp -a -- "${WORKER_PATH}" "${backup_dir}/nochain-sw.js.before"
fi
if [[ -f "${MU_PLUGIN_PATH}" ]]; then
    cp -a -- "${MU_PLUGIN_PATH}" "${backup_dir}/olama-security-cleanup.php.before"
fi

worker_tmp="$(mktemp "${DOCROOT}/.nochain-sw.js.tmp.XXXXXX")"
mu_tmp="$(mktemp "${MU_PLUGIN_DIR}/.olama-security-cleanup.php.tmp.XXXXXX")"
cleanup_temporary_files() {
    rm -f -- "${worker_tmp}" "${mu_tmp}"
}
trap cleanup_temporary_files EXIT

cat >"${worker_tmp}" <<'JAVASCRIPT'
'use strict';

// One-time cleanup worker for browsers that still have the former root-scope
// worker installed. It must never navigate controlled pages or handle fetches.
self.addEventListener('install', function (event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function (event) {
    event.waitUntil((async function () {
        var keys = await caches.keys();
        await Promise.all(keys.map(function (key) { return caches.delete(key); }));
        await self.clients.claim();
        await self.registration.unregister();
    })());
});
JAVASCRIPT

cat >"${mu_tmp}" <<'PHP'
<?php
/**
 * Remove the obsolete cleanup-worker injector from public HTML.
 *
 * The old injector re-registered /nochain-sw.js on every navigation. The
 * replacement worker cleans up existing registrations without reloading the
 * page, so this legacy inline script must not be emitted anymore.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', function () {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    echo '<script id="olama-security-cleanup">(function(){if(!("serviceWorker" in navigator)){return;}navigator.serviceWorker.getRegistrations().then(function(registrations){registrations.forEach(function(registration){var worker=registration.active||registration.waiting||registration.installing;if(!worker){return;}try{if(new URL(worker.scriptURL,window.location.href).pathname==="/nochain-sw.js"){registration.unregister();}}catch(error){}});}).catch(function(){});})();</script>';
}, 100);
PHP

if grep -Eq 'client\.navigate|addEventListener\([[:space:]]*["'"']fetch' "${worker_tmp}"; then
    echo 'ERROR: Generated worker contains forbidden navigation/fetch behavior.' >&2
    exit 1
fi

php -l "${mu_tmp}" >/dev/null

worker_owner='olama'
worker_group='olama'
if [[ -f "${WORKER_PATH}" ]]; then
    worker_owner="$(stat -c '%U' "${WORKER_PATH}")"
    worker_group="$(stat -c '%G' "${WORKER_PATH}")"
fi

mu_owner='olama'
mu_group='olama'
if [[ -f "${MU_PLUGIN_PATH}" ]]; then
    mu_owner="$(stat -c '%U' "${MU_PLUGIN_PATH}")"
    mu_group="$(stat -c '%G' "${MU_PLUGIN_PATH}")"
elif [[ -d "${MU_PLUGIN_DIR}" ]]; then
    mu_owner="$(stat -c '%U' "${MU_PLUGIN_DIR}")"
    mu_group="$(stat -c '%G' "${MU_PLUGIN_DIR}")"
fi

install -o "${worker_owner}" -g "${worker_group}" -m 0644 "${worker_tmp}" "${WORKER_PATH}"
install -o "${mu_owner}" -g "${mu_group}" -m 0644 "${mu_tmp}" "${MU_PLUGIN_PATH}"

echo "Installed safe worker: $(sha256sum "${WORKER_PATH}" | awk '{print $1}')"
echo "Installed MU cleanup: $(sha256sum "${MU_PLUGIN_PATH}" | awk '{print $1}')"
echo "Rollback backup: ${backup_dir}"

if [[ -n "${CLOUDFLARE_ZONE_ID:-}" || -n "${CLOUDFLARE_API_TOKEN:-}" ]]; then
    if [[ -z "${CLOUDFLARE_ZONE_ID:-}" || -z "${CLOUDFLARE_API_TOKEN:-}" ]]; then
        echo 'ERROR: Set both CLOUDFLARE_ZONE_ID and CLOUDFLARE_API_TOKEN, or neither.' >&2
        exit 1
    fi

    purge_response="$(curl --fail-with-body --silent --show-error \
        --request POST \
        "https://api.cloudflare.com/client/v4/zones/${CLOUDFLARE_ZONE_ID}/purge_cache" \
        --header "Authorization: Bearer ${CLOUDFLARE_API_TOKEN}" \
        --header 'Content-Type: application/json' \
        --data '{"purge_everything":true}')"

    if [[ "${purge_response}" != *'"success":true'* ]]; then
        echo "ERROR: Cloudflare did not confirm the cache purge: ${purge_response}" >&2
        exit 1
    fi
    echo 'Cloudflare cache purge confirmed.'
else
    echo 'WARNING: Cloudflare was not purged. Purge Everything in Cloudflare before final testing.' >&2
fi

public_worker="$(curl --fail --silent --show-error --max-time 30 \
    -H 'Cache-Control: no-cache' \
    "${PUBLIC_WORKER_URL}?verify=${timestamp}")"
public_home="$(curl --fail --silent --show-error --max-time 30 \
    -H 'Cache-Control: no-cache' \
    "${PUBLIC_HOME_URL}?verify=${timestamp}")"

verification_failed=0
if grep -Eq 'client\.navigate|addEventListener\([[:space:]]*["'"']fetch' <<<"${public_worker}"; then
    echo 'ERROR: Public worker still contains the reload loop or no-op fetch handler.' >&2
    verification_failed=1
fi
if grep -Eq 'serviceWorker\.register\([[:space:]]*["'"']/nochain-sw\.js' <<<"${public_home}"; then
    echo 'ERROR: Public HTML still contains the obsolete worker-registration injector.' >&2
    verification_failed=1
fi
if [[ ${verification_failed} -ne 0 ]]; then
    echo 'Purge Cloudflare, then rerun this script to repeat verification.' >&2
    exit 2
fi

echo 'SUCCESS: Production files and public responses no longer contain the refresh loop.'
echo 'Close all olama.online tabs and test again in a new Incognito window.'
