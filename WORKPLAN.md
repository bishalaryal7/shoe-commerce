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
4. Product pages, staff product creation and photo upload, paginated customer order history. Staff orders and payments are paginated, with order search and status/payment filters.
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

## Staff workspace review

Staff orders/payments now use independent 20-record pagination. Orders support customer/reference/phone/email search and combined order/payment status filters. Each order exposes its recorded audit history and staff actor. Readiness checks require the audit schema. Validation: 89 tests, 528 assertions; TypeScript, lint, formatting and production frontend build passed.

## Completion audit: still open

| Requirement | Current evidence | Remaining verification |
| --- | --- | --- |
| Laravel/Vue retail and wholesale shop | Catalog/cart/checkout/partner authorization feature tests; compiled Vue assets | Actual browser interaction and visual inspection against the running app |
| Khalti and PayPay scaffolding | Trusted amount/status checks, callback/retry/refund tests; official PayPay HMAC vector and transmitted JSON/header test | Actual sandbox merchant sessions/callbacks with credentials |
| Transactional email | Outbox and rendered receipt tests; queued jobs, retry scheduling, Mailpit configuration | SMTP capture on the running laptop; external delivery once a provider exists |
| Customer order history | Ownership/signed guest access, cancellation and checkout-resume tests | Browser navigation |
| Product/staff management | Product creation/upload authorization and inventory tests; staff pagination/filter/audit tests | Running MySQL migrations and browser upload/edit flows |
| Meaningful automated tests | 89 passing SQLite tests, 528 assertions; frontend quality/build passed | MariaDB matrix in Docker/GitHub |
| CI/CD | Parsed workflows, Bash syntax checks and previously built inspected production archive | GitHub push/workflow execution and real staging deployment |
| Production dependency safety | Known cached Laravel advisories documented; CI security gate blocks releases | Online dependency update and clean audits |

The project is not marked complete: missing runtime verification, network/authentication, merchant credentials and hosting remain material. Do not replace these checks with another passing SQLite run. Existing data and local environment are preserved.

## Release consistency review

Revalidated external access: Docker socket connections are still denied and GitHub CLI reports invalid authentication. Added frontend commit metadata and packaging checks that reject uncommitted work or frontend assets from a different commit. This prevents mixing PHP and JavaScript versions in an uploaded release. Live workflow/deployment verification remains unavailable.

## Owner usability updates

Added category/description editing and photo preview/reset to the existing staff inventory controls. Customer descriptions preserve line breaks. A denied staff page now identifies the signed-in account and offers an account switch, retaining all authorization checks. Attempting the owner's staff grant was blocked by MySQL socket restrictions; no role change was claimed. Public storage symlink created successfully. Preserved the user's untracked code-workspace file.

## EVE depth, validation and bilingual pass

User requested EVE/Everest branding, deeper storefront behavior, comprehensive validation, proper service/repository/controller/route separation, and Nepali. Implemented EVE brand/slogan, mountain marks, English/Nepali session preference and core customer/auth translations. Staff can maintain Nepali names/descriptions and manage team access. Search supports both names and prioritizes results over the marketing hero when filtered.

Checkout/product/wholesale/cart/staff mutations have dedicated Form Requests. Order placement, product creation/photo replacement and staff operations use services; catalog/order/inventory/variant/user persistence has repositories. Contact rules, price relationships and quantity boundaries are validated. Client contact checks have a separate test suite included in CI. Duplicate checkout tokens are checked against the current account before any further payment action.

A new additive migration introduces optional Nepali content fields; actual MySQL migration remains blocked by this session's socket restrictions. Full bilingual copy/visual browser verification remains open; missing translations use English fallback. Amazon's shoe listing could not be opened by the browsing tool; no claim of visual comparison is made. User asked for Astra, but this session has no callable model-switch capability. Preserve the user's untracked workspace file.

The language switch is available on login pages and restores the preference from an encrypted cookie when the session changes. Nepali phone digits are normalized consistently in client and server validation. Added an original Everest-inspired brand hero using the imagegen skill; asset and full prompt are documented in docs/BRAND.md. The hero is compact on mobile and hidden during filtered catalog searches. This pass does not assert a visually verified or perfect production store.

Found a real upload mismatch: local PHP allowed only 2 MB while the UI advertised 5 MB. The development ServeCommand now passes suitable PHP limits to its child server, and PHP-FPM has matching public/.user.ini settings. Application validation still caps uploads at 5 MB; server arguments are tested without binding a socket.

Latest pass verification: 102 backend tests (691 assertions), 4 frontend contact-validation tests, TypeScript, ESLint, Prettier and Pint checks. The generated hero is 1536×1024 WebP, approximately 202 KB. CI now runs the client validation tests. Actual browser/MySQL/provider/GitHub execution remains unverified.
