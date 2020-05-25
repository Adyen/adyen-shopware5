;(function ($) {
    'use strict';

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            shopLocale: 'en-US',
            adyenOriginkey: '',
            adyenEnvironment: 'test',
            adyenPaymentMethodsResponse: {},
            formSelector: '#shippingPaymentForm',
            resetSessionUrl: '',
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
            methodBankdataSelector: '.method--bankdata',
            /**
             * Classname for 'Update Payment informations' button
             */
            classChangePaymentInfo: 'method--change-info',
            /**
             * Selector for the payment method form submit button element.
             *
             * @type {String}
             */
            paymentMethodFormSubmitSelector: 'button[type=submit]',
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

            $(document).on('submit', me.opts.formSelector, $.proxy(me.onPaymentFormSubmit, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChangedBefore'), $.proxy(me.onPaymentChangedBefore, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.onPaymentChangedAfter, me));
        },
        onPaymentFormSubmit: function(e) {
            var me = this;
            if ($(me.opts.paymentMethodFormSubmitSelector).hasClass('is--disabled')) {
                e.preventDefault();
                return false;
            }
        },
        onPaymentChangedBefore: function ($event) {
            var me = this;

            me.currentSelectedPaymentId = event.target.id;
            me.currentSelectedPaymentType = $(event.target).val();
        },
        onPaymentChangedAfter: function () {
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
                    .find(me.opts.methodBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));

                $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');
                me.handleComponent(payment.type);

                return;
            }

            me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(payment));
        },
        setConfig: function () {
            var me = this;

            me.adyenConfiguration = {
                locale: me.opts.shopLocale,
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

            switch (type) {
                case 'paywithgoogle':
                    me.handleComponentPayWithGoogle(type);
                    break;
                default:
                    me.handleComponentGeneral(type);
                    break;
            }
        },
        handleComponentGeneral: function(type) {
            var me = this;
            me.adyenCheckout.create(type, {}).mount('#' + me.getCurrentComponentId(me.currentSelectedPaymentId));
        },
        handleComponentPayWithGoogle: function(type) {
            var me = this;
            $(me.opts.paymentMethodFormSubmitSelector).removeClass('is--disabled');
        },
        handleOnChange: function (state) {
            var me = this;

            if (state.isValid) {
                $(me.opts.paymentMethodFormSubmitSelector).removeClass('is--disabled');
            } else {
                $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');
            }

            if (state.isValid && state.data && state.data.paymentMethod) {
                me.setPaymentSession(state);
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

            if(!me.isPaymentMethodValid(paymentMethod)){
                return;
            }

            //Return when redirect
            var payment = me.getPaymentMethodByType(paymentMethod.val());
            if (typeof payment.details === "undefined") {
                return;
            }

            me.currentSelectedPaymentId = paymentMethod.attr('id');
            me.currentSelectedPaymentType = paymentMethod.val();

            // Return when no data has been entered yet + see if component is needed
            if (!me.sessionStorage.getItem(me.paymentMethodSession) ||
                me.sessionStorage.getItem(me.paymentMethodSession) === "{}") {
                me.onPaymentChangedAfter();
                return;
            }

            me.changeInfosButton = $('<a/>')
                .addClass(me.opts.classChangePaymentInfo)
                .html('Update your payment information')
                .on('click', $.proxy(me.updatePaymentInfo, me));
            paymentMethodContainer.find(me.opts.methodLabelSelector).append(me.changeInfosButton);
        },
        isPaymentMethodValid: function (paymentMethod) {
            var me = this;

            if (!paymentMethod.length) {
                return false;
            }

            //Return when no adyen payment
            if (paymentMethod.val().indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                return false;
            }

            //Return when redirect
            if (typeof me.getPaymentMethodByType(paymentMethod.val()).details === "undefined") {
                return false;
            }

            return true;
        },
        updatePaymentInfo: function () {
            var me = this;

            me.removePaymentSession();
            $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');

            var paymentMethod = $(me.opts.formSelector).find('input[name=payment]:checked');
            var payment = me.getPaymentMethodByType(paymentMethod.val());

            //When details is set load the component
            if (typeof payment.details !== "undefined") {
                $('#' + me.currentSelectedPaymentId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));

                me.handleComponent(payment.type);

                if (me.changeInfosButton) {
                    me.changeInfosButton.remove();
                    me.changeInfosButton = null;
                }
            }
        },
        setPaymentSession: function (state) {
            var me = this;
            me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
        },
        removePaymentSession: function () {
            var me = this;

            me.sessionStorage.removeItem(me.paymentMethodSession);
            $.get(me.opts.resetSessionUrl);
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