# Marketplace development

## Accepted direction

- Laravel 12 and Vue 3 / Inertia / Tailwind; no Bootstrap.
- Nepal storefront, NPR, Khalti as the normal online payment option.
- PayPay adapter and JPY sandbox tests kept separate from NPR customer payments.
- No email provider or hosting account yet. Capture mail through Mailpit locally, with SMTP configuration ready for later.
- Test email recipient stays in ignored local environment configuration.
- Make implementation decisions autonomously; do not ask further questions.
- Preserve existing MySQL data, SQLite backup, and user modifications.

## Completed baseline

- Catalog, filters, variant stock, retail and approved wholesale prices.
- Modern storefront, product detail dialog, bag quantity controls, separate staff workspace.
- Order requests, stock reservation/restoration, duplicate submission protection.
- Docker MariaDB/Adminer and verified data-import command. Actual Docker access remains blocked in the agent execution sandbox.
- 57 tests passed before marketplace expansion; TypeScript and frontend build passed.

## Implemented marketplace expansion

1. Payment attempt records, gateway services, trusted server verification, reconciliation, retry protection, payment failure handling.
2. Order emails, local mail capture, mail test command.
3. Customer order history and signed guest tracking.
4. Product pages, staff product creation and photo upload, paginated customer order history. Staff lists show the latest 50 records.
5. CI on SQLite/MariaDB, frontend checks, security audits, deployment package and environment-gated deployment workflow.
6. Setup documentation and local validation. Real MySQL/provider/SMTP/deployment verification remains external.

## External limits

No live merchant keys, SMTP account, or hosting configured. Do not invent completed payments, email deliveries, deployments, or GitHub workflow runs. The cached Laravel package has known advisories; online package refresh is required before a public launch. No sandbox escalation is available. User Docker configuration was modified externally; preserve host settings when fixing a demonstrable mapping error.

## Latest verification

- 80 automated tests passed, 416 assertions, isolated SQLite.
- TypeScript, ESLint, Prettier, Pint and production frontend build passed.
- Workflow/Compose YAML parsed; deployment scripts passed Bash syntax checks.
- Production archive built and inspected: dependencies/assets included; local environment, databases and uploads excluded.
- Actual MySQL migration attempted but blocked by sandbox socket permissions. User host port is currently 3340 and preserved.
- Main marketplace changes committed locally; no GitHub remote or push available.

## Follow-up review

- Customer cancellation added with explicit confirmation; signed guest access and unrelated-user denial tested.
- Unexpired provider checkout can be resumed without another payment attempt.
- Partial refunds block fulfillment and add a staff review audit event; completed refunds cannot regress to pending.
- PayPay POST signing tested against actual transmitted JSON and headers.
- Email jobs deduplicate queued outbox dispatches while retaining delivery retries.

Follow-up validation: 85 tests passed (458 assertions); TypeScript, ESLint, formatting and production build passed.
