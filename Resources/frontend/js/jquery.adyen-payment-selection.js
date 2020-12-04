;(function ($) {
    'use strict';

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenOriginkey: '',
            adyenPaymentMethodsResponse: {},
            resetSessionUrl: '',
            /**
             * Fallback environment variable
             *
             * @type {string}
             */
            adyenEnvironment: 'test',
            /**
             * Default shopLocale when no locate is assigned
             *
             * @type {string}
             */
            shopLocale: 'en-US',
            /**
             * Prefix to identify adyen payment methods
             *
             * @type {String}
             */
            adyenPaymentMethodPrefix: 'adyen_',
            /**
             * Selector for the payment form.
             *
             * @type {String}
             */
            formSelector: '#shippingPaymentForm',
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
            paymentMethodFormSubmitSelector: '#shippingPaymentForm button[type=submit]',
            /**
             * @type {string} the group name of Gift card types
             */
            giftCardGroupName: 'Gift Card',
            /**
             * @type {string} Snippets associated with the payment page
             */
            adyenSnippets: {
                updatePaymentInformation: 'Update your payment information'
            }
        },

        currentSelectedPaymentId: '',
        currentSelectedPaymentType: '',
        adyenConfiguration: {},
        adyenCheckout: null,
        changeInfosButton: null,
        paymentMethodSession: 'paymentMethod',
        storePaymentMethodSession: 'storePaymentMethod',
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
        onPaymentFormSubmit: function (e) {
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
            const me = this;

            //Return when no adyen payment
            if (me.currentSelectedPaymentType.indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                me.sessionStorage.removeItem(me.paymentMethodSession);
                return;
            }

            const payment = me.getPaymentMethodByType(me.currentSelectedPaymentType);
            if (me.__canHandlePayment(payment)) {
                $('#' + me.currentSelectedPaymentId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));
                $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');
                me.handleComponent(payment);

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
            const me = this;
            // stored payment methods: pmType contains the id of the payment method
            const pmType = type.split(me.opts.adyenPaymentMethodPrefix).pop();
            const paymentMethod = me.opts.adyenPaymentMethodsResponse['paymentMethods'].find(
                paymentMethod => paymentMethod.type === pmType
            );

            return paymentMethod ? paymentMethod : me.getStoredPaymentMethodById(pmType);
        },
        /**
         * @param {String} storedPaymentMethodId
         * @return {({} | undefined)}
         */
        getStoredPaymentMethodById(storedPaymentMethodId) {
            const me = this;
            const storedPaymentMethods = me.opts.adyenPaymentMethodsResponse['storedPaymentMethods'] || [];

            return storedPaymentMethods.find(
                paymentMethod => paymentMethod.id === storedPaymentMethodId
            );
        },
        /**
         * @param {String} paymentType
         * @param {String} detailKey
         * @return {({} | null)}
         * @private
         */
        __retrievePaymentMethodDetailByKey(paymentType, detailKey) {
            const me = this;
            const paymentMethod = me.getPaymentMethodByType(paymentType) || {};
            const details = (paymentMethod && paymentMethod.details) || [];

            return details.find(detail => detail.key === detailKey);
        },
        setCheckout: function () {
            var me = this;

            me.adyenCheckout = new AdyenCheckout(me.adyenConfiguration);
        },
        handleComponent: function (paymentMethod) {
            const me = this;

            if ('paywithgoogle' === paymentMethod.type) {
                me.handleComponentPayWithGoogle(paymentMethod);
                return;
            }

            const adyenCheckoutData = me.__buildCheckoutComponentData(paymentMethod);
            me.adyenCheckout
                .create(adyenCheckoutData.cardType, adyenCheckoutData.paymentMethodData)
                .mount('#' + me.getCurrentComponentId(me.currentSelectedPaymentId));
        },
        handleComponentPayWithGoogle: function (paymentMethod) {
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

            if (!me.isPaymentMethodValid(paymentMethod)) {
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
                .html(me.opts.adyenSnippets.updatePaymentInformation)
                .on('click', $.proxy(me.updatePaymentInfo, me));
            paymentMethodContainer.find(me.opts.methodBankdataSelector).append(me.changeInfosButton);
        },
        isPaymentMethodValid: function (paymentMethod) {
            const me = this;

            if (!paymentMethod.length) {
                return false;
            }

            //Return when no adyen payment
            if (paymentMethod.val().indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                return false;
            }

            return me.__canHandlePayment(
                me.getPaymentMethodByType(paymentMethod.val())
            );
        },
        updatePaymentInfo: function () {
            var me = this;

            me.removePaymentSession();
            $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');

            var paymentMethod = $(me.opts.formSelector).find('input[name=payment]:checked');
            var payment = me.getPaymentMethodByType(paymentMethod.val());

            if (me.__canHandlePayment(payment)) {
                $('#' + me.currentSelectedPaymentId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));

                me.handleComponent(payment);

                if (me.changeInfosButton) {
                    me.changeInfosButton.remove();
                    me.changeInfosButton = null;
                }
            }
        },
        setPaymentSession: function (state) {
            const me = this;
            me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
            me.sessionStorage.setItem(me.storePaymentMethodSession, state.data.storePaymentMethod || false);
        },
        removePaymentSession: function () {
            const me = this;
            me.sessionStorage.removeItem(me.paymentMethodSession);
            me.sessionStorage.removeItem(me.storePaymentMethodSession);
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
        /**
         * @param {string} paymentType
         * @return {boolean}
         * @private
         */
        __isGiftCardType(paymentType) {
            const paymentGroups = this.opts.adyenPaymentMethodsResponse['groups'] || [];
            const giftCardGroup = paymentGroups.find(
                group => this.defaults.giftCardGroupName === group['name']
            );
            const giftCardGroupTypes = (giftCardGroup && giftCardGroup['types']) || [];

            return giftCardGroupTypes.includes(paymentType);
        },
        /**
         * paymentType contains the id for a stored payment
         * @param {string} paymentMethodId
         * @return {boolean}
         * @private
         */
        __isStoredPaymentMethod(paymentMethodId) {
            return !!this.getStoredPaymentMethodById(paymentMethodId);
        },
        __canHandlePayment(paymentMethod) {
            if (this.__isStoredPaymentMethod(paymentMethod.id || '')) {
                return true;
            }

            return "undefined" !== typeof paymentMethod.details;
        },
        /**
         * Modify AdyenPaymentMethod with additional data for the web-component library
         * @param paymentMethod Adyen response: Payment Method response
         * @return  {{cardType: string, paymentMethodData: object}}
         * @private
         */
        __buildCheckoutComponentData(paymentMethod) {
            const defaultData = {
                cardType: paymentMethod.type,
                paymentMethodData: {
                    ...paymentMethod,
                }
            };

            if (this.__isStoredPaymentMethod(paymentMethod.id || '')) {
                return {
                    ...defaultData,
                    paymentMethodData: {
                        ...defaultData.paymentMethodData,
                        storedPaymentMethodId: paymentMethod.id,
                    }
                };
            }

            if (this.__isGiftCardType(paymentMethod)) {
                const pinRequiredDetail = this.__retrievePaymentMethodDetailByKey(
                    paymentMethod.type,
                    'encryptedSecurityCode'
                );

                return {
                    ...defaultData,
                    cardType: 'giftcard',
                    paymentMethodData: {
                        ...defaultData.paymentMethodData,
                        type: paymentMethod.type,
                        pinRequired: false === pinRequiredDetail.optional || false
                    }
                };
            }

            return {
                ...defaultData,
                paymentMethodData: {
                    ...defaultData.paymentMethodData,
                    enableStoreDetails: paymentMethod.supportsRecurring || false,
                }
            };
        },
    });

})(jQuery);
