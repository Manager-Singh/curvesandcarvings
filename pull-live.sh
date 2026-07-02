#!/usr/bin/env bash
# Pull customized theme files from live Curves and Carvings server.
# Requires explicit approval: CONFIRM_SSH=1 bash pull-live.sh
set -euo pipefail

if [[ "${CONFIRM_SSH:-}" != "1" ]]; then
  echo "SSH pull disabled by default. To run: CONFIRM_SSH=1 bash pull-live.sh"
  exit 1
fi

ROOT="/var/www/html/curvesandcarvings"
PULL="${ROOT}/live-pull"
LIVE="master_ppznhmbjtx@128.199.194.240"
REMOTE="/home/master/applications/qgcyetztur/public_html"
PASS="${LIVE_SSH_PASS:?Set LIVE_SSH_PASS environment variable}"

ssh_cmd() {
  sshpass -p "$PASS" ssh -o StrictHostKeyChecking=no "$LIVE" "$@"
}

mkdir -p \
  "${PULL}/theme-templates/extra" \
  "${PULL}/theme-templates/html" \
  "${PULL}/layouts" \
  "${PULL}/static-css"

echo "=== Pulling extra templates ==="
for f in expert-zone-new.phtml gallery.phtml home-view.phtml our-store.phtml testimonials.phtml; do
  ssh_cmd "cat ${REMOTE}/vendor/magento/module-theme/view/frontend/templates/extra/${f}" \
    > "${PULL}/theme-templates/extra/${f}"
  echo "  extra/${f}"
done

echo "=== Pulling html templates ==="
for f in nvdfooter.phtml skip.phtml; do
  ssh_cmd "cat ${REMOTE}/vendor/magento/module-theme/view/frontend/templates/html/${f}" \
    > "${PULL}/theme-templates/html/${f}"
  echo "  html/${f}"
done

ssh_cmd "cat ${REMOTE}/vendor/magento/module-search/view/frontend/templates/form.mini.phtml" \
  > "${PULL}/theme-templates/form.mini.phtml"
echo "  form.mini.phtml"

echo "=== Pulling layouts ==="
for f in default.xml default_head_blocks.xml; do
  ssh_cmd "cat ${REMOTE}/vendor/magento/module-theme/view/frontend/layout/${f}" \
    > "${PULL}/layouts/${f}"
  echo "  layout/${f}"
done

echo "=== Pulling CSS ==="
for f in styles-m.css styles-l.css owl.carousel.min.css owl.theme.default.min.css; do
  ssh_cmd "cat ${REMOTE}/pub/static/frontend/Magento/luma/en_US/css/${f}" \
    > "${PULL}/static-css/${f}"
  echo "  css/${f} ($(wc -c < "${PULL}/static-css/${f}") bytes)"
done

echo "=== Applying to local theme ==="
bash "${ROOT}/magento/apply-live-pull.sh"

echo "Live pull complete."
