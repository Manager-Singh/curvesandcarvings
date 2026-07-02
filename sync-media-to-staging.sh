#!/usr/bin/env bash
# Copy pub/media to staging server (media is NOT in git — too large for GitHub).
#
# Usage on STAGING after git clone:
#   rsync -avz user@SOURCE_HOST:/var/www/html/curvesandcarvings/magento/pub/media/ pub/media/
#
# Usage from THIS machine to remote staging:
#   ./sync-media-to-staging.sh user@staging-server:/var/www/curvesandcarvings/pub/media/
set -euo pipefail

SOURCE="${1:-}"
DEST="${2:-}"

if [[ -z "${SOURCE}" || -z "${DEST}" ]]; then
  echo "Copy pub/media to staging (not stored in git)."
  echo ""
  echo "From local backup:"
  echo "  rsync -av /var/www/html/curvesandcarvings/magento-backup-required/pub/media/ user@staging:/path/to/pub/media/"
  echo ""
  echo "From this dev server:"
  echo "  rsync -av /var/www/html/curvesandcarvings/magento/pub/media/ user@staging:/path/to/pub/media/"
  echo ""
  echo "Or run:"
  echo "  $0 user@staging:/var/www/curvesandcarvings/pub/media/"
  exit 0
fi

LOCAL_MEDIA="/var/www/html/curvesandcarvings/magento/pub/media/"
if [[ ! -d "${LOCAL_MEDIA}" ]]; then
  echo "Missing: ${LOCAL_MEDIA}"
  exit 1
fi

echo "Syncing ${LOCAL_MEDIA} -> ${SOURCE}"
rsync -av --progress "${LOCAL_MEDIA}" "${SOURCE}"
echo "Done."
