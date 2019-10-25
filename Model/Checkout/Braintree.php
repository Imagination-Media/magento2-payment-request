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

namespace ImaginationMedia\PaymentRequest\Model\Checkout;

use Magento\Braintree\Observer\DataAssignObserver;
use Magento\Quote\Api\Data\CartInterface;

class Braintree
{
    /**
     * Set Braintree payment info
     * @param CartInterface $cart
     * @param array $paymentInfo
     */
    public function setPaymentInfo(CartInterface &$cart, array $paymentInfo) : void
    {
        $cart->getPayment()->importData([
            'method' => $paymentInfo["code"]
        ]);
        $cart->getPayment()->setAdditionalInformation([
            DataAssignObserver::PAYMENT_METHOD_NONCE => $paymentInfo["token"]
        ]);
    }
}
