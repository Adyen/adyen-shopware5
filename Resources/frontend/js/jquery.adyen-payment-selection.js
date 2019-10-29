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
            formSelector: '#shippingPaymentForm',
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
             * Selector for the payment method label wrapper.
             *
             * @type {String}
             */
            methodLabelSelector: '.method--label',
            /**
             * Selector for the payment method component wrapper.
             *
             * @type {String}
             */
            methodeBankdataSelector: '.method--bankdata',
            /**
             * Classname for 'Update Payment informations' button
             */
            classChangePaymentInfo: 'method--change-info',
        },

        currentSelectedPaymentId: '',
        currentSelectedPaymentType: '',
        adyenConfiguration: {},
        adyenCheckout: null,
        changeInfosButton: null,
        paymentMethodSession: 'paymentMethod',
        adyenConfigSession: 'adyenConfig',

        init: function () {
            var me = this;

            me.sessionStorage = StorageManager.getStorage('session');

            me.applyDataAttributes();
            me.eventListeners();
            me.setConfig();
            me.setCheckout();
            me.handleSelectedMethod();
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
            if (me.currentSelectedPaymentType.indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                me.sessionStorage.removeItem(me.paymentMethodSession);
                return;
            }

            payment = me.getPaymentMethodByType(me.currentSelectedPaymentType);

            //When details is set load the component
            if (typeof payment.details !== "undefined") {
                $('#' + me.currentSelectedPaymentId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodeBankdataSelector)
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

            me.saveAdyenConfigInSession(me.adyenConfiguration);
        },
        getCurrentComponentId: function (currentSelectedPaymentId) {
            return 'component-' + currentSelectedPaymentId;
        },
        getPaymentMethodByType(type) {
            var me = this;

            type = type.split(me.opts.adyenPaymentMethodPrefix).pop();
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

            console.log(me.currentSelectedPaymentId);
            me.adyenCheckout.create(type, {}).mount('#' + me.getCurrentComponentId(me.currentSelectedPaymentId));
        },
        handleOnChange: function (state) {
            var me = this;

            if (state.isValid && state.data && state.data.paymentMethod) {
                me.setPayment(state);
            }

            if (me.changeInfosButton) {
                me.changeInfosButton.remove();
                me.changeInfosButton = null;
            }
        },
        handleSelectedMethod: function () {
            var me = this;

            var form = $(me.opts.formSelector);
            var paymentMethod = form.find('input[name=payment]:checked');
            var paymentMethodContainer = form.find('input[name=payment]:checked').closest(me.opts.paymentMethodSelector);

            //Return when no adyen payment
            if (paymentMethod.val().indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                return;
            }

            me.currentSelectedPaymentId = paymentMethod.attr('id');
            me.currentSelectedPaymentType = paymentMethod.val();

            console.log(me.currentSelectedPaymentId);
            me.changeInfosButton = $('<a/>')
                .addClass(me.opts.classChangePaymentInfo)
                .html('Update your payment information')
                .on('click', $.proxy(me.updatePaymentInfo, me));
            paymentMethodContainer.find(me.opts.methodLabelSelector).append(me.changeInfosButton);
        },
        updatePaymentInfo: function () {
            var me = this;

            var paymentMethod = $(me.opts.formSelector).find('input[name=payment]:checked');
            var payment = me.getPaymentMethodByType(paymentMethod.val());

            //When details is set load the component
            if (typeof payment.details !== "undefined") {
                $('#' + me.currentSelectedPaymentId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodeBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));

                me.handleComponent(payment.type);

                if (me.changeInfosButton) {
                    me.changeInfosButton.remove();
                    me.changeInfosButton = null;
                }
            }
        },
        setPayment: function (state) {
            var me = this;

            me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
        },
        saveAdyenConfigInSession: function (adyenConfiguration) {
            var me = this;

            var data = {
                locale: adyenConfiguration.locale,
                environment: adyenConfiguration.environment,
                originKey: adyenConfiguration.originKey,
                paymentMethodsResponse: adyenConfiguration.paymentMethodsResponse
            };

            me.sessionStorage.setItem(me.adyenConfigSession, JSON.stringify(data));
        },
    });

})(jQuery);