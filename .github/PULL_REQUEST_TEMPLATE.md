## Summary

Short description of the changes and the security hardening applied.

## Changes
- Fixed SQL injection risks by converting vulnerable queries to prepared statements
- Enabled error logging and removed error suppression
- Added lightweight CI checks and security-scan

## Checklist
- [ ] Tested locally (php -l)
- [ ] No secrets committed
- [ ] Added/updated tests if applicable

## Notes for reviewers
Run the CI workflow and focus review on DB queries and any remaining `->query()`/`mysqli_query()` usages.