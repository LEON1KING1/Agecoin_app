#!/usr/bin/env bash
set -euo pipefail
echo "Running lightweight security scan..."
ROOT_DIR=$(git rev-parse --show-toplevel 2>/dev/null || printf ".")
cd "$ROOT_DIR"

ERRORS=0
check() {
  if grep -nH --line-number -E "$1" "$2"; then
    ERRORS=$((ERRORS+1))
  fi
}

# Patterns to flag (intentional — require human review)
check "error_reporting\(0\)" "$(git ls-files '*.php')"
check "\@file_put_contents" "$(git ls-files '*.php')"
check "\@file_get_contents" "$(git ls-files '*.php')"
check "\{\$[A-Za-z0-9_]+\}" "$(git ls-files '*.php')"
check "mysql_query\(|mysqli_query\(|->query\(" "$(git ls-files '*.php')"

if [ "$ERRORS" -ne 0 ]; then
  echo "\nSecurity-scan: found $ERRORS suspicious occurrences — please review the flagged locations."
  exit 2
fi

echo "No high-confidence issues found by the lightweight scan."