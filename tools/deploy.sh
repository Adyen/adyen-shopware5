#!/usr/bin/env bash
#
# Builds the distributable plugin archive tools/AdyenPayment.zip.
#
# Usage:
#   ./tools/deploy.sh              production build (E2E test code is stripped)
#   KEEP_E2E=1 ./tools/deploy.sh   E2E build, keeps the AdyenTest controller and the E2ETest
#                                  services (used by tools/deploy-test.sh and the CI pipeline)
#
# The archive contains a single top-level folder "AdyenPayment/", no top-level dotfiles and
# no .git* files anywhere, which is the layout the Shopware plugin manager expects.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

PLUGIN_NAME="AdyenPayment"
OUT_ZIP="$PWD/tools/${PLUGIN_NAME}.zip"
BUILD_DIR="$(mktemp -d)"
DEPLOY="${BUILD_DIR}/${PLUGIN_NAME}"
trap 'rm -rf "$BUILD_DIR"' EXIT

# Cleanup any leftovers
rm -f "$OUT_ZIP"

# Create deployment source
echo "Copying plugin source..."
mkdir -p "$DEPLOY"
cp -R ./ "$DEPLOY"

# Ensure proper composer dependencies (composer.lock is the source of truth)
echo "Installing composer dependencies..."
rm -rf "${DEPLOY:?}/vendor"
composer install --no-dev --no-interaction --no-progress --prefer-dist --working-dir="$DEPLOY"

# Remove unnecessary files from final release archive
echo "Removing unnecessary files from final release archive..."
for path in \
    tests \
    tools \
    PluginInstallation \
    .git \
    .idea \
    .github \
    .gitignore \
    .gitattributes \
    .php-cs-fixer.cache \
    .php-cs-fixer.dist.php \
    .phpunit.result.cache \
    bitbucket-pipelines.yml \
    grumphp.yml \
    grumphp.yml.dist \
    phpcs.xml \
    phpunit.xml.dist \
    psalm.xml.dist; do
    rm -rf "${DEPLOY:?}/${path}"
done

if [ "${KEEP_E2E:-0}" = "1" ]; then
    echo "KEEP_E2E=1: keeping the E2E test controller and services in the archive (not for release)."
else
    echo "Removing E2E test code..."
    rm -rf "${DEPLOY:?}/E2ETest"
    rm -f "${DEPLOY:?}/Controllers/Frontend/AdyenTest.php"
    rm -rf "${DEPLOY:?}/vendor/adyen/integration-core/src/BusinessLogic/E2ETest"
fi

# Packages installed from source (git clone fallback) leave .git directories behind
find "${DEPLOY}/vendor" -type d -name .git -prune -exec rm -rf {} +

# Create plugin archive
echo "Reading plugin archive version from plugin.xml file..."
version=$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "${DEPLOY}/plugin.xml" | head -n1)
echo "The plugin version from plugin.xml is: $version"

echo "Creating new archive..."
# find lists files and directories explicitly (no zip -r), sorted so the entry order is stable
# between builds; -X drops uid/gid extra fields. Top-level dotfiles and .git* at any depth are
# excluded, matching the layout the previous Shopware CLI based build produced.
(
    cd "$BUILD_DIR"
    find "$PLUGIN_NAME" -print | LC_ALL=C sort \
        | zip -q -X "$OUT_ZIP" -@ -x "${PLUGIN_NAME}/.*" -x '*.git*'
)
echo "New plugin archive for version $version created: $OUT_ZIP"
