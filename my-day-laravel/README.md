# My Day

A Laravel 12 daily companion with a Blade view and plain browser JavaScript. It provides a locally saved to-do list, a memory game, and technology-help guides.

The application requires PHP 8.2 or later and Composer. Install the Composer dependencies, create an `.env` from `.env.example`, generate an application key, and start the Laravel development server. No JavaScript build step is required; CSS and JavaScript live in `public/css` and `public/js`.

Tasks and notes are stored in the Laravel database and scoped to the signed-in account. Users can register, sign in, sign out, edit tasks, set due dates, filter tasks, and save a note. In-app reminders appear for due or overdue tasks when the app is open; they are not push notifications. The news section supports search, category filters, saved stories, related stories and an admin-only editor. The news feed contains manually curated summaries with links to their original sources; it is not automatically updated. Factory-generated stories remain available as unpublished fictional samples. Laravel protects private routes with authentication and CSRF middleware; login and registration are rate limited. Existing anonymous database records are claimed during registration in the same browser session, and compatible legacy browser-local tasks are imported at registration. Dates do not send notifications.

This is a development prototype. Email verification, password recovery, transport security and production hosting, account management, push notifications, live news ingestion, weather, advertising, and Android packaging are not implemented. Do not use the development configuration or store sensitive information in a public deployment. Run database migrations when setting up the application.
