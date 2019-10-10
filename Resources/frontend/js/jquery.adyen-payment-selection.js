;(function ($) {
    'use strict';

    $(function () {
        StateManager.addPlugin('.adyen-payment-selection', 'adyen-payment-selection');
    });

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenOriginkey: '',
            adyenEnvironment: 'test',
            adyenPaymentMethodsResponse: {},
        },

        /**
         * Prefix to identify adyen payment methods
         *
         * @type {String}
         */
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
        adyenConfiguration: {},
        adyenCheckout: null,

        init: function () {
            var me = this;

            me.sessionStorage = StorageManager.getStorage('session');

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

            me.currentSelectedPaymentId = event.target.id;
            me.currentSelectedPaymentType = $(event.target).val();
        },
        onPaymentChangedAfter: function (event) {
            var me = this;
            var payment;

            //Return when no adyen payment
            if (me.currentSelectedPaymentType.indexOf(me.adyenPaymentMethodPrefix) === -1) {
                return;
            }

            payment = me.getPaymentMethodByType(me.currentSelectedPaymentType);

            //When details is set load the component
            if (typeof payment.details !== "undefined") {
                $('#' + me.currentSelectedPaymentId)
                    .closest(me.paymentMethodSelector)
                    .find(me.methodeBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));

                me.handleComponent(payment.type);
            }
        },
        setConfig: function () {
            var me = this;

            me.adyenConfiguration = {
                locale: "en_US", //todo: make locale dynamic
                environment: me.opts.adyenEnvironment,
                originKey: me.opts.adyenOriginkey,
                paymentMethodsResponse: me.opts.adyenPaymentMethodsResponse,
                onChange: $.proxy(me.handleOnChange, me),
            };
        },
        getCurrentComponentId: function (currentSelectedPaymentId) {
            return 'component-' + currentSelectedPaymentId;
        },
        getPaymentMethodByType(type) {
            var me = this;

            var type = type.split(me.adyenPaymentMethodPrefix).pop();

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

            me.adyenCheckout.create(type, {}).mount('#' + me.getCurrentComponentId(me.currentSelectedPaymentId));
        },
        handleOnChange: function (state) {
            var me = this;

            if (state.isValid && state.data && state.data.paymentMethod) {
                me.setPayment(state);
            }
        },
        setPayment: function (state) {
            var me = this;

            me.sessionStorage.setItem('paymentMethod', JSON.stringify(state.data));
        },
    });

})(jQuery);