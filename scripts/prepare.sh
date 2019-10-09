#!/usr/bin/env bash
echo "Updating using composer"
cd "$WORKSPACE" && /usr/bin/php7.2 /usr/local/bin/composer install --no-dev --optimize-autoloader

