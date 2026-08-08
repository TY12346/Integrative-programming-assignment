# FoodLink
## Implemented modules

1. **User & Partner Management** - registration by role, login/logout, role checks, profile editing, document upload, admin user search/filter, account status updates, and verification reviews.
2. **Food Donation Management** - create/edit/cancel donations, view own donations, view/filter available donations, upload a donation photo, and track quantity/status/expiry.
3. **Food Request Management** (NG JIA QIN) - request dashboard with active/history views, create/edit/cancel requests, reserved and delivered quantity tracking, fulfilment deadline monitoring, a request status lifecycle, and browsing/filtering/searching active donations before reserving them. See [docs/module-3.3-food-request-management.md](docs/module-3.3-food-request-management.md).
4. **Delivery Task Management** - create delivery tasks from reservations, list delivery tasks, update delivery status, and store delivery status history.

## Database tables

Migrations create these assignment tables: `users`, `partner_profiles`, `verification_documents`, `verification_reviews`, `user_sessions`, `food_categories`, `food_donations`, `donation_photos`, `donation_status_histories`, `food_requests`, `reservations`, `delivery_tasks`, and `delivery_status_histories`.

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

Integration drafts 
3.1 only ACTIVE + APPROVED charities pass the policy.
3.2 DonationGateway reads donations either locally or over their REST service (FOODLINK_DONATION_GATEWAY=http); 
    reserving decrements current_quantity, cancelling restores it.
3.4 DeliveryTaskObserver translates their delivery_status = DELIVERED into reservation_status = COMPLETED, which raises my fulfilled quantity automatically.
    GET /api/v1/requests/{id}/status feeds impact dashboard.
