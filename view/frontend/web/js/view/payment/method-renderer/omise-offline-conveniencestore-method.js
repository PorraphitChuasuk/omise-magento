define(
    [
        'ko',
        'Omise_Payment/js/view/payment/omise-base-method-renderer',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        ko,
        Base,
        Component,
        redirectOnSuccessAction,
        quote
    ) {
        'use strict';

        return Component.extend(Base).extend({
            defaults: {
                template: 'Omise_Payment/payment/offline-conveniencestore-form'
            },

            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),

            code: 'omise_offline_conveniencestore',
            restrictedToCurrencies: ['jpy'],

            /**
             * Hook the placeOrder function.
             * Original source: placeOrder(data, event); @ module-checkout/view/frontend/web/js/view/payment/default.js
             *
             * @return {boolean}
             */
            placeOrder: function (data, event) {
                var failHandler = this.buildFailHandler(this);

                event && event.preventDefault();

                this.getPlaceOrderDeferredObject()
                    .fail(failHandler)
                    .done(function () {
                        redirectOnSuccessAction.execute();
                    });

                return true;
            },

            /**
            * Get a checkout form data
            *
            * @return {Object}
            */
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'conv_store_phone_number': this.convenienceStorePhoneNumber(),
                        'conv_store_email': this.convenienceStoreEmail(),
                        'conv_store_customer_name': this.convenienceStoreCustomersName()
                    }
                };
            },

            /**
             * Hook the validate function.
             * Original source: validate(); @ module-checkout/view/frontend/web/js/view/payment/default.js
             *
             * @return {boolean}
             */
            validate: function () {
                /**
                 * Initiate observable fields
                 *
                 * @return this
                 */
                $('#' + this.getCode() + 'Form').validation();

                var isCustomerNameValid = $('#' + this.getCode() + 'CustomerName').valid();
                var isEmailValid = $('#' + this.getCode() + 'Email').valid();
                var isPhoneNumberValid = $('#' + this.getCode() + 'PhoneNumber').valid();

                return isPhoneNumberValid && isCustomerNameValid && isEmailValid;
            },

            initObservable: function () {
                this._super()
                    .observe([
                        'convenienceStorePhoneNumber',
                        'convenienceStoreEmail',
                        'convenienceStoreCustomersName'
                    ]);
                return this;
            },
        });
    }
);