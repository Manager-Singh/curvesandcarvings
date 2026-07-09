#!/bin/bash
# Deploy PDP Figma fixes + variation images to staging.
# Run from your machine (where SSH to staging works):
#   bash staging-deploy-all.sh

set -e

STAGING="root@69.62.82.53"
SSH_PORT="${SSH_PORT:-22}"
SSH_OPTS="-o StrictHostKeyChecking=no -p ${SSH_PORT}"
SITE="/home/qudratxdigital-curvesandcarvings/htdocs/curvesandcarvings.qudratxdigital.com"
LOCAL="$(cd "$(dirname "$0")" && pwd)"
THEME="app/design/frontend/Curvesandcarvings/luma"

FILES=(
  "$THEME/web/css/pdp-figma.css"
  "$THEME/web/css/catalog-fixes.css"
  "$THEME/Magento_Catalog/layout/catalog_product_view.xml"
  "$THEME/Magento_Catalog/templates/product/view/addtocart.phtml"
  "$THEME/Magento_Catalog/templates/product/composite/fieldset/options/view/checkable.phtml"
  "$THEME/Magento_Catalog/templates/product/view/form/options-container.phtml"
  "scripts/assign-variation-images.php"
)

echo "=== Uploading files ==="
for f in "${FILES[@]}"; do
  ssh $SSH_OPTS "$STAGING" "mkdir -p \"$SITE/$(dirname "$f")\""
  scp $SSH_OPTS "$LOCAL/$f" "$STAGING:$SITE/$f"
  echo "  uploaded $f"
done

echo "=== Running on staging ==="
ssh $SSH_OPTS "$STAGING" "cd $SITE && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/pdp-figma.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/pdp-figma.css && \
  cp app/design/frontend/Curvesandcarvings/luma/web/css/catalog-fixes.css pub/static/frontend/Curvesandcarvings/luma/en_US/css/catalog-fixes.css && \
  php scripts/assign-variation-images.php && \
  php bin/magento cache:flush && \
  php bin/magento cache:clean full_page block_html layout && \
  echo DONE"

echo ""
echo "Deploy complete. Hard-refresh the PDP: Ctrl+Shift+R"
