#!/usr/bin/env bash
set -e

echo "Getting differences through rsync"
cd "$WORKSPACE" || exit
rsync -avzphlcn --delete --exclude-from="scripts/excludes" --chmod=u+rwx,g+rwx,o-rx "." "$SSHUSER@$PROJECTHOST:$PROJECT_ROOT"
echo "All Done!"
