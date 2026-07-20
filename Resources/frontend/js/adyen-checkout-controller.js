;var AdyenComponents = window.AdyenComponents || {};
(function () {
    'use strict';

    function CheckoutConfigProvider() {
        let configCache = {};

        this.getConfiguration = async (configUrl) => {
            if (configCache[configUrl]) {
                return configCache[configUrl];
            }

            configCache[configUrl] = new Promise(async (resolve, reject) => {
                let checkoutConfig = await (await fetch(configUrl, {
                    method: "GET"
                })).json();

                if (checkoutConfig.errorCode) {
                    reject('Checkout configuration error');
                }

                return resolve(checkoutConfig);
            });

            return configCache[configUrl];
        };
    }

    AdyenComponents.CheckoutConfigProvider = new CheckoutConfigProvider();
})();

(function () {
    'use strict';
    // Use for local testing only
    const devOnlyConfig = {
        localShopDomain: '',
        globalReplacementDomain: ''
    };

    const wallets = ['applepay', 'paywithgoogle', 'googlepay', 'paypal'],
        giftCards = [
            'auriga', 'babygiftcard', 'bloemengiftcard', 'cashcomgiftcard', 'eagleeye_voucher', 'entercard',
            'expertgiftcard', 'fashioncheque', 'fijncadeau', 'valuelink', 'fleuropbloemenbon', 'fonqgiftcard',
            'gallgall', 'givex', 'hallmarkcard', 'igive', 'ikano', 'kadowereld', 'kidscadeau', 'kindpas',
            'leisurecard', 'nationalebioscoopbon', 'netscard', 'oberthur', 'pathegiftcard', 'payex', 'podiumcard',
            'resursgiftcard', 'rotterdampas', 'genericgiftcard', 'schoolspullenpas', 'sparnord', 'sparebank',
            'svs', 'universalgiftcard', 'vvvcadeaubon', 'vvvgiftcard', 'webshopgiftcard', 'winkelcheque',
            'winterkledingpas', 'xponcard', 'yourgift', 'prosodie_illicado'
        ];

    /**
     * Handles Adyen web components mounting and session data managing.
     *
     * @constructor
     *
     * @param {{
     * checkoutConfigUrl: string,
     * showPayButton: boolean,
     * requireAddress: boolean,
     * requireEmail: boolean,
     * sessionStorage: sessionStorage,
     * onStateChange: function|undefined,
     * onSubmit: function|undefined,
     * onAdditionalDetails: function|undefined,
     * onAuthorized: function|undefined,
     * onPaymentAuthorized: function|undefined,
     * onApplePayPaymentAuthorized: function|undefined,
     * onShippingContactSelected: function|undefined,
     * onPaymentDataChanged: function|undefined,
     * onShopperDetails: function|undefined,
     * onPayButtonClick: function|undefined,
     * onClickToPay: function|undefined,
     * onShippingAddressChanged: function|undefined
     * }} config
     */
    function CheckoutController(config) {
        const url = new URL(location.href);
        let clickToPayHandled = false;

        if (url.hostname === devOnlyConfig.localShopDomain && devOnlyConfig.globalReplacementDomain) {
            url.hostname = devOnlyConfig.globalReplacementDomain;
            url.protocol = 'https:';
        }

        config.requireAddress = config.requireAddress || false;

        config.requireEmail = config.requireEmail || false;

        config.onStateChange = config.onStateChange || function () {
        };
        config.onSubmit = config.onSubmit || function (state, component, actions) {
            if (!actions || typeof actions.resolve !== 'function') {
                return;
            }

            const paymentMethod = state && state.data && state.data.paymentMethod;
            const type = paymentMethod && paymentMethod.type;
            if (type === 'googlepay' || type === 'paywithgoogle') {
                actions.resolve({resultCode: 'Authorised'});
                return;
            }

            actions.resolve();
        };
        config.onAdditionalDetails = config.onAdditionalDetails || function () {
        };
        config.onAuthorized = config.onAuthorized || function (paymentData, actions) {
            if (actions && typeof actions.resolve === 'function') {
                actions.resolve({transactionState: 'SUCCESS'});
            }
        };
        config.onShippingAddressChanged = config.onShippingAddressChanged || function (data, actions) {
            if (actions && typeof actions.resolve === 'function') {
                actions.resolve();
            }
        };
        config.onPaymentAuthorized = config.onPaymentAuthorized || function () {
            return new Promise(function (resolve, reject) {
                resolve({transactionState: 'SUCCESS'});
            });
        };
        config.onPaymentDataChanged = config.onPaymentDataChanged || function () {
            return new Promise(async resolve => {
                resolve({});
            });
        };
        config.onApplePayPaymentAuthorized = config.onApplePayPaymentAuthorized || function (resolve, reject, event) {
            resolve(window.ApplePaySession.STATUS_SUCCESS);
        };
        config.onShippingContactSelected = config.onShippingContactSelected || function (resolve, reject, event) {
            resolve({});
        };
        config.onShopperDetails = config.onShopperDetails || function (shopperDetails, rawData, actions) {
            actions.resolve();
        };
        config.onPayButtonClick = config.onPayButtonClick || function (resolve, reject) {
            resolve();
        };
        config.onClickToPay = config.onClickToPay || function () {
        };

        const handleOnClick = (resolve, reject) => {
            return config.onPayButtonClick(resolve, reject);
        };

        const handleAuthorized = (paymentData, actions) => {
            return config.onAuthorized(paymentData, actions);
        }

        const handleShopperDetails = (shopperDetails, rawData, actions) => {
            return config.onShopperDetails(shopperDetails, rawData, actions);
        }

        const handleShippingAddressChanged = (data, actions, component) => {
            return config.onShippingAddressChanged(data, actions, component);
        }

        const handlePaymentDataChanged = (intermediatePaymentData) => {
            return config.onPaymentDataChanged(intermediatePaymentData);
        };

        const handlePaymentAuthorized = (paymentData) => {
            return config.onPaymentAuthorized(paymentData);
        }

        const handleApplePayPaymentAuthorized = (resolve, reject, event) => {
            return config.onApplePayPaymentAuthorized(resolve, reject, event);
        }

        const handleOnShippingContactSelected = (resolve, reject, event) => {
            return config.onShippingContactSelected(resolve, reject, event);
        }

        let checkout,
            activeComponent,
            isStateValid = true,
            paymentMethodsConfiguration = {},
            paymentMethodsResponse = {paymentMethods: [], storedPaymentMethods: []},
            checkoutCountryCode = '',
            sessionStorage = config.sessionStorage || window.sessionStorage;

        let googlePaymentDataCallbacks = {};
        if (config.requireAddress) {
            googlePaymentDataCallbacks = {
                onPaymentDataChanged: handlePaymentDataChanged,
                onPaymentAuthorized: handlePaymentAuthorized,
            };
        }

        let paymentMethodSpecificConfig = {
            "paywithgoogle": {
                onClick: handleOnClick,
                isExpress: true,
                callbackIntents: config.requireAddress ? ['SHIPPING_ADDRESS', 'PAYMENT_AUTHORIZATION'] : [],
                shippingAddressRequired: config.requireAddress,
                emailRequired: config.requireEmail,
                shippingAddressParameters: {
                    allowedCountryCodes: [],
                    phoneNumberRequired: true
                },
                shippingOptionRequired: false,
                buttonSizeMode: "fill",
                onAuthorized: handleAuthorized,
                paymentDataCallbacks: googlePaymentDataCallbacks
            },
            "googlepay": {
                onClick: handleOnClick,
                isExpress: true,
                callbackIntents: config.requireAddress ? ['SHIPPING_ADDRESS', 'PAYMENT_AUTHORIZATION'] : [],
                shippingAddressRequired: config.requireAddress,
                emailRequired: config.requireEmail,
                shippingAddressParameters: {
                    allowedCountryCodes: [],
                    phoneNumberRequired: true
                },
                shippingOptionRequired: false,
                buttonSizeMode: "fill",
                onAuthorized: handleAuthorized,
                paymentDataCallbacks: googlePaymentDataCallbacks
            },
            "paypal": {
                blockPayPalCreditButton: true,
                blockPayPalPayLaterButton: true,
                onClick: (source, event, self) => {
                    return handleOnClick(event.resolve, event.reject);
                }
            }
        };

        if (config.requireAddress) {
            paymentMethodSpecificConfig.applepay = {
                isExpress: true,
                requiredBillingContactFields: ['postalAddress'],
                requiredShippingContactFields: ['postalAddress', 'name', 'phoneticName', 'phone', 'email'],
                onAuthorized: handleApplePayPaymentAuthorized,
            }

            if (config.onShippingContactSelected) {
                paymentMethodSpecificConfig.applepay.onShippingContactSelected = handleOnShippingContactSelected
            }

            paymentMethodSpecificConfig.paypal.isExpress = true;
            paymentMethodSpecificConfig.paypal.onAuthorized = handleShopperDetails;
            paymentMethodSpecificConfig.paypal.onShippingAddressChange = handleShippingAddressChanged;
        }

        if (config.amount) {
            paymentMethodSpecificConfig['paypal']['amount'] = config.amount;
        }

        /**
         *
         * @returns {Promise<AdyenCheckout>}
         */
        const getCheckoutInstance = async () => {
            if (!checkout) {
                let checkoutConfig = await AdyenComponents.CheckoutConfigProvider.getConfiguration(config.checkoutConfigUrl);

                checkoutConfig.onChange = handleOnChange;
                checkoutConfig.onSubmit = handleOnSubmit;
                checkoutConfig.onAdditionalDetails = handleAdditionalDetails;

                if (config.showPayButton) {
                    checkoutConfig.showPayButton = true;
                }

                if (!checkoutConfig.countryCode) {
                    checkoutConfig.countryCode = checkoutConfig.locale.split('-')[1] || 'NL';
                }
                checkoutCountryCode = checkoutConfig.countryCode;

                paymentMethodsConfiguration = checkoutConfig.paymentMethodsConfiguration || {};
                paymentMethodsResponse = checkoutConfig.paymentMethodsResponse ||
                    {paymentMethods: [], storedPaymentMethods: []};

                if (paymentMethodsConfiguration.card) {
                    delete paymentMethodsConfiguration.card.showBrandsUnderCardNumber;
                }

                delete checkoutConfig.paymentMethodsConfiguration;

                const { AdyenCheckout } = window.AdyenWeb;
                checkout = await AdyenCheckout(checkoutConfig);
            }

            return Promise.resolve(checkout);
        };

        const handleOnChange = (state) => {
            isStateValid = state.isValid;

            if (isStateValid) {
                sessionStorage.setItem('adyen-payment-method-state-data', JSON.stringify(state.data));
            }

            if (isStateValid && !clickToPayHandled && isClickToPayPaymentMethod(state.data.paymentMethod)) {
                clickToPayHandled = true;
                config.onClickToPay();
            }

            config.onStateChange();
        };

        /**
         * Returns true if Click to Pay is selected.
         *
         * @returns {boolean}
         */
        const isClickToPayPaymentMethod = (paymentMethod) => {
            return paymentMethod.type === 'scheme' && !paymentMethod.hasOwnProperty('encryptedSecurityCode');
        };

        const handleOnSubmit = (state, component, actions) => {
            handleOnChange(state);
            config.onSubmit(state, component, actions);
        };

        const handleAdditionalDetails = (state, component, actions) => {
            config.onAdditionalDetails(state.data, actions);
        };

        /**
         * Mounts adyen web component for a given type under the mount element.
         *
         * @param paymentType: string Web component payment method type
         * @param mountElement: string|HTMLElement Dom elemnt or selector for dom element
         * @param storedPaymentMethodId: string Optional stored payment method id to render component for
         */
        const mount = (paymentType, mountElement, storedPaymentMethodId) => {
            isStateValid = true;

            getCheckoutInstance().then((checkoutInstance) => {
                unmount();

                // Do not mount unavailable payment method
                if (!findPaymentMethodConfig(checkoutInstance, paymentType)) {
                    return;
                }

                sessionStorage.setItem('adyen-needs-sate-data-reinit', 'false');

                if (!config.showPayButton && wallets.includes(paymentType)) {
                    return;
                }

                if (storedPaymentMethodId && paymentType !== 'scheme') {
                    sessionStorage.setItem('adyen-payment-method-state-data', JSON.stringify({
                        'paymentMethod': {
                            'type': paymentType,
                            'storedPaymentMethodId': storedPaymentMethodId
                        },
                    }));

                    config.onStateChange();

                    return;
                }

                let paymentMethodConfig = findSpecificPaymentMethodConfig(paymentType) ||
                    findStoredPaymentMethodConfig(checkoutInstance, storedPaymentMethodId);

                if ('googlepay' === paymentType || 'paywithgoogle' === paymentType) {
                    let responsePaymentMethod = findPaymentMethodConfig(checkoutInstance, paymentType) || {};
                    let responseConfiguration = responsePaymentMethod.configuration || {};
                    let conf = paymentMethodsConfiguration.googlepay ?? paymentMethodsConfiguration.paywithgoogle ?? {};

                    let mergedConfiguration = Object.assign(
                        {},
                        responseConfiguration,
                        paymentMethodConfig.configuration || {}
                    );
                    if (conf.merchantId) {
                        mergedConfiguration.merchantId = conf.merchantId;
                    }
                    if (conf.gatewayMerchantId) {
                        mergedConfiguration.gatewayMerchantId = conf.gatewayMerchantId;
                    }

                    paymentMethodConfig.configuration = mergedConfiguration;
                }

                // If there is applepay specific configuration then set country code to configuration
                if ('applepay' === paymentType &&
                    paymentMethodsConfiguration[paymentType] &&
                    paymentMethodConfig) {
                    paymentMethodConfig.countryCode = checkoutCountryCode;
                }

                // Adyen Web v6 no longer applies the global paymentMethodsConfiguration; pass the
                // backend per-method config straight to the component. Card config is keyed under
                // 'card'; co-badged card scheme selection is rendered automatically by v6.
                if (paymentType === 'scheme') {
                    paymentMethodConfig = Object.assign({}, paymentMethodsConfiguration.card || {}, paymentMethodConfig || {});
                } else if (!wallets.includes(paymentType) && paymentMethodsConfiguration[paymentType]) {
                    paymentMethodConfig = Object.assign({}, paymentMethodsConfiguration[paymentType], paymentMethodConfig || {});
                }

                activeComponent = window.AdyenWeb.createComponent(
                    giftCards.includes(paymentType) ? 'giftcard' : paymentType,
                    checkoutInstance,
                    paymentMethodConfig
                ).mount(mountElement);

                isStateValid = !!activeComponent.isValid && activeComponent.isValid;

                config.onStateChange();
            });
        };

        const handleAdditionalAction = (action, mountElement) => {
            getCheckoutInstance().then((checkoutInstance) => {
                unmount();

                checkoutInstance.createFromAction(action).mount(mountElement);
            });
        };

        const handleAction = (action) => {
            activeComponent.handleAction(action);
        }

        /**
         * Unmounts the active web component (f there is one) and resets the payment method state
         */
        const unmount = () => {
            isStateValid = true;
            sessionStorage.removeItem('adyen-payment-method-state-data');
            forceFetchingComponentStateData();


            if (activeComponent && checkout) {
                checkout.remove(activeComponent);
                activeComponent = null;
            }

            config.onStateChange();
        };

        /**
         * Checks if payment method state is valid for currently mounted component
         *
         * @returns {boolean}
         */
        const isPaymentMethodStateValid = () => {
            return isStateValid;
        };

        /**
         * Returns stringify version of payment method state data
         *
         * @returns {string}
         */
        const getPaymentMethodStateData = () => {
            return sessionStorage.getItem('adyen-payment-method-state-data');
        };

        /**
         * Returns true if Adyen web component was never mounted and therefore the initial payment state data collection
         * is required.
         *
         * @returns {boolean}
         */
        const isPaymentMethodStateReinitializationRequired = () => {
            return 'true' === sessionStorage.getItem('adyen-needs-sate-data-reinit');
        };

        /**
         * Forces the validation errors to appear in currently mounted component
         */
        const showValidation = () => {
            if (activeComponent && 'showValidation' in activeComponent) {
                activeComponent.showValidation();
            }
        };

        const forceFetchingComponentStateData = () => {
            sessionStorage.setItem('adyen-needs-sate-data-reinit', 'true');
        }

        const findSpecificPaymentMethodConfig = (paymentType) => {
            if (giftCards.includes(paymentType)) {
                return {
                    type: 'giftcard',
                    brand: paymentType
                };
            }

            return paymentMethodSpecificConfig[paymentType] || null;
        };

        const findStoredPaymentMethodConfig = (checkoutInstance, storedPaymentMethodId) => {
            if (!storedPaymentMethodId) {
                return null;
            }

            for (const paymentMethod of paymentMethodsResponse.storedPaymentMethods) {
                if (paymentMethod.id === storedPaymentMethodId) {
                    return {
                        ...paymentMethod,
                        storedPaymentMethodId
                    };
                }
            }

            return null;
        };

        const findPaymentMethodConfig = (checkoutInstance, paymentMethodType) => {
            if (!paymentMethodType) {
                return null;
            }

            let isGiftCard = giftCards.includes(paymentMethodType);

            for (const paymentMethod of paymentMethodsResponse.paymentMethods) {
                if (paymentMethod.type === paymentMethodType) {
                    return paymentMethod;
                }

                if (isGiftCard && paymentMethod.brand === paymentMethodType) {
                    return paymentMethod;
                }

                if (paymentMethodType === 'googlepay' && paymentMethod.type === 'paywithgoogle') {
                    return paymentMethod;
                }

                if (paymentMethodType === 'paywithgoogle' && paymentMethod.type === 'googlepay') {
                    return paymentMethod;
                }
            }

            return null;
        };

        this.mount = mount;
        this.handleAdditionalAction = handleAdditionalAction;
        this.handleAction = handleAction;
        this.unmount = unmount;
        this.getPaymentMethodStateData = getPaymentMethodStateData;
        this.isPaymentMethodStateReinitializationRequired = isPaymentMethodStateReinitializationRequired;
        this.isPaymentMethodStateValid = isPaymentMethodStateValid;
        this.showValidation = showValidation;
        this.forceFetchingComponentStateData = forceFetchingComponentStateData;
    }

    AdyenComponents.CheckoutController = CheckoutController;
})();
