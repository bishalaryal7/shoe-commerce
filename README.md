# EVE Shoes — Reach higher. Walk further.

Laravel 12 + Vue 3 / TypeScript / Inertia storefront and staff workspace. Main market: Nepal, NPR. PHP 8.4+ and Node 22+ required. Demo catalog prices and images are placeholders; enter actual selling prices before launch.

## Run on this laptop

```bash
cd ~/Projects/shoe-commerce
export PATH="/opt/homebrew/opt/php@8.5/bin:$PATH"
docker compose up -d --wait
php artisan config:clear
php artisan migrate
php artisan storage:link
npm run build
php artisan serve --port=8002
```

Open http://127.0.0.1:8002. Match `APP_URL` to this address; reuse an existing server on that port. Run `php artisan queue:work --tries=5` in another terminal for notifications, and `php artisan schedule:work` for payment reconciliation and unsent mail retries. Use `npm run dev` while editing frontend code.

MariaDB persists in the Docker volume. The local `.env` selects the host port through `DB_PORT` (currently 3340); Docker forwards it to MariaDB port 3306. Database: `shoe_commerce`, user: `laravel`. Adminer: http://localhost:8101, system MySQL, server `mysql`, user `laravel`, password from `.env`, database `shoe_commerce`. Do not open the database port in a browser.

Mailpit: http://localhost:8026. Local SMTP uses 127.0.0.1:1026. `php artisan shop:mail-test` sends to the private test address configured in `.env`, captured in Mailpit; it does not deliver to Gmail. Configure a real SMTP provider later. `php artisan shop:mail-outbox` retries unsent notifications.

Register your own account, then grant staff access with `php artisan shop:admin your-email@example.com`. Visit `/admin`. No administrator password is seeded. An existing customer account must be granted staff access once; the access-denied page identifies the signed-in email so you can grant the correct account.

After signing in as staff, use **Inventory** to edit names, descriptions, categories, prices and visibility. The workspace counts low-stock sizes and marks them in the size grid; change the default five-pair alert level with `SHOP_LOW_STOCK_THRESHOLD`. The customer description appears on the product page. Choose **Replace product photo**, preview the selected JPG/PNG/WebP (up to 5 MB), then save it. Export inventory as CSV, edit its **Stock** column and import it to update up to 1,000 sizes at once; every SKU and quantity is checked before stock changes are written, and each change is recorded in the inventory audit. Uploads are stored on the public disk; `php artisan storage:link` makes them available to customers. Regular catalog updates require no source-code edits. The development server explicitly allows uploads above the 5 MB application limit; `public/.user.ini` provides matching PHP-FPM limits. The application still rejects files over 5 MB. Restart the development server after updating this code.

## Brand and languages

EVE Shoes takes its name from Everest. The storefront uses the slogan **Reach higher. Walk further.** The header includes English/नेपाली selection, persisted in the session and a language-preference cookie. Storefront, checkout, order, review and authentication interface copy are translated. Inventory supports optional Nepali product names and descriptions, with English fallback for merchant content. Search matches either name. Run `php artisan migrate` before editing bilingual fields or using saved shoes and reviews.

## Application structure and validation

Routes apply session/CSRF, authentication, staff authorization and throttling. Form Requests validate checkout, product content/prices, wholesale applications, cart quantities, language changes and staff actions. Services handle order placement, inventory/order state transitions, photo storage and staff access. Repositories handle catalog, order, inventory and user persistence. Payment adapters verify authoritative provider responses.

Contact fields have length/type limits and phone validation (including normalization of Nepali digits); checkout also shows inline client errors. Orders use server prices and atomic stock reservation. Staff cannot grant customer access without authorization, remove their own staff role, fulfill unpaid orders or release unsettled payment stock. Wholesale prices cannot exceed retail. The validation suites assert that invalid input creates no order and leaves inventory unchanged.

After the initial owner grant, **Admin → Team** lets staff grant/revoke access to existing registered accounts without editing the database.

## What is built

- Responsive storefront, global search, category/size/rating filters, price and rating sorting, product pages, size selection, stock checks and quantity controls.
- Saved shoes (guest session, merged into the signed-in account), recently viewed shoes with a clear-history control, and practical foot-measuring guidance on product pages. Guests' saved shoes remain on their current browser until sign-in; signed-in lists persist in MySQL.
- Side-by-side comparison for up to three shoes, including current customer or approved wholesale price, available sizes, color options, review rating, and product description. The comparison list stays in the current browser session.
- Verified-purchase reviews with 1–5 star ratings and optional fit feedback, sorted by newest, rating or helpful votes. Signed-in accounts can vote once per review and remove their vote; authors cannot vote on their own review. Public pages do not expose buyer or order details. Reviews are not prefilled with demo ratings.
- Retail and approved wholesale pricing, shared inventory, partner applications and a 12-pair wholesale minimum.
- Server-calculated checkout totals, immutable order item snapshots, atomic inventory reservation and duplicate submission protection.
- Customer order history, private signed guest tracking links, unpaid order cancellation and resuming unexpired provider checkout.
- Staff product creation, prices, photo uploads, inventory adjustments, spreadsheet-ready inventory export and atomic bulk stock import, searchable and paginated orders/payments, order audit history, confirmation, payment receipt recording, fulfillment and cancellation.
- Khalti server-side initiation and authoritative lookup verification. Redirect parameters never mark an order paid.
- PayPay HMAC integration and isolated JPY sandbox testing. PayPay is excluded from NPR checkout.
- Transactional email outbox, queued receipts/status updates, retry commands and scheduled reconciliation.
- Order and inventory audit records. Unsettled payments block stock release; fulfillment requires recorded payment.
- Email/password authentication, Google and Facebook sign-in, verified-phone SMS sign-in, account connections, password resets and two-factor authentication. External sign-in options need provider credentials.
- Cash on delivery for NPR orders, with unpaid status until staff record a collection receipt. Wholesale phone numbers are mandatory.
- Automated tests, GitHub quality/security workflows, release packaging and manual SSH deployment workflow.

Shipping is a configured flat amount in minor units; tax is configured in basis points. Defaults are zero. This is not a shipping carrier or jurisdictional tax integration. Reservations require staff cancellation; automatic expiration and a returns/refund processing workflow are not implemented. Refund lookup records provider status but never initiates a refund or automatically restocks. Partial refunds hold fulfillment for staff review.

## Sign-in providers

Read [Sign-in and payment setup](docs/SIGN_IN_AND_PAYMENTS.md) for Google, Facebook, Twilio Verify and Khalti credentials, callback URLs and test steps. Run `php artisan shop:integrations` for a safe configuration summary. Phone sign-in uses numbers verified in account settings; entering a checkout/wholesale contact number alone never grants account access.

## Payments

Cash on delivery works for NPR orders without merchant keys. Staff must record the collection receipt before fulfillment. `SHOP_CASH_ON_DELIVERY=false` disables it.

Keep both providers in sandbox while testing. Add merchant keys only to ignored `.env`, then `php artisan config:clear`.

```dotenv
KHALTI_ENABLED=true
KHALTI_SANDBOX=true
KHALTI_SECRET_KEY=your_test_dashboard_secret
PAYPAY_ENABLED=true
PAYPAY_SANDBOX=true
PAYPAY_API_KEY=your_sandbox_key
PAYPAY_API_SECRET=your_sandbox_secret
PAYPAY_MERCHANT_ID=your_sandbox_merchant_id
```

`APP_URL` must be the real reachable callback URL. Khalti appears in NPR checkout only after configuration. Run `php artisan shop:payment-sandbox khalti` or `php artisan shop:payment-sandbox paypay` to create an isolated test session; use the printed reference with `--lookup=REFERENCE` to check it. These attempts do not reserve products or create customer orders. Real provider tests have not run because merchant credentials are absent.

References: [Khalti ePayment](https://docs.khalti.com/khalti-epayment/), [PayPay authorization](https://www.paypay.ne.jp/opa/doc/v1.0/api_authorization.html), [PayPay dynamic QR](https://www.paypay.ne.jp/opa/doc/v1.0/dynamicqrcode).

## Tests and CI

```bash
php artisan test
npm run typecheck
npm run test:frontend
npm run lint:check
npm run format:check
vendor/bin/pint --test
npm run build
```

Default tests force isolated in-memory SQLite, regardless of local `.env`. For isolated MariaDB:

```bash
docker compose -f docker-compose.test.yml up -d --wait
php artisan test --configuration=phpunit.mysql.xml
```

Never point test configuration at the shop database. CI runs SQLite and MariaDB on PHP 8.4/8.5, frontend checks/build, and dependency audits. CI has not executed on GitHub yet.

## Deployment preparation

`.github/workflows/deploy.yml` runs verified CI before manual deployment from main. Configure staging/production GitHub environments, SSH secrets and variables listed in that workflow. Use a PHP-capable host with MariaDB, HTTPS and document root `current/public`. Provision shared `.env` and writable `shared/storage`, a persistent queue worker, and a cron running `php artisan schedule:run` every minute. Set production `APP_ENV=production`, `APP_DEBUG=false`, correct HTTPS `APP_URL`, database, SMTP, and merchant settings. Rebuild cached configuration after changes.

Release packaging requires a clean committed worktree and frontend assets built from that same commit. Build metadata prevents accidentally pairing old JavaScript with newer PHP code.

The deploy script uploads a release, links shared files, runs migrations, switches the current symlink, restarts queue workers and checks `/ready`. It can restore the previous code symlink on failure; it does not reverse database migrations. Back up the database before deploys and use compatible additive migrations. No host or live deployment has been configured.

Cached dependencies were used because this session cannot resolve external package hosts. Laravel 12.53 has known advisories: update Laravel to at least 12.61.1 and run online Composer/npm audits before production. The security CI job intentionally blocks a release when advisories fail. This project is not yet cleared for a public launch.

## Data and Git

`shop:move-to-mysql` preserves SQLite, backs it up, imports into a new/empty schema and verifies data before switching `.env`. Do not run it again against an already populated MySQL database; use normal migrations for new features. Never remove the Docker volume to update the application.

`.env`, runtime databases, uploaded files, dependencies and generated releases are ignored. Git commits are local; GitHub push is blocked by this session's network and unavailable authentication. When access is restored:

```bash
gh auth login -h github.com
gh repo create shoe-commerce --private --source=. --remote=origin --push
```

Docker/MySQL, real SMTP, browser preview and live payment callbacks cannot be exercised from this restricted session. Run the startup commands above in the laptop terminal to apply new migrations and inspect the site.
