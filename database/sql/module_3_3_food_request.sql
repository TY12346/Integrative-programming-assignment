-- =====================================================================
-- FoodLink - Module 3.3 Food Request Management
-- Author : NG JIA QIN
-- File   : database/sql/module_3_3_food_request.sql
--
-- SQL script for the tables owned by the Food Request Management module,
-- together with the data that populates them for the demonstration.
--
-- The tables match the entity classes on the team's analysis class diagram
-- exactly: FoodRequest and Reservation. No extra entity was introduced.
--
-- It is the SQL equivalent of:
--   database/migrations/2026_01_01_000000_create_foodlink_tables.php  (team base)
--   database/migrations/2026_02_01_000000_extend_food_request_module.php (this module)
--
-- Run it against the `foodlink` database AFTER the shared base tables
-- (users, partner_profiles, food_categories, food_donations) exist:
--   mysql -u root -p foodlink < database/sql/module_3_3_food_request.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Table structure
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `food_requests`;

-- A food request raised by a charity: what is needed, how much, and by when.
CREATE TABLE `food_requests` (
    `request_id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `charity_id`         BIGINT UNSIGNED NOT NULL,
    `category_id`        BIGINT UNSIGNED NOT NULL,
    `requested_quantity` DECIMAL(10,2)   NOT NULL,
    `fulfilled_quantity` DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `unit`               VARCHAR(255)    NOT NULL,
    `notes`              TEXT            NULL,
    `request_deadline`   DATETIME        NOT NULL,
    `request_status`     ENUM('PENDING','PARTIALLY_FULFILLED','COMPLETED','CANCELLED','EXPIRED')
                         NOT NULL DEFAULT 'PENDING',
    `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`request_id`),
    KEY `food_requests_owner_status_index` (`charity_id`, `request_status`),
    KEY `food_requests_deadline_index` (`request_deadline`),
    CONSTRAINT `food_requests_charity_id_foreign`
        FOREIGN KEY (`charity_id`) REFERENCES `partner_profiles` (`profile_id`),
    CONSTRAINT `food_requests_category_id_foreign`
        FOREIGN KEY (`category_id`) REFERENCES `food_categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- The quantity a donor has committed from one donation to one request.
CREATE TABLE `reservations` (
    `reservation_id`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`         BIGINT UNSIGNED NOT NULL,
    `donation_id`        BIGINT UNSIGNED NOT NULL,
    `reserved_quantity`  DECIMAL(10,2)   NOT NULL,
    `reservation_status` ENUM('PENDING','CONFIRMED','CANCELLED','COMPLETED')
                         NOT NULL DEFAULT 'CONFIRMED',
    `pickup_deadline`    DATETIME        NOT NULL,
    `created_at`         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`reservation_id`),
    KEY `reservations_request_status_index` (`request_id`, `reservation_status`),
    CONSTRAINT `reservations_request_id_foreign`
        FOREIGN KEY (`request_id`) REFERENCES `food_requests` (`request_id`),
    CONSTRAINT `reservations_donation_id_foreign`
        FOREIGN KEY (`donation_id`) REFERENCES `food_donations` (`donation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bearer token used by the module's REST web service. Only the SHA-256 hash of
-- the token is stored, never the token itself.
ALTER TABLE `users`
    ADD COLUMN `api_token` VARCHAR(64) NULL UNIQUE AFTER `password_hash`;

-- ---------------------------------------------------------------------
-- 2. Sample data
--    Assumes partner_profiles.profile_id = 2 is the seeded charity and
--    food_categories 1..4 are Rice / Vegetables / Bakery / Canned Food.
-- ---------------------------------------------------------------------

INSERT INTO `food_requests`
    (`request_id`, `charity_id`, `category_id`, `requested_quantity`, `fulfilled_quantity`, `unit`, `notes`, `request_deadline`, `request_status`)
VALUES
    (1, 2, 1, 20.00,  0.00, 'packs', 'Rice packs for the daily meal programme.',            DATE_ADD(NOW(), INTERVAL 2 DAY),  'PENDING'),
    (2, 2, 4, 80.00,  0.00, 'boxes', 'Monthly food bank top-up for 60 families.',           DATE_ADD(NOW(), INTERVAL 6 DAY),  'PENDING'),
    (3, 2, 3, 40.00,  0.00, 'packs', 'Bread for tomorrow morning breakfast programme.',     DATE_ADD(NOW(), INTERVAL 8 HOUR), 'PENDING'),
    (4, 2, 2, 60.00,  0.00, 'kg',    'Fresh vegetables for the community kitchen.',         DATE_ADD(NOW(), INTERVAL 2 DAY),  'PARTIALLY_FULFILLED'),
    (5, 2, 3, 20.00, 20.00, 'trays', 'Pastries for the weekend soup kitchen.',              DATE_SUB(NOW(), INTERVAL 1 DAY),  'COMPLETED'),
    (6, 2, 4, 30.00,  0.00, 'boxes', 'Canned food drive that no donor could cover in time.',DATE_SUB(NOW(), INTERVAL 2 DAY),  'EXPIRED');

-- Reservation 1 belongs to the seeded rice donation (donation_id = 1),
-- reservation 2 to the vegetable donation created by FoodRequestSeeder.
INSERT INTO `reservations`
    (`reservation_id`, `request_id`, `donation_id`, `reserved_quantity`, `reservation_status`, `pickup_deadline`)
VALUES
    (1, 1, 1, 10.00, 'CONFIRMED', DATE_ADD(NOW(), INTERVAL 1 DAY)),
    (2, 4, 2, 25.00, 'CONFIRMED', DATE_ADD(NOW(), INTERVAL 1 DAY)),
    (3, 5, 1, 20.00, 'COMPLETED', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Demo REST API bearer token for charity@foodlink.test.
-- Plain token : foodlink-charity-demo-token
UPDATE `users`
   SET `api_token` = SHA2('foodlink-charity-demo-token', 256)
 WHERE `email` = 'charity@foodlink.test';

SET FOREIGN_KEY_CHECKS = 1;
