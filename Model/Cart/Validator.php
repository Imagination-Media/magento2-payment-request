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

namespace ImaginationMedia\PaymentRequest\Model\Cart;

use ImaginationMedia\PaymentRequest\Api\Cart\TotalValidatorInterface;

class Validator
{
    /**
     * @var array
     */
    private $validators;

    /**
     * Validator constructor.
     * @param array $validators
     */
    public function __construct(
        array $validators = []
    ) {
        $this->validators = $validators;
    }

    /**
     * Validate the request
     * @param array $params
     * @return array
     */
    public function validate(array $params) : array
    {
        /**
         * @var $validator TotalValidatorInterface
         */
        foreach ($this->validators as $validator) {
            $result = $validator->validate($params);
            if ($result['error'] === false) {
                return $result;
            }
        }

        return [
            'error' => false,
            'error_message' => ''
        ];
    }
}
