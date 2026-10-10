# Shoe Commerce

A Laravel + Vue application for retail shoe customers, wholesale partners, and company staff. The temporary company name is **Sole Supply**; change `APP_NAME` in `.env` when your brand is ready.

## Stack

Laravel 12, Vue 3, TypeScript, Inertia 2, Tailwind CSS, and Laravel Fortify. PHP 8.4+ and Node 22+ are required. SQLite is configured for local development; Laravel also supports MySQL and PostgreSQL.

## Run on this laptop

Dependencies, the application key, and the demo database are already prepared.

```bash
cd ~/Projects/shoe-commerce
export PATH="/opt/homebrew/opt/php@8.5/bin:$PATH"
php artisan serve
```

Open http://127.0.0.1:8000. Built frontend assets are available. For frontend development, run `npm run dev` in a second terminal, or use `composer dev` for the combined development processes.

## Fresh installation

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm ci
npm run build
php artisan serve
```

Register your own account through `/register`. No preset administrator credentials are seeded. To grant a trusted team member staff access:

```bash
php artisan shop:admin your-email@example.com
```

Visit `/admin`. Repeat the command for each trusted staff member. Registration cannot grant staff access.

## First version

- Responsive catalog with category filters, search, sizes EU 36–45, and six demo shoe styles.
- Session-backed bag with stock validation and server-calculated prices.
- Guest or authenticated order requests, protected confirmation pages, price snapshots, atomic stock reservation, and duplicate-submission protection.
- Wholesale applications, staff approval/rejection, private approved-partner prices, and a 12-pair minimum across sizes/styles.
- Staff workspace to edit existing product names/prices/listing status, adjust variant stock, review requests, confirm/fulfill orders, cancel and restore stock, and approve/revoke wholesale accounts.
- Registration, login, password reset, account settings, and two-factor authentication from the official starter kit.

Prices use integer minor units. `SHOP_CURRENCY=USD` is the temporary display currency; set it before entering real prices. Demo illustrations, catalog entries, prices, and stock are placeholders. Seeding is idempotent and does not reset existing stock.

## Order behavior

Checkout explicitly creates an **unpaid request**, not a paid purchase. Stock is reserved on submission. Our team must contact the buyer, confirm shipping and taxes, and arrange payment. Pending requests remain reserved until staff cancel or fulfill them. Cancelling restores stock once; fulfilled/cancelled orders cannot be reopened.

Retail customers and approved wholesale customers share inventory. An approved account receives wholesale prices throughout the shop; ordering fewer than 12 pairs is rejected. Staff order status does not claim or record payment.

## Before a public launch

Choose the company's country/currency, payment gateway, tax rules, shipping regions/rates, brand assets, and product photography. Remaining work includes online payments and verified webhooks, transactional email delivery, product creation/photo uploads, automatic reservation expiry, customer order history, staff roles/audit logging, returns, and legal/store policies. No hosting deployment is configured. Enable and validate SSR for the public storefront before launch if desired (`npm run build:ssr` and `php artisan inertia:start-ssr`).

Local dependency installation used the laptop's cached official package archives because this execution environment cannot resolve external hosts. Both lockfiles are committed for reproducible installs. The cached Laravel 12.53 release has three advisories in the available metadata; the fixed Laravel 12 versions are not cached. Upgrade Laravel to at least 12.61.1 and run online `composer audit` and `npm audit` before production. A live audit could not run here.

## Verification

```bash
php artisan test
npm run typecheck
npm run build
```

Feature tests cover private pricing, staff authorization, stock shortage rollback, wholesale minimums, server-calculated prices, duplicate requests, a fresh cart after an order, stock restoration, application self-approval prevention, and confirmation-session ownership. The starter authentication/settings tests remain included.

## GitHub

Once GitHub CLI authentication and network access are available, create a private repository and push:

```bash
gh auth login -h github.com
gh repo create shoe-commerce --private --source=. --remote=origin --push
```

Do not commit `.env`, runtime databases, generated builds, or dependencies; these are ignored.
