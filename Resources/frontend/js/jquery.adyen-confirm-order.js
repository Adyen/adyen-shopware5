;(function ($) {
    'use strict';

    $.plugin('adyen-confirm-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            mountRedirectSelector: '.is--act-confirm',
            adyenType: '',
            adyenSetSession: {}, // TODO check
            adyenIsAdyenPayment: false,
            adyenAjaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment',
            adyenAjaxPaymentDetails: '/frontend/adyen/paymentDetails',
            adyenSnippets: {
                errorTransactionCancelled: 'Your transaction was cancelled by the Payment Service Provider.',
                errorTransactionProcessing: 'An error occured while processing your payment.',
                errorTransactionRefused: 'Your transaction was refused by the Payment Service Provider.',
                errorTransactionUnknown: 'Your transaction was cancelled due to an unknown reason.',
                errorTransactionNoSession: 'Your transaction was cancelled due to an unknown reason. Please make sure your browser allows cookies.',
            },
        },
        paymentMethodSession: 'paymentMethod',
        storePaymentMethodSession: 'storePaymentMethod',
        adyenConfiguration: {},
        adyenCheckout: null,

        init: function () {
            var me = this;

            me.sessionStorage = StorageManager.getStorage('session'); // TODO weg

            me.applyDataAttributes();
            me.eventListeners(); // TODO ok
            me.setConfig(); //
            me.setCheckout();
        },

        // TODO ok
        eventListeners: function () {
            var me = this;

            me._on(me.opts.placeOrderSelector, 'click', $.proxy(me.onPlaceOrder, me));
        },

        // TODO ok
        onPlaceOrder: function (event) {
            var me = this;

            if (typeof event !== 'undefined') {
                event.preventDefault();
            }

            me.clearAdyenError();

            // TODO bespreken, mag normaal weg
            if (me.sessionStorage.getItem(me.paymentMethodSession)) {
                if (!$(me.opts.confirmFormSelector)[0].checkValidity()) {
                    return;
                }

                $.loadingIndicator.open();

                var data = {
                    'paymentMethod': me.getPaymentMethod(), // TODO normaal ophalen via id
                    'storePaymentMethod': me.getStorePaymentMethod(), // TODO normaal ophalen via id
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
                            me.handlePaymentData(response['content']);
                        } else {
                            me.addAdyenError(response['content']);
                        }

                        $.loadingIndicator.close();
                    }
                });
            } else { // TODO mag weg door if die wegvalt
                if (me.opts.adyenIsAdyenPayment) {
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionNoSession);
                    return;
                }

                $(me.opts.confirmFormSelector).submit();
            }
        },

        // TODO ok
        handlePaymentData: function (data) {
            var me = this;

            switch (data.resultCode) {
                case 'Authorised':
                    me.handlePaymentDataAuthorised(data);
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

        // TODO ok
        handlePaymentDataAuthorised: function (data) {
            var me = this;
            $(me.opts.confirmFormSelector).submit();
        },

        // TODO ok
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

        // TODO ok
        handlePaymentDataRedirectShopper: function (data) {
            var me = this;
            if ('redirect' === data.action.type || 'qrCode' === data.action.type) {
                me.adyenCheckout
                    .createFromAction(data.action)
                    .mount(me.opts.mountRedirectSelector);
            }
        },

        // TODO ok
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

        // TODO ok
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

        // TODO ok
        clearAdyenError: function () {
            $.publish('plugin/AdyenPaymentCheckoutError/cleanErrors');
        },

        // TODO ok
        setConfig: function () {
            var me = this;

            var adyenConfigTpl = document.querySelector('.adyen-payment-selection.adyen-config').dataset;

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
                locale: adyenConfigTpl.shoplocale,
                environment: adyenConfigTpl.adyenenvironment,
                clientKey: adyenConfigTpl.adyenclientkey,
                paymentMethodsResponse: Object.assign({}, adyenPaymentMethodsResponseConfig),
                onAdditionalDetails: me.handleOnAdditionalDetails.bind(me)
            };
        },

        // TODO ok
        setCheckout: function () {
            var me = this;

            me.adyenCheckout = new AdyenCheckout(me.adyenConfiguration);
        },

        // TODO ok
        getComment: function() {
            return $('[data-storagekeyname="sComment"]').val();
        },

        // TODO bespreken, mag normaal weg, ophalen via id
        getPaymentMethod: function () {
            var me = this;

            return me.sessionStorage.getItem(me.paymentMethodSession);
        },

        // TODO bespreken, mag normaal weg, ophalen via id en type
        getStorePaymentMethod: function () {
            var me = this;

            return me.sessionStorage.getItem(me.storePaymentMethodSession);
        },

        getAdyenConfigSession: function () {
            var me = this;

            return me.sessionStorage.getItem('adyenConfig');
        },

        // TODO ok
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

        // TODO ok
        handleOnAdditionalDetails: function (state, component) {
            $.loadingIndicator.close();
        }
    });
})(jQuery);
