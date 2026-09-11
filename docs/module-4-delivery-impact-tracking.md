# Module 4 - Delivery & Impact Tracking

**Author:** KHOO SHENG HAO  
**Design pattern:** Observer Pattern

## Functions

1. Create delivery task from an active reservation.
2. View delivery tasks and delivery details/history.
3. Update delivery status using controlled transitions:
   - ASSIGNED -> PICKED_UP or CANCELLED
   - PICKED_UP -> DELIVERED or CANCELLED
4. Track completed-delivery impact automatically.
5. Provide a REST/JSON web service for delivery creation, viewing and status updates.

## MVC + ORM

- Model: `DeliveryTask`, `DeliveryStatusHistory`, `DeliveryImpact`, `DeliveryApiRequest`
- View: `resources/views/deliveries/*`
- Controller: `DeliveryController` and `Api/DeliveryApiController`
- ORM: Laravel Eloquent models/relationships are used for database access. Raw PDO queries are not required by this module.

This adapts the same idea taught in the JSON web-service practical, but keeps the assignment's required Laravel MVC and ORM architecture. Instead of `SELECT * FROM ...` through PDO, the service uses Eloquent models such as `DeliveryTask::query()` and `DeliveryTask::create()`.

## REST / JSON Web Service

The Module 4 API uses JSON over HTTP:

| Method | Endpoint | Purpose | Security |
| --- | --- | --- | --- |
| GET | `/api/v1/deliveries` | View delivery tasks visible to the API user | Bearer token |
| GET | `/api/v1/deliveries/{delivery}` | View one delivery task | Bearer token |
| POST | `/api/v1/deliveries` | Create a delivery task | Bearer token + HMAC |
| PATCH | `/api/v1/deliveries/{delivery}/status` | Update delivery status | Bearer token + HMAC |

The GET endpoint corresponds closely to the practical pattern:

```text
Client PHP
   -> HTTP GET
Delivery REST API
   -> JSON response
json_decode(..., true)
   -> PHP associative array
```

A demonstration client is provided in `scripts/delivery_api_read_client.php`. It intentionally uses `file_get_contents()` and `json_decode()` so the relationship with the practical exercise is easy to explain.

The API does not expose delivery records without authentication because records include pickup and delivery addresses. Only active administrator/volunteer accounts with a valid bearer token can use the Module 4 API. Volunteers can only access deliveries assigned to their own profile; administrators can access all deliveries.

Demo API tokens created by `DeliveryModuleSeeder`:

```text
Admin:     foodlink-delivery-admin-demo-token
Volunteer: foodlink-delivery-volunteer-demo-token
```

Only the SHA-256 hash of each token is stored in `users.api_token`.

## Observer Pattern

`DeliveryImpactObserver` observes the Eloquent `DeliveryTask` model. When the status changes to `DELIVERED`, the observer automatically creates one `FOOD_DELIVERED` impact record containing the reserved food quantity and unit. The unique database constraint makes this idempotent.

```text
DeliveryService updates DeliveryTask
              |
              v
        Eloquent updated event
              |
              v
     DeliveryImpactObserver
              |
              v
       DeliveryImpact record
```

The existing `DeliveryTaskObserver` written by Module 3.3 remains separate. It translates delivery outcomes into reservation outcomes for cross-module integration.

## Secure Coding 1 - Replay / Forged Delivery Update

State-changing REST requests are protected using both an authenticated API identity and HMAC signing.

The POST/PATCH request requires:

- `Authorization: Bearer <API token>`
- `X-FoodLink-Request-Id`: unique ID (16-100 safe characters)
- `X-FoodLink-Timestamp`: Unix timestamp
- `X-FoodLink-Signature`: SHA-256 HMAC

Canonical string:

```text
HTTP_METHOD
REQUEST_PATH
TIMESTAMP
REQUEST_ID
SHA256(RAW_REQUEST_BODY)
```

Signature:

```text
HMAC_SHA256(canonical_string, DELIVERY_API_HMAC_SECRET)
```

Controls:

- Bearer token identifies the real FoodLink user making the API request.
- Only administrators and volunteers can use Delivery API operations.
- Volunteers can only read/update deliveries assigned to themselves.
- `hash_hmac()` creates the expected MAC.
- `hash_equals()` performs constant-time comparison.
- Requests outside the configured timestamp window (default 300 seconds) are rejected.
- Every accepted signed request ID is inserted into `delivery_api_requests`.
- `request_id` has a UNIQUE constraint, so the same captured signed request cannot be accepted twice, including concurrent replay attempts.
- Status history records the authenticated API user as `changed_by`.

## Secure Coding 2 - Stored XSS

Delivery notes are intentionally treated as plain text:

1. `DeliveryNoteSanitizer` removes HTML tags and control characters before storage.
2. Length is limited to 1000 characters by server-side validation.
3. Blade `{{ ... }}` output is used instead of `{!! ... !!}`, so HTML-sensitive characters are encoded on web pages.
4. JSON responses return strings as JSON data; clients should render them as text rather than inserting them as raw HTML.

## Demo setup

Set this in `.env`:

```env
DELIVERY_API_HMAC_SECRET=replace-with-a-long-random-secret-at-least-32-characters
DELIVERY_API_HMAC_WINDOW=300
```

Run:

```bash
php artisan migrate --seed
php artisan serve
```

### 1. Read deliveries through the JSON web service

Windows CMD:

```bat
set FOODLINK_DELIVERY_API_TOKEN=foodlink-delivery-volunteer-demo-token
php scripts/delivery_api_read_client.php
```

The client performs an HTTP GET, receives JSON and decodes it into a PHP array, matching the approach used in the practical exercise.

### 2. Create a delivery through REST

```bat
set FOODLINK_DELIVERY_API_TOKEN=foodlink-delivery-volunteer-demo-token
set DELIVERY_API_HMAC_SECRET=the-same-secret-used-in-your-env
php scripts/delivery_api_create_client.php 2 "Handle with care"
```

### 3. Update delivery status through REST

```bat
php scripts/delivery_api_client.php 1 PICKED_UP "Collected from donor"
php scripts/delivery_api_client.php 1 DELIVERED "Delivered to recipient"
```

After the second valid transition, `DeliveryImpactObserver` creates the impact record automatically.
