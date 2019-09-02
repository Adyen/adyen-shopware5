#!/usr/bin/env bash
set -e

MSMESSAGE="**DRYRUN**: $DRYRUN\\n\\r**Environment**: $ENV\\n\\r**Branch**: $BRANCH_NAME\\n\\r**Result**: Failed"

curl -s -X POST \
         -H "Content-Type: Application/json" \
         -H "Cache-control: no-cache" \
         -d@- \
         "$LINK" <<EOF
    {
      "themeColor": "FF0000",
      "title": "Build $PROJECT",
      "text": "$MSMESSAGE"
    }
EOF
