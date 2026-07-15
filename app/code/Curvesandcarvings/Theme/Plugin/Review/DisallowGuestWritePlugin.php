<?php
declare(strict_types=1);

namespace Curvesandcarvings\Theme\Plugin\Review;

/**
 * Guests may read reviews but must log in to write them.
 */
class DisallowGuestWritePlugin
{
    public function afterGetIsGuestAllowToWrite(
        \Magento\Review\Helper\Data $subject,
        $result
    ): bool {
        return false;
    }
}
