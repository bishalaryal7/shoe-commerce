# EVE Shoes: activate sign-in and payments

The code supports Google, Facebook, SMS sign-in, Khalti and PayPay. Provider credentials are deliberately empty. Buttons become available when the relevant credentials are configured; a configured flag does not prove that the provider account or callback is approved. Cash on delivery is available for NPR orders without external credentials.

## Local setup

```bash
cd ~/Projects/shoe-commerce
export PATH="/opt/homebrew/opt/php@8.5/bin:$PATH"
php artisan migrate
php artisan config:clear
php artisan serve --port=8002
```

Use the existing server if it already runs on port 8002. Set `APP_URL=http://127.0.0.1:8002` in `.env`. Keep the address, port and hostname consistent. Use HTTPS and your real domain for production. Put credentials only in ignored `.env` or the hosting secret store. Run `php artisan config:clear` and restart queue workers after changing configuration. `php artisan shop:integrations` reports missing configuration without printing secrets or sending requests.

## Google

Create a Google OAuth web application, configure its consent screen and test users, and register the exact callback `http://127.0.0.1:8002/auth/google/callback` for local testing. Add:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Use the actual production callback when deploying. See [Google web-server OAuth setup](https://developers.google.com/identity/protocols/oauth2/web-server).

## Facebook

Create a Meta app with Facebook Login, configure the website domain and valid OAuth redirect URI, and add development test users as permitted by your app configuration. Register your allowed callback ending in `/auth/facebook/callback`; use an HTTPS development domain when required by Meta. Add:

```dotenv
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

Public sign-in requires the provider app's publishing requirements to be satisfied. See [Meta Facebook Login for the web](https://developers.facebook.com/docs/facebook-login/web/).

For both providers, an existing EVE Shoes account with the same email is **never automatically linked**. Sign in using the existing method, then visit **Account settings → Sign-in methods** to connect it. A provider that does not share an email can still be linked to an existing authenticated account. New social accounts receive an unguessable password; use **Email a reset link and sign out** under Sign-in methods to establish a personal password before managing connections. Local reset messages appear in Mailpit until a real mail provider is configured. Provider access/refresh tokens are not stored. Existing two-factor authentication remains required.

## Phone / SMS

Create a Twilio Verify service with SMS enabled and the destination countries you need allowed. Configure:

```dotenv
PHONE_SIGN_IN_ENABLED=true
TWILIO_ACCOUNT_SID=
TWILIO_AUTH_TOKEN=
TWILIO_VERIFY_SERVICE_SID=
```

See [Twilio Verify SMS](https://www.twilio.com/docs/verify/api/verification). Trial accounts and destination permissions can restrict delivery. Codes are real provider-issued SMS codes; there is no fixed development code and no code is logged.

Customers first create an account using email or a social provider, then verify their mobile number under **Sign-in methods**. Afterwards **Sign in with phone** sends an SMS code to that verified number. Nepali mobile numbers accept local `98…`/`97…`, `+977…`, spaces and Nepali numerals; other countries need their international prefix. The wholesale contact number is independently mandatory and does not enable phone sign-in until verified.

Each challenge is bound to its browser session and purpose, lasts ten minutes, allows five checks, and can be used once. Sends have number and IP limits. Connecting/removing a method requires password confirmation. Unknown/unverified numbers receive the same response without sending SMS. Expired challenges are pruned by the daily scheduler. Enable the provider's abuse protections and monitor SMS usage before public launch.

## Khalti

Get the merchant secret key from [Khalti's test dashboard](https://test-admin.khalti.com/). The test dashboard may name this `live_secret_key`; the dashboard/environment determines whether it is a sandbox key. Do not copy documentation example keys as your merchant credentials.

```dotenv
KHALTI_ENABLED=true
KHALTI_SANDBOX=true
KHALTI_SECRET_KEY=
```

Follow the [official ePayment setup](https://docs.khalti.com/khalti-epayment/). Test a normal NPR checkout or run `php artisan shop:payment-sandbox khalti`. Complete payment at the provider, return to the shop, then check the provider-verified status. Browser success parameters alone cannot mark an order paid. Verify twice to confirm the receipt is not duplicated. Test cancellation and failed payment too.

An approved production merchant account and production secret are needed before setting `KHALTI_SANDBOX=false`. Never switch environments while unresolved attempts are outstanding; resolve them first. PayPay remains a separate JPY sandbox option using the existing `shop:payment-sandbox paypay` command, not a payment option for NPR orders.

## Cash on delivery

`SHOP_CASH_ON_DELIVERY=true` offers cash on delivery for NPR orders. Checkout reserves stock and records an **unpaid** order. Staff confirm delivery by contacting the customer. After collection, staff enter the cash receipt/reference under **Admin → Orders → Record received payment**, then mark the order fulfilled. The system never marks cash collected just because the customer placed an order. Set the flag to `false` if your delivery operation does not collect cash.

## Verification performed

Automated tests use provider fakes and an isolated test database. They cover state/callback validation, account ownership and email collisions, two-factor challenges, SMS expiry/replay/rate limits, unavailable providers, COD receipts and mandatory wholesale phone validation. Live provider sign-in, real SMS, real merchant transactions and the laptop's MySQL migration require the external services to be available. They are not certified by the automated fake-provider tests.
