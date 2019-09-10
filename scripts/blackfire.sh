#!/usr/bin/env bash
set -e

echo "Starting blackfire"
curl -s -X POST --user "$BLACK_USER" "$ENV_URL" -d "endpoint=$ENDPOINT" -d "http_username=shopware" -d "http_password=plugins" -d "title=$(date +"%Y-%m-%d") - $ENV Shopware Adyen Plugins - $BRANCH_NAME"

