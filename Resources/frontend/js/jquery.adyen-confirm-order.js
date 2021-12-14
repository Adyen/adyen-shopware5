;(function ($) {
    'use strict';

    $.plugin('adyen-confirm-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            adyenType: '',
            adyenGoogleConfig: {},
            adyenPaymentState: {},
            adyenIsAdyenPayment: false,
            adyenAjaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment',
            adyenAjaxPaymentDetails: '/frontend/adyen/paymentDetails',
            checkoutShippingPaymentUrl: '',
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

            if (!me.cookiesAllowed()) {
                this.addAdyenError(me.opts.adyenSnippets.errorTransactionNoSession);
                return;
            }

            if (!$.isEmptyObject(me.opts.adyenPaymentState)) {
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
                        me.handlePaymentData(response['content'], response['sUniqueID']);
                    } else {
                        me.addAdyenError(response['content']);
                    }

                    $.loadingIndicator.close();
                }
            });
        },
        handlePaymentData: function (data, sUniqueID = null) {
            var me = this;
            switch (data.resultCode) {
                case 'Authorised':
                    me.handlePaymentDataAuthorised(data, sUniqueID);
                    break;
                case 'IdentifyShopper':
                case 'ChallengeShopper':
                case 'Pending':
                case 'RedirectShopper':
                    me.handlePaymentDataCreateFromAction(data);
                    break;
                default:
                    me.handlePaymentDataError(data);
                    break;
            }
        },
        handlePaymentDataAuthorised: function (data, sUniqueID = null) {
            var me = this;
            let input = $("<input>").attr("type", "hidden").attr("name", "sUniqueID").val(sUniqueID);
            $(me.opts.confirmFormSelector).append(input).submit();
        },
        handlePaymentDataCreateFromAction: function (data) {
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
                            },
                            success: function (response) {
                                me.handlePaymentData(response);
                            },
                        });
                    },
                    onError: function (error) {
                        console.error(error);
                    }
                })
                .mount('#AdyenModal');
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
            var adyenConfigTpl = document.querySelector('.adyen-payment-selection.adyen-config').dataset;

            var adyenPaymentMethodsResponseConfig = Object.values(adyenConfigTpl.enrichedpaymentmethods).reduce(
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
                locale: adyenConfigSession ? adyenConfigSession.locale : adyenConfigTpl.shoplocale,
                environment: adyenConfigSession ? adyenConfigSession.environment : adyenConfigTpl.adyenenvironment,
                clientKey: adyenConfigSession ? adyenConfigSession.clientKey : adyenConfigTpl.adyenclientkey,
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
        },
        cookiesAllowed: function () {
            let value = `; ${window.document.cookie}`;
            let parts = value.split(`; cookiePreferences=`);
            let cookieContent = (parts.length === 2) ? parts.pop().split(';').shift() : '{}';
            let parsedPreferences = JSON.parse(cookieContent);

            return parsedPreferences?.groups?.technical?.cookies?.allowCookie?.active;
        }
    });
})(jQuery);
