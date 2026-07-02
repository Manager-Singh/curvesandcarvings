#!/usr/bin/env bash
# Package only the files needed for staging (no vendor, var, generated).
set -euo pipefail

ROOT="/var/www/html/curvesandcarvings"
OUT="${ROOT}/curvesandcarvings-staging-package.tar.gz"

cd "${ROOT}/magento"

tar -czf "${OUT}" \
  --exclude='vendor' \
  --exclude='var' \
  --exclude='generated' \
  --exclude='pub/static' \
  --exclude='.git' \
  app/code \
  app/design \
  app/etc/config.php \
  app/etc/env.php \
  app/etc/NonComposerComponentRegistration.php \
  app/etc/registration.php \
  app/etc/db_schema.xml \
  app/etc/di.xml \
  app/bootstrap.php \
  app/autoload.php \
  bin \
  composer.json \
  composer.lock \
  lib \
  pub/media \
  pub/.htaccess \
  pub/index.php \
  pub/static.php \
  pub/errors \
  pub/opt \
  setup \
  auth.json.sample \
  deploy-staging.sh \
  start-opensearch.sh \
  scripts

echo "Created: ${OUT}"
echo "Size: $(du -h "${OUT}" | cut -f1)"
echo "On staging: extract, edit app/etc/env.php, then run ./deploy-staging.sh"
