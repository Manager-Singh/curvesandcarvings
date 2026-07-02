#!/bin/bash
set -euo pipefail

MAGENTO_ROOT="/var/www/html/curvesandcarvings/magento"
THEME_CSS="${MAGENTO_ROOT}/app/design/frontend/Curvesandcarvings/luma/web/css"
STATIC_CSS="${MAGENTO_ROOT}/pub/static/frontend/Curvesandcarvings/luma/en_US/css"

mkdir -p "${STATIC_CSS}"

for file in styles-m.css styles-l.css owl.carousel.min.css owl.theme.default.min.css catalog-fixes.css; do
    if [[ -f "${THEME_CSS}/${file}" ]]; then
        cp "${THEME_CSS}/${file}" "${STATIC_CSS}/${file}"
        echo "Applied ${file}"
    fi
done
