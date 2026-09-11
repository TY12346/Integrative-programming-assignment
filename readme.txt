FOODLINK - SMART FOOD RESCUE & DONATION MANAGEMENT SYSTEM
BMIT3173 INTEGRATIVE PROGRAMMING

Student Module: Delivery & Impact Tracking
Framework: Laravel 12
Language: PHP 8.2+
Database: MySQL / MariaDB

============================================================
1. SYSTEM OVERVIEW
============================================================

FoodLink is a web-based Smart Food Rescue & Donation Management System.
It connects food donors, charities, volunteers and administrators to support
surplus-food donation, food requests, reservations and delivery tracking.

Main implemented modules include:

1. User & Partner Management
   - User registration and login
   - Role-based access
   - Partner profile management
   - Verification and account management

2. Food Donation Management
   - Create, edit and cancel food donations
   - Browse available donations
   - Donation quantity and expiry tracking
   - Donation photo support

3. Food Request Management
   - Create, edit and cancel food requests
   - Browse and reserve suitable food donations
   - Track requested, reserved and fulfilled quantities
   - Food request status lifecycle and progress

4. Delivery & Impact Tracking
   - Create delivery tasks from confirmed reservations
   - View delivery tasks
   - Update delivery status
   - Record delivery status history
   - Automatically record delivery impact when a delivery is completed
   - Expose and consume RESTful web services

============================================================
2. SOFTWARE REQUIREMENTS
============================================================

Required software:

- PHP 8.2 or later
- Composer
- MySQL or MariaDB
- Laravel dependencies installed through Composer

Recommended local environment:

- XAMPP (for MySQL / MariaDB and phpMyAdmin)
- Apache NetBeans, VS Code or another PHP IDE/editor
- Web browser such as Chrome, Edge or Firefox

The application does not require Node.js or npm for the current submitted
prototype.

============================================================
3. INSTALLATION AND SETUP
============================================================

STEP 1 - Extract the project

Extract the submitted ZIP file to a suitable folder.

Example:

D:\FoodLink\Integrative-programming-assignment-main

Open a terminal or PowerShell window in the project root folder. The project
root is the folder containing "artisan" and "composer.json".


STEP 2 - Start MySQL / MariaDB

If XAMPP is used:

1. Open XAMPP Control Panel.
2. Start MySQL.
3. Apache is optional when the application is run using "php artisan serve".


STEP 3 - Install PHP dependencies

Run:

composer install

This creates the vendor folder and installs Laravel and other Composer
packages required by the project.


STEP 4 - Create the environment file

Windows PowerShell:

Copy-Item .env.example .env

Windows Command Prompt:

copy .env.example .env

Alternatively, manually copy ".env.example" and rename the copy to ".env".

Do not commit or distribute a real .env file containing secrets.


STEP 5 - Create the database

Create a MySQL database named:

foodlink

This can be done using phpMyAdmin or the following SQL command:

CREATE DATABASE foodlink
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;


STEP 6 - Configure the database connection

Open the .env file and check the following values:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=foodlink
DB_USERNAME=root
DB_PASSWORD=

For a standard XAMPP installation, the MySQL root account commonly has no
password by default. If your MySQL account has a password, enter the correct
value in DB_PASSWORD.


STEP 7 - Configure Module 3.4 API settings

Add or verify the following values in .env:

DELIVERY_API_HMAC_SECRET=foodlink-module4-hmac-secret-2026
DELIVERY_API_HMAC_WINDOW_SECONDS=300

If the Delivery module consumes the Food Request REST API through
FoodRequestStatusClient, also add:

FOOD_REQUEST_API_URL=http://127.0.0.1:8000/api/v1
FOOD_REQUEST_API_TOKEN=foodlink-charity-demo-token

The HMAC secret above is only a local demonstration value. A real deployed
system should use a strong secret that is not stored in source control.

After modifying .env, run:

php artisan config:clear


STEP 8 - Generate the Laravel application key

Run:

php artisan key:generate


STEP 9 - Create the database tables and seed initial data

For a new installation, run:

php artisan migrate --seed

This runs the Laravel migration files and populates the database with sample
users and demonstration data.

If Module 4 API tokens were not seeded automatically, run:

php artisan db:seed --class=DeliveryModuleSeeder

WARNING:

"php artisan migrate:fresh --seed" deletes all existing tables and data before
recreating them. Use it only when a complete reset is intended.


STEP 10 - Run the application

Run:

php artisan serve --host=127.0.0.1 --port=8000

Then open:

http://127.0.0.1:8000/login

============================================================
4. DEFAULT SEEDED LOGIN ACCOUNTS
============================================================

All default seeded web users use the password:

password

Role            Email
------------------------------------------------------------
Administrator   admin@foodlink.test
Food Donor      donor@foodlink.test
Charity         charity@foodlink.test
Volunteer       volunteer@foodlink.test

============================================================
5. DELIVERY & IMPACT TRACKING MODULE
============================================================

The Delivery & Impact Tracking module is available to verified Volunteer and
Administrator users.

Main functions:

- Create Delivery Task
- View Delivery Task List
- View Delivery Task Details
- Update Delivery Status
- Record Delivery Status History
- Record Delivery Impact

Typical delivery status flow:

ASSIGNED -> PICKED_UP -> DELIVERED

When a delivery reaches DELIVERED, DeliveryImpactObserver reacts to the
DeliveryTask model update and records the delivered food quantity as an impact
record. This implements the Observer design pattern.

Relevant paths include:

app/Http/Controllers/DeliveryController.php
app/Services/DeliveryService.php
app/Observers/DeliveryImpactObserver.php
app/Models/DeliveryTask.php
app/Models/DeliveryImpact.php
resources/views/deliveries/

============================================================
6. REST API ENDPOINTS
============================================================

Delivery & Impact Tracking REST API:

GET    /api/v1/deliveries
GET    /api/v1/deliveries/{delivery}
POST   /api/v1/deliveries
PATCH  /api/v1/deliveries/{delivery}/status

GET requests require Bearer-token authentication.

POST and PATCH requests additionally require HMAC-signed request headers:

X-FoodLink-Request-Id
X-FoodLink-Timestamp
X-FoodLink-Signature

The HMAC mechanism uses HMAC-SHA256, timestamp validation and unique request
IDs to protect state-changing requests against forgery and replay attacks.

Demo Delivery API tokens:

Administrator:
foodlink-delivery-admin-demo-token

Volunteer:
foodlink-delivery-volunteer-demo-token

Only SHA-256 hashes of these demo tokens are stored in the database.


Food Request REST API consumed by the Delivery module:

GET /api/v1/requests/{foodRequest}/status

Demo Food Request token:

foodlink-charity-demo-token

The response contains request status and fulfilment information such as
requested quantity, fulfilled quantity, outstanding quantity and progress
percentage.

============================================================
7. EXAMPLE API TEST - READ DELIVERY
============================================================

PowerShell example:

$headers = @{
    Authorization = "Bearer foodlink-delivery-admin-demo-token"
    Accept = "application/json"
}

Invoke-RestMethod `
    -Uri "http://127.0.0.1:8000/api/v1/deliveries/1?requestID=DELIVERY-GET-0001" `
    -Headers $headers `
    -Method GET

The response is returned as JSON.

For signed POST/PATCH API demonstrations, helper scripts are available in:

scripts/delivery_api_client.php
scripts/delivery_api_create_client.php
scripts/replay_test.php

Before using the signed client scripts, the client and server must use the
same DELIVERY_API_HMAC_SECRET value.

============================================================
8. SECURITY FEATURES
============================================================

The Delivery & Impact Tracking module includes the following security
controls:

1. Bearer Token Authentication
   - Identifies authenticated API users.
   - API token hashes are stored rather than plain API tokens.

2. HMAC-SHA256 Request Signing
   - Protects the integrity and authenticity of state-changing API requests.

3. Timestamp Validation
   - Rejects signed requests that fall outside the configured time window.

4. Replay Attack Protection
   - Each signed request contains a unique request ID.
   - Used request IDs are stored in delivery_api_requests.
   - A repeated request ID is rejected as a replay attempt.

5. Stored XSS Protection
   - Delivery notes and remarks are sanitised before storage.
   - HTML tags are removed by DeliveryNoteSanitizer.
   - Laravel Blade {{ }} escaped output is used when displaying stored text.

6. Input Validation
   - Laravel Form Request classes validate delivery creation and status-update
     input before business logic is executed.

7. Role and Ownership Checks
   - Delivery web functions are restricted to verified Administrators and
     Volunteers.
   - Volunteers can access only their own assigned delivery tasks.

============================================================
9. DATABASE FILES
============================================================

Laravel database setup files are located in:

database/migrations/
database/seeders/

Important Delivery module database files include:

database/migrations/2026_09_10_000000_extend_delivery_impact_module.php
database/seeders/DeliveryModuleSeeder.php

The migrations create and extend the required database tables. The seeders
populate demonstration data and API credentials.

If a complete exported SQL script is included in the submission, it should be
placed under:

database/sql/

For example:

database/sql/foodlink_full.sql

The SQL export should contain both table structures and initial data.

============================================================
10. COMMON TROUBLESHOOTING
============================================================

Problem: "Connection refused" / database connection error
Solution:
- Start MySQL in XAMPP.
- Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env.

Problem: "vendor/autoload.php" missing
Solution:
- Run: composer install

Problem: Application key error
Solution:
- Run: php artisan key:generate

Problem: Environment/configuration changes do not take effect
Solution:
- Run: php artisan config:clear

Problem: API returns 401 Unauthorized
Solution:
- Check that the correct Bearer token is supplied.
- Seed the required API demo token if necessary.

For Delivery API demo tokens:

php artisan db:seed --class=DeliveryModuleSeeder

Problem: Signed POST/PATCH request returns "Invalid signed request"
Solution:
- Check that the client and Laravel application use the same HMAC secret.
- Check X-FoodLink-Timestamp.
- Check X-FoodLink-Request-Id.
- Ensure the request body used to calculate the signature is unchanged.

Problem: Repeated signed request returns "Replay request rejected"
Solution:
- This is expected security behaviour when the same signed request ID is used
  more than once.
- Use a new unique request ID for a new legitimate request.

Problem: Database contains old or inconsistent development data
Solution:
- If it is safe to delete all existing development data, run:

  php artisan migrate:fresh --seed

============================================================
11. IMPORTANT SUBMISSION NOTES
============================================================

- Do not include a real .env file containing private secrets.
- Include .env.example so the system can be configured after extraction.
- Include composer.json and composer.lock.
- The vendor folder does not need to be submitted if Composer is available;
  run "composer install" after extraction.
- Include all migration and seeder files.
- Include the complete SQL export required by the assignment, if prepared.
- Source files should retain the required author/module headers.

============================================================
12. QUICK START SUMMARY
============================================================

1. Start MySQL.
2. Open the project root in a terminal.
3. Run: composer install
4. Copy .env.example to .env
5. Create MySQL database: foodlink
6. Configure DB settings and Module 3.4 API settings in .env
7. Run: php artisan key:generate
8. Run: php artisan migrate --seed
9. If required, run: php artisan db:seed --class=DeliveryModuleSeeder
10. Run: php artisan serve --host=127.0.0.1 --port=8000
11. Open: http://127.0.0.1:8000/login
12. Login using one of the default seeded accounts.

*PS. THIS TEXT FILE WAS GENERATED WITH THE HELP OF CHATGPT*

END OF README
