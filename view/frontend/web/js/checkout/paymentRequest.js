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
    'ImaginationMedia_PaymentRequest/js/checkout/payment-type/base',
    'ImaginationMedia_PaymentRequest/js/checkout/shipping',
    'ImaginationMedia_PaymentRequest/js/checkout/payment',
    'ImaginationMedia_PaymentRequest/js/checkout/items'
], function (
    $,
    ko,
    _,
    utils,
    Collapsible,
    $t,
    customerData,
    basePaymentHandler,
    shipping,
    payment,
    items
) {
    'use strict';

    var shippingMethods = [];
    var shippingAddress = [];

    return {
        init: function () {
            var w3cPaymentRequest = customerData.get('payment-request')().paymentRequestApi;
            if (w3cPaymentRequest && this.isAvailable()) {
                /**
                 * Get available payment methods
                 * @type {*|Array}
                 */
                var methodData = payment.getPaymentMethods(w3cPaymentRequest);

                /**
                 * Get items (products, shipping, tax, subtotal)
                 * @type {*|Array|observable}
                 */
                var displayItems = items.getItems(w3cPaymentRequest);

                /**
                 * Load all the available shipping options
                 * @type {Array}
                 */
                var finalShippingOptions = [];

                var quoteTotal = (!w3cPaymentRequest.discount.amount)
                    ? w3cPaymentRequest.quoteTotal
                    : w3cPaymentRequest.quoteTotal + w3cPaymentRequest.discount.amount;

                var details = {
                    displayItems: displayItems,
                    total: {
                        label: $t("Total"),
                        amount: {currency: w3cPaymentRequest.currency, value: quoteTotal},
                    },
                    shippingOptions: finalShippingOptions
                };

                var options = {
                    requestShipping: true,
                    requestPayerEmail: true,
                    requestPayerPhone: true,
                    requestPayerName: true
                };

                var paymentRequest = new PaymentRequest(
                    methodData,
                    details,
                    options
                );

                /**
                 * When shipping address change
                 * @param ev
                 */
                paymentRequest.onshippingaddresschange = ev => {
                    let result = shipping.onAddressChange(ev, w3cPaymentRequest);
                    shippingMethods = result.methods;
                    shippingAddress = result.address;
                };

                /**
                 * When shipping method option change
                 * @param ev
                 */
                paymentRequest.onshippingoptionchange = ev => {
                    shipping.onOptionChange(paymentRequest, ev, w3cPaymentRequest, shippingMethods, shippingAddress);
                };

                /**
                 * Process payment
                 */
                paymentRequest.show().then(function (paymentResponse) {
                    basePaymentHandler.init(paymentRequest, paymentResponse, w3cPaymentRequest);
                });


            } else {
                console.log($t("Payment Request Api data not available."));
            }
        },

        /**
         * Is payment request available for the browser
         */
        isAvailable : function() {
            var sUsrAg = navigator.userAgent;
            if (sUsrAg.indexOf("Chrome") <= -1 && sUsrAg.indexOf("Safari") > -1) { //Safari is not supported but it's also returning true without this condition
                return false;
            } else if (window.PaymentRequest) {
                return true;
            }
            return false;
        }
    };
});
