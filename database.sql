-- =====================================================================
-- SLA Tracker - Database Schema & Sample Data
-- Compatible with MySQL 5.7+ / MariaDB
--
-- Import this file into a freshly created database, then update
-- config/config.php with your database credentials.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('Administrator','Manager','User') NOT NULL DEFAULT 'User',
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: sla_policies
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `sla_policies`;
CREATE TABLE `sla_policies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `policy_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `hours` INT UNSIGNED NOT NULL DEFAULT 0,
  `minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `warning_before_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: records
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `records`;
CREATE TABLE `records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_number` VARCHAR(50) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `assigned_to` INT UNSIGNED NULL,
  `priority` ENUM('Critical','High','Medium','Low') NOT NULL DEFAULT 'Medium',
  `sla_policy_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL,
  `due_at` DATETIME NOT NULL,
  `completed_at` DATETIME NULL,
  `status` ENUM('Open','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Open',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_records_reference` (`reference_number`),
  KEY `idx_records_status` (`status`),
  KEY `idx_records_due_at` (`due_at`),
  KEY `idx_records_department` (`department`),
  CONSTRAINT `fk_records_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_records_sla_policy` FOREIGN KEY (`sla_policy_id`) REFERENCES `sla_policies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: activity_logs
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_record` (`record_id`),
  KEY `idx_activity_user` (`user_id`),
  CONSTRAINT `fk_activity_record` FOREIGN KEY (`record_id`) REFERENCES `records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: login_attempts
-- Tracks failed login attempts per client IP for brute-force lockout.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `identifier` VARCHAR(45) NOT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `first_attempt` DATETIME NOT NULL,
  `last_attempt` DATETIME NOT NULL,
  PRIMARY KEY (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: settings
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_name` VARCHAR(100) NOT NULL DEFAULT 'SLA Tracker',
  `timezone` VARCHAR(64) NOT NULL DEFAULT 'UTC',
  `company_name` VARCHAR(150) NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Sample / Seed Data
-- =====================================================================

-- Default settings
INSERT INTO `settings` (`site_name`, `timezone`, `company_name`) VALUES
('SLA Tracker', 'UTC', 'Acme Operations Inc.');

-- Default administrator account
-- Username: admin | Password: Admin@12345 (bcrypt hash below)
INSERT INTO `users` (`full_name`, `username`, `email`, `password`, `role`, `status`, `created_at`) VALUES
('System Administrator', 'admin', 'admin@example.com', '$2y$10$nuVk2OhFHkb1cJbHEA.4turJSLN0WmH59xyba7KyFs4cMckDomcq2', 'Administrator', 1, NOW());

-- Additional demo users (same default password: Admin@12345) so that
-- Assigned User filters and User Performance reports have sample data.
INSERT INTO `users` (`full_name`, `username`, `email`, `password`, `role`, `status`, `created_at`) VALUES
('Sarah Khan', 'sarah.khan', 'sarah.khan@example.com', '$2y$10$nuVk2OhFHkb1cJbHEA.4turJSLN0WmH59xyba7KyFs4cMckDomcq2', 'Manager', 1, NOW()),
('Ahmed Ali', 'ahmed.ali', 'ahmed.ali@example.com', '$2y$10$nuVk2OhFHkb1cJbHEA.4turJSLN0WmH59xyba7KyFs4cMckDomcq2', 'User', 1, NOW()),
('Priya Sharma', 'priya.sharma', 'priya.sharma@example.com', '$2y$10$nuVk2OhFHkb1cJbHEA.4turJSLN0WmH59xyba7KyFs4cMckDomcq2', 'User', 1, NOW());

-- SLA Policies (5)
INSERT INTO `sla_policies` (`policy_name`, `description`, `hours`, `minutes`, `warning_before_minutes`, `status`, `created_at`) VALUES
('Critical', 'Highest priority issues requiring immediate attention.', 2, 0, 15, 1, NOW()),
('High', 'Urgent issues affecting business operations.', 4, 0, 30, 1, NOW()),
('Medium', 'Standard priority issues.', 8, 0, 60, 1, NOW()),
('Low', 'Low priority, non-urgent issues.', 24, 0, 120, 1, NOW()),
('Extended', 'Custom long-duration SLA for complex or low-urgency work.', 72, 0, 240, 1, NOW());

-- Sample Records (20)
INSERT INTO `records` (`reference_number`, `title`, `description`, `customer_name`, `department`, `assigned_to`, `priority`, `sla_policy_id`, `created_at`, `due_at`, `completed_at`, `status`) VALUES
('REC-DEMO-0001', 'Payment gateway timeout', 'Customer unable to complete checkout due to gateway timeout errors.', 'Nova Retail Ltd', 'IT Support', 3, 'Critical', 1, NOW() - INTERVAL 300 MINUTE, NOW() - INTERVAL 180 MINUTE, NULL, 'Open'),
('REC-DEMO-0002', 'Shipment delayed at customs', 'Container held at customs, customer requesting updated ETA.', 'Blue Horizon Freight', 'Logistics', 4, 'High', 2, NOW() - INTERVAL 120 MINUTE, NOW() + INTERVAL 120 MINUTE, NULL, 'In Progress'),
('REC-DEMO-0003', 'Password reset not working', 'User reports reset link expires immediately.', 'Orbit Telecom', 'Customer Service', 2, 'Medium', 3, NOW() - INTERVAL 450 MINUTE, NOW() + INTERVAL 30 MINUTE, NULL, 'In Progress'),
('REC-DEMO-0004', 'Forklift maintenance request', 'Routine maintenance requested for warehouse forklift #12.', 'Internal Ops', 'Warehouse', 3, 'Low', 4, NOW() - INTERVAL 240 MINUTE, NOW() + INTERVAL 1200 MINUTE, NULL, 'Open'),
('REC-DEMO-0005', 'Employee onboarding paperwork', 'New hire documentation review and system access setup.', 'Internal HR', 'HR', NULL, 'Low', 5, NOW() - INTERVAL 720 MINUTE, NOW() + INTERVAL 3600 MINUTE, NULL, 'Open'),
('REC-DEMO-0006', 'Server outage - checkout API', 'Full outage of checkout API resolved after failover.', 'Nova Retail Ltd', 'Billing', 4, 'Critical', 1, NOW() - INTERVAL 720 MINUTE, NOW() - INTERVAL 600 MINUTE, NOW() - INTERVAL 630 MINUTE, 'Completed'),
('REC-DEMO-0007', 'Incorrect invoice amount', 'Customer billed twice for the same subscription period.', 'Aster Financial', 'IT Support', 3, 'High', 2, NOW() - INTERVAL 1440 MINUTE, NOW() - INTERVAL 1200 MINUTE, NOW() - INTERVAL 900 MINUTE, 'Completed'),
('REC-DEMO-0008', 'Damaged goods on delivery', 'Received pallet had visible water damage.', 'Blue Horizon Freight', 'Logistics', 2, 'Medium', 3, NOW() - INTERVAL 3480 MINUTE, NOW() - INTERVAL 3000 MINUTE, NOW() - INTERVAL 3120 MINUTE, 'Completed'),
('REC-DEMO-0009', 'Account access request', 'Customer requested elevated portal access for new team member.', 'Orbit Telecom', 'Customer Service', NULL, 'Low', 4, NOW() - INTERVAL 7440 MINUTE, NOW() - INTERVAL 6000 MINUTE, NOW() - INTERVAL 6300 MINUTE, 'Completed'),
('REC-DEMO-0010', 'Duplicate stock count entry', 'Cycle count entered twice for same SKU batch.', 'Internal Ops', 'Warehouse', 4, 'Critical', 1, NOW() - INTERVAL 420 MINUTE, NOW() - INTERVAL 300 MINUTE, NULL, 'Cancelled'),
('REC-DEMO-0011', 'Benefits enrollment support', 'Employee needs help selecting new benefits plan.', 'Internal HR', 'HR', 3, 'Low', 5, NOW() - INTERVAL 4200 MINUTE, NOW() + INTERVAL 120 MINUTE, NULL, 'Open'),
('REC-DEMO-0012', 'Late payment reminder dispute', 'Customer disputes an automated late payment notice.', 'Aster Financial', 'Billing', 2, 'Medium', 3, NOW() - INTERVAL 540 MINUTE, NOW() - INTERVAL 60 MINUTE, NULL, 'In Progress'),
('REC-DEMO-0013', 'Critical API authentication failure', 'Third-party integration cannot authenticate against public API.', 'Nova Retail Ltd', 'IT Support', NULL, 'Critical', 1, NOW() - INTERVAL 108 MINUTE, NOW() + INTERVAL 12 MINUTE, NULL, 'Open'),
('REC-DEMO-0014', 'Tracking number not updating', 'Shipment tracking page frozen at ''label created'' status.', 'Blue Horizon Freight', 'Logistics', 3, 'High', 2, NOW() - INTERVAL 216 MINUTE, NOW() + INTERVAL 24 MINUTE, NULL, 'Open'),
('REC-DEMO-0015', 'Service outage - call center', 'Inbound call routing failure affecting support queue.', 'Orbit Telecom', 'Customer Service', 4, 'Critical', 1, NOW() - INTERVAL 150 MINUTE, NOW() - INTERVAL 30 MINUTE, NULL, 'Open'),
('REC-DEMO-0016', 'Pallet racking inspection', 'Scheduled safety inspection of racking in bay 4.', 'Internal Ops', 'Warehouse', 2, 'Low', 4, NOW() - INTERVAL 1260 MINUTE, NOW() + INTERVAL 180 MINUTE, NULL, 'In Progress'),
('REC-DEMO-0017', 'Duplicate HR ticket', 'Ticket raised twice for same leave request by mistake.', 'Internal HR', 'HR', NULL, 'Medium', 3, NOW() - INTERVAL 180 MINUTE, NOW() + INTERVAL 300 MINUTE, NULL, 'Cancelled'),
('REC-DEMO-0018', 'Refund not processed', 'Approved refund never reached customer''s bank account.', 'Aster Financial', 'Billing', 3, 'High', 2, NOW() - INTERVAL 2040 MINUTE, NOW() - INTERVAL 1800 MINUTE, NOW() - INTERVAL 1500 MINUTE, 'Completed'),
('REC-DEMO-0019', 'Legacy system migration task', 'Migrate archived tickets from legacy helpdesk system.', 'Nova Retail Ltd', 'IT Support', 4, 'Low', 5, NOW() - INTERVAL 1320 MINUTE, NOW() + INTERVAL 3000 MINUTE, NULL, 'Open'),
('REC-DEMO-0020', 'Priority support escalation', 'VIP customer escalation regarding response times.', 'Orbit Telecom', 'Customer Service', 2, 'High', 2, NOW() - INTERVAL 60 MINUTE, NOW() + INTERVAL 180 MINUTE, NULL, 'In Progress');

-- Sample Activity Logs
INSERT INTO `activity_logs` (`record_id`, `user_id`, `action`, `remarks`, `created_at`) VALUES
(1, 3, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 300 MINUTE),
(1, 3, 'Comment', 'Customer requested a status update.', NOW() - INTERVAL 270 MINUTE),
(2, 4, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 120 MINUTE),
(2, 4, 'Updated', 'Status changed from Open to In Progress.', NOW() - INTERVAL 60 MINUTE),
(2, 4, 'Comment', 'Customer requested a status update.', NOW() - INTERVAL 90 MINUTE),
(3, 2, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 450 MINUTE),
(3, 2, 'Updated', 'Status changed from Open to In Progress.', NOW() - INTERVAL 225 MINUTE),
(4, 3, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 240 MINUTE),
(5, NULL, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 720 MINUTE),
(6, 4, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 720 MINUTE),
(6, 4, 'Completed', 'Marked as Completed.', NOW() - INTERVAL 630 MINUTE),
(7, 3, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 1440 MINUTE),
(7, 3, 'Completed', 'Marked as Completed.', NOW() - INTERVAL 900 MINUTE),
(8, 2, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 3480 MINUTE),
(8, 2, 'Completed', 'Marked as Completed.', NOW() - INTERVAL 3120 MINUTE),
(9, NULL, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 7440 MINUTE),
(9, NULL, 'Completed', 'Marked as Completed.', NOW() - INTERVAL 6300 MINUTE),
(10, 4, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 420 MINUTE),
(10, 4, 'Cancelled', 'Record cancelled.', NOW() - INTERVAL 360 MINUTE),
(11, 3, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 4200 MINUTE),
(12, 2, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 540 MINUTE),
(12, 2, 'Updated', 'Status changed from Open to In Progress.', NOW() - INTERVAL 270 MINUTE),
(12, 2, 'Comment', 'Customer requested a status update.', NOW() - INTERVAL 510 MINUTE),
(13, NULL, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 108 MINUTE),
(14, 3, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 216 MINUTE),
(15, 4, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 150 MINUTE),
(16, 2, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 1260 MINUTE),
(16, 2, 'Updated', 'Status changed from Open to In Progress.', NOW() - INTERVAL 630 MINUTE),
(17, NULL, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 180 MINUTE),
(17, NULL, 'Cancelled', 'Record cancelled.', NOW() - INTERVAL 120 MINUTE),
(18, 3, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 2040 MINUTE),
(18, 3, 'Completed', 'Marked as Completed.', NOW() - INTERVAL 1500 MINUTE),
(19, 4, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 1320 MINUTE),
(20, 2, 'Created', 'Record created and SLA policy assigned.', NOW() - INTERVAL 60 MINUTE),
(20, 2, 'Updated', 'Status changed from Open to In Progress.', NOW() - INTERVAL 30 MINUTE);

SET FOREIGN_KEY_CHECKS = 1;
