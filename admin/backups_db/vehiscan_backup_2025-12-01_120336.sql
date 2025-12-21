-- VehiScan Database Backup
-- Date: 2025-12-01 12:03:36

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` VALUES ('1', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: CAMERA', '::1', '2025-11-08 04:50:24');
INSERT INTO `audit_logs` VALUES ('2', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: BBO-111', '::1', '2025-11-08 04:51:08');
INSERT INTO `audit_logs` VALUES ('3', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: BBO-111', '::1', '2025-11-08 17:52:23');
INSERT INTO `audit_logs` VALUES ('4', NULL, 'ydnAr', 'DELETE', 'recent_logs', '56', 'Deleted access log', '::1', '2025-11-13 19:54:35');
INSERT INTO `audit_logs` VALUES ('5', NULL, 'admin', 'DELETE', 'recent_logs', '66', 'Deleted access log', '::1', '2025-11-15 05:53:58');
INSERT INTO `audit_logs` VALUES ('6', NULL, 'Bato', 'DELETE_ALL', 'recent_logs', NULL, 'Cleared all logs (55 records)', '::1', '2025-11-16 06:24:14');
INSERT INTO `audit_logs` VALUES ('7', NULL, 'Bato', 'DELETE_ALL', 'recent_logs', NULL, 'Cleared all logs (7 records)', '::1', '2025-11-16 18:00:25');
INSERT INTO `audit_logs` VALUES ('8', NULL, 'Administrator', 'VISITOR_PASS_CANCELLED', 'visitor_passes', '1', 'Cancelled visitor pass #1', '::1', '2025-11-20 18:10:23');
INSERT INTO `audit_logs` VALUES ('9', NULL, 'Administrator', 'DELETE', 'recent_logs', '114', 'Deleted access log', '::1', '2025-12-01 17:41:18');

DROP TABLE IF EXISTS `failed_login_attempts`;
CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` enum('invalid_password','account_locked','account_not_found') DEFAULT 'invalid_password',
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_attempted` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `failed_login_attempts` VALUES ('1', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:11', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('2', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:21', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('3', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:23', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('4', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:25', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('5', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:17:11', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('6', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:19:16', 'account_not_found');

DROP TABLE IF EXISTS `homeowners`;
CREATE TABLE `homeowners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `owner_img` varchar(255) DEFAULT NULL,
  `car_img` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `homeowners` VALUES ('19', 'KYLE JANSEN ', '09656817848', 'B13 L42 Majestic St.', 'TOYOTA GT 86', 'BLACK', 'BBO-111', 'uploads/68983da885afc.png', 'uploads/68983da885cb1_13.jpg', '2025-08-10 14:35:20');
INSERT INTO `homeowners` VALUES ('20', 'dan bringer', '45642133', 'basd njasd', 'sea', 'orange', 'wds-111', 'uploads/68aadce27b8b0.png', 'uploads/68aadce27bb25_536272754_122143014434623944_4374817182458139454_n.jpg', '2025-08-24 17:35:30');
INSERT INTO `homeowners` VALUES ('22', 'asdasd', 'asdawd', 'asdwdasd', 'dawdawd', '', 'wadasdaw', NULL, NULL, '2025-10-31 15:10:24');
INSERT INTO `homeowners` VALUES ('28', '123123', 'asdawd', 'asdwdasd', 'dawdawd', '121312', 'wadasdaw', NULL, NULL, '2025-10-31 17:38:18');
INSERT INTO `homeowners` VALUES ('32', 'asdwd', 'q23qwed', 'qweqda', 'Car', 'awdwa', 'WADAWD', NULL, NULL, '2025-11-01 16:03:49');
INSERT INTO `homeowners` VALUES ('35', 'etaerqewr', 'qwr3rasfd', '314efrqwef', 'Motorcycle', 'q24235rqwrfafd', 'Q3RQWR', NULL, NULL, '2025-11-02 18:01:03');
INSERT INTO `homeowners` VALUES ('36', '123', 'qwe', 'ewq', 'qwewqe', '', 'wqe', NULL, NULL, '2025-11-05 08:30:39');
INSERT INTO `homeowners` VALUES ('41', 'qwe12', '1234wqe', 'qd qwe', 'Car', 'qwe125wre', 'QWER', NULL, NULL, '2025-11-06 21:01:35');
INSERT INTO `homeowners` VALUES ('44', 'test', 'test', 'test', 'Motorcycle', 'yest', 'TEST', NULL, NULL, '2025-11-07 16:23:11');
INSERT INTO `homeowners` VALUES ('48', 'test camera', 'camera', 'test', 'Motorcycle', 'test', 'CAMERA', 'homeowners/upload_690de086328228.78115433.jpg', 'homeowners/upload_690de086331fb8.67660717.jpg', '2025-11-07 20:05:26');
INSERT INTO `homeowners` VALUES ('49', 'Keyboard Mouse', 'test', 'test', 'Van', 'RGB', 'FAH-6761', 'homeowners/upload_6911482c927ab2.67204070.jpg', 'homeowners/upload_6911482c92f1a1.86442996.jpg', '2025-11-10 10:04:28');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) NOT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `execution_time_ms` int(11) DEFAULT NULL,
  `status` enum('success','failed') DEFAULT 'success',
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `migrations` VALUES ('1', '001_create_super_admin.sql', '2025-11-20 11:46:58', '317', 'success');

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_action_time` (`ip_address`,`action`,`created_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rate_limits` VALUES ('1', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 05:34:45');
INSERT INTO `rate_limits` VALUES ('2', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 06:51:27');
INSERT INTO `rate_limits` VALUES ('3', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 12:55:08');
INSERT INTO `rate_limits` VALUES ('4', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 12:55:17');
INSERT INTO `rate_limits` VALUES ('5', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 13:57:54');
INSERT INTO `rate_limits` VALUES ('6', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 19:44:39');
INSERT INTO `rate_limits` VALUES ('7', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 19:44:49');
INSERT INTO `rate_limits` VALUES ('8', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 20:50:03');
INSERT INTO `rate_limits` VALUES ('9', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-13 20:50:20');
INSERT INTO `rate_limits` VALUES ('10', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-14 04:40:25');
INSERT INTO `rate_limits` VALUES ('11', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '1', '2025-11-14 04:40:32');
INSERT INTO `rate_limits` VALUES ('12', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '0', '2025-11-14 05:23:00');
INSERT INTO `rate_limits` VALUES ('13', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '0', '2025-11-14 05:25:47');
INSERT INTO `rate_limits` VALUES ('14', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '0', '2025-11-14 05:25:49');
INSERT INTO `rate_limits` VALUES ('15', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '0', '2025-11-14 05:27:09');
INSERT INTO `rate_limits` VALUES ('16', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 OPR/122.0.0.0', '0', '2025-11-14 05:27:11');
INSERT INTO `rate_limits` VALUES ('17', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-17 18:08:58');
INSERT INTO `rate_limits` VALUES ('18', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 06:56:36');
INSERT INTO `rate_limits` VALUES ('19', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 11:22:47');
INSERT INTO `rate_limits` VALUES ('20', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '0', '2025-11-20 11:52:09');
INSERT INTO `rate_limits` VALUES ('21', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '0', '2025-11-20 11:52:11');
INSERT INTO `rate_limits` VALUES ('22', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '0', '2025-11-20 11:52:26');
INSERT INTO `rate_limits` VALUES ('23', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 11:56:28');
INSERT INTO `rate_limits` VALUES ('24', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 11:56:33');
INSERT INTO `rate_limits` VALUES ('25', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 12:11:07');
INSERT INTO `rate_limits` VALUES ('26', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 12:11:15');
INSERT INTO `rate_limits` VALUES ('27', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 12:11:57');
INSERT INTO `rate_limits` VALUES ('28', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 12:12:01');
INSERT INTO `rate_limits` VALUES ('29', '::1', 'login', NULL, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '1', '2025-11-20 12:12:34');

DROP TABLE IF EXISTS `recent_logs`;
CREATE TABLE `recent_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) NOT NULL,
  `status` enum('IN','OUT') NOT NULL,
  `log_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `recent_logs` VALUES ('86', 'CAMERA', 'IN', '15:04:35', '2025-11-17 15:04:35');
INSERT INTO `recent_logs` VALUES ('87', 'wds-111', 'IN', '15:04:39', '2025-11-17 15:04:39');
INSERT INTO `recent_logs` VALUES ('88', 'CAMERA', 'OUT', '15:04:44', '2025-11-17 15:04:44');
INSERT INTO `recent_logs` VALUES ('89', 'wds-111', 'OUT', '15:04:49', '2025-11-17 15:04:49');
INSERT INTO `recent_logs` VALUES ('90', 'Q3RQWR', 'IN', '14:07:00', '2025-11-20 14:07:00');
INSERT INTO `recent_logs` VALUES ('91', 'Q3RQWR', 'OUT', '14:07:14', '2025-11-20 14:07:14');
INSERT INTO `recent_logs` VALUES ('92', 'wadasdaw', 'IN', '07:40:24', '2025-11-21 07:40:24');
INSERT INTO `recent_logs` VALUES ('93', 'wadasdaw', 'OUT', '07:40:38', '2025-11-21 07:40:38');
INSERT INTO `recent_logs` VALUES ('94', 'wqe', 'IN', '09:46:55', '2025-11-21 09:46:55');
INSERT INTO `recent_logs` VALUES ('95', 'wqe', 'OUT', '09:46:57', '2025-11-21 09:46:57');
INSERT INTO `recent_logs` VALUES ('96', 'wadasdaw', 'IN', '17:18:51', '2025-11-21 17:18:51');
INSERT INTO `recent_logs` VALUES ('97', 'wadasdaw', 'OUT', '17:18:54', '2025-11-21 17:18:54');
INSERT INTO `recent_logs` VALUES ('98', 'wqe', 'IN', '18:28:05', '2025-11-21 18:28:05');
INSERT INTO `recent_logs` VALUES ('99', 'wqe', 'OUT', '18:28:07', '2025-11-21 18:28:07');
INSERT INTO `recent_logs` VALUES ('100', 'wds-111', 'IN', '20:06:44', '2025-11-21 20:06:44');
INSERT INTO `recent_logs` VALUES ('101', 'wds-111', 'OUT', '20:06:45', '2025-11-21 20:06:45');
INSERT INTO `recent_logs` VALUES ('102', 'wds-111', 'IN', '20:47:18', '2025-11-21 20:47:18');
INSERT INTO `recent_logs` VALUES ('103', 'wds-111', 'OUT', '20:47:21', '2025-11-21 20:47:21');
INSERT INTO `recent_logs` VALUES ('104', 'wadasdaw', 'IN', '16:45:41', '2025-12-01 16:45:41');
INSERT INTO `recent_logs` VALUES ('105', 'wadasdaw', 'OUT', '16:50:55', '2025-12-01 16:50:55');
INSERT INTO `recent_logs` VALUES ('106', 'wadasdaw', 'IN', '16:58:37', '2025-12-01 16:58:37');
INSERT INTO `recent_logs` VALUES ('107', 'wadasdaw', 'OUT', '16:58:47', '2025-12-01 16:58:47');
INSERT INTO `recent_logs` VALUES ('108', 'wadasdaw', 'IN', '16:59:01', '2025-12-01 16:59:01');
INSERT INTO `recent_logs` VALUES ('109', 'wqe', 'IN', '17:08:47', '2025-12-01 17:08:47');
INSERT INTO `recent_logs` VALUES ('110', 'wqe', 'OUT', '17:08:49', '2025-12-01 17:08:49');
INSERT INTO `recent_logs` VALUES ('111', 'wqe', 'IN', '17:11:55', '2025-12-01 17:11:55');
INSERT INTO `recent_logs` VALUES ('112', 'BBO-111', 'IN', '17:15:58', '2025-12-01 17:15:58');
INSERT INTO `recent_logs` VALUES ('113', 'wqe', 'OUT', '17:27:35', '2025-12-01 17:27:35');
INSERT INTO `recent_logs` VALUES ('115', 'wadasdaw', 'OUT', '17:54:26', '2025-12-01 17:54:26');
INSERT INTO `recent_logs` VALUES ('116', 'wadasdaw', 'IN', '17:54:28', '2025-12-01 17:54:28');
INSERT INTO `recent_logs` VALUES ('117', 'wadasdaw', 'OUT', '17:54:31', '2025-12-01 17:54:31');

DROP TABLE IF EXISTS `rfid_simulator`;
CREATE TABLE `rfid_simulator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) DEFAULT NULL,
  `simulated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_plate` (`plate_number`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rfid_simulator` VALUES ('1', 'CAMERA', '2025-11-08 04:50:24');
INSERT INTO `rfid_simulator` VALUES ('2', 'BBO-111', '2025-11-08 04:51:08');
INSERT INTO `rfid_simulator` VALUES ('3', 'BBO-111', '2025-11-08 17:52:23');
INSERT INTO `rfid_simulator` VALUES ('4', 'CAMERA', '2025-11-11 09:34:04');
INSERT INTO `rfid_simulator` VALUES ('5', 'HAHNWOZNN', '2025-11-11 09:34:51');
INSERT INTO `rfid_simulator` VALUES ('6', 'HAHNWOZNN', '2025-11-11 09:35:22');
INSERT INTO `rfid_simulator` VALUES ('7', 'BBO-111', '2025-11-11 09:39:59');
INSERT INTO `rfid_simulator` VALUES ('8', 'TEST', '2025-11-11 09:40:19');
INSERT INTO `rfid_simulator` VALUES ('9', 'TEST', '2025-11-11 09:40:27');
INSERT INTO `rfid_simulator` VALUES ('10', 'wds-111', '2025-11-11 09:45:40');
INSERT INTO `rfid_simulator` VALUES ('11', 'FAH-6761', '2025-11-11 09:51:11');
INSERT INTO `rfid_simulator` VALUES ('12', 'HAHNWOZNN', '2025-11-11 09:55:57');
INSERT INTO `rfid_simulator` VALUES ('13', 'CAMERA', '2025-11-11 09:57:40');
INSERT INTO `rfid_simulator` VALUES ('14', 'BBO-111', '2025-11-11 09:59:55');
INSERT INTO `rfid_simulator` VALUES ('15', 'HAHNWOZNN', '2025-11-11 10:00:05');
INSERT INTO `rfid_simulator` VALUES ('16', 'CAMERA', '2025-11-11 10:11:15');
INSERT INTO `rfid_simulator` VALUES ('17', 'HQYQTQ', '2025-11-11 10:21:51');
INSERT INTO `rfid_simulator` VALUES ('18', 'CAMERA', '2025-11-12 06:31:47');
INSERT INTO `rfid_simulator` VALUES ('19', 'BBO-111', '2025-11-12 06:32:10');
INSERT INTO `rfid_simulator` VALUES ('20', 'HAHNWOZNN', '2025-11-12 06:37:01');
INSERT INTO `rfid_simulator` VALUES ('21', 'CAMERA', '2025-11-12 06:56:27');
INSERT INTO `rfid_simulator` VALUES ('22', 'CAMERA', '2025-11-12 06:56:41');
INSERT INTO `rfid_simulator` VALUES ('23', 'CAMERA', '2025-11-12 07:05:25');
INSERT INTO `rfid_simulator` VALUES ('24', 'CAMERA', '2025-11-12 07:09:23');
INSERT INTO `rfid_simulator` VALUES ('25', 'CAMERA', '2025-11-12 07:22:00');
INSERT INTO `rfid_simulator` VALUES ('26', 'BBO-111', '2025-11-12 07:22:16');
INSERT INTO `rfid_simulator` VALUES ('27', 'TEST', '2025-11-12 07:30:51');
INSERT INTO `rfid_simulator` VALUES ('28', 'CAMERA', '2025-11-12 08:15:46');
INSERT INTO `rfid_simulator` VALUES ('29', 'CAMERA', '2025-11-12 08:15:51');
INSERT INTO `rfid_simulator` VALUES ('30', 'CAMERA', '2025-11-12 08:38:05');
INSERT INTO `rfid_simulator` VALUES ('31', 'CAMERA', '2025-11-13 05:01:40');
INSERT INTO `rfid_simulator` VALUES ('32', 'CAMERA', '2025-11-13 06:51:21');
INSERT INTO `rfid_simulator` VALUES ('33', 'CAMERA', '2025-11-13 07:11:12');
INSERT INTO `rfid_simulator` VALUES ('34', 'BBO-111', '2025-11-13 12:58:35');
INSERT INTO `rfid_simulator` VALUES ('35', 'BBO-111', '2025-11-13 19:54:55');
INSERT INTO `rfid_simulator` VALUES ('36', 'BBO-111', '2025-11-13 20:09:04');
INSERT INTO `rfid_simulator` VALUES ('37', 'BBO-111', '2025-11-13 20:09:47');
INSERT INTO `rfid_simulator` VALUES ('38', 'BBO-111', '2025-11-13 20:50:35');
INSERT INTO `rfid_simulator` VALUES ('39', 'TEST', '2025-11-13 20:50:54');
INSERT INTO `rfid_simulator` VALUES ('40', 'FAH-6761', '2025-11-14 06:42:29');
INSERT INTO `rfid_simulator` VALUES ('41', 'wqe', '2025-11-14 16:18:32');
INSERT INTO `rfid_simulator` VALUES ('42', 'wadasdaw', '2025-11-15 05:54:08');
INSERT INTO `rfid_simulator` VALUES ('43', 'wadasdaw', '2025-11-15 05:55:06');
INSERT INTO `rfid_simulator` VALUES ('44', 'wadasdaw', '2025-11-15 12:39:32');
INSERT INTO `rfid_simulator` VALUES ('45', 'wadasdaw', '2025-11-15 13:28:40');
INSERT INTO `rfid_simulator` VALUES ('46', 'wadasdaw', '2025-11-15 13:28:58');
INSERT INTO `rfid_simulator` VALUES ('47', 'wadasdaw', '2025-11-15 13:46:26');
INSERT INTO `rfid_simulator` VALUES ('48', 'HAHNWOZNN', '2025-11-15 13:58:24');
INSERT INTO `rfid_simulator` VALUES ('49', 'FAH-6761', '2025-11-15 14:09:01');
INSERT INTO `rfid_simulator` VALUES ('50', 'FAH-6761', '2025-11-15 14:17:08');
INSERT INTO `rfid_simulator` VALUES ('51', 'FAH-6761', '2025-11-15 14:17:14');
INSERT INTO `rfid_simulator` VALUES ('52', 'BBO-111', '2025-11-16 06:24:55');
INSERT INTO `rfid_simulator` VALUES ('53', 'BBO-111', '2025-11-16 06:54:05');
INSERT INTO `rfid_simulator` VALUES ('54', 'wds-111', '2025-11-16 07:39:02');
INSERT INTO `rfid_simulator` VALUES ('55', 'QWER', '2025-11-16 17:29:46');
INSERT INTO `rfid_simulator` VALUES ('56', 'QWER', '2025-11-16 17:29:51');
INSERT INTO `rfid_simulator` VALUES ('57', 'CAMERA', '2025-11-16 17:31:26');
INSERT INTO `rfid_simulator` VALUES ('58', 'CAMERA', '2025-11-16 17:31:31');
INSERT INTO `rfid_simulator` VALUES ('59', 'CAMERA', '2025-11-17 15:04:35');
INSERT INTO `rfid_simulator` VALUES ('60', 'wds-111', '2025-11-17 15:04:39');
INSERT INTO `rfid_simulator` VALUES ('61', 'CAMERA', '2025-11-17 15:04:44');
INSERT INTO `rfid_simulator` VALUES ('62', 'wds-111', '2025-11-17 15:04:49');
INSERT INTO `rfid_simulator` VALUES ('63', 'Q3RQWR', '2025-11-20 14:07:01');
INSERT INTO `rfid_simulator` VALUES ('64', 'Q3RQWR', '2025-11-20 14:07:14');
INSERT INTO `rfid_simulator` VALUES ('65', 'wadasdaw', '2025-11-21 07:40:24');
INSERT INTO `rfid_simulator` VALUES ('66', 'wadasdaw', '2025-11-21 07:40:38');
INSERT INTO `rfid_simulator` VALUES ('67', 'wqe', '2025-11-21 09:46:55');
INSERT INTO `rfid_simulator` VALUES ('68', 'wqe', '2025-11-21 09:46:57');
INSERT INTO `rfid_simulator` VALUES ('69', 'wadasdaw', '2025-11-21 17:18:51');
INSERT INTO `rfid_simulator` VALUES ('70', 'wadasdaw', '2025-11-21 17:18:54');
INSERT INTO `rfid_simulator` VALUES ('71', 'wqe', '2025-11-21 18:28:05');
INSERT INTO `rfid_simulator` VALUES ('72', 'wqe', '2025-11-21 18:28:07');
INSERT INTO `rfid_simulator` VALUES ('73', 'wds-111', '2025-11-21 20:06:44');
INSERT INTO `rfid_simulator` VALUES ('74', 'wds-111', '2025-11-21 20:06:45');
INSERT INTO `rfid_simulator` VALUES ('75', 'wds-111', '2025-11-21 20:47:18');
INSERT INTO `rfid_simulator` VALUES ('76', 'wds-111', '2025-11-21 20:47:21');
INSERT INTO `rfid_simulator` VALUES ('77', 'wadasdaw', '2025-12-01 16:45:41');
INSERT INTO `rfid_simulator` VALUES ('78', 'wadasdaw', '2025-12-01 16:50:55');
INSERT INTO `rfid_simulator` VALUES ('79', 'wadasdaw', '2025-12-01 16:58:37');
INSERT INTO `rfid_simulator` VALUES ('80', 'wadasdaw', '2025-12-01 16:58:47');
INSERT INTO `rfid_simulator` VALUES ('81', 'wadasdaw', '2025-12-01 16:59:01');
INSERT INTO `rfid_simulator` VALUES ('82', 'wqe', '2025-12-01 17:08:47');
INSERT INTO `rfid_simulator` VALUES ('83', 'wqe', '2025-12-01 17:08:49');
INSERT INTO `rfid_simulator` VALUES ('84', 'wqe', '2025-12-01 17:11:55');
INSERT INTO `rfid_simulator` VALUES ('85', 'BBO-111', '2025-12-01 17:15:58');
INSERT INTO `rfid_simulator` VALUES ('86', 'wqe', '2025-12-01 17:27:35');
INSERT INTO `rfid_simulator` VALUES ('87', 'wqe', '2025-12-01 17:27:37');
INSERT INTO `rfid_simulator` VALUES ('88', 'wadasdaw', '2025-12-01 17:54:26');
INSERT INTO `rfid_simulator` VALUES ('89', 'wadasdaw', '2025-12-01 17:54:28');
INSERT INTO `rfid_simulator` VALUES ('90', 'wadasdaw', '2025-12-01 17:54:31');

DROP TABLE IF EXISTS `security_settings`;
CREATE TABLE `security_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `security_settings` VALUES ('1', 'password_min_length', '12', 'integer', 'Minimum password length requirement', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('2', 'password_require_uppercase', 'true', 'boolean', 'Require at least one uppercase letter', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('3', 'password_require_lowercase', 'true', 'boolean', 'Require at least one lowercase letter', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('4', 'password_require_number', 'true', 'boolean', 'Require at least one number', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('5', 'password_require_special', 'true', 'boolean', 'Require at least one special character', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('6', 'password_expiry_days', '90', 'integer', 'Days before password expires (0 = never)', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('7', 'max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('8', 'lockout_duration_minutes', '30', 'integer', 'Account lockout duration in minutes', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('9', 'session_timeout_minutes', '30', 'integer', 'Session timeout in minutes', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('10', 'https_only', 'true', 'boolean', 'Enforce HTTPS connections', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('11', 'two_factor_enabled', 'false', 'boolean', 'Enable two-factor authentication', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('12', 'backup_encryption_enabled', 'true', 'boolean', 'Encrypt backup files', NULL, '2025-11-20 11:46:58');
INSERT INTO `security_settings` VALUES ('13', 'audit_log_retention_days', '365', 'integer', 'Days to retain audit logs', NULL, '2025-11-20 11:46:58');

DROP TABLE IF EXISTS `super_admin`;
CREATE TABLE `super_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `require_password_change` tinyint(1) DEFAULT 0,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `two_factor_secret` varchar(100) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `is_setup_complete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_locked` (`locked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `super_admin` VALUES ('1', 'Administrator', '$2y$10$CcXZ9GxY7vntmL8vgtTDW.gqHt43vDQMDo3u82y3XJ9QsAAEqlYjm', 'itstotallynotrandy@gmail.com', 'rAndy', '2025-11-20 11:51:18', '2025-12-01 18:19:16', '::1', '2025-11-20 11:51:18', '0', '0', NULL, NULL, '0', '1');

DROP TABLE IF EXISTS `system_installation`;
CREATE TABLE `system_installation` (
  `id` int(11) NOT NULL DEFAULT 1,
  `is_installed` tinyint(1) DEFAULT 0,
  `installed_at` timestamp NULL DEFAULT NULL,
  `installed_by` varchar(50) DEFAULT NULL,
  `installation_key` varchar(64) DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `installation_key` (`installation_key`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_installation` VALUES ('1', '1', '2025-11-20 11:51:18', 'Administrator', '5a043527785a61c68f568f326e3c2a8f4a44389fb25ed26cb71e9a88730925bf', '2.0.0');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','guard','owner') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Guards and homeowners only - admins use super_admin table';

INSERT INTO `users` VALUES ('11', 'jojo', '$2y$10$WRQJtkRS2r0NgguwjxqgoOiPJjoyjlF54vWm8pLr9t3lof//N4iDm', 'guard', '2025-08-10 11:05:25');
INSERT INTO `users` VALUES ('14', 'jaja', '$2y$10$13IkLGDRVc.VApU8NIMFh.4WLYf/1T276iKJ0.71h2gv7EmS2SQIC', 'guard', '2025-08-10 11:34:42');
INSERT INTO `users` VALUES ('15', 'ydnAr', '$2y$10$b80Wy/bGXNs50rl.OwUE8uX.ap7Pm9P3AyG7UMT5n.ICPJKDwEgiG', 'admin', '2025-10-31 15:08:52');
INSERT INTO `users` VALUES ('16', 'Bato', '$2y$10$e2MK08iPh3yjl7CvFPYp3et1NvD2uaeCRxzQaB8bGSFovzUBcNtXK', 'guard', '2025-11-01 14:02:23');
INSERT INTO `users` VALUES ('17', 'Duterte', '$2y$10$Hl15uCFltazaxegRcITsF.u0D3Ulcyc0xgigMblTcAoMiNAu/fBPy', 'owner', '2025-11-01 14:02:32');

DROP TABLE IF EXISTS `visitor_passes`;
CREATE TABLE `visitor_passes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `homeowner_id` int(11) DEFAULT NULL,
  `visitor_name` varchar(255) DEFAULT NULL,
  `visitor_plate` varchar(20) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `status` enum('active','used','expired','cancelled') DEFAULT 'active',
  `qr_code` text DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `homeowner_id` (`homeowner_id`),
  KEY `idx_plate` (`visitor_plate`),
  KEY `idx_status` (`status`),
  KEY `idx_valid` (`valid_from`,`valid_until`),
  CONSTRAINT `visitor_passes_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `homeowners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `visitor_passes` VALUES ('1', '19', 'Jha', 'KUSH-420', 'Service Provider', '2025-11-12 22:23:00', '2025-11-13 00:23:00', '0', 'cancelled', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASYAAAEmAQMAAAD1Cq+ZAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACrElEQVRoge2Z3c3sIAxELVEAJdE6JaUAJK5nxibR3gqMPrRaZeHsQ4x/xmD2N+4YfftYZu0ZzR/63I8tGwtLE2t71qfw5dOa8I+z+xlmowEPoDrlK6tjpeGl8e0cDONUh22uobDD3c0AS9CJgV9GNb699twf1vn3FRQ82qkHz5jrbomhhPTj92UpZiau///5yV9VKQ2+fcx1pSik5J9RleqaGF5YIkXxp5mcOtjalE/Ai2kABOuWd6OuIkvN2O3i1GYlOSaBL7OEagYP1amuvTX98mnOEFGushuoDNYpL2b4YhGx+3yzb11qKnbdEj7X+BeaBJKBnwuo8OVNSjGK8E1L5G7XppbZUQchf/g/BvRInyhNTSUno/ZZUVgmhDyQU4dqU5SxFH2MXbr2ULy+u12b2tF+0Ysbsy9jd8oSqQsrUxi5sT3ykHSQFMSr++pSfcZ779REzL4p21MplKZUObWrPcsp99/O/BUUVZ4Ot6bUn50m7LVEXcq4z/tIBu4/c1KYpD6FdXouy6Z9OrCYv4AyCb35kUU6t5vyhbBQbWooA6lgsvcai4vU7+P0aYUp9pT0a2WjzcCdLVqxEbW2OKUdpqodkgxU8TRJP3WoNoX3DoqJCs8WBSeO2C+geN6j4/M80DJe/ujI+QJKCZguHPqOUbvVW2sUp7R43htRqwbliIXyVLaYOulZ9gqEKDUyRW2KyYldZhyE5FXe6TvrUyMaSkRt5FrlqjjrOrW2LkUNuz7hG6pBHfa3T6tL5Ya3bJ25+ZJI+F9Kv8pUSvXTa65sMSWLsjspTc1wYV7yZCctY8y3AytO6e1lFZ2mS7/HyXoohQuosITwc3vQZI9bqH2ilndcK++4MqprU6bmMiRtXOjtvDFYN1Bb720W8ke1lDexsE176lN/44bxDzsUagnVvoK2AAAAAElFTkSuQmCC', '742974a37c33e4c05843fff61ce05a42d1e71dbeddae84fe73d00d2c227c8582', '2025-11-13 05:23:49');
INSERT INTO `visitor_passes` VALUES ('2', '49', 'Monitor', 'QWE-222', 'Delivery', '2025-11-20 11:09:00', '2025-11-20 13:09:00', '1', 'active', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASYAAAEmAQMAAAD1Cq+ZAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACqklEQVRoge1awY0EIQyLRAGUROuUNAUg5YidsHOra8Do0GjFgucBJI4Txuy/3dG677ZsNPf22H78Ge0ZK6ZmzPnUR8XPHmsxuSHW9nB3s/i7CqCO2mtd3Rc6DajYFfQ5fgtqd0ecs/PwY7zV2zehVpnwPnl2rkGFRW/fTVuOZzuxxfNt97IoHG+ffz5f/KWKYuvhpnszHLS0Wxj4a14bheXieI0mTK8troIHy6MQTPa8GakIsWUUDc+yCW3Ugpvu+RAIgTL8ndykDddHTc57xEzSLTw1XrX8VUelIc8MLE8ycY5bsq82qlMXOI83Ass+cBAVdO4or1VGwXhXRs7YhjBkTDr4uJGDtVHho9R3DYHFDDlK9EPIl01IowbXvY4Jg4aZh1G/y6M6NwBL7ylp8eqblsRRJWnhvogw4bKO2JKxVB6F5bYn5S15l2QMFV/aVxoFuuW6efIcXjVeXiuNMq64pVKY1A6rxo9+F0ZlVSB1EPsMp6kgDjPpophpAZvxhGd+srFLULRcGnX0g6I+ln4D6virsTwA0jUj747ybWUU/DL9tcqxFA7w3VF5mjIKqhbqwMjEzKqLd9/KXRhlST/UswN52Mh7A7ykj4prkLRcwD0NfFLwJjNJo1gRyRUjxYSEp7B956LCKMsK1nodNTeGN103oDrrkShG0n1ZY0aHg/KoclbP/Thea6SrUgrSKLOepVbykyPpNESYVhBx1KCPplqnyxZLsSgrjyrljqSZDXewqfhOrVYZ9Vmp2ymiV/XO6pZSG/W66qldYS1ktHeepo2KhaIKa/gGJM2ZXHUyMHVU3RhkWT03xvILgsxObkGlsyLgUCmsuo+9A1V1yhiLHWBc/RVChVFmXLG3qsUSaFVo10fBhCEKsAHGT8+4Kw3foMmj/tsN7QfGIfDmmWwzCQAAAABJRU5ErkJggg==', 'd8eec966661bf3b2858c5b56c43875d069556e9f08a9cb6b477419bde9241cd5', '2025-11-20 18:09:53');
INSERT INTO `visitor_passes` VALUES ('3', '20', 'Rider', 'ZXC - 222', 'Delivery', '2025-11-21 00:49:00', '2025-11-21 02:49:00', '1', 'active', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASYAAAEmAQMAAAD1Cq+ZAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAAClklEQVRoge2a0Y3tIAxELVEAJdE6JaUAJD88Mw7Z1WvAaFG0YuHkA2zM2Llmf+2O1n23ZWOZRe8xf4bFv8P6jBGf9an4E2OY2WMtHt+D7dnjAqpTe2b1PTwaOg3L30hQ3dc91O6OberPNuiliyis3tnmdup4LqLCo7eR99KfOKkYGenpP/y+LIXI1Od/n1/xqyrFtq2KRW8v5nt08F+tKoXl4j7xiE+6cCIeL03VpyLohjuvdG16d0apKWsXpxSWHqNe4OW5OpEYL08hvnLR2/KLLz3wAswvu4HClWJUQ9iJMDUEIG7XN/rWpULrKSZx6U6lAMlATVSegtZzmBoD0WdAQmdKKZSmYOH3VmFfps4YXJ7q58Jcmaa0FH307hso2DlOKqIRw7BD/fnHo0tTCLcMuhS20gvspy6sTCEURdbVKYLg47EHg8LW1CpTkudYsdLomPHchpOn1aUYe45mn7Q2BdGxdmkKp/ZTEUG/pzhKTXQBxWedXQmNwF25gnpNOljKokzIsJTKvTaFsPRBPpfneEsjxalJiedIUFbgg0f23Z7ylNFzJxbt2JjQeqxTSvCWp8Kw7m4qxMLUUHxMsjOHqU3pjDIVU9/aJyzdQUU0wvFtqFYiOKHwA+++gHqDEy8ZpiaU7Z7ZSW1KFeUJC0vCv2Bs0i0UBdFivdl4w4ympz4lEEqW6SYrecYLNquwtamRw+P4tSKxUeGWp7oEAm0uyfCc1CQ9ujIlg8es9F0/Xyktv1LWplKqZyo2+VVEO3QysNJULFQR199szFvuxB2U8wcRrN65Nga/GvBUgpdQRGhkZWB0bb+GAsKPIY8EoIcA1MGuTpmK6NCw3dUXIlVbnHKXbJ/UPqjIso6O+PTUp/7aDe0fTu53iTgVEUYAAAAASUVORK5CYII=', '6431096cae60fde580eed199b6e6e4afbb6275f38cd526cff1ebbcfd61ba391f', '2025-11-21 07:50:09');

