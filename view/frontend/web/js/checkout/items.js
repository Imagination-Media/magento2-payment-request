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

define([], function () {
    return {
        /**
         * Get items
         * @param w3cPaymentRequest
         * @returns {Array}
         */
        getItems: function(w3cPaymentRequest) {
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

            return displayItems;
        }
    }
});
