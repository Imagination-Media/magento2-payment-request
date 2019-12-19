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
    'https://js.braintreegateway.com/js/braintree-2.32.0.min.js',
    'mage/translate',
    'jquery'
], function (braintree, $t, $) {
    'use strict';

    return {
        process: function (paymentRequest, paymentResponse, w3cPaymentRequest) {
            var details = paymentResponse.details;

            /**
             * Validate client token
             */
            if (!w3cPaymentRequest.cardConfig.hasOwnProperty("additionalInfo") ||
                !w3cPaymentRequest.cardConfig.additionalInfo.hasOwnProperty("clientToken")) {
                console.log($t("No client Braintree key was provided."));
                paymentResponse.complete('fail');
            }

            var client = new braintree.api.Client({clientToken: w3cPaymentRequest.cardConfig.additionalInfo.clientToken});
            client.tokenizeCard({
                number: details.cardNumber,
                cardholderName: details.cardholderName,
                expirationMonth: details.expiryMonth,
                expirationYear: details.expiryYear,
                cvv: details.cardSecurityCode,
                billingAddress: details.billingAddress
            }, function (err, nonce) {
                if (!err) {
                    var params = {
                        paymentMethod: "braintree",
                        token: nonce,
                        shippingAddress: JSON.parse(JSON.stringify(paymentResponse.shippingAddress)),
                        billingAddress: details.billingAddress,
                        contactInfo: {
                            name: paymentResponse.payerName,
                            email: paymentResponse.payerEmail,
                            phone: paymentResponse.payerPhone
                        },
                        shippingMethod: JSON.parse(paymentRequest.shippingOption)
                    };
                    $.ajax({
                        url: w3cPaymentRequest.urls.checkout,
                        action: 'POST',
                        cache: false,
                        data: params,
                        success: function (response) {
                            if (response.result === true) {
                                paymentResponse.complete('success').then(() => {
                                    window.location.href = w3cPaymentRequest.urls.success;
                                });
                            } else {
                                paymentResponse.complete('fail');
                            }
                        }
                    });
                } else {
                    console.log(err);
                    paymentResponse.complete('fail');
                }
            });
        }
    };
});
