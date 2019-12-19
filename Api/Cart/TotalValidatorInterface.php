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

namespace ImaginationMedia\PaymentRequest\Api\Cart;

/**
 * Interface TotalValidatorInterface
 * @package ImaginationMedia\PaymentRequest\Api\Cart
 * @api
 */
interface TotalValidatorInterface
{
    /**
     * Validate params
     * @param array $params
     *
     *
     * Expected result:
     * [
     *      'error' => true
     *      'error_message' => 'Invalid field'
     * ]
     *
     * @return array
     */
    public function validate(array $params) : array;
}
