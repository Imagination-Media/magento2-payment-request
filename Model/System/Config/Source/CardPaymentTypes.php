<?php

/**
 * W3C Payment Request (https://www.w3.org/TR/payment-request/)
 *
 * Add the W3C payment request api to Magento 2
 *
 * @package     ImaginationMedia\PaymentRequest
 * @author      Igor Ludgero Miura <igor@imaginationmedia.com>
 * @copyright   Copyright (c) 2019 Imagination Media (https://www.imaginationmedia.com/)
 * @license     https://opensource.org/licenses/OSL-3.0.php Open Software License 3.0
 */

declare(strict_types=1);

namespace ImaginationMedia\PaymentRequest\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CardPaymentTypes implements OptionSourceInterface
{
    /**
     * Get the available card payment types
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                "value" => "debit",
                "label" => __("Debit")
            ],
            [
                "value" => "credit",
                "label" => __("Credit")
            ]
        ];
    }
}
