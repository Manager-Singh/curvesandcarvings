#!/bin/bash
# Merge parent theme requirejs-config with Curvesandcarvings/luma overrides.
# Child-theme static deploy only emits the theme snippet (~1KB); copy the
# merged Magento/luma config and append theme-specific paths/mixins.

set -euo pipefail

SITE="${1:-.}"
THEME_STATIC="$SITE/pub/static/frontend/Curvesandcarvings/luma/en_US"
PARENT_CONFIG="$SITE/pub/static/frontend/Magento/luma/en_US/requirejs-config.js"
CHILD_CONFIG="$THEME_STATIC/requirejs-config.js"

if [[ ! -f "$PARENT_CONFIG" ]]; then
  echo "Missing parent requirejs-config: $PARENT_CONFIG" >&2
  exit 1
fi

cp "$PARENT_CONFIG" "$CHILD_CONFIG"

cat >> "$CHILD_CONFIG" << 'EOF'

(function() {
var config = {
    paths: {
        'js/owl.carousel': 'js/owl.carousel',
        'js/simple-lightbox': 'js/simple-lightbox',
        'js/jquery.popupoverlay': 'js/jquery.popupoverlay',
        'js/readmore': 'js/readmore',
        'js/jquery-mTab-min': 'js/jquery-mTab-min',
        'js/navigation-menu': 'js/navigation-menu',
        'js/grt-responsive-menu': 'js/grt-responsive-menu'
    },
    config: {
        mixins: {
        }
    },
    shim: {
        'js/owl.carousel': {
            deps: ['jquery']
        },
        'js/simple-lightbox': {
            deps: ['jquery'],
            exports: 'SimpleLightbox'
        },
        'js/jquery.popupoverlay': {
            deps: ['jquery']
        },
        'js/readmore': {
            deps: ['jquery']
        },
        'js/jquery-mTab-min': {
            deps: ['jquery']
        },
        'js/navigation-menu': {
            deps: ['jquery']
        },
        'js/grt-responsive-menu': {
            deps: ['jquery']
        }
    }
};
require.config(config);
})();
EOF

echo "Merged requirejs-config -> $CHILD_CONFIG ($(wc -c < "$CHILD_CONFIG") bytes)"
