#!/usr/bin/env bash
# Deploy Curves and Carvings to a new staging/production server.
# Do NOT copy vendor/ — run composer install on the server instead.
set -euo pipefail

MAGENTO_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "${MAGENTO_ROOT}"

echo "=== Curves & Carvings — staging deploy ==="

echo "1. Composer install (creates vendor/ from lock file)"
composer install --no-dev --optimize-autoloader

echo "2. Magento setup upgrade (modules + theme registration)"
php bin/magento setup:upgrade --keep-generated || php bin/magento setup:upgrade

echo "3. Deploy static content (frontend + admin)"
php bin/magento setup:static-content:deploy -f en_US \
  --theme Curvesandcarvings/luma \
  --theme Magento/luma \
  --theme Magento/blank
php bin/magento setup:static-content:deploy -f en_US \
  --area adminhtml \
  --theme MageOS/m137-admin-theme \
  --theme Magento/backend

echo "4. Reindex catalog search (requires OpenSearch running)"
php bin/magento indexer:reindex catalogsearch_fulltext || true

echo "5. Flush cache"
php bin/magento cache:flush || rm -rf var/cache/* var/page_cache/* var/view_preprocessed/*

echo "Done. Theme: Curvesandcarvings/luma (assigned by Curvesandcarvings_Theme module)"
echo ""
echo "Transfer these from local — do NOT copy vendor/:"
echo "  app/code/          (all custom modules)"
echo "  app/design/        (Curvesandcarvings/luma theme)"
echo "  app/etc/env.php    (server-specific — edit DB/URL on staging)"
echo "  pub/media/         (product images)"
echo "  composer.json + composer.lock"
echo ""
echo "Also ensure on staging:"
echo "  - OpenSearch on localhost:9200 (or update env.php search settings)"
echo "  - pub/.htaccess our-gallery redirect is included"
echo "  - design theme = Curvesandcarvings/luma (auto-set by setup:upgrade)"
