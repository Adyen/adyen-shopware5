//{block name="backend/adyen/transaction"}
Ext.define('Shopware.apps.AdyenTransaction.model.Transaction', {
    extend: 'Shopware.data.Model',

    fields: [
        { name : 'pspReference', type: 'string' },
        { name : 'date', type: 'date' },
        { name : 'status', type: 'string' },
        { name : 'paymentMethod', type: 'string' },
        { name : 'eventCode', type: 'string' },
        { name : 'success', type: 'string' },
        { name : 'merchantAccountCode', type: 'string' },
        { name : 'paidAmount', type: 'float' },
        { name : 'amountCurrency', type: 'string' },
        { name : 'refundedAmount', type: 'float' },
        { name : 'viewOnAdyenUrl', type: 'string' },
        { name : 'merchantReference', type: 'string'},
        { name: 'storeId', type: 'string'},
        { name: 'currencyIso', type: 'string'},
        { name: 'captureSupported', type: 'boolean'},
        { name: 'captureAmount', type: 'string'},
        { name: 'partialCapture', type: 'boolean'},
        { name: 'refund', type: 'boolean'},
        { name: 'partialRefund', type: 'boolean'},
        { name: 'refundAmount', type: 'string'},
        { name: 'riskScore', type: 'string'},
        { name: 'capturableAmount', type: 'string'},
        { name: 'refundableAmount', type: 'string'},
        { name: 'cancelSupported', type: 'boolean'},
        { name: 'paymentLink', type: 'string'},
        { name: 'displayPaymentLink', type: 'boolean'}
    ]
});
//{/block}
