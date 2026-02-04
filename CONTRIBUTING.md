Thank you for contributing — quick guide

1) Local checks (before opening PR)
   - php -l <file.php>  # syntax check
   - ./scripts/secret_scan.sh  # detect accidental secrets in tracked files

2) Environment
   - Copy `.env.example` → `.env` and populate values
   - Do NOT commit secrets or `.env`

3) Tests & static analysis
   - If the project has `composer.json`: `composer install && vendor/bin/phpstan analyse` and `vendor/bin/phpunit`

4) Security issues
   - Do NOT open a public issue containing secrets. Use a private repository/disclosure channel or contact the maintainers and follow `SECURITY.md`.

5) Pull request
   - Open PR against `main` with a clear title and description. CI will run automatically.
