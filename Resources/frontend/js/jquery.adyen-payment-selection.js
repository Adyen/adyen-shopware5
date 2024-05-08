;(function ($) {
    'use strict';

    $.plugin('adyen-payment-selection', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
            formSelector: '#shippingPaymentForm',
            paymentMeanSelector: 'input[type=radio][name=payment]',
            paymentMeanChangerSelector: 'input[type=radio][name=payment], label[for^=payment_mean]',
            shippingChangerSelector: 'input[type=radio][name=sDispatch], label[for^=confirm_dispatch]',
            storedPaymentMethodSelector: 'input[type=hidden][name=adyenStoredPaymentMethodId]',
            formSubmitButtonSelector: '#shippingPaymentForm button[type=submit], button[form="shippingPaymentForm"]',
            activePaymentMeanSelector: 'input[type=radio][name=payment][checked]',
            paymentMethodBlockSelector: '.payment--method.block',
            paymentMethodComponentContainerSelector: '.method--bankdata',
            updatePaymentInfoButtonClass: 'method--change-info',
            updatePaymentInfoButtonText: 'Update your payment information'
        },

        checkoutController: null,
        selectedPaymentMeanId: null,

        init: function () {
            let me = this;

            me.applyDataAttributes();

            me.checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "sessionStorage": StorageManager.getStorage('session'),
                "onStateChange": $.proxy(me.updateFormSubmitButton, me),
                "onClickToPay": $.proxy(me.handleClickToPay, me),
            });

            $(document).on('submit', me.opts.formSelector, $.proxy(me.onPaymentFormSubmit, me));
            $(document).on('mousedown', me.opts.paymentMeanChangerSelector, $.proxy(me.onPaymentMethodBeforeChange, me));
            $(document).on('mousedown', me.opts.shippingChangerSelector, $.proxy(me.onShippingBeforeChange, me));

            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.onPaymentChangedAfter, me));

            me.onPaymentChangedAfter();
        },

        onPaymentChangedAfter: function () {
            let me = this,
                selectedPaymentMeanEl = $(me.opts.activePaymentMeanSelector).first();

            if (
                selectedPaymentMeanEl.attr("id") === me.selectedPaymentMeanId &&
                me.checkoutController.getPaymentMethodStateData()
            ) {
                me.showUpdatePaymentInfoButton();

                return;
            }

            me.selectedPaymentMeanId = selectedPaymentMeanEl.attr("id");

            if (!selectedPaymentMeanEl.data("adyen-payment-method")) {
                me.checkoutController.unmount();

                return;
            }

            let adyenPaymentMethodType = selectedPaymentMeanEl.data("adyen-payment-method-type");

            let componentContainerEl = selectedPaymentMeanEl
                .closest(me.opts.paymentMethodBlockSelector)
                .find(me.opts.paymentMethodComponentContainerSelector);

            if (1 === componentContainerEl.length) {
                me.checkoutController.mount(
                    adyenPaymentMethodType,
                    componentContainerEl[0],
                    selectedPaymentMeanEl.data("adyen-stored_payment_method_id")
                );
            }
        },

        onPaymentFormSubmit: function (event) {
            let me = this,
                selectedPaymentMeanEl = $(me.opts.activePaymentMeanSelector).first();

            if (!me.checkoutController.isPaymentMethodStateValid()) {
                event.preventDefault();
                me.checkoutController.showValidation();

                return;
            }

            me.updateStoredMethodId(selectedPaymentMeanEl)

            if (
                !selectedPaymentMeanEl.data("adyen-payment-method") ||
                !selectedPaymentMeanEl.data("adyen-stored_payment_method_id")
            ) {
                return;
            }

            selectedPaymentMeanEl.val(selectedPaymentMeanEl.data('adyen-payment-mean-id'));
        },

        onPaymentMethodBeforeChange: function (event) {
            let me = this,
                selectedPaymentMeanEl = $(event.target)
                    .closest(me.opts.paymentMethodBlockSelector)
                    .find(me.opts.paymentMeanSelector);

            me.updateStoredMethodId(selectedPaymentMeanEl);
        },

        onShippingBeforeChange: function () {
            let me = this,
                selectedPaymentMeanEl = $(me.opts.activePaymentMeanSelector).first();

            me.updateStoredMethodId(selectedPaymentMeanEl);
        },

        updateStoredMethodId: function (selectedPaymentMeanEl) {
            let me = this,
                storedPaymentMethodEl = $(me.opts.storedPaymentMethodSelector);

            storedPaymentMethodEl.val('');
            if (selectedPaymentMeanEl.data("adyen-payment-method")) {
                storedPaymentMethodEl.val(selectedPaymentMeanEl.data('adyen-stored_payment_method_id'));
            }
        },

        updateFormSubmitButton: function () {
            let me = this,
                formSubmit = $(me.opts.formSubmitButtonSelector);

            if (me.checkoutController.isPaymentMethodStateValid()) {
                formSubmit.removeClass('is--disabled');
            } else {
                formSubmit.addClass('is--disabled');
            }
        },

        /**
         * This button is required because Shopware triggers `plugin/swShippingPayment/onInputChanged` event even for
         * shipping methods changes and re-renders the complete page content on the server side.
         *
         * In order to avoid the need for the customer to reenter already entered valid payment data we show this button
         * if customer explicitly wants to change his data, otherwise, his data is already stored in the session.
         */
        showUpdatePaymentInfoButton: function () {
            let me = this;

            if (!me.checkoutController.getPaymentMethodStateData()) {
                return;
            }

            let componentContainerEl = $(me.opts.activePaymentMeanSelector)
                .first()
                .closest(me.opts.paymentMethodBlockSelector)
                .find(me.opts.paymentMethodComponentContainerSelector);

            if (1 !== componentContainerEl.length) {
                return;
            }

            let updatePaymentInfoButton = $('<a/>')
                .addClass(me.opts.updatePaymentInfoButtonClass)
                .html(me.opts.updatePaymentInfoButtonText)
                .on('click', function () {
                    updatePaymentInfoButton.remove();
                    me.selectedPaymentMeanId = null;
                    me.onPaymentChangedAfter();
                });

            componentContainerEl.append(updatePaymentInfoButton);
        },

        handleClickToPay: function () {
            let me = this,
                selectedPaymentMeanEl = $(me.opts.activePaymentMeanSelector).first(),
                 componentContainerEl = selectedPaymentMeanEl
                .closest(me.opts.paymentMethodBlockSelector)
                .find(me.opts.paymentMethodComponentContainerSelector),
                clickToPayLabel = selectedPaymentMeanEl.attr("data-adyen-click-to-pay-label"),
                labelElement = $("<p>").text(clickToPayLabel);

            componentContainerEl.prepend(labelElement);
        }
    });

})(jQuery);
