;(function ($) {
    'use strict';

    $(function () {
        StateManager.addPlugin('.adyen-config', 'adyen-payment-selection');
    });

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenOriginkey: '',
            adyenEnvironment: 'test',
            adyenPaymentMethodsResponse: {},
            adyenPaymentMethodPrefix: 'adyen_',
            /**
             * Selector for the payment method select fields.
             *
             * @type {String}
             */
            paymentMethodSelector: '.payment--method',
            /**
             * Selector for the payment method component wrapper.
             *
             * @type {String}
             */
            methodeBankdataSelector: '.method--bankdata',
            currentSelectedPaymentId: '',
            currentSelectedPaymentType: '',
            getCurrentComponentId: function (currentSelectedPaymentId) {
                return 'component-' + currentSelectedPaymentId;
            }
        },

        adyenConfiguration: {},
        adyenCheckout: null,

        init: function () {
            var me = this;

            me.applyDataAttributes();
            me.eventListeners();
            me.setConfig();
            me.setCheckout();
        },
        eventListeners: function () {
            var me = this;

            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChangedBefore'), $.proxy(me.onPaymentChangedBefore, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.onPaymentChangedAfter, me));
        },
        onPaymentChangedBefore: function ($event) {
            var me = this;

            me.opts.currentSelectedPaymentId = event.target.id;
            me.opts.currentSelectedPaymentType = $(event.target).val();
        },
        onPaymentChangedAfter: function (event) {
            var me = this;
            var payment;

            //Return when no adyen payment
            if (me.opts.currentSelectedPaymentType.indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                return;
            }

            payment = me.getPaymentMethodByType(me.opts.currentSelectedPaymentType);

            //When details is set load the component
            if (typeof payment.details !== "undefined") {
                $('#' + me.opts.currentSelectedPaymentId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodeBankdataSelector)
                    .prop('id', me.opts.getCurrentComponentId(me.opts.currentSelectedPaymentId));

                me.handleComponent(payment.type);
            }
        },
        setConfig: function () {
            var me = this;

            me.adyenConfiguration = {
                locale: "en_US",
                environment: me.opts.adyenEnvironment,
                originKey: me.opts.adyenOriginkey,
                paymentMethodsResponse: me.opts.adyenPaymentMethodsResponse,
                onChange: me.handleOnChange,
            };
        },
        getPaymentMethodByType(type) {
            var me = this;

            var type = type.split(me.opts.adyenPaymentMethodPrefix).pop();

            return me.opts.adyenPaymentMethodsResponse['paymentMethods'].find(function (paymentMethod) {
                return paymentMethod.type === type
            });
        },
        setCheckout: function () {
            var me = this;

            me.adyenCheckout = new AdyenCheckout(me.adyenConfiguration);
        },
        handleComponent: function (type) {
            var me = this;

            me.adyenCheckout.create(type, {}).mount('#' + me.opts.getCurrentComponentId(me.opts.currentSelectedPaymentId));
        },
        handleOnChange: function (state, component) {
        },

    });

})(jQuery);