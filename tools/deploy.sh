

# Cleanup any leftovers
rm -f ./tools/AdyenPayment.zip
rm -fR /tmp/Adyen

# Create deployment source
echo "Copying plugin source..."
mkdir -p /tmp/Adyen/deploy
cp -R ./ /tmp/Adyen/deploy/AdyenPayment

# Ensure proper composer dependencies
echo "Installing composer dependencies..."
rm -fR /tmp/Adyen/deploy/AdyenPayment/vendor
composer install --no-dev --working-dir=/tmp/Adyen/deploy/AdyenPayment/

# Remove unnecessary files from final release archive
echo "Removing unnecessary files from final release archive..."
rm -fR /tmp/Adyen/deploy/AdyenPayment/tests
rm -fR /tmp/Adyen/deploy/AdyenPayment/tools
rm -fR /tmp/Adyen/deploy/AdyenPayment/PluginInstallation
rm -fR /tmp/Adyen/deploy/AdyenPayment/.git
rm -fR /tmp/Adyen/deploy/AdyenPayment/.idea
rm -fR /tmp/Adyen/deploy/AdyenPayment/.github
rm -fR /tmp/Adyen/deploy/AdyenPayment/.gitignore
rm -fR /tmp/Adyen/deploy/AdyenPayment/.php-cs-fixer.cache
rm -fR /tmp/Adyen/deploy/AdyenPayment/.php-cs-fixer.dist.php
rm -fR /tmp/Adyen/deploy/AdyenPayment/.phpunit.result.cache
rm -fR /tmp/Adyen/deploy/AdyenPayment/bitbucket-pipelines.yml
rm -fR /tmp/Adyen/deploy/AdyenPayment/grumphp.yml
rm -fR /tmp/Adyen/deploy/AdyenPayment/grumphp.yml.dist
rm -fR /tmp/Adyen/deploy/AdyenPayment/phpcs.xml
rm -fR /tmp/Adyen/deploy/AdyenPayment/phpunit.xml.dist
rm -fR /tmp/Adyen/deploy/AdyenPayment/psalm.xml.dist
rm -fR /tmp/Adyen/deploy/AdyenPayment/E2ETest
rm -fR /tmp/Adyen/deploy/AdyenPayment/Controllers/Frontend/AdyenTest.php
# Create plugin archive
echo "Reading plugin archive version from plugin.xml file..."
version=$(grep -oPm1 "(?<=<version>)[^<]+" ./plugin.xml)
echo "The plugin version from plugin.xml is: $version"

echo "Creating new archive..."
php tools/sw.phar plugin:zip:dir -q /tmp/Adyen/deploy/AdyenPayment/
rm -fR /tmp/Adyen

mv AdyenPayment.zip ./tools/AdyenPayment.zip
echo "New plugin archive for version $version created: $PWD/tools/AdyenPayment.zip"