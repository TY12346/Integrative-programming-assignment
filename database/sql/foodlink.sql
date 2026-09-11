-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2026 at 06:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `foodlink`
--
CREATE DATABASE IF NOT EXISTS `foodlink` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `foodlink`;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_api_requests`
--

DROP TABLE IF EXISTS `delivery_api_requests`;
CREATE TABLE `delivery_api_requests` (
  `request_log_id` bigint(20) UNSIGNED NOT NULL,
  `request_id` varchar(100) NOT NULL,
  `request_timestamp` bigint(20) UNSIGNED NOT NULL,
  `http_method` varchar(10) NOT NULL,
  `request_path` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_api_requests`
--

INSERT INTO `delivery_api_requests` (`request_log_id`, `request_id`, `request_timestamp`, `http_method`, `request_path`, `ip_address`, `processed_at`) VALUES
(1, 'REPLAY-TEST-0001', 1789138047, 'PATCH', '/api/v1/deliveries/2/status', '127.0.0.1', '2026-09-11 14:47:27');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_impacts`
--

DROP TABLE IF EXISTS `delivery_impacts`;
CREATE TABLE `delivery_impacts` (
  `impact_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `impact_type` varchar(50) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `measurement_unit` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_impacts`
--

INSERT INTO `delivery_impacts` (`impact_id`, `delivery_id`, `impact_type`, `quantity`, `measurement_unit`, `description`, `recorded_at`) VALUES
(1, 1, 'FOOD_DELIVERED', 10.00, 'packs', 'Completed delivery of 10 packs of donated food.', '2026-09-11 06:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_status_histories`
--

DROP TABLE IF EXISTS `delivery_status_histories`;
CREATE TABLE `delivery_status_histories` (
  `delivery_history_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) NOT NULL,
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_status_histories`
--

INSERT INTO `delivery_status_histories` (`delivery_history_id`, `delivery_id`, `old_status`, `new_status`, `changed_by`, `changed_at`, `remarks`) VALUES
(1, 1, 'ASSIGNED', 'PICKED_UP', 1, '2026-09-11 06:22:02', 'hnhng'),
(2, 2, NULL, 'ASSIGNED', 1, '2026-09-11 06:33:25', 'fafsaafsafs'),
(3, 1, 'PICKED_UP', 'DELIVERED', 1, '2026-09-11 06:39:05', 'hnhng'),
(4, 3, NULL, 'ASSIGNED', 1, '2026-09-11 07:10:07', 'saddsadsada'),
(5, 3, 'ASSIGNED', 'PICKED_UP', 1, '2026-09-11 07:13:53', 'Picked Up by Jia Qin'),
(6, 2, 'ASSIGNED', 'PICKED_UP', 4, '2026-09-11 14:47:27', 'Replay attack test');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_tasks`
--

DROP TABLE IF EXISTS `delivery_tasks`;
CREATE TABLE `delivery_tasks` (
  `delivery_id` bigint(20) UNSIGNED NOT NULL,
  `reservation_id` bigint(20) UNSIGNED NOT NULL,
  `volunteer_id` bigint(20) UNSIGNED NOT NULL,
  `pickup_address` text NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_status` enum('ASSIGNED','PICKED_UP','DELIVERED','CANCELLED') NOT NULL DEFAULT 'ASSIGNED',
  `delivery_notes` text DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `delivery_tasks`
--

INSERT INTO `delivery_tasks` (`delivery_id`, `reservation_id`, `volunteer_id`, `pickup_address`, `delivery_address`, `delivery_status`, `delivery_notes`, `assigned_at`, `picked_up_at`, `delivered_at`) VALUES
(1, 1, 3, '12 Donor Street', '45 Charity Avenue', 'DELIVERED', 'hnhng', '2026-09-11 06:12:10', '2026-09-11 06:22:02', '2026-09-11 06:39:05'),
(2, 2, 3, '12 Donor Street', '45 Charity Avenue', 'PICKED_UP', 'Replay attack test', '2026-09-11 06:33:25', '2026-09-11 14:47:27', NULL),
(3, 3, 3, '12 Donor Street', '45 Charity Avenue', 'PICKED_UP', 'Picked Up by Jia Qin', '2026-09-11 07:10:07', '2026-09-11 07:13:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `donation_photos`
--

DROP TABLE IF EXISTS `donation_photos`;
CREATE TABLE `donation_photos` (
  `photo_id` bigint(20) UNSIGNED NOT NULL,
  `donation_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donation_status_histories`
--

DROP TABLE IF EXISTS `donation_status_histories`;
CREATE TABLE `donation_status_histories` (
  `donation_history_id` bigint(20) UNSIGNED NOT NULL,
  `donation_id` bigint(20) UNSIGNED NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) NOT NULL,
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `food_categories`
--

DROP TABLE IF EXISTS `food_categories`;
CREATE TABLE `food_categories` (
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `food_categories`
--

INSERT INTO `food_categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Rice', 'Staple food'),
(2, 'Vegetables', 'Fresh produce'),
(3, 'Bakery', 'Bread and pastries'),
(4, 'Canned Food', 'Shelf-stable items');

-- --------------------------------------------------------

--
-- Table structure for table `food_donations`
--

DROP TABLE IF EXISTS `food_donations`;
CREATE TABLE `food_donations` (
  `donation_id` bigint(20) UNSIGNED NOT NULL,
  `donor_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `food_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `donation_quantity` decimal(10,2) NOT NULL,
  `current_quantity` decimal(10,2) NOT NULL,
  `measurement_unit` varchar(255) NOT NULL,
  `expiry_datetime` datetime NOT NULL,
  `pickup_address` text NOT NULL,
  `storage_type` varchar(255) DEFAULT NULL,
  `halal_status` varchar(255) DEFAULT NULL,
  `donation_status` enum('AVAILABLE','RESERVED','COMPLETED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'AVAILABLE',
  `donation_datetime` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `food_donations`
--

INSERT INTO `food_donations` (`donation_id`, `donor_id`, `category_id`, `food_name`, `description`, `donation_quantity`, `current_quantity`, `measurement_unit`, `expiry_datetime`, `pickup_address`, `storage_type`, `halal_status`, `donation_status`, `donation_datetime`) VALUES
(1, 1, 1, 'Cooked Rice Packs', 'Freshly prepared surplus rice packs.', 50.00, 30.00, 'packs', '2026-09-12 14:12:10', '12 Donor Street', 'Room temperature', 'Halal', 'AVAILABLE', '2026-09-11 06:12:10'),
(2, 1, 2, 'Surplus Mixed Vegetables', 'Assorted vegetables from the evening market, still fresh.', 50.00, 25.00, 'kg', '2026-09-13 14:12:10', '12 Donor Street', 'Chilled', 'Halal', 'AVAILABLE', '2026-09-11 06:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `food_requests`
--

DROP TABLE IF EXISTS `food_requests`;
CREATE TABLE `food_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `charity_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `requested_quantity` decimal(10,2) NOT NULL,
  `fulfilled_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `request_deadline` datetime NOT NULL,
  `request_status` enum('PENDING','PARTIALLY_FULFILLED','COMPLETED','CANCELLED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `food_requests`
--

INSERT INTO `food_requests` (`request_id`, `charity_id`, `category_id`, `requested_quantity`, `fulfilled_quantity`, `unit`, `notes`, `request_deadline`, `request_status`, `created_at`) VALUES
(1, 2, 1, 20.00, 10.00, 'packs', 'Rice packs for the daily meal programme.', '2026-09-13 14:12:10', 'PARTIALLY_FULFILLED', '2026-09-11 06:12:10'),
(2, 2, 4, 80.00, 0.00, 'boxes', 'Monthly food bank top-up for 60 families.', '2026-09-17 14:12:10', 'PENDING', '2026-09-11 06:12:10'),
(3, 2, 3, 40.00, 0.00, 'packs', 'Bread for tomorrow morning breakfast programme.', '2026-09-11 22:12:10', 'PENDING', '2026-09-11 06:12:10'),
(4, 2, 2, 60.00, 0.00, 'kg', 'Fresh vegetables for the community kitchen.', '2026-09-13 14:12:10', 'PARTIALLY_FULFILLED', '2026-09-11 06:12:10'),
(5, 2, 3, 20.00, 20.00, 'trays', 'Pastries for the weekend soup kitchen.', '2026-09-10 14:12:10', 'COMPLETED', '2026-09-11 06:12:10'),
(6, 2, 4, 30.00, 0.00, 'boxes', 'Canned food drive that no donor could cover in time.', '2026-09-09 14:12:10', 'EXPIRED', '2026-09-11 06:12:10'),
(7, 2, 1, 10.00, 0.00, 'kg', '', '2026-09-14 15:08:00', 'PARTIALLY_FULFILLED', '2026-09-11 07:08:41');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_01_01_000000_create_foodlink_tables', 1),
(2, '2026_02_01_000000_extend_food_request_module', 1),
(3, '2026_08_16_000000_add_pending_user_status_and_verification_timestamps', 1),
(4, '2026_09_10_000000_extend_delivery_impact_module', 1),
(5, '2026_09_10_220046_add_login_security_fields_to_users_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `partner_profiles`
--

DROP TABLE IF EXISTS `partner_profiles`;
CREATE TABLE `partner_profiles` (
  `profile_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `address` text DEFAULT NULL,
  `verification_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partner_profiles`
--

INSERT INTO `partner_profiles` (`profile_id`, `user_id`, `address`, `verification_status`, `created_at`) VALUES
(1, 2, '12 Donor Street', 'APPROVED', '2026-09-11 06:12:10'),
(2, 3, '45 Charity Avenue', 'APPROVED', '2026-09-11 06:12:10'),
(3, 4, '78 Volunteer Road', 'APPROVED', '2026-09-11 06:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE `reservations` (
  `reservation_id` bigint(20) UNSIGNED NOT NULL,
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `donation_id` bigint(20) UNSIGNED NOT NULL,
  `reserved_quantity` decimal(10,2) NOT NULL,
  `reservation_status` enum('PENDING','CONFIRMED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'CONFIRMED',
  `pickup_deadline` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `request_id`, `donation_id`, `reserved_quantity`, `reservation_status`, `pickup_deadline`, `created_at`) VALUES
(1, 1, 1, 10.00, 'COMPLETED', '2026-09-12 14:12:10', '2026-09-11 06:12:10'),
(2, 4, 2, 25.00, 'CONFIRMED', '2026-09-12 14:12:10', '2026-09-11 06:12:10'),
(3, 7, 1, 10.00, 'CONFIRMED', '2026-09-12 14:12:00', '2026-09-11 07:08:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `api_token` varchar(64) DEFAULT NULL,
  `phone_no` varchar(255) DEFAULT NULL,
  `role` enum('FOOD_DONOR','CHARITY','VOLUNTEER','ADMIN') NOT NULL,
  `account_status` enum('PENDING','ACTIVE','INACTIVE','SUSPENDED','DELETED') NOT NULL DEFAULT 'PENDING',
  `failed_login_attempts` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `last_failed_login_at` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `api_token`, `phone_no`, `role`, `account_status`, `failed_login_attempts`, `last_failed_login_at`, `locked_until`, `created_at`) VALUES
(1, 'Admin User', 'admin@foodlink.test', '$2y$12$scNNGzRE1tYqoaehForL5e6HBiiPe.y6sMUS7BKqCIZeZDH0pL/Ci', 'dfc6d19265f3d45c8d85cbce45ebada5d98387e3236e3899c4dacb54c6e2f63d', NULL, 'ADMIN', 'ACTIVE', 0, NULL, NULL, '2026-09-11 06:12:09'),
(2, 'Donor Partner', 'donor@foodlink.test', '$2y$12$5t2Giz1EgpbkAIvzwyWw4eVDrMwWbu0mMxDOiadEadAjANgKI.EFC', NULL, NULL, 'FOOD_DONOR', 'ACTIVE', 0, NULL, NULL, '2026-09-11 06:12:09'),
(3, 'Charity Partner', 'charity@foodlink.test', '$2y$12$.WA0aZbZ4z19txggx67ppuO.S1DwX5Xr47jyzhtWrkO3foj95MJw2', 'd5c65c6e65c8397205e314fe4047ddd3ea7a82b70f8530b61faf54e236cafa7d', NULL, 'CHARITY', 'ACTIVE', 0, NULL, NULL, '2026-09-11 06:12:10'),
(4, 'Volunteer Rider', 'volunteer@foodlink.test', '$2y$12$GRQdwnOiSEjXAIlOkZdD2enPDX3N/JdqCzNp2gl/GHKmGy.dWbZ8m', '8d57c451f1477433c8c1fe60fb543b9000298a6aa35e780435b06444399eea19', NULL, 'VOLUNTEER', 'ACTIVE', 0, NULL, NULL, '2026-09-11 06:12:10');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_at` timestamp NULL DEFAULT NULL,
  `session_status` enum('ACTIVE','LOGGED_OUT') NOT NULL DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`session_id`, `user_id`, `session_token`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `session_status`) VALUES
(1, 1, 'Z2y593d7zZS2ONifzniUlmdv2miH7Rtcbiob0gjP', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-09-11 06:13:08', '2026-09-11 06:42:00', 'LOGGED_OUT'),
(2, 4, 'fkyLqScLtiq3T0xZRh0SZuaV4orVVTeJujR2bxXf', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-09-11 06:42:16', '2026-09-11 07:07:23', 'LOGGED_OUT'),
(3, 2, '8GhzVB5T39Alpxf8o5kCcaM37SAHa1VE5jGdShnz', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-09-11 07:07:40', '2026-09-11 07:07:57', 'LOGGED_OUT'),
(4, 3, 'iRAj6YXuq4FLb04SGCUCIzxZ6p61wfgYWSZyJKi0', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-09-11 07:08:07', '2026-09-11 07:09:18', 'LOGGED_OUT'),
(5, 1, '5Zkr1HqdDq94NevgSrTOtVcbIvYuxUWIPdlAXV3l', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-09-11 07:09:30', '2026-09-11 07:35:01', 'LOGGED_OUT'),
(6, 1, 'Q4SYjP3bFMCD1FjPRZN50cWzmZrZSrXzGfyzden5', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-09-11 14:41:28', '2026-09-11 14:57:23', 'LOGGED_OUT');

-- --------------------------------------------------------

--
-- Table structure for table `verification_documents`
--

DROP TABLE IF EXISTS `verification_documents`;
CREATE TABLE `verification_documents` (
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `partner_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `document_status` enum('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_reviews`
--

DROP TABLE IF EXISTS `verification_reviews`;
CREATE TABLE `verification_reviews` (
  `review_id` bigint(20) UNSIGNED NOT NULL,
  `partner_id` bigint(20) UNSIGNED NOT NULL,
  `reviewed_by` bigint(20) UNSIGNED NOT NULL,
  `decision` enum('APPROVED','REJECTED') NOT NULL,
  `remarks` text DEFAULT NULL,
  `reviewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `delivery_api_requests`
--
ALTER TABLE `delivery_api_requests`
  ADD PRIMARY KEY (`request_log_id`),
  ADD UNIQUE KEY `delivery_api_requests_request_id_unique` (`request_id`),
  ADD KEY `delivery_api_requests_processed_at_index` (`processed_at`);

--
-- Indexes for table `delivery_impacts`
--
ALTER TABLE `delivery_impacts`
  ADD PRIMARY KEY (`impact_id`),
  ADD UNIQUE KEY `delivery_impact_unique_event` (`delivery_id`,`impact_type`);

--
-- Indexes for table `delivery_status_histories`
--
ALTER TABLE `delivery_status_histories`
  ADD PRIMARY KEY (`delivery_history_id`),
  ADD KEY `delivery_status_histories_delivery_id_foreign` (`delivery_id`),
  ADD KEY `delivery_status_histories_changed_by_foreign` (`changed_by`);

--
-- Indexes for table `delivery_tasks`
--
ALTER TABLE `delivery_tasks`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `delivery_tasks_reservation_id_unique` (`reservation_id`),
  ADD KEY `delivery_volunteer_status_index` (`volunteer_id`,`delivery_status`);

--
-- Indexes for table `donation_photos`
--
ALTER TABLE `donation_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `donation_photos_donation_id_foreign` (`donation_id`);

--
-- Indexes for table `donation_status_histories`
--
ALTER TABLE `donation_status_histories`
  ADD PRIMARY KEY (`donation_history_id`),
  ADD KEY `donation_status_histories_donation_id_foreign` (`donation_id`),
  ADD KEY `donation_status_histories_changed_by_foreign` (`changed_by`);

--
-- Indexes for table `food_categories`
--
ALTER TABLE `food_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `food_donations`
--
ALTER TABLE `food_donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `food_donations_donor_id_foreign` (`donor_id`),
  ADD KEY `food_donations_category_id_foreign` (`category_id`);

--
-- Indexes for table `food_requests`
--
ALTER TABLE `food_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `food_requests_category_id_foreign` (`category_id`),
  ADD KEY `food_requests_owner_status_index` (`charity_id`,`request_status`),
  ADD KEY `food_requests_deadline_index` (`request_deadline`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_profiles`
--
ALTER TABLE `partner_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `partner_profiles_user_id_unique` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `reservations_request_id_foreign` (`request_id`),
  ADD KEY `reservations_donation_id_foreign` (`donation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_api_token_unique` (`api_token`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_sessions_user_id_foreign` (`user_id`);

--
-- Indexes for table `verification_documents`
--
ALTER TABLE `verification_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `verification_documents_partner_id_foreign` (`partner_id`);

--
-- Indexes for table `verification_reviews`
--
ALTER TABLE `verification_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `verification_reviews_partner_id_foreign` (`partner_id`),
  ADD KEY `verification_reviews_reviewed_by_foreign` (`reviewed_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delivery_api_requests`
--
ALTER TABLE `delivery_api_requests`
  MODIFY `request_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `delivery_impacts`
--
ALTER TABLE `delivery_impacts`
  MODIFY `impact_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery_status_histories`
--
ALTER TABLE `delivery_status_histories`
  MODIFY `delivery_history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `delivery_tasks`
--
ALTER TABLE `delivery_tasks`
  MODIFY `delivery_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `donation_photos`
--
ALTER TABLE `donation_photos`
  MODIFY `photo_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donation_status_histories`
--
ALTER TABLE `donation_status_histories`
  MODIFY `donation_history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_categories`
--
ALTER TABLE `food_categories`
  MODIFY `category_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `food_donations`
--
ALTER TABLE `food_donations`
  MODIFY `donation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `food_requests`
--
ALTER TABLE `food_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `partner_profiles`
--
ALTER TABLE `partner_profiles`
  MODIFY `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `verification_documents`
--
ALTER TABLE `verification_documents`
  MODIFY `document_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verification_reviews`
--
ALTER TABLE `verification_reviews`
  MODIFY `review_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery_impacts`
--
ALTER TABLE `delivery_impacts`
  ADD CONSTRAINT `delivery_impacts_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `delivery_tasks` (`delivery_id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_status_histories`
--
ALTER TABLE `delivery_status_histories`
  ADD CONSTRAINT `delivery_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `delivery_status_histories_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `delivery_tasks` (`delivery_id`);

--
-- Constraints for table `delivery_tasks`
--
ALTER TABLE `delivery_tasks`
  ADD CONSTRAINT `delivery_tasks_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`),
  ADD CONSTRAINT `delivery_tasks_volunteer_id_foreign` FOREIGN KEY (`volunteer_id`) REFERENCES `partner_profiles` (`profile_id`);

--
-- Constraints for table `donation_photos`
--
ALTER TABLE `donation_photos`
  ADD CONSTRAINT `donation_photos_donation_id_foreign` FOREIGN KEY (`donation_id`) REFERENCES `food_donations` (`donation_id`);

--
-- Constraints for table `donation_status_histories`
--
ALTER TABLE `donation_status_histories`
  ADD CONSTRAINT `donation_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `donation_status_histories_donation_id_foreign` FOREIGN KEY (`donation_id`) REFERENCES `food_donations` (`donation_id`);

--
-- Constraints for table `food_donations`
--
ALTER TABLE `food_donations`
  ADD CONSTRAINT `food_donations_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `food_categories` (`category_id`),
  ADD CONSTRAINT `food_donations_donor_id_foreign` FOREIGN KEY (`donor_id`) REFERENCES `partner_profiles` (`profile_id`);

--
-- Constraints for table `food_requests`
--
ALTER TABLE `food_requests`
  ADD CONSTRAINT `food_requests_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `food_categories` (`category_id`),
  ADD CONSTRAINT `food_requests_charity_id_foreign` FOREIGN KEY (`charity_id`) REFERENCES `partner_profiles` (`profile_id`);

--
-- Constraints for table `partner_profiles`
--
ALTER TABLE `partner_profiles`
  ADD CONSTRAINT `partner_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_donation_id_foreign` FOREIGN KEY (`donation_id`) REFERENCES `food_donations` (`donation_id`),
  ADD CONSTRAINT `reservations_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `food_requests` (`request_id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `verification_documents`
--
ALTER TABLE `verification_documents`
  ADD CONSTRAINT `verification_documents_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partner_profiles` (`profile_id`);

--
-- Constraints for table `verification_reviews`
--
ALTER TABLE `verification_reviews`
  ADD CONSTRAINT `verification_reviews_partner_id_foreign` FOREIGN KEY (`partner_id`) REFERENCES `partner_profiles` (`profile_id`),
  ADD CONSTRAINT `verification_reviews_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
