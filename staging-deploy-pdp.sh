#!/bin/bash
# Run on staging server from Magento root:
#   bash staging-deploy-pdp.sh
set -e
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

mkdir -p scripts

if [ ! -f scripts/assign-variation-images.php ]; then
  echo "ERROR: scripts/assign-variation-images.php not found in $ROOT"
  echo "Upload it from local workspace first:"
  echo "  scp magento/scripts/assign-variation-images.php root@69.62.82.53:$ROOT/scripts/"
  exit 1
fi

echo "=== Assigning variation images ==="
php scripts/assign-variation-images.php

echo "=== Copying static CSS ==="
cp -f app/design/frontend/Curvesandcarvings/luma/web/css/pdp-figma.css \
  pub/static/frontend/Curvesandcarvings/luma/en_US/css/pdp-figma.css
cp -f app/design/frontend/Curvesandcarvings/luma/web/css/catalog-fixes.css \
  pub/static/frontend/Curvesandcarvings/luma/en_US/css/catalog-fixes.css

echo "=== Flushing cache ==="
php bin/magento cache:flush
php bin/magento cache:clean full_page block_html layout

echo "=== Done. Hard-refresh the PDP in browser (Ctrl+Shift+R) ==="
