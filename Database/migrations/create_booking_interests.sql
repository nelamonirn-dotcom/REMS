CREATE TABLE IF NOT EXISTS `booking_interests` (
  `booking_id` bigint UNSIGNED NOT NULL,
  `interest_type` enum('buy','rent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','invoiced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  CONSTRAINT `fk_booking_interests_booking`
    FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
