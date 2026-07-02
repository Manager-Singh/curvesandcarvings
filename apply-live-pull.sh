#!/usr/bin/env bash
set -euo pipefail

ROOT="/var/www/html/curvesandcarvings"
PULL="${ROOT}/live-pull"
THEME="${ROOT}/magento/app/design/frontend/Curvesandcarvings/luma"

echo "=== Applying live-pulled templates to theme ==="

mkdir -p \
  "${THEME}/Magento_Theme/templates/extra" \
  "${THEME}/Magento_Theme/templates/html" \
  "${THEME}/Magento_Search/templates" \
  "${THEME}/Magento_Theme/layout" \
  "${THEME}/web/css"

# Extra page templates
cp -f "${PULL}/theme-templates/extra/"*.phtml "${THEME}/Magento_Theme/templates/extra/"

# Header/footer/search
cp -f "${PULL}/theme-templates/html/nvdfooter.phtml" "${THEME}/Magento_Theme/templates/html/"
cp -f "${PULL}/theme-templates/html/skip.phtml" "${THEME}/Magento_Theme/templates/html/"
cp -f "${PULL}/theme-templates/form.mini.phtml" "${THEME}/Magento_Search/templates/"

# Layouts
cp -f "${PULL}/layouts/default.xml" "${THEME}/Magento_Theme/layout/"
cp -f "${PULL}/layouts/default_head_blocks.xml" "${THEME}/Magento_Theme/layout/"

# CSS from live
cp -f "${PULL}/static-css/styles-m.css" "${THEME}/web/css/"
cp -f "${PULL}/static-css/styles-l.css" "${THEME}/web/css/"
cp -f "${PULL}/static-css/owl.carousel.min.css" "${THEME}/web/css/"
cp -f "${PULL}/static-css/owl.theme.default.min.css" "${THEME}/web/css/"

echo "=== Fixing pub/media URLs for local docroot ==="
python3 << 'PYEOF'
import re
from pathlib import Path

theme = Path("/var/www/html/curvesandcarvings/magento/app/design/frontend/Curvesandcarvings/luma")
media_header = """<?php
$objectManager = \\Magento\\Framework\\App\\ObjectManager::getInstance();
$mediaUrl = $objectManager->get(\\Magento\\Store\\Model\\StoreManagerInterface::class)
    ->getStore()->getBaseUrl(\\Magento\\Framework\\UrlInterface::URL_TYPE_MEDIA);
?>
"""

patterns = [
    (r"<\?php echo \$block->getBaseUrl\(\); \?>pub/media/", "<?php echo $mediaUrl; ?>"),
    (r"<\?php //echo \$block->getBaseUrl\(\); \?>pub/media/", "<?php echo $mediaUrl; ?>"),
    (r"https://www\.curvesandcarvings\.com/pub/media/", "<?php echo $mediaUrl; ?>"),
    (r'"pub/media/', '"<?php echo $mediaUrl; ?>'),
    (r"'pub/media/", "'<?php echo $mediaUrl; ?>"),
]

skip_header = Path(theme / "Magento_Theme/templates/html/skip.phtml")
skip_content = skip_header.read_text(encoding="utf-8")
if "catalog-fixes.css" not in skip_content:
    skip_content = skip_content.replace(
        "<div class=\"topleftcal01\">",
        '<link rel="stylesheet" type="text/css" href="<?= $block->escapeUrl($block->getViewFileUrl(\'css/catalog-fixes.css\')) ?>" />\n\n<div class="topleftcal01">',
        1,
    )
skip_header.write_text(skip_content, encoding="utf-8")

for path in theme.rglob("*.phtml"):
    text = path.read_text(encoding="utf-8")
    original = text

    for pattern, repl in patterns:
        text = re.sub(pattern, repl, text)

    if "pub/media/" in text or "curvesandcarvings.com/pub/media" in text:
        # still has unresolved paths in plain HTML
        text = text.replace("pub/media/", "<?php echo $mediaUrl; ?>")

    needs_media = "$mediaUrl" in text and "URL_TYPE_MEDIA" not in text
    if needs_media:
        media_block = """<?php
$objectManager = \\Magento\\Framework\\App\\ObjectManager::getInstance();
$mediaUrl = $objectManager->get(\\Magento\\Store\\Model\\StoreManagerInterface::class)
    ->getStore()->getBaseUrl(\\Magento\\Framework\\UrlInterface::URL_TYPE_MEDIA);
?>
"""
        text = media_block + text

    if text != original:
        path.write_text(text, encoding="utf-8")
        print(f"fixed: {path.relative_to(theme)}")

PYEOF

echo "=== Deploying CSS to pub/static ==="
bash "${ROOT}/magento/apply-live-css.sh"

echo "=== Clearing cache ==="
rm -rf "${ROOT}/magento/var/cache/"* "${ROOT}/magento/var/page_cache/"* "${ROOT}/magento/var/view_preprocessed/"* 2>/dev/null || true

echo "Done. Live files applied to ${THEME}"
