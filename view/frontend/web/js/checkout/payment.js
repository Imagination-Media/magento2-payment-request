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
    'use strict';

    return {
        /**
         * Get all available payment methods
         * @param w3cPaymentRequest
         * @returns {Array}
         */
        getPaymentMethods : function(w3cPaymentRequest) {
            var methodData = [];
            /**
             * Check if credit/debit cards is available
             */
            if (w3cPaymentRequest.cardConfig.enabled) {
                var networks = w3cPaymentRequest.cardConfig.flags;
                var types = w3cPaymentRequest.cardConfig.types;

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

            return methodData;
        }
    };
});
