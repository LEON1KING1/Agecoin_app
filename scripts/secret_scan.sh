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

# build list of tracked files but exclude large/bundled dirs that cause false-positives
FILE_LIST=$(git ls-files | grep -vE '^(vendor/|node_modules/|assets/)' || true)

for p in "${PATTERNS[@]}"; do
  if [ -n "$FILE_LIST" ]; then
    # gather matches (filename:lineno:line)
    matches=$(echo "$FILE_LIST" | xargs grep -nIH -E "$p" || true)
    # ignore matches from the scanner itself or documentation
    matches=$(echo "$matches" | grep -vE '^scripts/secret_scan.sh:' | grep -vE '\.md:' || true)
    # only treat as high-confidence if the pattern appears in a literal assignment (e.g. key = '...')
    literal_matches=$(echo "$matches" | grep -E ":|=" | grep -E "[:=]\s*['\"][^'\"]{6,}['\"]" || true)
    if [ -n "$literal_matches" ]; then
      echo "[secret-scan] MATCH for pattern: $p"
      echo "$literal_matches"
      FOUND=1
    fi
  fi
done

# heuristic: long base64-ish/hex strings (>= 30 chars) — may cause false-positives
if [ -n "$FILE_LIST" ]; then
  long_matches=$(echo "$FILE_LIST" | xargs grep -nIH -E "[A-Za-z0-9_\\-]{30,}" || true)
  # ignore matches from scanner/docs and typical binary assets
  long_matches=$(echo "$long_matches" | grep -vE '^scripts/secret_scan.sh:' | grep -vE '\.md:' | grep -vE "\.(map|svg|png|jpg|gif)" || true)
  if [ -n "$long_matches" ]; then
    echo "[secret-scan] WARNING: long token-like strings detected (run manually to review)"
    echo "$long_matches" || true
    FOUND=1
  fi
fi

if [ "$FOUND" -ne 0 ]; then
  echo "\n[secret-scan] FAILURE: potential secrets found. Do NOT merge until reviewed." >&2
  echo "If these are false-positives, update scripts/secret_scan.sh to whitelist safely." >&2
  exit 1
fi

echo "[secret-scan] no high-confidence secrets found in tracked files."
exit 0
