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
    'Magento_Customer/js/model/authentication-popup',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'underscore',
    'ImaginationMedia_PaymentRequest/js/checkout/paymentRequest',
    'jquery/ui',
    'mage/decorate',
    'mage/collapsible',
    'mage/cookies'
], function ($, authenticationPopup, customerData, alert, confirm, _, paymentRequest) {
    'use strict';

    var widgetMixin = {
        /**
         * @private
         */
        _initContent: function () {
            var self = this,
                events = {};

            this.element.decorate('list', this.options.isRecursive);

            /**
             * @param {jQuery.Event} event
             */
            events['click ' + this.options.button.close] = function (event) {
                event.stopPropagation();
                $(self.options.targetElement).dropdownDialog('close');
            };
            events['click ' + this.options.button.checkout] = $.proxy(function () {
                var cart = customerData.get('cart'),
                    customer = customerData.get('customer'),
                    element = $(this.options.button.checkout);

                if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
                    // set URL for redirect on successful login/registration. It's postprocessed on backend.
                    $.cookie('login_redirect', this.options.url.checkout);

                    if (this.options.url.isRedirectRequired) {
                        element.prop('disabled', true);
                        location.href = this.options.url.loginUrl;
                    } else {
                        authenticationPopup.showModal();
                    }

                    return false;
                }

                let paymentRequestApi = customerData.get('payment-request')().paymentRequestApi;
                if (paymentRequestApi &&
                    paymentRequestApi.enabled &&
                    paymentRequestApi.buttonMode === 1 &&
                    paymentRequest.isAvailable()) {
                    paymentRequest.init();
                } else {
                    element.prop('disabled', true);
                    location.href = this.options.url.checkout;
                }

            }, this);

            /**
             * @param {jQuery.Event} event
             */
            events['click ' + this.options.button.remove] =  function (event) {
                event.stopPropagation();
                confirm({
                    content: self.options.confirmMessage,
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            self._removeItem($(event.currentTarget));
                        },

                        /** @inheritdoc */
                        always: function (e) {
                            e.stopImmediatePropagation();
                        }
                    }
                });
            };

            /**
             * @param {jQuery.Event} event
             */
            events['keyup ' + this.options.item.qty] = function (event) {
                self._showItemButton($(event.target));
            };

            /**
             * @param {jQuery.Event} event
             */
            events['change ' + this.options.item.qty] = function (event) {
                self._showItemButton($(event.target));
            };

            /**
             * @param {jQuery.Event} event
             */
            events['click ' + this.options.item.button] = function (event) {
                event.stopPropagation();
                self._updateItemQty($(event.currentTarget));
            };

            /**
             * @param {jQuery.Event} event
             */
            events['focusout ' + this.options.item.qty] = function (event) {
                self._validateQty($(event.currentTarget));
            };

            this._on(this.element, events);
            this._calcHeight();
            this._isOverflowed();
        }
    };

    return function (targetWidget) {
        $.widget('mage.sidebar', targetWidget, widgetMixin);
        return $.mage.sidebar;
    };
});
