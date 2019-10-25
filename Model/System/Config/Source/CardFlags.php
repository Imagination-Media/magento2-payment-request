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

class CardFlags implements OptionSourceInterface
{
    /**
     * Get all the available credit/debit card flags
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                "value" => "amex",
                "label" => __("American Express")
            ],
            [
                "value" => "diners",
                "label" => __("Diners Club")
            ],
            [
                "value" => "discover",
                "label" => __("Discover")
            ],
            [
                "value" => "jcb",
                "label" => __("JCB")
            ],
            [
                "value" => "maestro",
                "label" => __("Mastercard Maestro")
            ],
            [
                "value" => "mastercard",
                "label" => __("Mastercard")
            ],
            [
                "value" => "unionpay",
                "label" => __("Union Pay")
            ],
            [
                "value" => "visa",
                "label" => __("Visa")
            ]
        ];
    }
}
