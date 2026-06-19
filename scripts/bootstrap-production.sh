#!/bin/bash

set -euo pipefail

# Compatibility wrapper.
# The canonical virgin/bootstrap reset script is now bootstrap-systest.sh.

echo "[INFO] bootstrap-production.sh has been superseded by scripts/bootstrap-systest.sh"
exec bash "$(dirname "$0")/bootstrap-systest.sh" "$@"