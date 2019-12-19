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

define([
    'Magento_Customer/js/customer-data',
    'mage/translate',
], function (customerData, $t) {
    'use strict';

    return {
        init: function (paymentRequest, paymentResponse, w3cPaymentRequest) {
            if (!paymentResponse.methodName) {
                console.log($t("Not valid response"));
                paymentResponse.complete('fail');
            }

            var paymentComponents = customerData.get('payment-request')().paymentRequestApi.paymentComponents;

            var cardPaymentMethod = w3cPaymentRequest.cardConfig.paymentMethod;

            if (!paymentComponents.hasOwnProperty(cardPaymentMethod)) {
                throw new Error($t("Invalid payment method provided for payment request."));
            }

            require([paymentComponents[cardPaymentMethod]], function(cardPaymentMethod) {
                cardPaymentMethod.process(paymentRequest, paymentResponse, w3cPaymentRequest);
            });
        }
    };
});
