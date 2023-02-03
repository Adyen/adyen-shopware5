;(function ($) {
    'use strict';
    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenClientKey: '',
            enrichedPaymentMethods: {},
            adyenOrderTotal: '',
            adyenOrderCurrency: '',
            resetSessionUrl: '',
            adyenConfigAjaxUrl: '',
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
             * Selector for the shipping payment content.
             *
             * @type {String}
             */
            shippingPaymentContentSelector: '#shipping_payment_wrapper',
            /**
             * Selector for stored payment method content.
             *
             * @type {String}
             */
            storedPaymentContentSelector: '#stored_payment_wrapper',
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
             * @type {string} Adyen payment method type for ApplePay
             */
            applePayType: 'applepay',
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
            me.handleVisibility();
            me.setCheckout();
            me.handleSelectedMethod();
        },
        eventListeners: function () {
            var me = this;

            $(document).on('submit', me.opts.formSelector, $.proxy(me.onPaymentFormSubmit, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChangedBefore'), $.proxy(me.onPaymentChangedBefore, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.onPaymentChangedAfter, me));
        },
        handleVisibility: function () {
            if (!window.StateManager.hasCookiesAllowed()) {
                this.hideAllAdyenPaymentMethods();
                $(this.opts.shippingPaymentContentSelector).removeClass('adyen-hidden--all');

                return;
            }

            this.handleApplePayVisibility();
            $(this.opts.shippingPaymentContentSelector).removeClass('adyen-hidden--all');
        },
        hideAllAdyenPaymentMethods: function() {
            this.hideStoredAdyenPaymentMethods();
            this.hideAdyenPaymentMethods();
        },
        hideStoredAdyenPaymentMethods: function () {
            for (let i = 0; i < this.opts.enrichedPaymentMethods.length; i++) {
                if (this.opts.enrichedPaymentMethods[i].isAdyenPaymentMethod && this.opts.enrichedPaymentMethods[i].isStoredPayment) {
                    $(this.opts.storedPaymentContentSelector).addClass('adyen-hidden--all');
                    return;
                }
            }
        },
        hideAdyenPaymentMethods: function () {
            for (let i = 0; i < this.opts.enrichedPaymentMethods.length; i++) {
                if (this.opts.enrichedPaymentMethods[i].isAdyenPaymentMethod) {
                    $('#payment_mean' + this.opts.enrichedPaymentMethods[i].id)
                        .parents(this.opts.paymentMethodSelector).addClass('adyen-hidden--all');
                }
            }
        },
        handleApplePayVisibility: function () {
            var me = this;
            var applePayAvailable = window.ApplePaySession || false;
            if (applePayAvailable) {
                return;
            }

            var applePayMethod = me.opts.enrichedPaymentMethods.filter(function(enrichedPaymentMethod) {
                return enrichedPaymentMethod.adyenType === me.opts.applePayType;
            })[0] || {};
            if (!applePayMethod) {
                return;
            }

            $('#payment_mean'+applePayMethod.id).parents(this.opts.paymentMethodSelector).addClass('adyen-hidden--all');
        },
        onPaymentFormSubmit: function (e) {
            var me = this;
            var $formSubmit = $(me.opts.paymentMethodFormSubmitSelector);
            if ($formSubmit.hasClass('is--disabled')) {
                e.preventDefault();
                return false;
            }
            var $paymentElement = $('#' + me.selectedPaymentElementId)[0];
            var paymentMethod = this.getPaymentMethodById($paymentElement.value);
            if(paymentMethod.isStoredPayment){
                $formSubmit.append(
                    $('<input type="hidden" name="adyenStoredMethodId" value="'+paymentMethod.stored_method_id+'"/>')
                );
            }
            $paymentElement.value = paymentMethod.id;
        },
        isPaymentElement: function (elementId) {
            return $('#' + elementId).parents(this.opts.paymentMethodSelector).length > 0;
        },
        onPaymentChangedBefore: function ($event) {
            var me = this;

            var previousSelectedPaymentElementId = me.selectedPaymentElementId;
            var selectedPaymentElementId = event.target.id;

            // only update when switching payment-methods (not on shipping methods)
            if (!me.isPaymentElement(selectedPaymentElementId)) {
                return;
            }

            me.selectedPaymentElementId = selectedPaymentElementId;

            var elementValue = $(event.target).val();
            var paymentMethod = this.getPaymentMethodById(elementValue);
            me.selectedPaymentId = paymentMethod.isStoredPayment ? paymentMethod.stored_method_id : elementValue;

            var paymentMethodSession = this.getPaymentSession();
            if (0 === Object.keys(paymentMethodSession).length) {
                return;
            }

            if (previousSelectedPaymentElementId !== me.selectedPaymentElementId) {
                me.clearPaymentSession();
            }
        },
        onPaymentChangedAfter: function () {
            var me = this;

            me.handleVisibility();

            var payment = me.getPaymentMethodById(me.selectedPaymentId);

            // Return & clear when no adyen payment
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

            me.fetchAdyenConfig();

            var adyenPaymentMethodsResponse = me.opts.enrichedPaymentMethods.reduce(
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
                paymentMethodsResponse: {'paymentMethods':adyenPaymentMethodsResponse},
                onChange: $.proxy(me.handleOnChange, me),
                showPayButton: false
            };
            me.saveAdyenConfigInSession(me.adyenConfiguration);
        },
        fetchAdyenConfig: function () {
            var me = this;

            return $.ajax({
                method: 'GET',
                async: false,
                dataType: 'json',
                url: me.opts.adyenConfigAjaxUrl,
                success: function (response) {
                    if (response['status'] === 'success') {
                        me.opts.shopLocale = response['shopLocale'];
                        me.opts.adyenClientKey = response['clientKey'];
                        me.opts.adyenEnvironment = response['environment'];
                        me.opts.enrichedPaymentMethods = response['enrichedPaymentMethods'];
                        me.opts.adyenOrderTotal = response['adyenOrderTotal'];
                        me.opts.adyenOrderCurrency = response['adyenOrderCurrency'];
                    } else {
                        me.addAdyenError(response['content']);
                    }

                    $.loadingIndicator.close();
                }
            });
        },
        getCurrentComponentId: function (selectedPaymentElementId) {
            return 'component-' + selectedPaymentElementId;
        },
        getPaymentMethodById: function (id) {
            var me = this;

            return me.opts.enrichedPaymentMethods.filter(function(paymentMethod) {
                return paymentMethod.id === id || (
                    paymentMethod.isStoredPayment === true
                    && (paymentMethod.stored_method_id === id || paymentMethod.stored_method_umbrella_id === id));
            })[0] || {};
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

            var adyenCheckoutData = me.__buildCheckoutComponentData(paymentMethod);

            if (this.__isApplePayPaymentMethod(paymentMethod)) {
                me.setConfig();
                me.setPaymentSession(me.__buildMinimalState(paymentMethod));
                adyenCheckoutData = me.__buildCheckoutComponentData(paymentMethod);
            }

            if (paymentMethod.adyenType === 'paywithgoogle' || paymentMethod.adyenType === 'onlineBanking_PL') {
                me.setConfig();
                me.setPaymentSession(me.__buildMinimalState(paymentMethod));
                me.handleComponentEnableSubmit();
                return;
            }

            me.adyenCheckout
                .create(adyenCheckoutData.cardType, adyenCheckoutData.paymentMethodData)
                .mount('#' + me.getCurrentComponentId(me.selectedPaymentElementId));
        },
        handleComponentEnableSubmit: function () {
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
            me.sessionStorage.setItem(me.storePaymentMethodSession, state.data.storePaymentMethod || false);
        },
        getPaymentSession: function () {
            var me = this;

            return JSON.parse(me.sessionStorage.getItem(me.paymentMethodSession) || "{}");
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
            var payment = me.getPaymentMethodById(me.selectedPaymentId);
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
         * @param {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __isGiftCard: function (paymentMethod) {
            var me = this;

            return 'giftcard' === paymentMethod.metadata.type;
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
         *
         * @param {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __isApplePayPaymentMethod: function (paymentMethod) {
            var me = this;

            return me.opts.applePayType === paymentMethod.adyenType;
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
            var storedPaymentMethod = this.getPaymentSession();
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

            // not all adyen payment methods have "details", these cannot be handled by webcomponents (e.g. Paypal)
            return "undefined" !== typeof paymentMethod.metadata.details
                || "undefined" !== typeof paymentMethod.metadata.brand;
        },
        /**
         * @param  {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __enableStoreDetails: function (paymentMethod) {
            return 'scheme' === paymentMethod.adyenType;
        },
        /**
         * Modify AdyenPaymentMethod with additional data for the web-component library
         * @param paymentMethod Shopware PaymentMean enriched with Adyen payment data
         * @return  {{cardType: string, paymentMethodData: object}}
         * @private
         */
        __buildCheckoutComponentData: function (paymentMethod) {
            var defaultData = {
                cardType: paymentMethod.adyenType,
                paymentMethodData: paymentMethod.metadata
            };

            if (this.__isStoredPaymentMethod(paymentMethod || {})) {
                return $.extend(true, {}, defaultData, {
                    paymentMethodData: {
                        storedPaymentMethodId: paymentMethod.metadata.id
                    }
                });
            }

            if (this.__isApplePayPaymentMethod(paymentMethod)) {
                var me = this;

                return $.extend(true, {}, defaultData, {
                    paymentMethodData: {
                        amount: {
                            'value': (Number(me.opts.adyenOrderTotal)*100).toString(),
                            'currency': (me.opts.adyenOrderCurrency).toString()
                        }
                    }
                });
            }

            if (this.__isGiftCard(paymentMethod)) {
                var pinRequiredDetail = this.__retrievePaymentMethodDetailByKey(
                    paymentMethod,
                    'encryptedSecurityCode'
                ) || false;

                return $.extend(true, {}, defaultData, {
                    cardType: 'giftcard',
                    paymentMethodData: {
                        type: paymentMethod.adyenType,
                        brand: paymentMethod.metadata.brand,
                        pinRequired: pinRequiredDetail && (false !== pinRequiredDetail.optional || false)
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
