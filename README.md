# Lab 5 Hardware Inventory System

A fresh Laravel 12 project for Laboratory Exercise 5, with Breeze authentication, product CRUD, stock alerts, transaction history, a database dashboard, and printable/CSV reports.

## Setup

Requires PHP 8.2+, Composer, Node.js/npm, and MySQL/MariaDB. In this folder run:

    composer install
    copy .env.example .env
    php artisan key:generate

Create the MySQL database lab5_facinal_2411600169. Configure database credentials in .env. Normal XAMPP uses port 3306; the isolated development database for this lab uses 3307.

    npm install
    npm run build
    php artisan migrate --seed
    php artisan serve

Open http://127.0.0.1:8000.

Local inventory manager email: manager@facinalhardware.test
Local inventory manager password: Hardware!2026

Replace the published local credentials before deployment to a shared server.

## MVC file map

- Model: app/Models/Product.php defines fields, casts, stock status, and relationships.
- Controller: app/Http/Controllers/ProductController.php implements seven resource actions.
- Validation: app/Http/Requests/ProductRequest.php checks fields and normalizes SKUs.
- Views: resources/views/products contains the catalog, details, and shared create/edit form.
- Routes: routes/web.php applies authentication middleware.
- Schema and opening inventory: database/migrations and database/seeders.
- Theme and responsive layout: public/css/custom.css.

Low stock means quantity at or below reorder_level, including zero. Out-of-stock items are counted separately too. Quantity edits create stock movements. Product deletion also deletes its stock history.

## Verification

25 browser checks passed: protected routes, incorrect password, login, dashboard totals, search/filtering, validation, unique SKU, CSRF, CRUD, stock history, CSV export, mobile overflow, and logout.

Seed totals: 18 products; 6 low-stock alerts; 2 out-of-stock products; PHP 87,190.00 stock value.

## Version control

Do not commit .env, vendor, node_modules, cache files, or logs. Install dependencies and generate an application key after cloning.
