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
    'jquery',
    'ko',
    'underscore',
    'mageUtils',
    'Magento_Ui/js/lib/collapsible',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'https://js.braintreegateway.com/js/braintree-2.32.0.min.js'
], function (
    $,
    ko,
    _,
    utils,
    Collapsible,
    $t,
    customerData,
    braintree
) {
    'use strict';

    let shippingMethods = [];
    let shippingAddress = [];

    let capitalize = (s) => {
        if (typeof s !== 'string') return ''
        return s.charAt(0).toUpperCase() + s.slice(1)
    };

    let calculateTotals = function (w3cPaymentRequest, shippingMethod) {
        let availableTotals = [
            "shipping",
            "tax",
            "discount"
        ];

        let result = {};

        $.ajax({
            url: w3cPaymentRequest.urls.totals,
            action: 'POST',
            cache: false,
            async: false,
            data: {
                "shippingMethod" : JSON.parse(shippingMethod),
                "shippingAddress" : shippingAddress,
                "quoteId" : w3cPaymentRequest.quoteId
            },
            success: function (response) {
                if (response.error === "") {
                    let total = {};
                    let displayItems = [];

                    /**
                     * Add products
                     */
                    for (let sku in w3cPaymentRequest.cartItems) {
                        let cartItem = w3cPaymentRequest.cartItems[sku];
                        displayItems.push({
                            label: cartItem.label,
                            amount: {
                                currency: w3cPaymentRequest.currency,
                                value: cartItem.price
                            }
                        });
                    }

                    /**
                     * Add shipping, tax, discount and total
                     */
                    for (let key in response.totals) {
                        let totalItem = response.totals[key];
                        if (key === "discount") {
                            displayItems.push({
                                label: totalItem.label,
                                amount: {currency: w3cPaymentRequest.currency, value: totalItem.amount},
                                type: key
                            });
                        } else if (key === "grand_total") {
                            total = {
                                label: $t("Total"),
                                amount: {currency: w3cPaymentRequest.currency, value: totalItem}
                            };
                        } else if (availableTotals.includes(key)) {
                            displayItems.push({
                                label: $t(capitalize(key)),
                                amount: {currency: w3cPaymentRequest.currency, value: totalItem},
                                type: key
                            });
                        }
                    }

                    /**
                     * Add shipping options
                     */
                    for (let key in shippingMethods) {
                        if (shippingMethods[key].id === shippingMethod) {
                            shippingMethods[key].selected = true;
                        } else {
                            shippingMethods[key].selected = false;
                        }
                    }

                    result = {
                        displayItems: displayItems,
                        total: total,
                        shippingOptions: shippingMethods
                    };
                } else {
                    console.log($t("Error getting the totals."));
                    console.log(response.error);
                }
            }
        });

        return result;
    };

    return {
        init: function () {
            let w3cPaymentRequest = customerData.get('payment-request')().paymentRequestApi;
            if (w3cPaymentRequest && this.isAvailable()) {
                let methodData = [];

                /**
                 * Check if credit/debit cards is available
                 */
                if (w3cPaymentRequest.cardConfig.enabled) {
                    let networks = w3cPaymentRequest.cardConfig.flags;
                    let types = w3cPaymentRequest.cardConfig.types;

                    /**
                     * Check if pre paid cards are enabled
                     **/
                    if (w3cPaymentRequest.cardConfig.prePaid) {
                        types.push("prepaid");
                    }

                    methodData.push({
                        supportedMethods: 'basic-card',
                        data: {
                            supportedNetworks: networks,
                            supportedTypes: types
                        },
                        sortOrder: w3cPaymentRequest.cardConfig.sortOrder
                    });
                }

                /**
                 * Check if PayPal express is available
                 */
                if (w3cPaymentRequest.paypalConfig.enabled) {
                    methodData.push({
                        supportedMethods: "https://innovations.imaginationmedia.com/paypal/",
                        sortOrder: w3cPaymentRequest.paypalConfig.sortOrder
                    });
                }

                /**
                 * Sort order payment methods
                 */
                methodData.sort((a, b) => (a.sortOrder > b.sortOrder) ? 1 : -1);

                let displayItems = [];

                /**
                 * Add products
                 */
                for (let sku in w3cPaymentRequest.cartItems) {
                    let cartItem = w3cPaymentRequest.cartItems[sku];
                    displayItems.push({
                        label: cartItem.label,
                        amount: {
                            currency: w3cPaymentRequest.currency,
                            value: cartItem.price
                        }
                    });
                }

                /**
                 * Add discount by coupon code
                 */
                if (w3cPaymentRequest.discount.amount) {
                    displayItems.push({
                        label: w3cPaymentRequest.discount.label,
                        amount: {
                            currency: w3cPaymentRequest.currency,
                            value: w3cPaymentRequest.discount.amount
                        }
                    });
                }

                /**
                 * Load all the available shipping options
                 * @type {Array}
                 */
                let finalShippingOptions = [];

                let quoteTotal = (!w3cPaymentRequest.discount.amount)
                    ? w3cPaymentRequest.quoteTotal
                    : w3cPaymentRequest.quoteTotal + w3cPaymentRequest.discount.amount;

                let details = {
                    displayItems: displayItems,
                    total: {
                        label: $t("Total"),
                        amount: {currency: w3cPaymentRequest.currency, value: quoteTotal},
                    },
                    shippingOptions: finalShippingOptions
                };

                let options = {
                    requestShipping: true,
                    requestPayerEmail: true,
                    requestPayerPhone: true,
                    requestPayerName: true
                };

                let paymentRequest = new PaymentRequest(
                    methodData,
                    details,
                    options
                );

                paymentRequest.onshippingaddresschange = ev => {
                    /**
                     * Get shipping rates from Magento
                     */
                    let localShippingAddress = {
                        region_id : null,
                        region : ev.currentTarget.shippingAddress.region,
                        country_id : ev.currentTarget.shippingAddress.country,
                        postcode : ev.currentTarget.shippingAddress.postalCode
                    };

                    shippingAddress = JSON.parse(JSON.stringify(ev.currentTarget.shippingAddress));

                    shippingMethods = [];
                    $.ajax({
                        type: "POST",
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: (w3cPaymentRequest.customerId !== 0)
                            ? w3cPaymentRequest.urls.estimateShipping.customer
                            : w3cPaymentRequest.urls.estimateShipping.guest,
                        cache: false,
                        data: JSON.stringify({
                            address : localShippingAddress
                        }),
                        async: false,
                        success: function (rates) {
                            for (let key in rates) {
                                let rate = rates[key];
                                shippingMethods.push({
                                    id: JSON.stringify({
                                        carrier_code: rate.carrier_code,
                                        method_code: rate.method_code
                                    }),
                                    label: rate.carrier_title + " - " + rate.method_title,
                                    amount: {
                                        currency: w3cPaymentRequest.currency, value: rate.price_incl_tax
                                    },
                                    selected: false
                                });
                            }
                        },
                        error: function(request, status, error) {
                            console.log($t("Error updating the shipping rates"));
                        }
                    });
                    const paymentDetails = {
                        shippingOptions: shippingMethods,
                    };
                    ev.updateWith(paymentDetails);
                };

                paymentRequest.onshippingoptionchange = ev => {
                    const { shippingOption } = paymentRequest;
                    let totals = calculateTotals(w3cPaymentRequest, shippingOption);
                    ev.updateWith(totals);
                };

                paymentRequest.show().then(function (paymentResponse) {
                    let details = paymentResponse.details;

                    if (!paymentResponse.methodName) {
                        console.log($t("Not valid response"));
                        paymentResponse.complete('fail');
                    }

                    /**
                     * Check if payment is debit/credit card
                     */
                    if (paymentResponse.methodName === "basic-card") {
                        let cardPaymentMethod = w3cPaymentRequest.cardConfig.paymentMethod;

                        /**
                         * Process as Braintree
                         */
                        if (cardPaymentMethod === "braintree") {
                            /**
                             * Validate client token
                             */
                            if (!w3cPaymentRequest.cardConfig.hasOwnProperty("additionalInfo") ||
                                !w3cPaymentRequest.cardConfig.additionalInfo.hasOwnProperty("clientToken")) {
                                console.log($t("No client Braintree key was provided."));
                                paymentResponse.complete('fail');
                            }

                            let client = new braintree.api.Client({clientToken: w3cPaymentRequest.cardConfig.additionalInfo.clientToken});
                            client.tokenizeCard({
                                number: details.cardNumber,
                                cardholderName: details.cardholderName,
                                expirationMonth: details.expiryMonth,
                                expirationYear: details.expiryYear,
                                cvv: details.cardSecurityCode,
                                billingAddress: details.billingAddress
                            }, function (err, nonce) {
                                if (!err) {
                                    let params = {
                                        paymentMethod: "braintree",
                                        token: nonce,
                                        quoteId: w3cPaymentRequest.quoteId,
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
                    } else if (cardPaymentMethod === "paypal") {
                        /**
                         * PayPal clicked
                         */
                    }
                });
            } else {
                console.log($t("Payment Request Api data not available."));
            }
        },

        /**
         * Is payment request available for the browser
         */
        isAvailable : function() {
            let sUsrAg = navigator.userAgent;
            if (sUsrAg.indexOf("Chrome") <= -1 && sUsrAg.indexOf("Safari") > -1) { //Safari is not supported but it's also returning true without this condition
                return false;
            } else if (window.PaymentRequest) {
                return true;
            }
            return false;
        }
    };
});
