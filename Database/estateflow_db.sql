-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 22, 2026 at 07:25 AM
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

--
-- Dumping data for table `agreements`
--

INSERT INTO `agreements` (`agreement_id`, `booking_id`, `property_id`, `customer_id`, `agreement_type`, `start_date`, `end_date`, `amount`, `document_path`, `status`, `created_at`) VALUES
(5, 22, 20, 5, 'sale', '2026-11-22', NULL, 20000.00, NULL, 'active', '2026-11-22 10:44:58'),
(6, 21, 20, 5, 'rent', '2026-11-22', NULL, 10000.00, NULL, 'active', '2026-11-22 11:02:26'),
(7, 5, 9, 3, 'rent', '2026-11-22', NULL, 5000.00, NULL, 'active', '2026-11-22 11:21:39');

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

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `property_id`, `customer_id`, `booking_date`, `status`, `created_at`) VALUES
(5, 9, 3, '2026-11-25 14:49:00', 'approved', '2026-11-16 14:49:47'),
(6, 9, 4, '2026-11-19 14:59:00', 'approved', '2026-11-16 14:59:18'),
(9, 12, 2, '2026-11-26 05:29:00', 'approved', '2026-11-21 11:29:10'),
(16, 10, 2, '2026-11-25 13:37:00', 'approved', '2026-11-22 09:37:11'),
(21, 20, 5, '2026-11-26 10:45:00', 'approved', '2026-11-22 10:42:05'),
(22, 20, 5, '2026-11-22 10:43:00', 'approved', '2026-11-22 10:43:30');

-- --------------------------------------------------------

--
-- Table structure for table `booking_interests`
--

CREATE TABLE `booking_interests` (
  `booking_id` bigint UNSIGNED NOT NULL,
  `interest_type` enum('buy','rent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','invoiced') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(4, 14, 'buyer', NULL, NULL, NULL, '2026-11-16 14:59:18'),
(5, 20, 'buyer', NULL, NULL, NULL, '2026-11-22 10:17:27');

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

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `agreement_id`, `amount`, `due_date`, `paid_at`, `payment_type`, `payment_method`, `transaction_ref`, `status`, `created_at`) VALUES
(5, 5, 20000.00, '2026-11-29', NULL, 'sale', 'SSLCommerz Sandbox', 'EF26B2165F0A7DE5DF', 'pending', '2026-11-22 10:44:58'),
(6, 6, 100000.00, '2026-11-22', NULL, 'rent', 'SSLCommerz Sandbox', 'EF8DF2196775DD6063', 'pending', '2026-11-22 11:02:26'),
(7, 7, 300000.00, '2026-11-22', NULL, 'rent', NULL, NULL, 'pending', '2026-11-22 11:21:39');

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
(8, 12, 'RSA corproat', 'commercial', 'Dhaka', 11800.00, 500000000.00, 500000.00, 'nai', 'rented', 'this is office', '2026-11-16 11:25:58', 'uploads/real estate1.jpg'),
(9, 12, 'Nira vila', 'flat', 'mirpur', 2000.00, 200000.00, 0.00, 'badroom-3 bathroom-2 ', 'available', 'this is flat', '2026-11-16 11:28:42', 'uploads/real estate6.jpg'),
(10, 12, 'Prestige Southern Star', 'flat', 'Begur Bannerghatta Road, Akshayanagar, in Bangalore', 2749.99, 300000000.00, 0.00, 'badroom-3 bathroom-2 ', 'available', 'Bangalore is one of the top-notch cities in India located in the southern State of Karnataka. It is famously recognized as the IT Capital of India, the city that hosts leading Multi-National Tech Companies. It is officially known as Bengaluru with a population of over 8.2 million.', '2026-11-17 12:52:35', 'uploads/real estate 9.jpg'),
(11, 19, 'famali house', 'flat', 'mirpur', 4555.00, 3000000.00, 0.00, 'badroom-3 bathroom-2', 'sold', 'hnjjyk,ol', '2026-11-17 14:13:37', 'uploads/8665bd0e064a222162b088f597cd8365.jpg'),
(12, 19, 'nil moni sqer', 'commercial', 'dhaka', 3000.00, 60000000.00, 0.00, 'nai', 'available', 'this is office', '2026-11-17 14:20:35', 'uploads/9c4429e8a23849365508ba6a238775e8.jpg'),
(15, 11, 'New  Bedroom', 'flat', 'mirpor', 2700.00, 200000.00, 0.00, 'bad room 1 bathroom 2 kacthin-1', 'available', 'Starting a new construction project gives you the unique opportunity to design a bedroom that perfectly fits your lifestyle and personal aesthetic from the very beginning. Instead of working around existing limitations, you can plan for structural elements like vaulted ceilings, strategic window placements, and integrated storage solutions that enhance both form and function. This phase of home building is the best time to consider how natural light, traffic flow, and modern technology will interact within your private sanctuary. By focusing on cohesive design themes rather than just furniture, you create a harmonious space that feels intentional and truly polished for many years to come.', '2026-11-22 10:22:36', 'uploads/97f6d911b6a25570c54b084b45832cbd.jpg'),
(17, 11, 'nil-kutir', 'commercial', 'afthabnogor', 3000.00, 3000000.00, 0.00, 'nai', 'available', 'Starting a new construction project gives you the unique opportunity to design a bedroom that perfectly fits your lifestyle and personal aesthetic from the very beginning. Instead of working around existing limitations, you can plan for structural elements like vaulted ceilings, strategic window placements, and integrated storage solutions that enhance both form and function. This phase of home building is the best time to consider how natural light, traffic flow, and modern technology will interact within your private sanctuary. By focusing on cohesive design themes rather than just furniture, you create a harmonious space that feels intentional and truly polished for many years to come.', '2026-11-22 10:29:03', 'uploads/4b78eb8be4e3b010180cf92d374cb5e9.jpg'),
(18, 11, 'land-1', 'land', 'mirpor', 2000.00, 500000.00, 0.00, 'nai', 'available', 'Starting a new construction project gives you the unique opportunity to design a bedroom that perfectly fits your lifestyle and personal aesthetic from the very beginning. Instead of working around existing limitations, you can plan for structural elements like vaulted ceilings, strategic window placements, and integrated storage solutions that enhance both form and function. This phase of home building is the best time to consider how natural light, traffic flow, and modern technology will interact within your private sanctuary. By focusing on cohesive design themes rather than just furniture, you create a harmonious space that feels intentional and truly polished for many years to come.', '2026-11-22 10:30:05', 'uploads/87397654eef4c73e4ef1eef5978d946a.jpg'),
(19, 11, 'tuhin vila', 'flat', 'dhaka', 3000.00, 500000.00, 0.00, 'badroom-3 bathroom-2 kichen-1 dingroom-1', 'available', 'When you are building a new home from the ground up, adding a vaulted ceiling can completely transform the atmosphere of your sleeping quarters. This architectural feature creates an immediate sense of grandeur and airiness that flat ceilings simply cannot match. You can leave the rafters exposed for a rustic look or keep things clean with smooth drywall for a contemporary feel. The extra vertical space allows for larger windows and much more natural light to flood the room throughout the day. It also provides the perfect opportunity to hang a statement chandelier that anchors the entire furniture arrangement. This design choice makes even smaller floor plans feel incredibly spacious and luxurious.', '2026-11-22 10:32:05', 'uploads/5488dd0031921830531c8105350039b4.jpg'),
(20, 11, 'land 2', 'land', 'dhaka', 40000.00, 20000.00, 0.00, 'N/A', 'available', 'When you are building a new home from the ground up, adding a vaulted ceiling can completely transform the atmosphere of your sleeping quarters. This architectural feature creates an immediate sense of grandeur and airiness that flat ceilings simply cannot match. You can leave the rafters exposed for a rustic look or keep things clean with smooth drywall for a contemporary feel. The extra vertical space allows for larger windows and much more natural light to flood the room throughout the day. It also provides the perfect opportunity to hang a statement chandelier that anchors the entire furniture arrangement. This design choice makes even smaller floor plans feel incredibly spacious and luxurious.', '2026-11-22 10:33:21', 'uploads/580b3a191a001d25b85e24a2e51c0823.jpg'),
(21, 11, 'thikana', 'flat', 'dhaka', 3300.00, 500000.00, 0.00, 'badroom-4 bathroom-3 kichen-1 dingroom-1', 'available', 'Beautiful house \r\nA warm minimalist living room works because of subtraction, not addition — and the rooms that actually feel warm share three decisions: a beige-to-oatmeal palette, at least four different material textures in the same space, and lighting that stays under 2700K after 6 p.m. I’ve pulled apart dozens of warm minimalist living room references to figure out why some rooms look calm and inviting while others look like staged showrooms, and the gap almost always comes down to those same three levers. Get them right and the space runs itself.', '2026-11-22 12:08:17', 'uploads/f0718ae02af3a5b3b18c4be729332ffa.jpg'),
(22, 11, 'family vila', 'flat', 'dhaka', 2200.00, 500000.00, 0.00, 'badroom-3 bathroom-2 kichen-1 dingroom-1', 'available', '• Per-sq.-ft. rates: Older resale flats can start around BDT 12,000–14,000 per sq. ft., while prime or lake-facing properties on roads like Road 8A, Road 11A, or Road 32 can reach BDT 25,000 to BDT 30,000+ per sq. ft..\r\n• Typical sale prices: A ~1,000 sq. ft. apartment commonly costs roughly BDT 1.4 crore to BDT 3 crore (often excluding parking, registration, and utility fees), while larger 2,500+ sq. ft. luxury residences can reach BDT 5 crore to BDT 9', '2026-11-22 12:15:19', 'uploads/0125d3d009c6ffe42096a88d56c3fcfe.jpg');

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
(3, 12, 'uploads/9c4429e8a23849365508ba6a238775e8.jpg', 1, '2026-11-17 14:20:35'),
(4, 12, 'uploads/73290d0d3094123799104421d296b9cf.jpg', 0, '2026-11-17 14:20:35'),
(5, 12, 'uploads/a3077c4a5145c86c917930ab7ea5014c.jpg', 0, '2026-11-17 14:20:35'),
(6, 12, 'uploads/ce88773a03d4fd8f7e14f0cdaf19b14a.jpg', 0, '2026-11-17 14:20:35'),
(7, 12, 'uploads/ddecc7452ef1f71fb3fc05676ef46760.jpg', 0, '2026-11-17 14:20:35'),
(8, 12, 'uploads/a3ac728e601066c50b3ffe34ffabd94e.jpg', 0, '2026-11-17 14:20:35'),
(19, 15, 'uploads/97f6d911b6a25570c54b084b45832cbd.jpg', 1, '2026-11-22 10:22:36'),
(20, 15, 'uploads/8971bfd3307fdee669205989904dcc53.jpg', 0, '2026-11-22 10:22:36'),
(21, 15, 'uploads/d8ca7587fac9aa21c9482eb9de14e434.jpg', 0, '2026-11-22 10:22:36'),
(22, 15, 'uploads/a0fedae6bd6ef64e112122c6f02c0ecb.jpg', 0, '2026-11-22 10:22:36'),
(23, 15, 'uploads/cc05639a8198148154f655533ff45c46.jpg', 0, '2026-11-22 10:22:36'),
(28, 17, 'uploads/4b78eb8be4e3b010180cf92d374cb5e9.jpg', 1, '2026-11-22 10:29:03'),
(29, 18, 'uploads/87397654eef4c73e4ef1eef5978d946a.jpg', 1, '2026-11-22 10:30:05'),
(30, 19, 'uploads/5488dd0031921830531c8105350039b4.jpg', 1, '2026-11-22 10:32:05'),
(31, 20, 'uploads/580b3a191a001d25b85e24a2e51c0823.jpg', 1, '2026-11-22 10:33:21'),
(32, 21, 'uploads/f0718ae02af3a5b3b18c4be729332ffa.jpg', 1, '2026-11-22 12:08:17'),
(33, 21, 'uploads/a7cbfd430fe997841ff7cbc1e21423c9.jpg', 0, '2026-11-22 12:08:17'),
(34, 21, 'uploads/65f0ebbab524f5fa6d689cb16e7dc99e.jpg', 0, '2026-11-22 12:08:17'),
(35, 21, 'uploads/b5a137cf36cd9688d66c5ae12f6db0f7.jpg', 0, '2026-11-22 12:08:17'),
(36, 21, 'uploads/be6d70914d9466602afc76f60de42bf5.jpg', 0, '2026-11-22 12:08:17'),
(37, 22, 'uploads/0125d3d009c6ffe42096a88d56c3fcfe.jpg', 1, '2026-11-22 12:15:19'),
(38, 22, 'uploads/9faf64704a58fadfb1eb593d361c8fcc.jpg', 0, '2026-11-22 12:15:19'),
(39, 22, 'uploads/7f1b1b87a0f3b2f58bef3973cdff6568.jpg', 0, '2026-11-22 12:15:19'),
(40, 22, 'uploads/1ba60b6d71793173b821f5a0e3339ea2.jpg', 0, '2026-11-22 12:15:19');

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
(19, 'aaaa', 'aaaa@gmil.com', '$2y$10$0cl2bSiVP3UQGYxg1CZ5ieJmtu6YLzGrFaRTMkeAt8FnFM9d.9qG6', '56695651351', 'active', '2026-11-17 14:04:00', 'owner'),
(20, 'jakiya', 'jakiya@gmail.com', '$2y$10$.Cf2pNbhKZ0OjN7Sg9DKfOy/4BoJlPIlX99qAydqg8GdNOKiJqL2u', '01300700982', 'active', '2026-11-22 10:17:04', 'customer');

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
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`visit_id`, `booking_id`, `property_id`, `customer_id`, `agent_id`, `visit_time`, `status`, `created_at`) VALUES
(8, 16, 10, 2, NULL, '2026-11-25 13:37:00', 'scheduled', '2026-11-22 09:37:11'),
(11, 9, 12, 2, NULL, '2026-11-26 05:29:00', 'scheduled', '2026-11-22 09:51:57'),
(14, 21, 20, 5, NULL, '2026-11-26 10:45:00', 'completed', '2026-11-22 10:42:05'),
(15, 22, 20, 5, NULL, '2026-11-22 10:44:14', 'completed', '2026-11-22 10:43:30');

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
  MODIFY `agreement_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `commissions`
--
ALTER TABLE `commissions`
  MODIFY `commission_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `property_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `image_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `visit_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
