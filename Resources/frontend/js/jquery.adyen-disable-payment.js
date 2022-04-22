;(function ($) {
    'use strict';
    $.plugin('adyen-disable-payment', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenDisableTokenUrl: '',
            adyenStoredMethodId: '',
            /**
             * Selector for the stored payment "disable" button.
             *
             * @type {String}
             */
            disableTokenSelector: '[data-adyen-disable-payment]',
            /**
             * @var string errorClass
             * CSS classes for the error element
             */
            errorClass: 'alert is--error is--rounded is--adyen-error',
            /**
             * @var string errorClassSelector
             * CSS classes selector to clear the error elements
             */
            errorClassSelector: '.alert.is--error.is--rounded.is--adyen-error',
            /**
             * @var string modalSelector
             * CSS classes selector to use as confirmation modal content.
             */
            modalSelector: '.adyenDisableTokenConfirmationModal',
            /**
             * @var string modalConfirmButtonSelector
             * CSS classes selector for the disable-confirm button
             */
            modalConfirmButtonSelector: '.disableConfirm',
            /**
             * @var string modalCancelButtonSelector
             * CSS classes selector for the disable-cancel button
             */
            modalCancelButtonSelector: '.disableCancel',
            /**
             * @var string errorMessageClass
             * CSS classes for the error message element
             */
            errorMessageClass: 'alert--content',
            /**
             * @var string modalErrorContainerSelector
             * CSS classes for the error message container in the modal
             */
            modalErrorContainerSelector: '.modal-error-container',
        },
        init: function () {
            var me = this;
            me.applyDataAttributes();
            me.modalContent = $(me.opts.modalSelector).html() ?? '';
            me.$el.on('click', $.proxy(me.enableDisableButtonClick, me));
        },
        enableDisableButtonClick: function () {
            var me = this;
            if (0 === me.opts.adyenStoredMethodId.length) {
                return;
            }
            if('' === me.modalContent){
                return;
            }
            me.modal = $.modal.open(me.modalContent, {
                showCloseButton: true,
                closeOnOverlay: false,
                additionalClass: 'adyen-modal disable-token-confirmation'
            });
            me.buttonConfirm = $(me.opts.modalConfirmButtonSelector);
            me.buttonConfirm.on('click', $.proxy(me.runDisableTokenCall, me));
            me.buttonCancel = $(me.opts.modalCancelButtonSelector);
            me.buttonCancel.on('click', $.proxy(me.closeModal, me));
        },
        closeModal: function () {
            var me = this;
            if(!me.modal){
                return;
            }
            me.modal.close();
        },
        runDisableTokenCall: function () {
            var me = this;
            $.loadingIndicator.open();
            $.loadingIndicator.loader.$loader.addClass('over-modal');
            $.post({
                url: me.opts.adyenDisableTokenUrl,
                dataType: 'json',
                data: {recurringToken: me.opts.adyenStoredMethodId},
                success: function () {
                    window.location.reload();
                }
            }).fail(function (response) {
                me.appendError(response.responseJSON.message);
            }).always(function () {
                $.loadingIndicator.close();
            });
        },
        appendError: function (message) {
            var me = this;
            $(me.opts.errorClassSelector).remove();
            var error = $('<div />').addClass(me.opts.errorClass);
            error.append($('<div />').addClass(me.opts.errorMessageClass).html(message));
            $(me.opts.modalErrorContainerSelector).append(error);
        }
    });
})(jQuery);
