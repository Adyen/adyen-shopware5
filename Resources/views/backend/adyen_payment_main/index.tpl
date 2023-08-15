{namespace name=backend/adyen/configuration}
{extends file="parent:backend/_base/layout.tpl"}
{block name="styles"}
    <link rel="stylesheet" href="{link file="backend/_resources/css/adyen.css"}?{$assetsVersion}"/>
    <link rel="stylesheet" href="{link file="backend/_resources/css/adyen-core.css"}?{$assetsVersion}"/>
{/block}
{block name="scripts"}
    <script type="text/javascript" src="{link file="backend/_resources/js/AjaxService.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/TranslationService.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/TemplateService.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/UtilityService.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/ValidationService.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/ResponseService.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/PageControllerFactory.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/ElementGenerator.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/StateController.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/ConnectionController.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/PaymentsController.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/SettingsController.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/NotificationsController.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/ModalComponent.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/DropdownComponent.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/MultiselectDropdownComponent.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/DataTableComponent.js"}?{$assetsVersion}"></script>
    <script type="text/javascript" src="{link file="backend/_resources/js/TableFilterComponent.js"}?{$assetsVersion}"></script>
{/block}
{block name="content/main"}
    <div id="adl-page" class="adl-page">
        <aside class="adl-sidebar"></aside>
        <main>
            <div class="adlp-content-holder">
                <header id="adl-main-header">
                    <div class="adl-header-navigation">
                        <ul class="adlp-nav-list">
                            <li class="adlp-nav-item adlm--merchant adls--hidden"></li>
                            <li class="adlp-nav-item" id="adl-store-switcher"></li>
                            <li class="adlp-nav-item adlm--mode adls--hidden"></li>
                            <li class="adlp-nav-item adlm--download adls--hidden"></li>
                        </ul>
                    </div>
                    <div class="adl-header-holder" id="adl-header-section"></div>
                </header>
                <main id="adl-main-page-holder"></main>
            </div>
        </main>
        <div class="adl-page-loader adls--hidden" id="adl-spinner">
            <div class="adl-loader adlt--large">
                <span class="adlp-spinner"></span>
            </div>
        </div>
    </div>
{/block}
{block name="content/javascript"}
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            () => {
                AdyenFE.utilities.showLoader()
                const response = {$response|json_encode};
                AdyenFE.translations = {
                    default: response['lang']['default'],
                    current: response['lang']['current'],
                };
                // holds URLs to all API endpoints and other possible configuration for each page
                const pageConfiguration = response['urls'];
                AdyenFE.state = new AdyenFE.StateController(
                    {
                        storesUrl: response['urls']['stores']['storesUrl'],
                        connectionDetailsUrl: response['urls']['connection']['getSettingsUrl'],
                        merchantsUrl: response['urls']['connection']['getMerchantsUrl'],
                        currentStoreUrl: response['urls']['stores']['currentStoreUrl'],
                        stateUrl: response['urls']['integration']['stateUrl'],
                        versionUrl: response['urls']['version']['versionUrl'],
                        pageConfiguration: pageConfiguration,
                        templates: {
                            'sidebar': response['sidebar']
                        }
                    })
                AdyenFE.state.display();
                AdyenFE.utilities.hideLoader();
            }
        );
    </script>
{/block}
