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
    'mage/translate'
], function (
    $,
    $t
) {
    'use strict';

    return {
        calculateTotals: function (w3cPaymentRequest, shippingMethod, shippingAddress, shippingMethods) {
            var availableTotals = [
                "shipping",
                "tax",
                "discount"
            ];

            var result = {};

            $.ajax({
                url: w3cPaymentRequest.urls.totals,
                action: 'POST',
                cache: false,
                async: false,
                data: {
                    "shippingMethod" : JSON.parse(shippingMethod),
                    "shippingAddress" : shippingAddress
                },
                success: function (response) {
                    if (response.error === "") {
                        var total = {};
                        var displayItems = [];

                        /**
                         * Add products
                         */
                        var values = Object.values(w3cPaymentRequest.cartItems);
                        for (var sku in values) {
                            var cartItem = values[sku];
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
                        var keys = Object.keys(response.totals);
                        values = Object.values(response.totals);
                        for (var key in values) {
                            var totalItem = values[key];
                            if (keys[key] === "discount") {
                                displayItems.push({
                                    label: totalItem.label,
                                    amount: {currency: w3cPaymentRequest.currency, value: totalItem.amount},
                                    type: key
                                });
                            } else if (keys[key] === "grand_total") {
                                total = {
                                    label: $t("Total"),
                                    amount: {currency: w3cPaymentRequest.currency, value: totalItem}
                                };
                            } else if (availableTotals.includes(key)) {
                                displayItems.push({
                                    label: $t(key),
                                    amount: {currency: w3cPaymentRequest.currency, value: totalItem},
                                    type: key
                                });
                            }
                        }

                        /**
                         * Add shipping options
                         */
                        values = Object.values(shippingMethods);
                        for (var key in Object.values(shippingMethods)) {
                            if (values[key].id === shippingMethod) {
                                values[key].selected = true;
                            } else {
                                values[key].selected = false;
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
        }
    }
});
