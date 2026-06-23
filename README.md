# Wealth Tracker API

A self-hosted personal finance management platform consolidating
stocks, mutual funds, FDs, bonds, gold, PPF, and EPF across multiple
platforms into a single API-driven dashboard.

This is the **API repo** (Laravel). The Vue 3 frontend lives in a
separate repo: [wealth-tracker-vue](link-once-it-exists).

## Stack
- Laravel 13 (PHP 8.4)
- Sanctum (token auth)
- Pest (testing)
- Swagger / OpenAPI (l5-swagger)
- MySQL

 

## Local Setup
\`\`\`bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
\`\`\`

API docs available at `/api/documentation` once running.