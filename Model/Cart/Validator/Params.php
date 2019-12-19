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

namespace ImaginationMedia\PaymentRequest\Model\Cart\Validator;

use ImaginationMedia\PaymentRequest\Api\Cart\TotalValidatorInterface;

class Params implements TotalValidatorInterface
{
    const REQUIRED_FIELDS = [
        "shippingAddress",
        "shippingMethod"
    ];

    /**
     * Validate request
     * @param array $params
     * @return array
     */
    public function validate(array $params) : array
    {
        $result = [
            'error' => false,
            'error_message' => ''
        ];
        /**
         * Validate info
         */
        foreach (self::REQUIRED_FIELDS as $REQUIRED_FIELD) {
            if (!isset($params[$REQUIRED_FIELD]) ||
                in_array($params[$REQUIRED_FIELD], ["undefined", "null"])) {
                $result['error'] = true;
                $result['error_message'] = __("Not provided " . $REQUIRED_FIELD . " field.");
            }
        }

        return $result;
    }
}
