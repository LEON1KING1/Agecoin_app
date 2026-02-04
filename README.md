# AgeCoin TG-game — repository

Quick dev & security checklist

## Local pre-merge checks (recommended)
1. PHP lint: find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
2. Run the lightweight security scan: chmod +x scripts/security-scan.sh && ./scripts/security-scan.sh
3. Run full checks (requires composer): composer install && composer test

## CI
- The repository includes a GitHub Action at `.github/workflows/ci.yml` that runs
  - PHP lint
  - security-scan
  - phpunit (if composer.json present)
  - phpstan (if composer.json present)

## Secrets required for full CI/integration tests
- AGECOIN_BOT_APIKEY
- MYSQL_USER, MYSQL_PASS, MYSQL_DBNAME
- APP_ENV (set to `production` in prod)

## Creating the PR (recommended)
- Create branch: `git checkout -b hardening/security-ci`
- Commit all changes, push, open PR with title: `security: harden DB usage, enable error logging, add CI checks`
- Add reviewers and request security review

## Post-merge (deploy) checklist
- Configure `storage/logs` with restricted permissions and logrotate
- Add Sentry or equivalent for error monitoring
- Run PHPStan at high level and fix findings

## Release & deployment (AUR-GAME) 🚀
This repository can be published as **AUR-GAME** (Docker image + GitHub Release).

Quick publish steps (recommended):
1. Tag a release: `git tag v0.1.0 && git push origin v0.1.0` (version stored in `VERSION`).
2. CI will build artifacts and the Docker image and publish to **GHCR** as `ghcr.io/<owner>/aur-game:v0.1.0`.

Local Docker (for quick test):
```bash
# build
docker build -t aur-game:local .
# run
docker run --rm -p 8080:80 -e MYSQL_USER=... -e MYSQL_PASS=... -e MYSQL_DBNAME=... aur-game:local
```

Notes:
- Ensure repository secrets are configured: `GITHUB_TOKEN` (packages write), `AGECOIN_BOT_APIKEY`, `MYSQL_*`.
- After release, enable branch-protection rules on `main` and require CI to pass before merging.
