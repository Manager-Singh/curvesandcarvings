#!/bin/bash
# Deploy navigation parity fixes to staging.
# Run from your machine (where SSH to staging works):
#   bash staging-deploy-nav.sh

set -e

STAGING="root@69.62.82.53"
SSH_PORT="${SSH_PORT:-22}"
SSH_OPTS="-o StrictHostKeyChecking=no -p ${SSH_PORT}"
SCP_OPTS="-o StrictHostKeyChecking=no -P ${SSH_PORT}"
SITE="/home/qudratxdigital-curvesandcarvings/htdocs/curvesandcarvings.qudratxdigital.com"
LOCAL="$(cd "$(dirname "$0")" && pwd)"
THEME="app/design/frontend/Curvesandcarvings/luma"

if [[ -n "${SSHPASS:-}" ]]; then
  SSH_CMD=(sshpass -e ssh)
  SCP_CMD=(sshpass -e scp)
else
  SSH_CMD=(ssh)
  SCP_CMD=(scp)
fi

FILES=(
  "$THEME/Magento_Theme/layout/default.xml"
  "$THEME/Magento_Theme/layout/default_head_blocks.xml"
  "$THEME/Magento_Theme/templates/html/skip.phtml"
  "$THEME/Magento_Theme/templates/html/header-init.phtml"
  "$THEME/Magento_Theme/templates/html/form-key-init.phtml"
  "$THEME/web/js/form-key-fix.js"
  "$THEME/Magento_Search/templates/form.mini.phtml"
  "$THEME/Magento_Theme/templates/extra/gallery.phtml"
  "$THEME/Magento_Catalog/templates/product/view/gallery.phtml"
  "$THEME/Magento_Catalog/templates/product/view/addtocart.phtml"
  "$THEME/Magento_Catalog/templates/product/view/pdp-price-header.phtml"
  "$THEME/Magento_Catalog/templates/product/view/social-proof.phtml"
  "$THEME/Magento_Catalog/templates/product/view/pdp-media-extras.phtml"
  "$THEME/Magento_Catalog/templates/product/view/product-pager.phtml"
  "$THEME/Magento_Catalog/templates/product/view/recently-viewed.phtml"
  "$THEME/web/js/pdp-social-proof.js"
  "app/code/Curvesandcarvings/Theme/Block/Product/Pager.php"
  "app/code/Curvesandcarvings/Theme/Block/Product/RecentlyViewed.php"
  "app/code/Curvesandcarvings/Theme/view/frontend/layout/catalog_product_view.xml"
  "app/code/Curvesandcarvings/Theme/etc/module.xml"
  "$THEME/Magento_Catalog/templates/product/view/form.phtml"
  "$THEME/Magento_Catalog/templates/product/view/form/options-container.phtml"
  "$THEME/Magento_Catalog/templates/product/view/options.phtml"
  "$THEME/Magento_Catalog/layout/catalog_product_view.xml"
  "$THEME/web/js/pdp-loader-fix.js"
  "$THEME/web/js/pdp-figma.js"
  "$THEME/web/js/pdp-figma-v3.js"
  "$THEME/web/js/pdp-figma-v4.js"
  "$THEME/web/js/swatch-renderer-mixin.js"
  "$THEME/web/css/pdp-figma.css"
  "$THEME/web/css/pdp-figma-v3.css"
  "$THEME/web/css/pdp-figma-v4.css"
  "$THEME/Magento_Catalog/templates/product/composite/fieldset/options/view/checkable.phtml"
  "$THEME/Magento_Swatches/templates/product/view/renderer.phtml"
  "$THEME/web/requirejs-config.js"
  "$THEME/web/css/styles-m.css"
  "$THEME/web/css/styles-l.css"
  "$THEME/web/css/catalog-fixes.css"
  "app/code/Curvesandcarvings/Theme/Plugin/Catalog/Category/UrlPlugin.php"
  "app/code/Curvesandcarvings/Theme/Plugin/Checkout/Cart/AddBuyNowRedirectPlugin.php"
  "app/code/Curvesandcarvings/Theme/etc/frontend/di.xml"
  "app/code/Ibnab/MegaMenu/view/frontend/templates/html/top-mega.phtml"
  "app/code/Ibnab/MegaMenu/Block/Html/Topmega.php"
  "staging-merge-requirejs.sh"
)

echo "=== Uploading navigation files ==="
for f in "${FILES[@]}"; do
  "${SSH_CMD[@]}" $SSH_OPTS "$STAGING" "mkdir -p \"$SITE/$(dirname "$f")\""
  "${SCP_CMD[@]}" $SCP_OPTS "$LOCAL/$f" "$STAGING:$SITE/$f"
  echo "  uploaded $f"
done

echo "=== Running on staging ==="
"${SSH_CMD[@]}" $SSH_OPTS "$STAGING" "cd $SITE && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/styles-m.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/styles-m.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/styles-l.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/styles-l.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/catalog-fixes.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/catalog-fixes.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/pdp-figma.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/pdp-figma.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/pdp-figma-v3.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/pdp-figma-v3.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/pdp-figma-v4.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/pdp-figma-v4.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/pdp-loader-fix.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/pdp-loader-fix.js && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/pdp-figma.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/pdp-figma.js && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/pdp-figma-v3.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/pdp-figma-v3.js && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/pdp-figma-v4.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/pdp-figma-v4.js && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/form-key-fix.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/form-key-fix.js && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/pdp-social-proof.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/pdp-social-proof.js && \
  cp app/design/frontend/Curvesandcarvings/luma/web/js/swatch-renderer-mixin.js pub/static/frontend/Curvesandcarvings/luma/en_US/js/swatch-renderer-mixin.js && \
  chown -R qudratxdigital-curvesandcarvings:qudratxdigital-curvesandcarvings generated var pub/static/frontend/Curvesandcarvings && \
  php bin/magento setup:static-content:deploy -f en_US --area frontend --theme Curvesandcarvings/luma && \
  bash staging-merge-requirejs.sh . && \
  chown -R qudratxdigital-curvesandcarvings:qudratxdigital-curvesandcarvings generated var pub/static/frontend/Curvesandcarvings && \
  php bin/magento cache:flush && \
  php bin/magento cache:clean full_page block_html layout && \
  echo DONE"

echo ""
echo "Deploy complete. Hard-refresh staging: Ctrl+Shift+R"
