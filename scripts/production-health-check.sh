#!/bin/bash

set -euo pipefail

echo "[INFO] production-health-check.sh is deprecated. Delegating to scripts/systest-health-check.sh"
exec bash "$(dirname "$0")/systest-health-check.sh" "$@"
