#!/usr/bin/env bash
set -euo pipefail
ROOT=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT"
version=${1:-$(jq -r '.version' composer.json 2>/dev/null || echo 'v0.0.0')}
out="${ROOT}/release/${version}.zip"
rm -f "$out"
mkdir -p release
zip -r "$out" . -x "**/vendor/**" "**/.git/**" "**/node_modules/**" "**/storage/**" "**/tests/**"
echo "Created $out"