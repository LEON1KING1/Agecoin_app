# Security checklist (short)

1. Remove secrets from git history and rotate keys (bot API key, DB credentials).
2. Use environment variables (see `.env.example`).
3. Enforce HTTPS + HSTS and secure cookies.
4. Server-side input validation and prepared statements (done for key endpoints).
5. Add rate-limiting / IP throttling on sensitive endpoints (wallet connect, tasks verify).
6. Protect administrative endpoints with a strong secret (recommended):
   - Configure `AGECOIN_ADMIN_TOKEN` in the environment and never commit it to source control.
   - Admin HTTP endpoints will reject requests if the token is not configured (fail-closed).
   - For web UIs, require a server-side session or enter the token in the browser (session-only) — do NOT embed the token in client JS.
   Example (env): `AGECOIN_ADMIN_TOKEN=replace-with-long-random-value`
7. Anti-cheat (short-term):
   - Server-side uniqueness checks (prevent duplicate task claims).
   - Score delta caps and anomaly detection for large jumps.
   - Queue suspicious payouts for manual review.
8. Monitoring & alerting for abnormal activity and failed signature attempts.

How to report a security issue: open a private issue and **do not** include secrets in the report.