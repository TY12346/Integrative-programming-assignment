# Module 3.3 — Food Request Management

**Author:** NG JIA QIN
**System:** FoodLink — Smart Food Rescue & Donation Management System
**SDG:** 2 — Zero Hunger

This document maps my module onto the four sections the individual assignment
report has to cover, and lists the file to open for each claim.

---

## 0. Module functions → code

| # | Module function (proposal 3.3.2) | Where it lives |
|---|---|---|
| 1 | Create Food Request | `RequestController@create/store`, `StoreFoodRequestRequest`, `FoodRequestService@create` |
| 2 | View Request Dashboard | `RequestController@index`, `FoodRequestRepository@dashboard/summary`, `requests/index.blade.php` |
| 3 | Edit Request Details | `RequestController@edit/update`, `UpdateFoodRequestRequest`, `FoodRequestService@update` (blocked once processing starts) |
| 4 | Cancel Food Request | `RequestController@cancel`, `FoodRequestService@cancel` (releases reserved quantity back to donors) |
| 5 | Track Reserved Quantity | `FoodRequest::getReservedQuantityAttribute`, `scopeWithReservedQuantity`, `FoodRequestService@reserve/cancelReservation`, `requests/show.blade.php` |
| 6 | Monitor Fulfillment Deadline | `FoodRequest::getUrgencyAttribute`, `ExpiredState`, `RefreshRequestStatuses` command (scheduled hourly) |
| 7 | Check Request Status | `App\Domain\RequestStatus\*` state classes, derived request timeline on the detail page |
| 8 | Display Active Donations | `RequestController@donations`, `DonationGateway` |
| 9 | Filter Donation Options | `App\Filters\Donation\{Category,StorageType,MinQuantity,ExpiryWindow}Filter` |
| 10 | Search Specific Donations | `App\Filters\Donation\KeywordFilter` |

Request lifecycle: `PENDING → PARTIALLY_FULFILLED → COMPLETED`, with
`CANCELLED` and `EXPIRED` as the two exits.

The module introduces 
PartnerProfile 1 ──◆ 0..* FoodRequest
FoodCategory   1 ─── 0..* FoodRequest
FoodRequest    1 ──◆ 0..* Reservation
FoodDonation   1 ──◆ 0..* Reservation
Reservation    1 ─── 0..1 DeliveryTask

## 1. PHP and MySQL

* Tables owned by the module: `food_requests` and `reservations` — the two
  entity classes the analysis class diagram assigns to it.
* Indexes added for the two queries the dashboard actually runs:
  `(charity_id, request_status)` and `(request_deadline)`.
* Derived values (reserved quantity, outstanding quantity, urgency) are computed
  with `withSum` aggregates and accessors instead of duplicated columns, so they
  can never drift out of date.

**MVC:** Models in `app/Models`, controllers in `app/Http/Controllers`, Blade
views in `resources/views/requests`. Controllers contain no business rules —


## 2. Design Patterns

| Pattern | Where | Why it was needed |

| **State** | `app/Domain/RequestStatus/` — `RequestState` + `Pending/PartiallyFulfilled/Completed/Cancelled/Expired` 
| The request status decides what is allowed (edit, cancel, reserve) and what happens next. Each status owns its own rules, so no `if ($status === ...)` chains exist in the controllers or views. |
| **Repository** | `app/Repositories/FoodRequestRepository.php` 
| One place for every food request query, reused by the web controller and the REST API. Also the single place where ownership scoping and the sort whitelist are enforced. |
| **Strategy** | `app/Filters/Donation/` — `DonationFilter` interface, five concrete filters, `DonationFilterPipeline` context 
| Each donation search criterion is an interchangeable object; a new filter is one line in the service provider instead of another `if` in the controller. |
| **Observer** | `app/Observers/ReservationObserver.php`, `app/Observers/DeliveryTaskObserver.php` 
| The reserved and delivered quantities must stay correct whichever module changed a reservation. The observers recalculate the request automatically, so module 3.4 does not have to call my code. |
| **Adapter / Gateway** | `app/Services/Gateways/DonationGateway` + `Local`/`Http` implementations 
| Donation data belongs to module 3.2. The interface lets my module read it either from the shared database or from the donation REST service without changing a single view. |
| **Singleton (DI container)** | `app/Providers/FoodRequestServiceProvider.php`
| Repository, service and filter pipeline are registered as container singletons and injected by constructor rather than instantiated ad hoc. |


## 3. Secure Coding Practices

| Threat | Control | File |
|---|---|---|
| SQL injection | Eloquent parameter binding everywhere; `ORDER BY` resolved from a fixed whitelist; LIKE metacharacters escaped | `FoodRequestRepository::SORTS`, `KeywordFilter::escapeLike` |
| Broken access control / IDOR | Policy checks ownership against the logged-in partner profile on every action; reservation ids are re-checked against their parent request | `FoodRequestPolicy`, `RequestController@releaseReservation` |
| Mass assignment | `charity_id`, `fulfilled_quantity` and `request_status` are **not** fillable; the service sets them from the session, never from input | `FoodRequest::$fillable`, `FoodRequestService::onlyEditable` |
| Invalid / hostile input | Form Request validation with type, range, whitelist and length rules before the controller runs | `StoreFoodRequestRequest`, `ReserveDonationRequest`, `BrowseDonationRequest` |
| XSS | Blade escaped echoes for all user text; control characters stripped from the search box | all `requests/*.blade.php` |
| CSRF | CSRF token on every state-changing form; safe HTTP verbs for reads only | all forms |
| Race condition / overselling a donation | `DB::transaction` + `SELECT … FOR UPDATE` on both request and donation rows, with the quantity rules re-checked inside the lock | `FoodRequestService@reserve` |
| Weak API authentication | Bearer token; only the SHA-256 hash is stored; suspended accounts rejected; generic 401 | `AuthenticateApiToken`, `users.api_token` |
| Brute force / scraping | `throttle:60,1` on the whole API group | `routes/api.php` |
| Excessive data exposure | API Resources act as an explicit output whitelist | `FoodRequestResource`, `DonationResource` |
| Unsafe outbound call | TLS verification on, timeouts, redirects disabled, response size capped, strict JSON decode | `HttpDonationGateway` |
| Auditability | Every create, edit, cancel, reserve, release and status transition is logged with the acting user id | `FoodRequestService` (`Log::info` calls) |

---

## 4. Web Service Technologies

### Provided (this module is the service)

All under `/api/v1`, JSON, bearer token + rate limited
(`App\Http\Controllers\Api\FoodRequestApiController`):

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/v1/requests` | List own requests (filter, search, sort, paginated) |
| POST | `/api/v1/requests` | Create a food request |
| GET | `/api/v1/requests/{id}` | Request detail with reservations |
| PATCH | `/api/v1/requests/{id}` | Edit a pending request |
| POST | `/api/v1/requests/{id}/cancel` | Cancel a request |
| GET | `/api/v1/requests/{id}/status` | Status + tracked quantities (**consumed by module 3.4**) |
| GET | `/api/v1/requests/{id}/reservations` | Reservations of a request |
| POST | `/api/v1/requests/{id}/reservations` | Reserve a donation |
| GET | `/api/v1/donations` | Active donations with filter and keyword search |

Response envelope, matching the team convention:

```json
{ "status": "success", "timestamp": "2026-09-01T10:00:00+00:00", "data": {} }
```

Demo call (token seeded by `FoodRequestSeeder`):

```bash
curl -H "Authorization: Bearer foodlink-charity-demo-token" -H "Accept: application/json" http://localhost:8000/api/v1/requests
```

### Consumed (this module is the client)

`HttpDonationGateway` calls the donation module's REST endpoint
(`GET /api/donations/available`) with cURL and adapts the JSON back into
`FoodDonation` objects. Switch it on with:

```
FOODLINK_DONATION_GATEWAY=http
FOODLINK_API_BASE_URL=http://localhost:8000
```

If the partner service is unreachable the gateway degrades to the local
implementation, so the charity still sees the donation board.

---

## 5. Integration with the other modules

| Module | Direction | Contract |
|---|---|---|
| 3.1 User & Partner Management (Ong Tin Yin) | consumes | `FoodRequestPolicy` only lets an **ACTIVE + APPROVED** charity create requests or reserve food |
| 3.2 Food Donation Management (Lau Ke Xin) | consumes + writes | Reads active donations through `DonationGateway`; reserving decrements `food_donations.current_quantity` and flips the donation to `RESERVED`, cancelling gives the quantity back |
| 3.4 Delivery & Impact Tracking (Khoo Sheng Hao) | provides | `DeliveryTaskObserver` turns `delivery_status = DELIVERED` into `reservation_status = COMPLETED`, which raises the request's fulfilled quantity; `GET /api/v1/requests/{id}/status` exposes the numbers for the impact dashboard |

---

## 6. Running and checking the module

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Log in as `charity@foodlink.test` / `password`, then use **My Requests** and
**Find Donations**.

Logic self-check (no database or framework needed):

```bash
php tests/module_3_3_selfcheck.php
```

Deadline sweep, also scheduled hourly:

```bash
php artisan foodlink:refresh-requests
```
