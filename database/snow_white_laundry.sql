-- =============================================================================
-- SNOW WHITE LAUNDRY — COMPLETE MYSQL DATABASE SCHEMA & SEED DATA (REVISION 1)
-- Compatible with MySQL 5.7+ / MySQL 8.x / MariaDB 10.4+ (cPanel phpMyAdmin Ready)
--
-- HOW TO IMPORT MANUALLY IN CPANEL:
--   1. Log in to cPanel -> MySQL Databases -> Create a Database & User -> Add User to DB (ALL PRIVILEGES).
--   2. Open cPanel -> phpMyAdmin -> Click your newly created database on the left sidebar.
--   3. Click the "Import" tab -> Choose File -> Select `snow_white_laundry.sql` -> Click "Go".
--   4. Update `config/config.php` (or visit `https://yourdomain.com/install.php`) with your DB credentials.
--
-- DEFAULT PORTAL CREDENTIALS (Change passwords immediately after going live):
--   * Admin Portal    : admin@snowwhitelaundry.com.ng     | Admin@123
--   * Staff Portal #1 : staff@snowwhitelaundry.com.ng     | Staff@123
--   * Staff Portal #2 : dispatch@snowwhitelaundry.com.ng  | Staff@123
--   * Visitor Portal  : customer@snowwhitelaundry.com.ng  | Customer@123
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+01:00";

-- -----------------------------------------------------------------------------
-- 1. TABLE: users (Visitors / Customers, Staff Members, and Administrators)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `enquiries`;
DROP TABLE IF EXISTS `faqs`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('visitor', 'staff', 'admin') NOT NULL DEFAULT 'visitor',
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `suspension_reason` VARCHAR(255) DEFAULT NULL,
  `staff_code` VARCHAR(30) DEFAULT NULL,
  `job_title` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city_area` VARCHAR(100) DEFAULT NULL,
  `passport_photo` VARCHAR(255) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password_hash`, `role`, `status`, `suspension_reason`, `staff_code`, `job_title`, `address`, `city_area`, `passport_photo`, `bio`, `last_login_at`, `created_at`) VALUES
(1, 'Chukwuemeka Okafor', 'admin@snowwhitelaundry.com.ng', '07062300865', '$2y$12$PlxBRG9gaDgnVc4AwAjbxeHBdJv40QbCS64TPRDutGilX1KEYwVaW', 'admin', 'active', NULL, 'SWL-ADM-001', 'Managing Director & Administrator', 'No 3 Kess Rubber Factory Street', 'Nigeria', 'uploads/passports/admin-passport.jpg', 'Oversees garment quality standards, digital operations, corporate accounts, and service excellence at Snow White Laundry.', NOW(), NOW()),
(2, 'Amaka Nwosu', 'staff@snowwhitelaundry.com.ng', '08034567891', '$2y$12$Xb/JpqajxgYHt.eKM.A.iOtLWnYn/1mINii406PxbZL4r6htdfpqS', 'staff', 'active', NULL, 'SWL-STF-001', 'Senior Garment Care Supervisor', 'No 3 Kess Rubber Factory Street', 'Operations Floor', 'uploads/passports/staff-passport.jpg', 'Specialist in fabric inspection, delicate stain removal, steam finishing, and final quality checks.', NOW(), NOW()),
(3, 'Tunde Adeyemi', 'dispatch@snowwhitelaundry.com.ng', '08056781234', '$2y$12$Xb/JpqajxgYHt.eKM.A.iOtLWnYn/1mINii406PxbZL4r6htdfpqS', 'staff', 'active', NULL, 'SWL-STF-002', 'Pickup & Delivery Coordinator', 'No 3 Kess Rubber Factory Street', 'Dispatch Unit', 'uploads/passports/visitor-passport.jpg', 'Coordinates doorstep garment collection, protective packaging, and timely customer deliveries.', NOW(), NOW()),
(4, 'Oluwaseun Balogun', 'customer@snowwhitelaundry.com.ng', '08123459876', '$2y$12$HfC00IulbNI2Unp3c/6MbOugvoYn3HtzTUQRKg9gQ0f/THRaMlSyO', 'visitor', 'active', NULL, NULL, 'Corporate Executive Client', '14 Admiralty Way, Phase 1', 'Residential Client', 'uploads/passports/visitor-passport.jpg', 'Regular weekly executive shirt pressing and native attire care customer.', NOW(), NOW());

-- -----------------------------------------------------------------------------
-- 2. TABLE: settings (Branding Logo/Favicon, Paystack Keys, Maintenance Mode)
-- -----------------------------------------------------------------------------
CREATE TABLE `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('site_name', 'Snow White Laundry', 'branding'),
('site_tagline', 'Clean, Fresh & Reliable Garment Care', 'branding'),
('site_logo', 'uploads/branding/default-logo.svg', 'branding'),
('site_favicon', 'uploads/branding/default-favicon.svg', 'branding'),
('footer_about', 'Snow White Laundry provides spotless fabric washing, gentle dry cleaning, crisp steam ironing, and dependable pickup & delivery for homes and businesses.', 'branding'),
('contact_business_name', 'Snow White Laundry', 'contact'),
('contact_address', 'No 3 Kess Rubber Factory Street', 'contact'),
('contact_email', 'info@snowwhitelaundry.com.ng', 'contact'),
('contact_phone', '07062300865', 'contact'),
('whatsapp_number', '2347062300865', 'contact'),
('business_hours', 'Mon – Sat: 8:00 AM – 7:00 PM | Sun: Pickup by Appointment', 'contact'),
('service_area', 'No 3 Kess Rubber Factory Street & Surrounding Residential and Commercial Districts', 'contact'),
('maintenance_mode', '0', 'maintenance'),
('maintenance_title', 'Scheduled Website & Service Upgrade in Progress', 'maintenance'),
('maintenance_message', 'Snow White Laundry is briefly performing scheduled updates to serve you better. For immediate garment drop-off, pickup scheduling, or order enquiries, call or WhatsApp us on 07062300865.', 'maintenance'),
('paystack_enabled', '1', 'paystack'),
('paystack_mode', 'test', 'paystack'),
('paystack_public_key', '', 'paystack'),
('paystack_secret_key', '', 'paystack'),
('paystack_currency', 'NGN', 'paystack'),
('pickup_delivery_fee', '1500', 'pricing'),
('express_surcharge_percent', '40', 'pricing');

-- -----------------------------------------------------------------------------
-- 3. TABLE: services (Laundry & Dry Cleaning Service Catalogue + Pricing)
-- -----------------------------------------------------------------------------
CREATE TABLE `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(140) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `category` VARCHAR(60) NOT NULL DEFAULT 'Laundry Care',
  `short_description` VARCHAR(255) NOT NULL,
  `full_description` TEXT NOT NULL,
  `unit_label` VARCHAR(60) NOT NULL DEFAULT 'Per Item',
  `price_ngn` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `turnaround_hours` VARCHAR(40) NOT NULL DEFAULT '24 - 48 Hours',
  `icon_name` VARCHAR(40) NOT NULL DEFAULT 'shirt',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_services_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `services` (`id`, `name`, `slug`, `category`, `short_description`, `full_description`, `unit_label`, `price_ngn`, `turnaround_hours`, `icon_name`, `is_featured`, `is_active`, `sort_order`) VALUES
(1, 'Everyday Wash, Dry & Neat Fold', 'wash-dry-fold', 'Everyday Laundry', 'Sorted by colour and fabric weight, washed with gentle pH-balanced detergents, hygienically dried, and crisply folded.', 'Ideal for weekly household clothing, t-shirts, casual wear, gym outfits, and children''s garments. Every load is washed separately per customer—never mixed with another client''s clothes—and packed fresh in breathable protective wraps.', 'Per Load Bag (up to 6kg)', 4500.00, '24 – 48 Hours', 'basket', 1, 1, 1),
(2, 'Executive Shirt Wash & Steam Press', 'executive-shirt-care', 'Corporate Wear', 'Collar and cuff pre-treatment, bright white/colour protection wash, and precision industrial steam pressing.', 'Designed for office shirts, blouses, and corporate tops. We treat collar rings gently without harsh bleach damage, restore crisp cuff lines, and return your shirts on hangers or neatly boxed.', 'Per Shirt', 1200.00, '24 Hours', 'shirt', 1, 1, 2),
(3, 'Two-Piece Suit Dry Cleaning & Finish', 'suit-dry-cleaning', 'Dry Cleaning', 'Solvent-safe dry cleaning that protects wool, linen, and structured lapels with zero shrinkage or shine marks.', 'Complete care for blazers, dinner jackets, corporate suits, and trousers. Includes lint removal, button inspection, lapel shaping, and protective garment cover.', 'Per 2-Piece Suit', 4500.00, '48 Hours', 'briefcase', 1, 1, 3),
(4, 'Native, Senator & Agbada Special Care', 'native-attire-care', 'Traditional Attire', 'Careful hand-inspected cleaning, starch level tailored to your preference, and sharp crease finishing for Nigerian attire.', 'Whether crisp Senator wear, embroidered Agbada, Ankara, Aso-Oke accents, or delicate Lace, our specialists preserve embroidery threads and fabric vibrancy while giving an unmistakable executive finish.', 'Per Complete Outfit', 3500.00, '48 Hours', 'sparkles', 1, 1, 4),
(5, 'Dresses, Gowns & Delicate Fabrics', 'delicate-fabric-care', 'Dry Cleaning', 'Gentle fabric-specific treatment for silk, chiffon, satin, corporate gowns, and occasion wear.', 'Each garment is inspected for beads, sequins, linings, and care labels before gentle cleaning and hand-steaming so drapes stay fluid and spotless.', 'Per Garment', 2500.00, '48 Hours', 'gem', 1, 1, 5),
(6, 'Duvet, Comforter & Bedding Sanitization', 'duvet-bedding-sanitization', 'Home Linen', 'Deep hygienic wash and high-efficiency drying for king/queen duvets, bed sheets, pillowcases, and blankets.', 'Eliminates dust mites, trapped odours, and allergens using high-capacity drums and skin-safe antibacterial rinses, returning your bedding fluffy, fresh, and sealed.', 'Per Duvet / Set', 6000.00, '48 – 72 Hours', 'bed', 1, 1, 6),
(7, 'Steam Ironing Only (Clean Garments)', 'steam-ironing-only', 'Pressing & Finishing', 'Already washed at home? Bring your clean clothes for crisp commercial steam pressing without scorch marks.', 'Fast, affordable finishing for clean shirts, trousers, skirts, and native outfits using temperature-regulated industrial steam irons.', 'Per Garment', 700.00, '12 – 24 Hours', 'zap', 0, 1, 7),
(8, 'Curtains, Towels & Commercial Linen', 'curtains-commercial-linen', 'Home & Corporate', 'Scheduled bulk care for home curtains, hotel towels, salon linen, clinics, and corporate offices.', 'Consistent turnaround, volume pricing, stain treatment, and reliable collection schedules from No 3 Kess Rubber Factory Street.', 'Per Panel / Bundle', 3000.00, '48 – 72 Hours', 'building', 0, 1, 8);

-- -----------------------------------------------------------------------------
-- 4. TABLE: orders (Customer Laundry Bookings, Pickup & Status Tracking)
-- -----------------------------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(32) NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_email` VARCHAR(160) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `pickup_address` VARCHAR(255) NOT NULL,
  `delivery_option` ENUM('drop_off', 'pickup_delivery') NOT NULL DEFAULT 'pickup_delivery',
  `service_speed` ENUM('standard', 'express') NOT NULL DEFAULT 'standard',
  `preferred_date` DATE NOT NULL,
  `preferred_time_slot` VARCHAR(50) NOT NULL DEFAULT 'Morning (8:00 AM - 12:00 PM)',
  `special_notes` TEXT DEFAULT NULL,
  `subtotal_ngn` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `delivery_fee_ngn` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `express_fee_ngn` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount_ngn` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('paystack', 'pay_on_pickup') NOT NULL DEFAULT 'paystack',
  `payment_status` ENUM('unpaid', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'unpaid',
  `order_status` ENUM('pending', 'confirmed', 'picked_up', 'processing', 'ready', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `assigned_staff_id` INT UNSIGNED DEFAULT NULL,
  `staff_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_staff` (`assigned_staff_id`),
  KEY `idx_orders_status` (`order_status`, `payment_status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `pickup_address`, `delivery_option`, `service_speed`, `preferred_date`, `preferred_time_slot`, `special_notes`, `subtotal_ngn`, `delivery_fee_ngn`, `express_fee_ngn`, `total_amount_ngn`, `payment_method`, `payment_status`, `order_status`, `assigned_staff_id`, `staff_notes`, `created_at`) VALUES
(1, 'SWL-2026-1001', 4, 'Oluwaseun Balogun', 'customer@snowwhitelaundry.com.ng', '08123459876', '14 Admiralty Way, Phase 1', 'pickup_delivery', 'standard', CURDATE(), 'Morning (8:00 AM - 12:00 PM)', 'Light starch on white shirts; please put Senator outfits on individual hangers.', 13000.00, 1500.00, 0.00, 14500.00, 'paystack', 'paid', 'processing', 2, 'Inspected 5 white shirts and 2 Senator outfits. Pre-treating cuffs now; steam finishing scheduled this afternoon.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'SWL-2026-1002', 4, 'Oluwaseun Balogun', 'customer@snowwhitelaundry.com.ng', '08123459876', 'No 3 Kess Rubber Factory Street (Branch Drop-off)', 'drop_off', 'express', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Afternoon (12:00 PM - 4:00 PM)', 'King duvet + 1 executive suit for Friday conference.', 10500.00, 0.00, 4200.00, 14700.00, 'paystack', 'unpaid', 'confirmed', 3, 'Awaiting express drop-off at reception counter.', NOW());

-- -----------------------------------------------------------------------------
-- 5. TABLE: order_items
-- -----------------------------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED DEFAULT NULL,
  `service_name` VARCHAR(140) NOT NULL,
  `unit_label` VARCHAR(60) NOT NULL,
  `unit_price_ngn` DECIMAL(12,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `line_total_ngn` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `order_items` (`order_id`, `service_id`, `service_name`, `unit_label`, `unit_price_ngn`, `quantity`, `line_total_ngn`) VALUES
(1, 2, 'Executive Shirt Wash & Steam Press', 'Per Shirt', 1200.00, 5, 6000.00),
(1, 4, 'Native, Senator & Agbada Special Care', 'Per Complete Outfit', 3500.00, 2, 7000.00),
(2, 6, 'Duvet, Comforter & Bedding Sanitization', 'Per Duvet / Set', 6000.00, 1, 6000.00),
(2, 3, 'Two-Piece Suit Dry Cleaning & Finish', 'Per 2-Piece Suit', 4500.00, 1, 4500.00);

-- -----------------------------------------------------------------------------
-- 6. TABLE: payments (Paystack Transactions & Audit Records)
-- -----------------------------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `reference` VARCHAR(100) NOT NULL,
  `gateway` VARCHAR(30) NOT NULL DEFAULT 'paystack',
  `mode` VARCHAR(20) NOT NULL DEFAULT 'test',
  `amount_ngn` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'NGN',
  `status` ENUM('initialized', 'success', 'failed') NOT NULL DEFAULT 'initialized',
  `channel` VARCHAR(40) DEFAULT NULL,
  `gateway_response` VARCHAR(255) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `raw_payload` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_payments_ref` (`reference`),
  KEY `idx_payments_order` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` (`order_id`, `user_id`, `reference`, `gateway`, `mode`, `amount_ngn`, `currency`, `status`, `channel`, `gateway_response`, `paid_at`, `created_at`) VALUES
(1, 4, 'SWL-PSK-20261001-DEMO', 'paystack', 'test', 14500.00, 'NGN', 'success', 'card', 'Approved', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));

-- -----------------------------------------------------------------------------
-- 7. TABLE: enquiries (Service Enquiries, Custom Quotes & Contact Messages)
-- -----------------------------------------------------------------------------
CREATE TABLE `enquiries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `enquiry_type` ENUM('service_enquiry', 'quote_request', 'general_contact') NOT NULL DEFAULT 'service_enquiry',
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `requested_service` VARCHAR(140) DEFAULT NULL,
  `preferred_date` DATE DEFAULT NULL,
  `estimated_items` VARCHAR(120) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'in_review', 'responded', 'closed') NOT NULL DEFAULT 'new',
  `handled_by_staff_id` INT UNSIGNED DEFAULT NULL,
  `staff_reply_note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_enquiries_status` (`status`),
  CONSTRAINT `fk_enquiries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_enquiries_staff` FOREIGN KEY (`handled_by_staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `enquiries` (`user_id`, `enquiry_type`, `full_name`, `email`, `phone`, `requested_service`, `preferred_date`, `estimated_items`, `message`, `status`, `handled_by_staff_id`, `staff_reply_note`) VALUES
(NULL, 'quote_request', 'Mrs. Ngozi Eze', 'ngozi.eze@example.com', '08091122334', 'Curtains, Towels & Commercial Linen', DATE_ADD(CURDATE(), INTERVAL 2 DAY), '18 Boutique Guest Towels & 6 Curtain Panels', 'Hello Snow White Laundry, we run a guest house nearby and would love a weekly laundry pickup quote.', 'in_review', 2, 'Called Mrs. Eze to confirm fabric dimensions; preparing weekly corporate rate sheet.'),
(4, 'service_enquiry', 'Oluwaseun Balogun', 'customer@snowwhitelaundry.com.ng', '08123459876', 'Native, Senator & Agbada Special Care', DATE_ADD(CURDATE(), INTERVAL 4 DAY), '3 White Agbada Sets', 'Can I schedule an express Friday morning press before 11 AM?', 'responded', 2, 'Confirmed! Express steam press slot reserved for Friday 8:30 AM.');

-- -----------------------------------------------------------------------------
-- 8. TABLE: faqs (Confirmed Customer Care Policies & Common Questions)
-- -----------------------------------------------------------------------------
CREATE TABLE `faqs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(60) NOT NULL DEFAULT 'General',
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `faqs` (`category`, `question`, `answer`, `sort_order`, `is_active`) VALUES
('Turnaround Times', 'How long does standard laundry and dry cleaning take?', 'Standard Wash & Fold and Executive Shirt orders are completed within 24 to 48 hours. Dry cleaning, traditional embroidered outfits (Agbada/Lace), and heavy duvets take 48 to 72 hours. Need garments urgently? Choose our Express Same-Day option during booking.', 1, 1),
('Garment Care', 'Do you wash my clothes together with other customers'' laundry?', 'Never. Every customer''s garments are tagged, sorted by colour and care label, and washed in dedicated loads so your whites stay snow-white and hygiene is 100% guaranteed.', 2, 1),
('Pickup & Delivery', 'Where are you located, and do you offer doorstep pickup and delivery?', 'Our main facility is at No 3 Kess Rubber Factory Street. You can drop off directly at our reception desk or select Doorstep Pickup & Delivery when booking online or chatting with us on 07062300865.', 3, 1),
('Payments & Paystack', 'How can I pay for my laundry order?', 'You can pay securely online using debit card, bank transfer, or USSD through our integrated Paystack gateway right from your Visitor Dashboard, or select Pay on Pickup/Drop-off.', 4, 1),
('Stains & Special Instructions', 'Can I specify starch levels or delicate stain instructions?', 'Yes! Every order and quote request includes a Special Care Notes box where you can request light, medium, or crisp starch, hanger vs. folded packaging, or point out specific stains for pre-treatment.', 5, 1),
('Accounts & Tracking', 'Do I need an account to track my clothes or view receipts?', 'You can request a quick quote anytime as a guest, or create a free Visitor Account in seconds to upload your profile photo, track real-time garment status, pay online via Paystack, and download payment receipts.', 6, 1);

-- -----------------------------------------------------------------------------
-- 9. TABLE: activity_logs (Admin & Security Audit Trail)
-- -----------------------------------------------------------------------------
CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `actor_role` VARCHAR(20) DEFAULT NULL,
  `action` VARCHAR(80) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` (`user_id`, `actor_role`, `action`, `description`, `ip_address`) VALUES
(1, 'admin', 'system.initialized', 'Snow White Laundry portal & MySQL database initialized (Revision 1).', '127.0.0.1');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
