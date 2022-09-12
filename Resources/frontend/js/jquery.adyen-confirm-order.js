;(function ($) {
    'use strict';

    $.plugin('adyen-confirm-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            /**
             * Default shopLocale when no locate is assigned
             *
             * @type {string}
             */
            shopLocale: 'en-US',
            /**
             * Fallback environment variable
             *
             * @type {string}
             */
            adyenEnvironment: 'test',
            adyenClientKey: '',
            enrichedPaymentMethods: {},
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            adyenType: '',
            adyenGoogleConfig: {},
            adyenPaymentState: {},
            adyenIsAdyenPayment: false,
            adyenConfigAjaxUrl: '/frontend/adyenconfig/index',
            adyenAjaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment',
            adyenAjaxPaymentDetails: '/frontend/adyen/paymentDetails',
            checkoutShippingPaymentUrl: '/checkout/shippingPayment/sTarget/checkout',
            accountLoginUrl: '/account/login/sTarget/checkout/sTargetAction/confirm/showNoAccount/true',
            adyenSnippets: {
                errorTransactionCancelled: 'Your transaction was cancelled by the Payment Service Provider.',
                errorTransactionProcessing: 'An error occurred while processing your payment.',
                errorTransactionRefused: 'Your transaction was refused by the Payment Service Provider.',
                errorTransactionUnknown: 'Your transaction was cancelled due to an unknown reason.',
                errorTransactionNoSession: 'Your transaction was cancelled due to an unknown reason. Please make sure your browser allows cookies.',
                errorGooglePayNotAvailable: 'Google Pay is currently not available.',
            },
        },
        paymentMethodSession: 'paymentMethod',
        storePaymentMethodSession: 'storePaymentMethod',
        adyenConfiguration: {},
        adyenCheckout: null,

        init: function () {
            var me = this;

            me.sessionStorage = StorageManager.getStorage('session');

            me.applyDataAttributes();
            me.eventListeners();
            me.checkSetSession();
            me.setConfig();
            me.setCheckout();
            me.handleCheckoutButton();
        },
        eventListeners: function () {
            var me = this;

            me._on(me.opts.placeOrderSelector, 'click', $.proxy(me.onPlaceOrder, me));
        },
        checkSetSession: function () {
            var me = this;

            if (!me.opts.adyenIsAdyenPayment) {
                me.sessionStorage.removeItem(me.paymentMethodSession);
                return;
            }

            var parsedPaymentMethodSession = JSON.parse(me.getPaymentMethod() || '{}');
            if (!$.isEmptyObject(me.opts.adyenPaymentState)
                && 0 === Object.keys(parsedPaymentMethodSession).length) {
                me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(me.opts.adyenPaymentState));
                return;
            }

            if (!me.sessionStorage.getItem(me.paymentMethodSession)) {
                window.location.href = me.opts.checkoutShippingPaymentUrl;
            }
        },
        onPlaceOrder: function (event) {
            var me = this;

            if (typeof event !== 'undefined') {
                event.preventDefault();
            }

            me.clearAdyenError();

            if (!me.sessionStorage.getItem(me.paymentMethodSession)) {
                if (me.opts.adyenIsAdyenPayment) {
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionNoSession);

                    return;
                }

                $(me.opts.confirmFormSelector).submit();
                return;
            }

            if (!$(me.opts.confirmFormSelector)[0].checkValidity()) {
                return;
            }

            $.loadingIndicator.open();

            var data = {
                'paymentMethod': me.getPaymentMethod(),
                'storePaymentMethod': me.getStorePaymentMethod(),
                'browserInfo': me.getBrowserInfo(),
                'origin': window.location.origin,
                'sComment': me.getComment()
            };

            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: me.opts.adyenAjaxDoPaymentUrl,
                data: data,
                success: function (response) {
                    if (response['status'] === 'success') {
                        me.handlePaymentData(response['content'], response['sUniqueID'], response['adyenTransactionId']);
                    } else {
                        me.addAdyenError(response['content']);
                    }

                    $.loadingIndicator.close();
                },
                error: me.handleAjaxRequestError.bind(me)
            });
        },
        handlePaymentData: function (data, sUniqueID = null, adyenTransactionId = null) {
            var me = this;
            switch (data.resultCode) {
                case 'Authorised':
                    me.handlePaymentDataAuthorised(data, sUniqueID);
                    break;
                case 'IdentifyShopper':
                case 'ChallengeShopper':
                case 'Pending':
                case 'RedirectShopper':
                    me.handlePaymentDataCreateFromAction(data, sUniqueID, adyenTransactionId);
                    break;
                default:
                    me.handlePaymentDataError(data);
                    break;
            }
        },
        handlePaymentDataAuthorised: function (data, sUniqueID = null) {
            var me = this;
            var input = $("<input>").attr("type", "hidden").attr("name", "sUniqueID").val(sUniqueID);
            $(me.opts.confirmFormSelector).append(input).submit();
        },
        handlePaymentDataCreateFromAction: function (data, sUniqueID = null, adyenTransactionId = null) {
            var me = this;
            var payload = {
                resultCode: data.resultCode,
                type: data.action.type,
                subtype: data.action.subtype
            };
            var modal = $.modal.open('<div id="AdyenModal"/>', {
                showCloseButton: false,
                closeOnOverlay: false,
                additionalClass: 'adyen-modal'
            });

            // data.action: "redirect" errors are handled by Process::returnAction()
            me.adyenCheckout
                .createFromAction(data.action, {
                    onAdditionalDetails: function (state) {
                        modal.close();
                        $.ajax({
                            method: 'POST',
                            dataType: 'json',
                            url: me.opts.adyenAjaxPaymentDetails,
                            data: {
                                'action': payload,
                                'details': state.data.details,
                                'adyenTransactionId': adyenTransactionId
                            },
                            success: function (response) {
                                me.handlePaymentData(response, sUniqueID, adyenTransactionId);
                            },
                            error: me.handleAjaxRequestError.bind(me)
                        });
                    },
                    onError: function (error) {
                        console.error(error);
                    }
                })
                .mount('#AdyenModal');
        },
        handleAjaxRequestError: function (xhr) {
            if (xhr.status === 401) {
                window.location.href = this.opts.accountLoginUrl;
            }

            this.addAdyenError(this.opts.adyenSnippets.errorTransactionProcessing);
            $.loadingIndicator.close();
        },
        handlePaymentDataError: function (data) {
            var me = this;

            $.loadingIndicator.close();

            switch (data.resultCode) {
                case 'Cancelled':
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionCancelled);
                    break;
                case 'Error':
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionProcessing);
                    break;
                case 'Refused':
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionRefused);
                    break;
                default:
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionUnknown);
                    break;
            }
        },
        handleCheckoutButton: function () {
            var me = this;

            if (me.opts.adyenType === 'paywithgoogle') {
                me.replaceCheckoutButtonForGooglePay();
            }
        },
        replaceCheckoutButtonForGooglePay: function () {
            var me = this;

            if (0 === Object.keys(me.opts.adyenGoogleConfig).length) {
                this.addAdyenError(me.opts.adyenSnippets.errorGooglePayNotAvailable);
                console.error('Adyen: Missing google configuration');
                return;
            }

            var orderButton = $(me.opts.placeOrderSelector);
            orderButton.parent().append(
                $('<div />')
                    .attr('id', 'AdyenGooglePayButton')
                    .addClass('right')
            );
            orderButton.remove();

            me.opts.adyenGoogleConfig.onSubmit = function (state, component) {
                me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
                me.onPlaceOrder();
            };

            var googlepay = me.adyenCheckout.create("paywithgoogle", me.opts.adyenGoogleConfig);
            googlepay
                .isAvailable()
                .then(function () {
                    googlepay.mount("#AdyenGooglePayButton");
                })
                .catch(function (e) {
                    this.addAdyenError(me.opts.adyenSnippets.errorGooglePayNotAvailable);
                });
        },
        addAdyenError: function (message) {
            var me = this;
            $.publish('plugin/AdyenPaymentCheckoutError/addError', message);
            $.publish('plugin/AdyenPaymentCheckoutError/scrollToErrors');

            $(me.opts.placeOrderSelector)
                .removeAttr('disabled')
                .removeClass('disabled')
                .find('.js--loading')
                .remove();
        },
        clearAdyenError: function () {
            $.publish('plugin/AdyenPaymentCheckoutError/cleanErrors');
        },
        setConfig: function () {
            var me = this;

            var adyenConfigSession = JSON.parse(me.getAdyenConfigSession());

            $.ajax({
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
                    } else {
                        me.addAdyenError(response['content']);
                    }

                    $.loadingIndicator.close();
                }
            });

            var adyenPaymentMethodsResponseConfig = me.opts.enrichedPaymentMethods.reduce(
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
                locale: adyenConfigSession ? adyenConfigSession.locale : me.opts.shoplocale,
                environment: adyenConfigSession ? adyenConfigSession.environment : me.opts.adyenenvironment,
                clientKey: adyenConfigSession ? adyenConfigSession.clientKey : me.opts.adyenclientkey,
                paymentMethodsResponse: Object.assign({}, adyenPaymentMethodsResponseConfig),
                onAdditionalDetails: me.handleOnAdditionalDetails.bind(me)
            };
        },
        setCheckout: function () {
            var me = this;

            me.adyenCheckout = new AdyenCheckout(me.adyenConfiguration);
        },
        getComment: function() {
            return $('[data-storagekeyname="sComment"]').val();
        },
        getPaymentMethod: function () {
            var me = this;

            return me.sessionStorage.getItem(me.paymentMethodSession);
        },
        getStorePaymentMethod: function () {
            var me = this;

            return me.sessionStorage.getItem(me.storePaymentMethodSession);
        },
        getAdyenConfigSession: function () {
            var me = this;

            return me.sessionStorage.getItem('adyenConfig');
        },
        getBrowserInfo: function () {
            return {
                'language': navigator.language,
                'userAgent': navigator.userAgent,
                'colorDepth': window.screen.colorDepth,
                'screenHeight': window.screen.height,
                'screenWidth': window.screen.width,
                'timeZoneOffset': new Date().getTimezoneOffset(),
                'javaEnabled': navigator.javaEnabled()
            };
        },
        handleOnAdditionalDetails: function (state, component) {
            $.loadingIndicator.close();
        }
    });
})(jQuery);
