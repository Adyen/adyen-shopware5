#!/usr/bin/env bash
set -e

if [[ -z "$BUILD_USER" ]];
then
    MSMESSAGE="**DRYRUN**: $DRYRUN\\n\\r**Environment**: $ENV\\n\\r**Branch** : $BRANCH_NAME\\n\\r"
    MSTITLE="Build $PROJECT started by bitbucket push"
else
    MSMESSAGE="**DRYRUN**: $DRYRUN\\n\\r**Environment**: $ENV\\n\\r**Branch** : $BRANCH_NAME\\n\\r"
    MSTITLE="Build $PROJECT started by $BUILD_USER"
fi
curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Cache-Control: no-cache" \
    -d@- \
   "$LINK" <<EOF
    {
      "themeColor": "FFFF00",
      "title": "$MSTITLE",
      "text": "$MSMESSAGE"
  }
EOF
