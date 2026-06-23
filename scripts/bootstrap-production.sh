#!/bin/bash

set -euo pipefail

# Compatibility wrapper.
# The canonical virgin/bootstrap reset script is now bootstrap-systest.sh.
# This script:
# - Drops and recreates all databases (TW4_base, TW4_live, TW4_history, TW4_holding)
# - Imports baseline schemas
# - Applies seed data (admin user, config)
# - Applies post-bootstrap migrations
# - Clears all report files and old logs
# - Leaves round state at day-one defaults (Start Round presents Round 1)

echo "[INFO] bootstrap-production.sh delegates to scripts/bootstrap-systest.sh"
exec bash "$(dirname "$0")/bootstrap-systest.sh" "$@"
