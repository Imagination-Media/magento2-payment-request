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
    'ImaginationMedia_PaymentRequest/js/checkout/totals',
    'jquery',
    'mage/translate'
], function (
    totals,
    $,
    $t
) {
    'use strict';

    return {
        /**
         * On shipping address change
         * @param ev
         * @param w3cPaymentRequest
         * @returns {{address: any, methods: Array}}
         */
        onAddressChange : function(ev, w3cPaymentRequest) {
            /**
             * Get shipping rates from Magento
             */
            var localShippingAddress = {
                region_id : null,
                region : ev.currentTarget.shippingAddress.region,
                country_id : ev.currentTarget.shippingAddress.country,
                postcode : ev.currentTarget.shippingAddress.postalCode
            };

            var shippingAddress = JSON.parse(JSON.stringify(ev.currentTarget.shippingAddress));

            var shippingMethods = [];
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
                    var values = Object.values(rates);
                    for (var key in values) {
                        var rate = values[key];
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
            var paymentDetails = {
                shippingOptions: shippingMethods,
            };
            ev.updateWith(paymentDetails);

            return {
                'address' : shippingAddress,
                'methods' : shippingMethods
            };
        },

        /**
         * On shipping option change
         * @param paymentRequest
         * @param ev
         * @param w3cPaymentRequest
         * @param shippingMethods
         * @param shippingAddress
         */
        onOptionChange: function(paymentRequest, ev, w3cPaymentRequest, shippingMethods, shippingAddress) {
            var { shippingOption } = paymentRequest;
            var totalsResult = totals.calculateTotals(w3cPaymentRequest, shippingOption, shippingAddress, shippingMethods);
            ev.updateWith(totalsResult);
        }
    };
});
