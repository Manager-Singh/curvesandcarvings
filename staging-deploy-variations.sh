#!/bin/bash
# Deploy Curvesandcarvings_Variations admin CRUD fix to staging.
#   bash staging-deploy-variations.sh

set -e

STAGING="root@69.62.82.53"
SSH_PORT="${SSH_PORT:-22}"
SSH_OPTS="-o StrictHostKeyChecking=no -p ${SSH_PORT}"
SCP_OPTS="-o StrictHostKeyChecking=no -P ${SSH_PORT}"
SITE="/home/qudratxdigital-curvesandcarvings/htdocs/curvesandcarvings.qudratxdigital.com"
LOCAL="$(cd "$(dirname "$0")" && pwd)"
MOD="app/code/Curvesandcarvings/Variations"

if [[ -n "${SSHPASS:-}" ]]; then
  SSH_CMD=(sshpass -e ssh)
  SCP_CMD=(sshpass -e scp)
else
  SSH_CMD=(ssh)
  SCP_CMD=(scp)
fi

FILES=(
  "$MOD/registration.php"
  "$MOD/etc/module.xml"
  "$MOD/etc/adminhtml/di.xml"
  "$MOD/etc/adminhtml/routes.xml"
  "$MOD/Setup/EavSetupFactory.php"
  "$MOD/Setup/Patch/Data/CreateVariationAttributes.php"
  "$MOD/Setup/Patch/Data/CreateVariationAttributeSets.php"
  "$MOD/Model/VariationManager.php"
  "$MOD/Block/Adminhtml/Product/VariationImages.php"
  "$MOD/Ui/DataProvider/Product/Form/Modifier/VariationImagesPanel.php"
  "$MOD/Controller/Adminhtml/Product/Save.php"
  "$MOD/Controller/Adminhtml/Product/Delete.php"
  "$MOD/Controller/Adminhtml/Product/SaveImages.php"
  "$MOD/view/adminhtml/layout/catalog_product_edit.xml"
  "$MOD/view/adminhtml/ui_component/product_form.xml"
  "$MOD/view/adminhtml/templates/product/variation-images.phtml"
  "$MOD/view/adminhtml/web/css/variation-images.css"
  "$MOD/view/adminhtml/requirejs-config.js"
  "app/etc/config.php"
)

echo "=== Uploading Variations module to staging ==="
for f in "${FILES[@]}"; do
  "${SSH_CMD[@]}" $SSH_OPTS "$STAGING" "mkdir -p \"$SITE/$(dirname "$f")\""
  "${SCP_CMD[@]}" $SCP_OPTS "$LOCAL/$f" "$STAGING:$SITE/$f"
  echo "  uploaded $f"
done

echo "=== Flush caches on staging ==="
"${SSH_CMD[@]}" $SSH_OPTS "$STAGING" "cd $SITE && \
  rm -rf var/view_preprocessed/pub/static/adminhtml/*/Curvesandcarvings_Variations \
         var/view_preprocessed/pub/static/app/code/Curvesandcarvings/Variations \
         generated/code/Curvesandcarvings/Variations \
         var/cache/* var/page_cache/* 2>/dev/null || true; \
  php bin/magento module:enable Curvesandcarvings_Variations 2>/dev/null || true; \
  php bin/magento setup:upgrade --keep-generated && \
  php bin/magento cache:flush && \
  chown -R qudratxdigital-curvesandcarvings:qudratxdigital-curvesandcarvings \
    app/code/Curvesandcarvings/Variations generated var 2>/dev/null || true; \
  echo DONE"

echo ""
echo "Deploy complete. Hard-refresh admin product edit: Ctrl+Shift+R"
echo "You should see 'Product Variations' (not 'Add or edit variations')."
