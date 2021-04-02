;(function ($) {
    'use strict';

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenClientKey: '',
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
        isPaymentElement: function(elementId) {
            return $('#'+elementId).parents(this.opts.paymentMethodSelector).length > 0;
        },
        onPaymentChangedBefore: function ($event) {
            var me = this;
            var selectedPaymentElementId = event.target.id;

            // only update when switching payment-methods (not on shipping methods)
            if (!me.isPaymentElement(selectedPaymentElementId)) {
                return;
            }

            me.currentSelectedPaymentId = selectedPaymentElementId;
            me.currentSelectedPaymentType = $(event.target).val();
        },
        onPaymentChangedAfter: function () {
            var me = this;

            // Return & clear when no adyen payment
            if (me.currentSelectedPaymentType.indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                me.sessionStorage.removeItem(me.paymentMethodSession);
                return;
            }

            var payment = me.getPaymentMethodByType(me.currentSelectedPaymentType);
            if (!me.__canHandlePayment(payment)) {
                me.setPaymentSession(me.__buildMinimalState(payment));
                return;
            }

            $('#' + me.currentSelectedPaymentId)
                .closest(me.opts.paymentMethodSelector)
                .find(me.opts.methodBankdataSelector)
                .prop('id', me.getCurrentComponentId(me.currentSelectedPaymentId));
            $(me.opts.paymentMethodFormSubmitSelector).addClass('is--disabled');
            me.handleComponent(payment);
        },
        setConfig: function () {
            var me = this;

            me.adyenConfiguration = {
                locale: me.opts.shopLocale,
                environment: me.opts.adyenEnvironment,
                clientKey: me.opts.adyenClientKey,
                paymentMethodsResponse: me.opts.adyenPaymentMethodsResponse,
                onChange: $.proxy(me.handleOnChange, me),
            };

            me.saveAdyenConfigInSession(me.adyenConfiguration);
        },
        getCurrentComponentId: function (currentSelectedPaymentId) {
            return 'component-' + currentSelectedPaymentId;
        },
        getPaymentMethodByType: function (type) {
            var me = this;
            // stored payment methods: pmType contains the id of the payment method
            var pmType = type.split(me.opts.adyenPaymentMethodPrefix).pop();
            var filteredPaymentMethods = me.opts.adyenPaymentMethodsResponse['paymentMethods'].filter(
                function (paymentMethod) {
                    return paymentMethod.type === pmType;
                }
            );
            var paymentMethod = filteredPaymentMethods[0];

            return paymentMethod ? paymentMethod : me.getStoredPaymentMethodById(pmType);
        },
        /**
         * @param {String} storedPaymentMethodId
         * @return {({} | undefined)}
         */
        getStoredPaymentMethodById: function (storedPaymentMethodId) {
            var me = this;
            var storedPaymentMethods = me.opts.adyenPaymentMethodsResponse['storedPaymentMethods'] || [];
            var filteredStoredPaymentMethods = storedPaymentMethods.filter(function (paymentMethod) {
                return paymentMethod.id === storedPaymentMethodId;
            });

            return filteredStoredPaymentMethods[0] || undefined;
        },
        /**
         * @param {String} paymentType
         * @param {String} detailKey
         * @return {({} | null)}
         * @private
         */
        __retrievePaymentMethodDetailByKey: function (paymentType, detailKey) {
            var me = this;
            var paymentMethod = me.getPaymentMethodByType(paymentType) || {};
            var details = (paymentMethod && paymentMethod.details) || [];
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

            if ('paywithgoogle' === paymentMethod.type) {
                me.handleComponentPayWithGoogle(paymentMethod);
                return;
            }

            var adyenCheckoutData = me.__buildCheckoutComponentData(paymentMethod);
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
            var me = this;

            if (!paymentMethod.length) {
                return false;
            }

            //Return when no adyen payment
            if (paymentMethod.val().indexOf(me.opts.adyenPaymentMethodPrefix) === -1) {
                me.clearPaymentSession();
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
            var me = this;
            me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
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
        /**
         * @param {string} paymentType
         * @return {boolean}
         * @private
         */
        __isGiftCardType: function (paymentType) {
            var me = this;
            var paymentGroups = me.opts.adyenPaymentMethodsResponse['groups'] || [];
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
         * paymentType contains the id for a stored payment
         * @param {string} paymentMethodId
         * @return {boolean}
         * @private
         */
        __isStoredPaymentMethod: function (paymentMethodId) {
            return !!this.getStoredPaymentMethodById(paymentMethodId);
        },
        __canHandlePayment: function (paymentMethod) {
            if (this.__isStoredPaymentMethod(paymentMethod.id || '')) {
                return true;
            }

            return "undefined" !== typeof paymentMethod.details;
        },
        /**
         * @param  {object} paymentMethod
         * @return {boolean}
         * @private
         */
        __enableStoreDetails: function (paymentMethod) {
            // ignore property "paymentMethod.supportsRecurring"
            return 'scheme' === paymentMethod.type;
        },
        /**
         * Modify AdyenPaymentMethod with additional data for the web-component library
         * @param paymentMethod Adyen response: Payment Method response
         * @return  {{cardType: string, paymentMethodData: object}}
         * @private
         */
        __buildCheckoutComponentData: function (paymentMethod) {
            var defaultData = {
                cardType: paymentMethod.type,
                paymentMethodData: paymentMethod
            };

            if (this.__isStoredPaymentMethod(paymentMethod.id || '')) {
                return $.extend(true, {}, defaultData, {
                    paymentMethodData: {
                        storedPaymentMethodId: paymentMethod.id
                    }
                });
            }

            if (this.__isGiftCardType(paymentMethod.type)) {
                var pinRequiredDetail = this.__retrievePaymentMethodDetailByKey(
                    paymentMethod.type,
                    'encryptedSecurityCode'
                );

                return $.extend(true, {}, defaultData, {
                    cardType: 'giftcard',
                    paymentMethodData: {
                        type: paymentMethod.type,
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
        __buildMinimalState: function(payment) {
            return {
                data: {
                    paymentMethod: {
                        type: payment.type
                    }
                }
            };
        }
    });

})(jQuery);
