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

namespace ImaginationMedia\PaymentRequest\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Payment\Model\MethodInterface;

class CardPaymentMethods implements OptionSourceInterface
{
    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;

    /**
     * @var array
     */
    protected $paymentMethods;

    /**
     * CardPaymentMethods constructor.
     * @param PaymentConfig $paymentConfig
     * @param array $paymentMethods
     */
    public function __construct(
        PaymentConfig $paymentConfig,
        array $paymentMethods = []
    ) {
        $this->paymentConfig = $paymentConfig;
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * Get all the supported payment methods
     * @return array
     */
    public function toOptionArray()
    {
        $methods = [];
        $availablePaymentMethods = $this->paymentConfig->getActiveMethods();

        /**
         * @var $paymentMethod MethodInterface
         */
        foreach ($availablePaymentMethods as $code => $paymentMethod) {
            if (in_array($code, $this->paymentMethods)) {
                $methods[] = [
                    "value" => $code,
                    "label" => "[" . $code . "] " . $paymentMethod->getTitle()
                ];
            }
        }
        return $methods;
    }
}
