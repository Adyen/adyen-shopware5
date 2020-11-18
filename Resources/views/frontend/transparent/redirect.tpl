{block name='frontend_adyen_transparent_redirect'}
{strip}
<!DOCTYPE html>
<html lang="en">
    <head><title></title></head>
    <body onload="document.forms['transparent_redirect'].submit();">
        <form id="transparent_redirect" method="POST" action="{$redirectUrl}">
            {foreach $postParams as $value}
                <input type="hidden" name="{$value@key}" value="{$value}" />
            {/foreach}
        </form>
    </body>
</html>
{/strip}
{/block}