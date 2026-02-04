#!/usr/bin/env bash
set -euo pipefail

# Simple repository secret scanner used by CI.
# - looks for obvious API keys, private key blocks, and known literal tokens
# - exits non-zero if suspicious matches are found

ROOT=$(git rev-parse --show-toplevel 2>/dev/null || echo ".")
cd "$ROOT"

# files/dirs to ignore in the scan (git ls-files respects .gitignore)
EXCLUDES=(vendor node_modules assets)

# patterns to flag (conservative, add more as needed)
PATTERNS=(
  "BEGIN RSA PRIVATE KEY"
  "-----BEGIN PRIVATE KEY-----"
  "APIKEY"
  "BOT_TOKEN"
  "MYSQL_PASS"
  "MYSQL_USER"
  "AGECOIN_BOT_APIKEY"
  "WEBHOOK_SECRET"
  "E4gqiX1n"
  "7074221280"
)

echo "[secret-scan] scanning tracked files for high-risk literals..."
FOUND=0
for p in "${PATTERNS[@]}"; do
  # search only tracked files to avoid scanning build outputs
  if git ls-files -z | xargs -0 grep -nIH --color=always -E "$p" >/dev/null 2>&1; then
    echo "[secret-scan] MATCH for pattern: $p"
    git ls-files -z | xargs -0 grep -nIH --color=always -E "$p" || true
    FOUND=1
  fi
done

# heuristic: long base64-ish/hex strings (>= 30 chars) — may cause false-positives
if git ls-files -z | xargs -0 grep -nIH --color=always -E "[A-Za-z0-9_\-]{30,}" | grep -vE "\.(map|svg|png|jpg|gif)" >/dev/null 2>&1; then
  echo "[secret-scan] WARNING: long token-like strings detected (run manually to review)"
  git ls-files -z | xargs -0 grep -nIH --color=always -E "[A-Za-z0-9_\-]{30,}" | grep -vE "\.(map|svg|png|jpg|gif)" || true
  FOUND=1
fi

if [ "$FOUND" -ne 0 ]; then
  echo "\n[secret-scan] FAILURE: potential secrets found. Do NOT merge until reviewed." >&2
  echo "If these are false-positives, update scripts/secret_scan.sh to whitelist safely." >&2
  exit 1
fi

echo "[secret-scan] no high-confidence secrets found in tracked files."
exit 0
