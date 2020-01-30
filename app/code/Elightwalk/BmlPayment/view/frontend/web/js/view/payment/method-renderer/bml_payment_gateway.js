/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Elightwalk_BmlPayment/payment/form'
            },

            initObservable: function () {

                this._super()
                return this;
            },

            getCode: function() {
                return 'bml_payment_gateway';
            },

            getLogo: function(){
                return window.checkoutConfig.payment.bml_payment_gateway.paymentlogo
            },
            getData: function() {
                return {
                    'method': this.item.method
                };
            },
            afterPlaceOrder: function () {
                $.mage.redirect(window.checkoutConfig.payment.bml_payment_gateway.redirectUrl);
                return false;
            }
            
        });
    }
);