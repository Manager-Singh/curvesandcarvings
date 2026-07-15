<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Plugin\PageBuilder;

use Magento\PageBuilder\Block\GoogleMapsApi;

/**
 * Magento core only skips the Google Maps script in admin when the API key is empty.
 * On frontend it always shims Magento_PageBuilder/js/utils/map → googleMaps, which loads
 * maps.googleapis.com with an empty key, breaks RequireJS, and leaves checkout stuck
 * on the loading spinner (rjsResolver never resolves).
 */
class GoogleMapsApiKeyGuardPlugin
{
    public function afterShouldIncludeGoogleMapsLibrary(GoogleMapsApi $subject, bool $result): bool
    {
        $apiKey = trim((string)$subject->getApiKey());
        return $apiKey !== '';
    }
}
