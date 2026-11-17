# Lab 6 Real Time Inventory Tracking

This project extends Lab 5 with audited stock movements, Eloquent observers, queued low-stock notifications, physical-count reconciliation, JSON endpoints, and a dashboard that polls every 30 seconds.

## Requirements and setup

Use PHP 8.2+, Composer, Node.js/npm, and MySQL or MariaDB.

    composer install
    copy .env.example .env
    php artisan key:generate
    npm ci
    npm run build

Create a database named lab6_facinal_2411600169. Update the DB_* values in .env.
Standard XAMPP normally uses port 3306. The isolated database on the development laptop uses 3307.

    php artisan migrate --seed
    php artisan serve --host=127.0.0.1 --port=8006

In a second terminal, start the queue worker:

    php artisan queue:work --sleep=1 --tries=3

Open http://127.0.0.1:8006.

## Local lab accounts

Administrator:
- Email: manager@facinalhardware.test
- Password: Hardware!2026

Warehouse staff:
- Email: clerk@facinalhardware.test
- Password: Warehouse!2026

These credentials are published for local coursework. Replace them before shared deployment. Public registration creates staff accounts; it cannot assign administrator privileges.

## Stock rules

- Stock In and Stock Out accept positive whole-number quantities.
- The observer calculates the signed change and stores before/after balances.
- InventoryService wraps the audit insertion and stock update in one database transaction and locks the product row.
- Excessive stock-out and unsafe reversal requests fail without changing stock or the audit log.
- Physical counts record only the signed difference and are restricted to administrators.
- Reversing a movement creates a compensating record. The original is retained and cannot be reversed twice.
- Product deletion archives the product with soft deletes and preserves its transactions.
- Stock movements cannot be edited or deleted through the model's normal lifecycle.

## Notifications and live data

InventoryChanged dispatches after a successful commit. The listener queues database and email notifications for administrators. A product flag prevents duplicate alerts during one continuous low-stock episode. Replenishment above the reorder level resets that flag.

MAIL_MAILER=log is the default for this local lab. Email content is written to storage/logs/laravel.log; no external email service is configured. Database alerts still appear through the queue worker.

Authenticated, same-origin endpoints:
- GET /api/dashboard/stats
- GET /api/products/low-stock
- GET /api/transactions/recent
- GET /api/products/{product}

public/js/dashboard.js polls the three dashboard endpoints every 30 seconds, updates text safely, animates changed totals, displays the last update time, and recovers after network failures. Refresh now requests an immediate update.

## Main source files

- app/Services/InventoryService.php: atomic stock operations and reversal rules
- app/Observers/InventoryTransactionObserver.php: automatic quantities and running balances
- app/Events/InventoryChanged.php and app/Listeners/SendLowStockAlerts.php: events and notifications
- app/Notifications/LowStockNotification.php: queued database and mail channels
- app/Http/Controllers/InventoryTransactionController.php: stock workflow and filters
- app/Http/Resources: JSON data shape
- resources/views/transactions: movement, adjustment, and audit views
- public/js/stock-form.js: AJAX stock preview and quantity limits
- public/js/dashboard.js: 30-second polling

## Reports

The reports page provides dated stock movements, current low stock, and current valuation. Each report exports to CSV. Print / save PDF uses the browser print dialog.

## Tests

    php artisan test

phpunit.xml uses SQLite in memory so tests do not modify the working MySQL database. InventoryTrackingTest covers movement balances, overselling rollback, actor identity, staff restrictions, reversals, archiving, and authenticated API access.

## Important operational details

Keep .env, dependencies, cache files, and logs out of version control. Keep the queue worker running for alerts. These pages refresh on a 30-second polling interval; they do not use WebSockets. Email sending requires a separately configured SMTP service.

The automatic stock update is implemented by the Eloquent observer requested in the activity. A second SQL trigger is intentionally not used because applying both would double-count stock changes.
