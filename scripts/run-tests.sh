#!/usr/bin/env bash
set -euo pipefail
ROOT=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT"

echo "1) PHP syntax check"
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l

echo "\n2) Lightweight security scan"
chmod +x scripts/security-scan.sh
./scripts/security-scan.sh

if [ -f composer.json ]; then
  echo "\n3) composer install (dev)"
  composer install --no-interaction --prefer-dist --no-progress
  echo "\n4) run phpunit"
  vendor/bin/phpunit --colors=always --stop-on-failure
  echo "\n5) run phpstan (level from phpstan.neon)"
  vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=512M || true
else
  echo "composer.json not found — skipping phpunit/phpstan"
fi

echo "\nAll checks passed (or reported)."