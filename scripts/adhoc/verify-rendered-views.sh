#!/bin/bash

set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CHECK_IMAGE=false
IMAGE_NAME=""

usage() {
    cat <<'EOF'
Usage: ./scripts/verify-rendered-views.sh [--check-image IMAGE_NAME]

Validates that every view referenced by render('...') in src/Controllers:
1. Exists on disk under src/Views/<view>.php
2. Is tracked by git
3. (optional) Exists in a built container image under /var/www/html/src/Views
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --check-image)
            if [[ $# -lt 2 ]]; then
                echo "Error: --check-image requires an image name"
                usage
                exit 2
            fi
            CHECK_IMAGE=true
            IMAGE_NAME="$2"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Error: Unknown argument '$1'"
            usage
            exit 2
            ;;
    esac
done

cd "$REPO_ROOT"

if ! command -v rg >/dev/null 2>&1; then
    echo -e "${RED}[FAIL]${NC} ripgrep (rg) is required"
    exit 1
fi

if ! command -v git >/dev/null 2>&1; then
    echo -e "${RED}[FAIL]${NC} git is required"
    exit 1
fi

if $CHECK_IMAGE && ! command -v docker >/dev/null 2>&1; then
    echo -e "${RED}[FAIL]${NC} docker is required when using --check-image"
    exit 1
fi

echo "Checking controller render targets..."

mapfile -t render_targets < <(
    rg -No "render\('([^']+)'" src/Controllers/*.php \
        | sed -E "s/.*render\('([^']+)'.*/\1/" \
        | sort -u
)

if [[ ${#render_targets[@]} -eq 0 ]]; then
    echo -e "${YELLOW}[WARN]${NC} No render targets found in src/Controllers"
    exit 0
fi

missing_files=()
untracked_files=()
view_paths=()

for view in "${render_targets[@]}"; do
    path="src/Views/${view}.php"
    view_paths+=("$path")

    if [[ ! -f "$path" ]]; then
        missing_files+=("$path")
        continue
    fi

    if ! git ls-files --error-unmatch "$path" >/dev/null 2>&1; then
        untracked_files+=("$path")
    fi
done

if [[ ${#missing_files[@]} -gt 0 ]]; then
    echo -e "${RED}[FAIL]${NC} Missing view files referenced by controllers:"
    for path in "${missing_files[@]}"; do
        echo "  - $path"
    done
fi

if [[ ${#untracked_files[@]} -gt 0 ]]; then
    echo -e "${RED}[FAIL]${NC} Untracked view files referenced by controllers:"
    for path in "${untracked_files[@]}"; do
        echo "  - $path"
    done
fi

image_missing=()
if $CHECK_IMAGE; then
    if ! docker image inspect "$IMAGE_NAME" >/dev/null 2>&1; then
        echo -e "${RED}[FAIL]${NC} Docker image not found: $IMAGE_NAME"
        exit 1
    fi

    mapfile -t image_missing < <(
        printf "%s\n" "${view_paths[@]}" \
            | docker run --rm -i --entrypoint /bin/sh "$IMAGE_NAME" -c '
                while IFS= read -r path; do
                    if [ ! -f "/var/www/html/${path}" ]; then
                        echo "$path"
                    fi
                done
            '
    )

    if [[ ${#image_missing[@]} -gt 0 ]]; then
        echo -e "${RED}[FAIL]${NC} View files missing from image $IMAGE_NAME:"
        for path in "${image_missing[@]}"; do
            echo "  - $path"
        done
    fi
fi

if [[ ${#missing_files[@]} -eq 0 && ${#untracked_files[@]} -eq 0 && ${#image_missing[@]} -eq 0 ]]; then
    echo -e "${GREEN}[PASS]${NC} All rendered views exist, are tracked in git, and passed checks."
    exit 0
fi

exit 1
