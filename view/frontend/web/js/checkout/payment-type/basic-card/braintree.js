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
    'braintreeClientV2',
    'mage/translate',
    'jquery'
], function (braintreeClient, $t, $) {
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

            var customerFullName = details.billingAddress.recipient;
            var names = customerFullName.split(" ");
            var finalData = {
                number: details.cardNumber,
                cardholderName: details.cardholderName,
                expirationMonth: details.expiryMonth,
                expirationYear: details.expiryYear,
                cvv: details.cardSecurityCode,
                billingAddress: {
                    firstName : names[0],
                    lastName : names.slice(-1)[0],
                    company : details.billingAddress.organization,
                    streetAddress : Object.values(details.billingAddress.addressLine).length > 0
                        ? details.billingAddress.addressLine[0] : '',
                    extendedAddress : Object.values(details.billingAddress.addressLine).length > 1
                        ? details.billingAddress.addressLine[1] : '',
                    locality : details.billingAddress.city,
                    region : details.billingAddress.region,
                    postalCode : details.billingAddress.postalCode,
                    countryCodeAlpha2 : details.billingAddress.country
                }
            };
            var client = new braintreeClient.api.Client({clientToken: w3cPaymentRequest.cardConfig.additionalInfo.clientToken});
            client.tokenizeCard(finalData, function (err, nonce) {
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
                    console.log(err.toString());
                    console.log(nonce);
                    paymentResponse.complete('fail');
                }
            });
        }
    };
});
