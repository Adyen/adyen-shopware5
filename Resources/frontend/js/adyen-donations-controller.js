;var AdyenComponents = window.AdyenComponents || {};

(function () {
    'use strict';

    /**
     * @constructor
     *
     * @param {{
     *     donationsConfigUrl : string,
     *     countryCode: string,
     *     makeDonation: function
     * }} config
     */
    function AdyenDonationsController(config) {
        let donations,
            activeComponent,
            isStateValid = true,
            donationComponentConfig = {};

        const getDonationsInstance = async () => {
            if (!donations) {
                let donationsConfig = await (await fetch(config.donationsConfigUrl, {
                    method: "GET"
                })).json().catch((error) => {
                    return null
                });

                if (donationsConfig.length === 0) {
                    return null;
                }

                if (donationsConfig.errorCode) {
                    throw 'Donations configuration error';
                }

                const coreDonation = (donationsConfig.paymentMethodsConfiguration &&
                    donationsConfig.paymentMethodsConfiguration.donation) || {};
                const coreAmounts = coreDonation.amounts || {};
                delete donationsConfig.paymentMethodsConfiguration;

                donationComponentConfig = {
                    donation: {
                        type: 'fixedAmounts',
                        currency: coreAmounts.currency,
                        values: coreAmounts.values || []
                    },
                    nonprofitName: coreDonation.name,
                    nonprofitDescription: coreDonation.description,
                    nonprofitUrl: coreDonation.url,
                    logoUrl: coreDonation.logoUrl,
                    bannerUrl: coreDonation.backgroundUrl
                };

                if (!donationsConfig.countryCode && config.countryCode) {
                    donationsConfig.countryCode = config.countryCode;
                }
                if (!donationsConfig.countryCode) {
                    throw new Error('Donation checkout countryCode is missing');
                }

                const { AdyenCheckout } = window.AdyenWeb;
                donations = await AdyenCheckout(donationsConfig);
            }

            return Promise.resolve(donations);
        }

        const handleOnDonate = (state, component) => {
            isStateValid = state.isValid;
            if (isStateValid) {
                config.makeDonation(state.data);
            }
        };

        const handleOnCancel = (state, component) => {
            unmount();
        }

        const mount = (mountingElement) => {
            let me = this,
                donationInstance = getDonationsInstance();
            isStateValid = true;

            donationInstance.then((donationInstance) => {
                if (!donationInstance) {
                    return;
                }

                unmount();

                activeComponent = window.AdyenWeb.createComponent('donation', donationInstance, Object.assign(
                    {},
                    donationComponentConfig,
                    {
                        'onDonate': handleOnDonate,
                        'onCancel': handleOnCancel
                    }
                ))
                    .mount(mountingElement);
            })
        }

        const unmount = () => {
            isStateValid = true;

            if (activeComponent && donations) {
                donations.remove(activeComponent);
                activeComponent = null;
            }
        }

        this.mount = mount;
        this.unmount = unmount;
    }

    AdyenComponents.DonationsController = AdyenDonationsController;
})();
