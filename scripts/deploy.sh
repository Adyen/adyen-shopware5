#!/usr/bin/env bash
set -e

echo "Syncing files"
cd "$WORKSPACE" || exit
rsync -avzphl --delete --exclude-from="scripts/excludes" --chmod=u+rwx,g+rwx,o-rx "." "$SSHUSER@$PROJECTHOST:$PLUGIN_ROOT"

echo "Running Shopware migrations"
ssh "$SSHUSER@$PROJECTHOST" "$PROJECT_ROOT/bin/console sw:migration:migrate"

echo "Building themes"
ssh "$SSHUSER@$PROJECTHOST" "$PROJECT_ROOT/bin/console sw:theme:cache:generate"

echo "Cleaning caches"
ssh "$SSHUSER@$PROJECTHOST" "$PROJECT_ROOT/bin/console sw:cache:clear"

ssh "$SSHUSER@$PROJECTHOST" "sudo systemctl restart $PHP"
echo "All Done!"


