# FoodLink

FoodLink is a beginner-friendly Laravel prototype for a surplus food donation system. It uses PHP, MySQL, MVC controllers, Blade views, Bootstrap styling, and Eloquent ORM.

## Implemented modules

1. **User & Partner Management** - registration by role, login/logout, role checks, profile editing, document upload, admin user search/filter, account status updates, and verification reviews.
2. **Food Donation Management** - create/edit/cancel donations, view own donations, view/filter available donations, upload a donation photo, and track quantity/status/expiry.
3. **Food Request Management** (NG JIA QIN) - request dashboard with active/history views, create/edit/cancel requests, reserved and delivered quantity tracking, fulfilment deadline monitoring, a status lifecycle with full history, and browsing/filtering/searching active donations before reserving them. See [docs/module-3.3-food-request-management.md](docs/module-3.3-food-request-management.md).
4. **Delivery Task Management** - create delivery tasks from reservations, list delivery tasks, update delivery status, and store delivery status history.

## Database tables

Migrations create these assignment tables: `users`, `partner_profiles`, `verification_documents`, `verification_reviews`, `user_sessions`, `food_categories`, `food_donations`, `donation_photos`, `donation_status_histories`, `food_requests`, `reservations`, `delivery_tasks`, `delivery_status_histories`, and `request_status_histories` (module 3.3).

## Setup instructions

```bash
composer install
cp .env.example .env
# Create a MySQL database named foodlink and update DB_USERNAME / DB_PASSWORD in .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Default seeded users

All seeded users use the password `password`.

| Role | Email |
| --- | --- |
| Admin | `admin@foodlink.test` |
| Food donor | `donor@foodlink.test` |
| Charity | `charity@foodlink.test` |
| Volunteer | `volunteer@foodlink.test` |

## API endpoints

- `GET /api/partners/{id}/status`
- `GET /api/donations/available?timestamp=2026-01-01T00:00:00Z`
- `GET /api/requests/{id}/reservations?requestID=1&timestamp=2026-01-01T00:00:00Z`
- `GET /api/deliveries/{id}/status`

Each response includes a `status`, `timestamp`, and `data` value.

### Food Request Management REST API (module 3.3, NG JIA QIN)

Versioned, authenticated with a bearer token and rate limited to 60 calls/minute:

- `GET|POST /api/v1/requests`
- `GET|PATCH /api/v1/requests/{id}`
- `POST /api/v1/requests/{id}/cancel`
- `GET /api/v1/requests/{id}/status` (consumed by the delivery module)
- `GET|POST /api/v1/requests/{id}/reservations`
- `GET /api/v1/donations?keyword=&category_id=&storage_type=&min_quantity=&expires_within_hours=`

```bash
curl -H "Authorization: Bearer foodlink-charity-demo-token" -H "Accept: application/json" http://localhost:8000/api/v1/requests
```

## SQL export note

A separate SQL dump is not required for the prototype because Laravel migrations are the source of truth. See `database/foodlink_schema_notes.sql` for the short database note, and `database/sql/module_3_3_food_request.sql` for the hand-written create + populate script of the food request tables.
