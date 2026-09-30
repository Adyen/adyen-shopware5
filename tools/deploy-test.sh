#!/usr/bin/env bash
#
# E2E build: identical to deploy.sh but keeps the AdyenTest controller and the E2ETest services.
# The resulting archive is for the CI E2E tests only and must never be published to merchants.
set -euo pipefail

KEEP_E2E=1 exec "$(dirname "${BASH_SOURCE[0]}")/deploy.sh"
