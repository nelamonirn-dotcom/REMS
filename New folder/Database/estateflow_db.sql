-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 21, 2026 at 03:32 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `estateflow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `agent_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `agent_properties`
--

CREATE TABLE `agent_properties` (
  `assignment_id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agreements`
--

CREATE TABLE `agreements` (
  `agreement_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `agreement_type` enum('rent','sale') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','expired','terminated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `booking_date` datetime NOT NULL,
  `status` enum('pending','approved','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_interests`
--

CREATE TABLE `booking_interests` (
  `booking_id` bigint UNSIGNED NOT NULL,
  `interest_type` enum('buy','rent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','invoiced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `property_id`, `customer_id`, `booking_date`, `status`, `created_at`) VALUES
(3, 1, 2, '2026-11-25 14:42:00', 'approved', '2026-11-14 14:42:39'),
(5, 9, 3, '2026-11-25 14:49:00', 'approved', '2026-11-16 14:49:47'),
(6, 9, 4, '2026-11-19 14:59:00', 'approved', '2026-11-16 14:59:18'),
(7, 1, 4, '2026-11-17 15:02:00', 'approved', '2026-11-16 15:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `commissions`
--

CREATE TABLE `commissions` (
  `commission_id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED NOT NULL,
  `agreement_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_status` enum('pending','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `customer_type` enum('buyer','tenant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `preferred_location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `user_id`, `customer_type`, `preferred_location`, `budget`, `address`, `created_at`) VALUES
(2, 9, 'buyer', NULL, NULL, NULL, '2026-11-14 14:41:40'),
(3, 15, 'buyer', NULL, NULL, NULL, '2026-11-16 14:49:47'),
(4, 14, 'buyer', NULL, NULL, NULL, '2026-11-16 14:59:18');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` bigint UNSIGNED NOT NULL,
  `recorded_by` bigint UNSIGNED NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `complaint_id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','resolved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` int NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` int NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` bigint UNSIGNED NOT NULL,
  `agreement_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_type` enum('rent','sale','advance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','paid','partial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `property_id` bigint UNSIGNED NOT NULL,
  `owner_id` bigint UNSIGNED NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_type` enum('flat','land','commercial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_sqft` decimal(10,2) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `monthly_rent` decimal(12,2) DEFAULT NULL,
  `flat_details` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('available','sold','rented') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `imagas` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`property_id`, `owner_id`, `title`, `property_type`, `address`, `area_sqft`, `price`, `monthly_rent`, `flat_details`, `status`, `description`, `created_at`, `imagas`) VALUES
(1, 11, 'Tuhin', 'flat', 'Dhaka', 1500.00, 15000.00, 5000.00, '3', 'available', 'hiu', '2026-11-12 15:04:29', ''),
(7, 11, 'land', 'land', 'afthabnogor', 2500.00, 100000.00, 0.00, 'nai', 'available', 'this is a land', '2026-11-16 11:17:38', 'uploads/real estate 11.jpg'),
(8, 12, 'RSA corproat', 'commercial', 'Dhaka', 11800.00, 500000000.00, 500000.00, 'nai', 'rented', 'this is office', '2026-11-16 11:25:58', 'uploads/real estate1.jpg'),
(9, 12, 'Nira vila', 'flat', 'mirpur', 2000.00, 200000.00, 0.00, 'badroom-3 bathroom-2 ', 'available', 'this is flat', '2026-11-16 11:28:42', 'uploads/real estate6.jpg'),
(10, 12, 'Prestige Southern Star', 'flat', 'Begur Bannerghatta Road, Akshayanagar, in Bangalore', 2749.99, 300000000.00, 0.00, 'badroom-3 bathroom-2 ', 'available', 'Bangalore is one of the top-notch cities in India located in the southern State of Karnataka. It is famously recognized as the IT Capital of India, the city that hosts leading Multi-National Tech Companies. It is officially known as Bengaluru with a population of over 8.2 million.', '2026-11-17 12:52:35', 'uploads/real estate 9.jpg'),
(11, 19, 'famali house', 'flat', 'mirpur', 4555.00, 3000000.00, 0.00, 'badroom-3 bathroom-2', 'sold', 'hnjjyk,ol', '2026-11-17 14:13:37', 'uploads/8665bd0e064a222162b088f597cd8365.jpg'),
(12, 19, 'nil moni sqer', 'commercial', 'dhaka', 3000.00, 60000000.00, 0.00, 'nai', 'available', 'this is office', '2026-11-17 14:20:35', 'uploads/9c4429e8a23849365508ba6a238775e8.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `image_id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_images`
--

INSERT INTO `property_images` (`image_id`, `property_id`, `image_path`, `is_primary`, `created_at`) VALUES
(1, 1, '../uploads/property-images/1/b31e27b90c3a2048f72a78495bf74091.jpg', 1, '2026-11-14 11:40:19'),
(2, 1, '../uploads/property-images/1/9af9a7cd6e63aa7f34d2d1103979144d.png', 0, '2026-11-14 13:08:34'),
(3, 12, 'uploads/9c4429e8a23849365508ba6a238775e8.jpg', 1, '2026-11-17 14:20:35'),
(4, 12, 'uploads/73290d0d3094123799104421d296b9cf.jpg', 0, '2026-11-17 14:20:35'),
(5, 12, 'uploads/a3077c4a5145c86c917930ab7ea5014c.jpg', 0, '2026-11-17 14:20:35'),
(6, 12, 'uploads/ce88773a03d4fd8f7e14f0cdaf19b14a.jpg', 0, '2026-11-17 14:20:35'),
(7, 12, 'uploads/ddecc7452ef1f71fb3fc05676ef46760.jpg', 0, '2026-11-17 14:20:35'),
(8, 12, 'uploads/a3ac728e601066c50b3ffe34ffabd94e.jpg', 0, '2026-11-17 14:20:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','customer','agent','owner') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password_hash`, `phone`, `status`, `created_at`, `role`) VALUES
(8, 'nela', 'nela@gmail.com', '$2y$10$b0OXTqRxXBVdMB7/RjNBO.xCZcxdHcuBMiIgEyCSS3C4l0vEO2UDS', '01300700982', 'active', '2026-11-09 14:00:07', 'admin'),
(9, 'moni', 'moni@gmail.com', '$2y$10$suQI61ldutMo6cUqB/b3kezcrCgrOlbrI2Sze/bKV1ahOlcV4Tuc6', NULL, 'active', '2026-11-09 14:00:07', 'customer'),
(10, 'rasel', 'rasel@gmail.com', '$2y$10$5XXziNsOd9Z.u.viQI8ONObd.Zp5SLNzKxO3Spro3c5dGgLWmfxlG', '01300700984', 'active', '2026-11-09 14:02:00', 'agent'),
(11, 'tuhin', 'tuhin@gmail.com', '$2y$10$uBhlbWt5jP9TygOyNmHaoOR8OLQ4W2XWQpDJKwioRKvwGoJeNb/.i', NULL, 'active', '2026-11-09 14:02:00', 'owner'),
(12, 'sarmin', 'sarmin@gmail.com', '$2y$10$EuVxmu/4LSF.MIQkTcpDZumT2jleARUzSvu/nQ6Sju8NGGio2.vU6', '01609095534', 'active', '2026-11-16 11:22:34', 'owner'),
(13, 'moni1', 'moni1@gmail.com', 'c9f68e39c1edb15171a94e3fe8ecdd00955a9ff6', '01300700984', 'active', '2026-11-16 11:32:10', 'customer'),
(14, 'Rokon', 'rokon@gmail.com', '$2y$10$7AxRgMkjDiqvz/a.XYwO3ewO.Fzo23TTMTMZSjXx5BldCIlAMHXOq', '+8801878464115', 'active', '2026-11-16 14:36:34', 'customer'),
(15, 'rokon', 'rokon1@gmail.com', '$2y$10$ZNP7aqiBXcCE.fOFXlD17OxYsaPApJ/gtw02a7yblxn94/xinMnFS', '0675654nmm', 'active', '2026-11-16 14:45:49', 'customer'),
(16, 'somi', 'somi@gmail.com', '$2y$10$DMDgZU/PdCi7q1Mn2.6DTepqBCVjDeqZJOwGJIbMAheyIPRkWNUoS', '2134567890', 'active', '2026-11-17 12:34:10', 'customer'),
(17, 'nil', 'nil@gmail.com', '$2y$10$6wZX7d1AYVxSZLisKc.R5uQAUqgf.uKat1TqJZQzhzMNW6MNsvjnC', '+8801878464114', 'active', '2026-11-17 13:57:57', 'admin'),
(18, 'yeasin', 'yeasin@gmail.com', '$2y$10$NBgzVhq/EVn1BBzeku92X.9Dh1v9z.Tjwux3tc9hKxeYOuqBPIEPe', '+8801878464114', 'active', '2026-11-17 14:00:05', 'customer'),
(19, 'aaaa', 'aaaa@gmil.com', '$2y$10$0cl2bSiVP3UQGYxg1CZ5ieJmtu6YLzGrFaRTMkeAt8FnFM9d.9qG6', '56695651351', 'active', '2026-11-17 14:04:00', 'owner');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `visit_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `agent_id` bigint UNSIGNED DEFAULT NULL,
  `visit_time` datetime NOT NULL,
  `status` enum('scheduled','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`agent_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `agent_properties`
--
ALTER TABLE `agent_properties`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_agent_property` (`agent_id`,`property_id`),
  ADD KEY `fk_ap_property` (`property_id`);

--
-- Indexes for table `agreements`
--
ALTER TABLE `agreements`
  ADD PRIMARY KEY (`agreement_id`),
  ADD KEY `fk_agreements_booking` (`booking_id`),
  ADD KEY `fk_agreements_property` (`property_id`),
  ADD KEY `fk_agreements_customer` (`customer_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_bookings_property` (`property_id`),
  ADD KEY `fk_bookings_customer` (`customer_id`);

--
-- Indexes for table `booking_interests`
--
ALTER TABLE `booking_interests`
  ADD PRIMARY KEY (`booking_id`);

--
-- Indexes for table `commissions`
--
ALTER TABLE `commissions`
  ADD PRIMARY KEY (`commission_id`),
  ADD KEY `fk_commissions_agent` (`agent_id`),
  ADD KEY `fk_commissions_agreement` (`agreement_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `fk_expenses_user` (`recorded_by`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `fk_maintenance_property` (`property_id`),
  ADD KEY `fk_maintenance_customer` (`customer_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notifications_user` (`user_id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `fk_payments_agreement` (`agreement_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`property_id`),
  ADD KEY `fk_properties_owner` (`owner_id`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_images_property` (`property_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`visit_id`),
  ADD KEY `fk_visits_booking` (`booking_id`),
  ADD KEY `fk_visits_property` (`property_id`),
  ADD KEY `fk_visits_customer` (`customer_id`),
  ADD KEY `fk_visits_agent` (`agent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `agent_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_properties`
--
ALTER TABLE `agent_properties`
  MODIFY `assignment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agreements`
--
ALTER TABLE `agreements`
  MODIFY `agreement_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `commissions`
--
ALTER TABLE `commissions`
  MODIFY `commission_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `complaint_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `owner_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `property_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `image_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `visit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agents`
--
ALTER TABLE `agents`
  ADD CONSTRAINT `fk_agents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `agent_properties`
--
ALTER TABLE `agent_properties`
  ADD CONSTRAINT `fk_ap_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`agent_id`),
  ADD CONSTRAINT `fk_ap_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`);

--
-- Constraints for table `agreements`
--
ALTER TABLE `agreements`
  ADD CONSTRAINT `fk_agreements_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `fk_agreements_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fk_agreements_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fk_bookings_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`);

--
-- Constraints for table `booking_interests`
--
ALTER TABLE `booking_interests`
  ADD CONSTRAINT `fk_booking_interests_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;

--
-- Constraints for table `commissions`
--
ALTER TABLE `commissions`
  ADD CONSTRAINT `fk_commissions_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`agent_id`),
  ADD CONSTRAINT `fk_commissions_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`agreement_id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maintenance_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fk_maintenance_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `owners`
--
ALTER TABLE `owners`
  ADD CONSTRAINT `owners_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_agreement` FOREIGN KEY (`agreement_id`) REFERENCES `agreements` (`agreement_id`);

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `fk_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `fk_visits_agent` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`agent_id`),
  ADD CONSTRAINT `fk_visits_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `fk_visits_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `fk_visits_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
