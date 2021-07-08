;(function ($) {
    'use strict';

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenClientKey: '',
            enrichedPaymentMethods: {},
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
            paymentMethodFormSubmitSelector: '#shippingPaymentForm button[type=submit], button[form="shippingPaymentForm"]',
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

        selectedPaymentElementId: '',
        selectedPaymentId: '',
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
        isPaymentElement: function (elementId) {
            return $('#' + elementId).parents(this.opts.paymentMethodSelector).length > 0;
        },
        onPaymentChangedBefore: function ($event) {
            var me = this;
            var selectedPaymentElementId = event.target.id;

            // only update when switching payment-methods (not on shipping methods)
            if (!me.isPaymentElement(selectedPaymentElementId)) {
                return;
            }

            me.selectedPaymentElementId = selectedPaymentElementId;
            me.selectedPaymentId = $(event.target).val();
        },
        onPaymentChangedAfter: function () {
            var me = this;

            // Return & clear when no adyen payment
            var payment = me.getPaymentMethodById(me.selectedPaymentId);

            if (!me.__isAdyenPaymentMethod(payment)) {
                me.clearPaymentSession();

                return;
            }

            if (!me.__canHandlePayment(payment)) {
                me.setPaymentSession(me.__buildMinimalState(payment));
                return;
            }

            if (me.__hasActivePaymentMethod()) {
                me.enableUpdatePaymentInfoButton();
                return;
            }

            $('#' + me.selectedPaymentElementId)
                .closest(me.opts.paymentMethodSelector)
                .find(me.opts.methodBankdataSelector)
                .prop('id', me.getCurrentComponentId(me.selectedPaymentElementId));
            $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');
            me.handleComponent(payment);
        },
        setConfig: function () {
            var me = this;

            var adyenPaymentMethodsResponseConfig = Object.values(me.opts.enrichedPaymentMethods).reduce(
                function (rawAdyen, enrichedPaymentMethod) {
                    var isAdyenPaymentMethod = enrichedPaymentMethod.isAdyenPaymentMethod || false;
                    if (true === isAdyenPaymentMethod) {
                        rawAdyen.push(enrichedPaymentMethod.metadata);
                    }

                    return rawAdyen;
                },
                []
            );

            me.adyenConfiguration = {
                locale: me.opts.shopLocale,
                environment: me.opts.adyenEnvironment,
                clientKey: me.opts.adyenClientKey,
                paymentMethodsResponse: Object.assign({}, adyenPaymentMethodsResponseConfig),
                onChange: $.proxy(me.handleOnChange, me)
            };

            me.saveAdyenConfigInSession(me.adyenConfiguration);
        },
        getCurrentComponentId: function (selectedPaymentElementId) {
            return 'component-' + selectedPaymentElementId;
        },
        getPaymentMethodById: function (id) {
            var me = this;
            var paymentMethod = me.opts.enrichedPaymentMethods[id] || {};

            return paymentMethod;
        },
        /**
         * @param {object} paymentMethod
         * @param {String} detailKey
         * @return {({} | null)}
         * @private
         */
        __retrievePaymentMethodDetailByKey: function (paymentMethod, detailKey) {
            var me = this;
            var details = (paymentMethod && paymentMethod.metadata && paymentMethod.metadata.details) || [];
            var filteredDetails = details.filter(function (detail) {
                return detail.key === detailKey
            });

            return filteredDetails[0] || null;
        },
        setCheckout: function () {
            var me = this;

            me.adyenCheckout = new AdyenCheckout(me.adyenConfiguration);
        },
        handleComponent: function (paymentMethod) {
            var me = this;

            if ('paywithgoogle' === paymentMethod.adyenType) {
                me.handleComponentPayWithGoogle();
                return;
            }

            var adyenCheckoutData = me.__buildCheckoutComponentData(paymentMethod);
            me.adyenCheckout
                .create(adyenCheckoutData.cardType, adyenCheckoutData.paymentMethodData)
                .mount('#' + me.getCurrentComponentId(me.selectedPaymentElementId));
        },
        handleComponentPayWithGoogle: function () {
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
            var paymentMethodElement = form.find('input[name=payment]:checked');

            if (!me.isPaymentMethodValid(paymentMethodElement)) {
                return;
            }

            me.selectedPaymentElementId = paymentMethodElement.attr('id');
            me.selectedPaymentId = paymentMethodElement.val();

            // Return when no data has been entered yet + see if component is needed
            if (!me.__hasActivePaymentMethod()) {
                me.onPaymentChangedAfter();
                return;
            }

            me.enableUpdatePaymentInfoButton();
        },
        isPaymentMethodValid: function (paymentMethodElement) {
            var me = this;

            if (!paymentMethodElement.length) {
                return false;
            }

            //Return when no adyen payment
            var paymentMethod = me.getPaymentMethodById(paymentMethodElement.val());

            if (!me.__isAdyenPaymentMethod(paymentMethod)) {
                me.clearPaymentSession();
                return false;
            }

            return me.__canHandlePayment(paymentMethod);
        },
        updatePaymentInfo: function () {
            var me = this;

            me.removePaymentSession();
            $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');

            var paymentMethod = $(me.opts.formSelector).find('input[name=payment]:checked');
            var payment = me.getPaymentMethodById(paymentMethod.val());

            if (me.__canHandlePayment(payment)) {
                $('#' + me.selectedPaymentElementId)
                    .closest(me.opts.paymentMethodSelector)
                    .find(me.opts.methodBankdataSelector)
                    .prop('id', me.getCurrentComponentId(me.selectedPaymentElementId));

                me.handleComponent(payment);

                if (me.changeInfosButton) {
                    me.changeInfosButton.remove();
                    me.changeInfosButton = null;
                }
            }
        },
        setPaymentSession: function (state) {
            var me = this;
            me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
            // TODO verify storePaymentSession
            me.sessionStorage.setItem(me.storePaymentMethodSession, state.data.storePaymentMethod || false);
        },
        clearPaymentSession: function () {
            var me = this;
            me.sessionStorage.removeItem(me.paymentMethodSession);
            me.sessionStorage.removeItem(me.storePaymentMethodSession);
        },
        removePaymentSession: function () {
            var me = this;
            me.clearPaymentSession();
            $.get(me.opts.resetSessionUrl);
        },
        saveAdyenConfigInSession: function (adyenConfiguration) {
            var me = this;

            var data = {
                locale: adyenConfiguration.locale,
                environment: adyenConfiguration.environment,
                clientKey: adyenConfiguration.clientKey,
                paymentMethodsResponse: adyenConfiguration.paymentMethodsResponse
            };

            me.sessionStorage.setItem(me.adyenConfigSession, JSON.stringify(data));
        },
        enableUpdatePaymentInfoButton: function () {
            var me = this;
            var paymentMethodContainer = $(me.opts.formSelector)
                .find('input[name=payment]:checked')
                .closest(me.opts.paymentMethodSelector);
            if (!paymentMethodContainer) {
                return;
            }

            // minimal state has no info that needs updating
            if (me.__hasActiveMinimalPaymentMethodState()) {
                return;
            }

            me.changeInfosButton = $('<a/>')
                .addClass(me.opts.classChangePaymentInfo)
                .html(me.opts.adyenSnippets.updatePaymentInformation)
                .on('click', $.proxy(me.updatePaymentInfo, me));
            paymentMethodContainer
                .find(me.opts.methodBankdataSelector)
                .append(me.changeInfosButton);
        },
        /**
         * @param {string} paymentType
         * @return {boolean}
         * @private
         */
        __isGiftCardType: function (paymentType) {
            var me = this;
            var paymentGroups = me.adyenConfiguration.paymentMethodsResponse['groups'] || [];
            var filteredGiftCardGroup = paymentGroups.filter(function (group) {
                return me.defaults.giftCardGroupName === group['name'];
            });
            var giftCardGroup = filteredGiftCardGroup[0] || [];
            var giftCardGroupTypes = (giftCardGroup && giftCardGroup['types']) || [];
            var filteredTypes = giftCardGroupTypes.filter(function (giftCardGroupType) {
                return giftCardGroupType === paymentType;
            });

            return filteredTypes.length > 0;
        },
        /**
         * @param {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __isStoredPaymentMethod: function (paymentMethod) {
            return paymentMethod.isStoredPayment || false;
        },
        /**
         * @return {boolean}
         * @private
         */
        __hasActivePaymentMethod: function () {
            var sessionPaymentMethod = this.sessionStorage.getItem(this.paymentMethodSession);
            if (!sessionPaymentMethod || "{}" === sessionPaymentMethod) {
                return false;
            }

            return true;
        },
        /**
         * @return {boolean}
         * @private
         */
        __hasActiveMinimalPaymentMethodState: function () {
            if (!this.__hasActivePaymentMethod()) {
                return false;
            }
            var storedPaymentMethod = this.sessionStorage.getItem(this.paymentMethodSession);
            var keys = Object.keys(storedPaymentMethod);

            return 1 === keys.length && 'type' === keys[0]; // Minimal state structure @see __buildMinimalState()
        },
        /**
         * @param {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __isAdyenPaymentMethod: function (paymentMethod) {
            return paymentMethod.isAdyenPaymentMethod || false;
        },
        /**
         * @param {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __canHandlePayment: function (paymentMethod) {
            var me = this;

            if (!me.__isAdyenPaymentMethod(paymentMethod))  {
                return false;
            }

            if (this.__isStoredPaymentMethod(paymentMethod)) {
                return true;
            }

            return "undefined" !== typeof paymentMethod.metadata.details;
        },
        /**
         * @param  {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __enableStoreDetails: function (paymentMethod) {
            // ignore property "paymentMethod.supportsRecurring"
            return 'scheme' === paymentMethod.adyenType;
        },
        /**
         * Modify AdyenPaymentMethod with additional data for the web-component library
         * @param paymentMethod Adyen response: Payment Method response
         * @return  {{cardType: string, paymentMethodData: object}}
         * @private
         */
        __buildCheckoutComponentData: function (paymentMethod) {
            var defaultData = {
                cardType: paymentMethod.adyenType,
                paymentMethodData: paymentMethod
            };

            if (this.__isStoredPaymentMethod(paymentMethod || '')) {
                return $.extend(true, {}, defaultData, {
                    paymentMethodData: {
                        storedPaymentMethodId: paymentMethod.id
                    }
                });
            }

            if (this.__isGiftCardType(paymentMethod.adyenType)) {
                var pinRequiredDetail = this.__retrievePaymentMethodDetailByKey(
                    paymentMethod,
                    'encryptedSecurityCode'
                );

                return $.extend(true, {}, defaultData, {
                    cardType: 'giftcard',
                    paymentMethodData: {
                        type: paymentMethod.adyenType,
                        pinRequired: false === pinRequiredDetail.optional || false
                    }
                });
            }

            return $.extend(true, {}, defaultData, {
                paymentMethodData: {
                    enableStoreDetails: this.__enableStoreDetails(paymentMethod)
                }
            });
        },
        /**
         * Create a minimal state when payment is handled by callback (e.g. PayPal payment)
         * Use only when web components does NOT handle the payment
         * @param payment
         * @return {{data: {paymentMethod: {type}}}}
         * @private
         */
        __buildMinimalState: function (payment) {
            return {
                data: {
                    paymentMethod: {
                        type: payment.adyenType
                    }
                }
            };
        }
    });

})(jQuery);
