// {block name="backend/index/view/menu" append}
Ext.onReady(function () {
    Ext.define('Shopware.apps.AdyenPayment.ShopNotifications', function () {
        return {
            statics: {
                openAdyenModule: function (storeId, page) {
                    let pageSuffix = page ? '#' + page : '';

                    sessionStorage.setItem('adl-active-store-id', storeId);
                    Shopware.ModuleManager.createSimplifiedModule(
                        "AdyenPaymentMain" + pageSuffix,
                        {
                            "title": "Adyen",
                            maximized: true
                        }
                    );
                }
            }
        };
    });

    Ext.Ajax.request({
        method: 'POST',
        url: "{url controller=AdyenShopNotifications action=get}",
        async: true,
        success: function (response) {
            var result = JSON.parse(response.responseText);

            result.forEach(
                function (item) {
                    Shopware.Notification.createStickyGrowlMessage({
                        title: Ext.String.format(
                            "{s name='notification/adyen/header'}You have new Adyen notifications ({literal}{0}{/literal}){/s}",
                            item.storeName
                        ),
                        text: "{s name='notification/adyen/message'}Open the Adyen plugin dashboard page to view them{/s}",
                        btnDetail: {
                            text: "{s name='notification/adyen/open'}Open{/s}",
                            callback: function () {
                                Shopware.apps.AdyenPayment.ShopNotifications.openAdyenModule(
                                    item.storeId, 'notifications-shop'
                                );
                            }
                        },
                        onCloseButton: function () {
                        }
                    })
                }
            )
        }
    });
});
//{/block}
