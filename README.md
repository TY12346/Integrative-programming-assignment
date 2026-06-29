# FoodLink

FoodLink is a beginner-friendly Laravel prototype for a surplus food donation system. It uses PHP, MySQL, MVC controllers, Blade views, Bootstrap styling, and Eloquent ORM.

## Modules

1. User & Partner Management
2. Food Donation Management
3. Food Request Management
4. Delivery Task Management

## Setup

```bash
composer install
cp .env.example .env
# Edit .env and configure MySQL: DB_DATABASE=foodlink, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Default seeded accounts all use password `password`:

- `admin@foodlink.test`
- `donor@foodlink.test`
- `charity@foodlink.test`
- `volunteer@foodlink.test`

## API endpoints

- `GET /api/partners/{id}/status`
- `GET /api/donations/available`
- `GET /api/requests/{id}/reservations`
- `GET /api/deliveries/{id}/status`

Each response includes a `status`, `timestamp`, and `data` value. Optional query strings such as timestamps can be added later by group members as modules grow.

## Database

Migrations create all database tables. See `database/foodlink_schema_notes.sql` for a short SQL export note.
