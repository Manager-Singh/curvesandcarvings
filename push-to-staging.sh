#!/usr/bin/env bash
# Push local custom Magento work → staging server.
#
# Usage (from this machine):
#   export SSHPASS='…'   # staging root password, if no SSH key
#   bash /var/www/html/curvesandcarvings/magento/push-to-staging.sh
#
# Deploys:
#   app/code/Curvesandcarvings/
#   app/design/frontend/Curvesandcarvings/
#   selective Ccavenuepay + MegaMenu fixes
# Does NOT copy: vendor/, env.php, var/, pub/media, Magento core.

set -euo pipefail

STAGING="${STAGING:-root@69.62.82.53}"
SSH_PORT="${SSH_PORT:-22}"
SITE="${SITE:-/home/qudratxdigital-curvesandcarvings/htdocs/curvesandcarvings.qudratxdigital.com}"
LOCAL="$(cd "$(dirname "$0")" && pwd)"

if [[ -n "${SSHPASS:-}" ]]; then
  SSH=(sshpass -e ssh -o StrictHostKeyChecking=no -p "$SSH_PORT")
  RSYNC_RSH="sshpass -e ssh -o StrictHostKeyChecking=no -p $SSH_PORT"
else
  SSH=(ssh -o StrictHostKeyChecking=no -p "$SSH_PORT")
  RSYNC_RSH="ssh -o StrictHostKeyChecking=no -p $SSH_PORT"
fi

echo "=== Testing $STAGING ==="
"${SSH[@]}" "$STAGING" 'echo STAGING_OK'

echo "=== Sync theme ==="
rsync -az --delete -e "$RSYNC_RSH" \
  "$LOCAL/app/design/frontend/Curvesandcarvings/" \
  "$STAGING:$SITE/app/design/frontend/Curvesandcarvings/"

echo "=== Sync Curvesandcarvings modules ==="
rsync -az --delete -e "$RSYNC_RSH" \
  "$LOCAL/app/code/Curvesandcarvings/" \
  "$STAGING:$SITE/app/code/Curvesandcarvings/"

echo "=== Sync CcAvenue + MegaMenu fixes ==="
rsync -az -e "$RSYNC_RSH" \
  "$LOCAL/app/code/Magento/Ccavenuepay/Helper/Data.php" \
  "$STAGING:$SITE/app/code/Magento/Ccavenuepay/Helper/Data.php"
rsync -az -e "$RSYNC_RSH" \
  "$LOCAL/app/code/Magento/Ccavenuepay/view/frontend/web/js/view/payment/method-renderer/ccavenuepay.js" \
  "$STAGING:$SITE/app/code/Magento/Ccavenuepay/view/frontend/web/js/view/payment/method-renderer/ccavenuepay.js"
rsync -az -e "$RSYNC_RSH" \
  "$LOCAL/app/code/Ibnab/MegaMenu/Block/Html/Topmega.php" \
  "$STAGING:$SITE/app/code/Ibnab/MegaMenu/Block/Html/Topmega.php"
rsync -az -e "$RSYNC_RSH" \
  "$LOCAL/app/code/Ibnab/MegaMenu/view/frontend/templates/html/top-mega.phtml" \
  "$STAGING:$SITE/app/code/Ibnab/MegaMenu/view/frontend/templates/html/top-mega.phtml"

echo "=== setup:upgrade + static copy + cache on staging ==="
"${SSH[@]}" "$STAGING" "bash -s" <<REMOTE
set -euo pipefail
cd "$SITE"
OWNER=\$(stat -c '%U:%G' app/etc/env.php 2>/dev/null || echo 'qudratxdigital-curvesandcarvings:qudratxdigital-curvesandcarvings')
chown -R "\$OWNER" app/code/Curvesandcarvings app/design/frontend/Curvesandcarvings 2>/dev/null || true
php bin/magento module:enable Curvesandcarvings_Theme Curvesandcarvings_Faq Curvesandcarvings_Variations Curvesandcarvings_Homepage 2>/dev/null || true
php bin/magento setup:upgrade --keep-generated
THEME_STATIC=pub/static/frontend/Curvesandcarvings/luma/en_US
mkdir -p "\$THEME_STATIC/css" "\$THEME_STATIC/js" "\$THEME_STATIC/images/pdp" "\$THEME_STATIC/Magento_Review/js"
for f in pdp-figma-v4.css designer-custom.css catalog-fixes.css pdp-figma.css cc-cart-variations.css; do
  [ -f "app/design/frontend/Curvesandcarvings/luma/web/css/\$f" ] && cp -f "app/design/frontend/Curvesandcarvings/luma/web/css/\$f" "\$THEME_STATIC/css/\$f"
done
for f in pdp-figma-v4.js pdp-figma.js header-init.js pdp-social-proof.js pdp-variation-cards.js form-key-fix.js pdp-loader-fix.js swatch-renderer-mixin.js; do
  [ -f "app/design/frontend/Curvesandcarvings/luma/web/js/\$f" ] && cp -f "app/design/frontend/Curvesandcarvings/luma/web/js/\$f" "\$THEME_STATIC/js/\$f"
done
[ -f app/design/frontend/Curvesandcarvings/luma/Magento_Review/web/js/process-reviews.js ] && \
  cp -f app/design/frontend/Curvesandcarvings/luma/Magento_Review/web/js/process-reviews.js "\$THEME_STATIC/Magento_Review/js/process-reviews.js"
[ -d app/design/frontend/Curvesandcarvings/luma/web/images/pdp ] && \
  cp -a app/design/frontend/Curvesandcarvings/luma/web/images/pdp/. "\$THEME_STATIC/images/pdp/" || true
mkdir -p pub/static/frontend/Curvesandcarvings/luma/en_US/Curvesandcarvings_Faq/css
[ -f app/code/Curvesandcarvings/Faq/view/frontend/web/css/faq.css ] && \
  cp -f app/code/Curvesandcarvings/Faq/view/frontend/web/css/faq.css pub/static/frontend/Curvesandcarvings/luma/en_US/Curvesandcarvings_Faq/css/faq.css
chown -R "\$OWNER" "\$THEME_STATIC" pub/static/frontend/Curvesandcarvings generated var 2>/dev/null || true
php bin/magento cache:flush
echo DEPLOY_DONE
REMOTE

echo ""
echo "Staging updated: https://curvesandcarvings.qudratxdigital.com/"
echo "Hard-refresh (Ctrl+Shift+R)."
