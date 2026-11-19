# Laboratory Exercise 7 - Facinal Hardware

Laravel 12 / PHP 8.2 inventory replenishment and supplier management, extending the previous inventory system. This branch is `lab7-procurement`.

## Features

- Supplier directory with contact details, search, active filters, pagination, soft deletion and administrator restoration. Historical orders retain their supplier.
- Preferred and alternative supplier agreements with unit cost, minimum quantity, package size and lead time.
- Purchase orders with price snapshots, tax, draft/pending/approved/sent/partially received/received/rejected/cancelled states and activity history.
- Policy authorization: buyers create orders; another manager approves; orders of PHP 50,000 or more require a manager and a different administrator. Warehouse staff receive goods.
- Goods received notes, partial deliveries, damaged units and replacement deliveries. Database transactions and row locks keep receipts and stock consistent. UUID receipt keys prevent double posting.
- Reorder point = average daily usage x supplier lead time + safety stock. Outstanding orders count toward inventory position. Reorders are grouped by supplier, respect package/minimum rules, and remain drafts for human review.
- Four queued, branded emails, including a purchase order PDF attachment. Mailpit captures local messages without sending external email.
- Four dashboard widgets with View Composers, authenticated JSON endpoints, 30-second polling, retry feedback and short cache lifetimes.
- Supplier performance charts and metrics, advanced filters, and CSV/PDF exports for products, orders, transactions and supplier performance. CSV formula injection is escaped.

## Setup

Use PHP 8.2+, Composer, MySQL and Node.js. Install dependencies with `composer install` and `npm ci`. Copy `.env.example` to `.env`, set your own database credentials, then run:

```sh
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve --port=8007
```

The local installation uses MySQL on port 3307 and database `lab7_facinal_2411600169`. It is separate from the previous lab databases. Do not run destructive reset commands against another database.

For local email, run Mailpit with SMTP port 1027 and web port 8027. Set `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1027`, `MAIL_MAILER=smtp`, and `QUEUE_CONNECTION=database`, then run:

```sh
php artisan queue:work --tries=3 --timeout=60
php artisan inventory:check-reorders
php artisan schedule:list
php artisan schedule:work
```

The scheduler checks daily at 08:00 Asia/Manila. Keep `schedule:work` running locally; on a server, run `php artisan schedule:run` once a minute from cron or Task Scheduler. Keep a queue worker running for mail. The command logs errors and queues an alert; inspect `storage/logs` and failed jobs if mail delivery fails. No automatic supplier dispatch occurs.

## Seeded local accounts

| Role | Email | Password |
| --- | --- | --- |
| Administrator | manager@facinalhardware.test | Hardware!2026 |
| Purchasing manager | purchasing.manager@facinalhardware.test | Purchasing!2026 |
| Buyer | buyer@facinalhardware.test | Purchasing!2026 |
| Warehouse clerk | clerk@facinalhardware.test | Warehouse!2026 |

These are local classroom accounts with fictitious addresses. Change or remove them before deployment. The seeder creates 18 products, six supplier catalogs and varied purchasing history; the browser verification added Northline Fasteners and a fully received order.

## Validation

`php artisan test` runs 65 tests, including rollback after a stock-update exception, role restrictions, two-level approvals, partial receipts, duplicate receipt prevention, reorder calculations, supplier history, metrics, filters and real PDF/CSV output. An additional 18 browser checks passed through the live MySQL application, including an actual create/approve/send/receive workflow and all eight export combinations.

The provided Windows session reports incorrect PHP filesystem readability/writability flags despite successful file access. Its dependency manifest and PHPUnit bootstraps were prepared outside application source as a local runtime workaround. On a normal PHP installation, standard Composer and Artisan commands apply. No Laravel framework files were changed.

The private `.env`, installed dependencies, logs and caches are excluded from Git. Composer/package lockfiles, migrations, seeders, tests and source assets are included.
