#!/bin/bash
# Applies the same post-baseline migrations as scripts/bootstrap-systest.sh so the
# E2E database matches a bootstrapped environment.

set -euo pipefail

MIGRATIONS=(
    036_eclectic_movement_only_and_ident_order
    018_seed_live_round
    037_between_rounds_workflow_state
    038_eclectic_config_and_round_context
    039_team_haggle_floating_setup
    040_team_haggle_serious_audit
    041_handicap_reference_tees
    042_card_entry_reopened
)

for migration in "${MIGRATIONS[@]}"; do
    echo "Applying migration ${migration}..."
    MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root TW4_base < "/migrations/${migration}.sql"
done
