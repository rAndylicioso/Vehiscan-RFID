-- VehiScan Database Backup
-- Date: 2025-12-09 11:35:22

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
  KEY `idx_created` (`created_at`),
  KEY `idx_audit_logs_action` (`action`),
  KEY `idx_audit_logs_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `audit_logs` VALUES ('1', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: CAMERA', '::1', '2025-11-08 04:50:24');
INSERT INTO `audit_logs` VALUES ('2', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: BBO-111', '::1', '2025-11-08 04:51:08');
INSERT INTO `audit_logs` VALUES ('3', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: BBO-111', '::1', '2025-11-08 17:52:23');
INSERT INTO `audit_logs` VALUES ('4', NULL, 'ydnAr', 'DELETE', 'recent_logs', '56', 'Deleted access log', '::1', '2025-11-13 19:54:35');
INSERT INTO `audit_logs` VALUES ('5', NULL, 'admin', 'DELETE', 'recent_logs', '66', 'Deleted access log', '::1', '2025-11-15 05:53:58');
INSERT INTO `audit_logs` VALUES ('6', NULL, 'Bato', 'DELETE_ALL', 'recent_logs', NULL, 'Cleared all logs (55 records)', '::1', '2025-11-16 06:24:14');
INSERT INTO `audit_logs` VALUES ('7', NULL, 'Bato', 'DELETE_ALL', 'recent_logs', NULL, 'Cleared all logs (7 records)', '::1', '2025-11-16 18:00:25');
INSERT INTO `audit_logs` VALUES ('8', NULL, 'Administrator', 'VISITOR_PASS_CANCELLED', 'visitor_passes', '1', 'Cancelled visitor pass #1', '::1', '2025-11-20 18:10:23');
INSERT INTO `audit_logs` VALUES ('9', NULL, 'Administrator', 'DELETE', 'recent_logs', '114', 'Deleted access log', '::1', '2025-12-01 17:41:18');
INSERT INTO `audit_logs` VALUES ('10', NULL, 'Bato', 'EXPORT_AND_DELETE', 'recent_logs', NULL, 'Exported and deleted all logs (56 records)', '::1', '2025-12-04 08:58:58');
INSERT INTO `audit_logs` VALUES ('11', NULL, 'Administrator', 'employee_update', 'users', '15', '{\"username\":\"ydnAr\",\"role\":\"admin\"}', '::1', '2025-12-04 12:48:47');
INSERT INTO `audit_logs` VALUES ('12', NULL, 'Administrator', 'employee_create', 'users', '18', '{\"username\":\"Guard1\",\"role\":\"guard\"}', '::1', '2025-12-04 12:49:05');

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
  KEY `idx_attempted` (`attempted_at`),
  KEY `idx_failed_login_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `failed_login_attempts` VALUES ('1', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:11', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('2', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:21', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('3', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:23', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('4', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:15:25', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('5', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:17:11', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('6', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 18:19:16', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('7', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 20:33:02', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('8', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 20:33:06', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('9', 'Administrator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-01 20:44:57', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('10', 'kyle_jansen', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-02 17:38:15', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('11', 'kyle_jansen', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 10:48:03', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('12', 'kyle_jansen', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 10:52:39', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('13', 'Administrator', '192.168.1.163', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2025-12-03 17:21:21', 'invalid_password');
INSERT INTO `failed_login_attempts` VALUES ('14', 'Administrator', '192.168.1.163', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Mobile Safari/537.36', '2025-12-03 17:21:21', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('15', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:27:49', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('16', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:28:01', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('17', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:29:13', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('18', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:29:25', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('19', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:35:01', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('20', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:35:16', 'account_not_found');
INSERT INTO `failed_login_attempts` VALUES ('21', 'Test_Account01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 OPR/124.0.0.0', '2025-12-03 19:39:37', 'account_not_found');

DROP TABLE IF EXISTS `homeowner_auth`;
CREATE TABLE `homeowner_auth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `homeowner_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `last_failed_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_homeowner_id` (`homeowner_id`),
  CONSTRAINT `homeowner_auth_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `homeowners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `homeowner_auth` VALUES ('1', '19', 'kyle_jansen', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'kyle_jansen@vehiscan.local', '2025-12-02 17:35:02', '2025-12-03 19:40:01', '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('2', '20', 'dan_bringer', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'dan_bringer@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('3', '22', 'asdasd', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'asdasd@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('4', '28', '123123', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', '123123@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('5', '32', 'asdwd', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'asdwd@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('6', '35', 'etaerqewr', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'etaerqewr@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('7', '36', '123', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', '123@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('8', '41', 'qwe12', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'qwe12@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('9', '44', 'test', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'test@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('10', '48', 'test_camera', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'test_camera@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('11', '49', 'keyboard_mouse', '$2y$10$ZFNZJOV0mEl4C..W5nbHxey1ocOJ1Crxl66guBqEzutH43/EQSF5.', 'keyboard_mouse@vehiscan.local', '2025-12-02 17:35:02', NULL, '1', '0', NULL, NULL);
INSERT INTO `homeowner_auth` VALUES ('12', '55', 'Test_Account01', '$2y$10$VVuB5SPw6A8Ky3QchHtx4eZVJOAC2RBEzqrLez26YfnmXO9le7.zu', NULL, '2025-12-04 07:34:58', '2025-12-04 08:22:12', '1', '0', NULL, NULL);

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
  PRIMARY KEY (`id`),
  KEY `idx_homeowners_plate_number` (`plate_number`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `homeowners` VALUES ('55', 'Homeowner Test', '0987-853-9902', 'Test Address', 'Car', 'Black', 'EUT-069', 'homeowners/20251203_122728_69301ea0b6f8a.jpg', 'vehicles/20251203_122728_69301ea0b84fb.webp', '2025-12-03 19:27:28');

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

DROP TABLE IF EXISTS `recent_logs`;
CREATE TABLE `recent_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) NOT NULL,
  `status` enum('IN','OUT') NOT NULL,
  `log_time` time DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_recent_logs_status` (`status`),
  KEY `idx_recent_logs_created_status` (`created_at`,`status`),
  KEY `idx_recent_logs_plate_number` (`plate_number`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `recent_logs` VALUES ('129', 'EUT-069', 'IN', '12:51:22', '2025-12-04 12:51:22');
INSERT INTO `recent_logs` VALUES ('130', 'EUT-069', 'OUT', '12:51:32', '2025-12-04 12:51:32');

DROP TABLE IF EXISTS `rfid_simulator`;
CREATE TABLE `rfid_simulator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) DEFAULT NULL,
  `simulated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_plate` (`plate_number`)
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
INSERT INTO `rfid_simulator` VALUES ('91', 'wqe', '2025-12-01 20:50:19');
INSERT INTO `rfid_simulator` VALUES ('92', 'wqe', '2025-12-01 20:50:21');
INSERT INTO `rfid_simulator` VALUES ('93', 'wqe', '2025-12-01 20:58:18');
INSERT INTO `rfid_simulator` VALUES ('94', 'CAMERA', '2025-12-01 21:04:24');
INSERT INTO `rfid_simulator` VALUES ('95', 'CAMERA', '2025-12-01 21:17:43');
INSERT INTO `rfid_simulator` VALUES ('96', 'wadasdaw', '2025-12-02 09:16:28');
INSERT INTO `rfid_simulator` VALUES ('97', 'wadasdaw', '2025-12-02 09:16:30');
INSERT INTO `rfid_simulator` VALUES ('98', 'BBO-111', '2025-12-02 17:13:37');
INSERT INTO `rfid_simulator` VALUES ('99', 'BBO-111', '2025-12-02 17:13:40');
INSERT INTO `rfid_simulator` VALUES ('100', 'EUT-069', '2025-12-04 08:37:41');
INSERT INTO `rfid_simulator` VALUES ('101', 'EUT-069', '2025-12-04 08:37:49');
INSERT INTO `rfid_simulator` VALUES ('102', 'EUT-069', '2025-12-04 12:51:22');
INSERT INTO `rfid_simulator` VALUES ('103', 'EUT-069', '2025-12-04 12:51:32');

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
  KEY `idx_locked` (`locked_until`),
  KEY `idx_super_admin_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `super_admin` VALUES ('1', 'Administrator', '$2y$10$CcXZ9GxY7vntmL8vgtTDW.gqHt43vDQMDo3u82y3XJ9QsAAEqlYjm', 'itstotallynotrandy@gmail.com', 'rAndy', '2025-11-20 11:51:18', '2025-12-09 18:28:24', '::1', '2025-11-20 11:51:18', '0', '0', NULL, NULL, '0', '1');

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
  UNIQUE KEY `username` (`username`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Guards and homeowners only - admins use super_admin table';

INSERT INTO `users` VALUES ('11', 'jojo', '$2y$10$WRQJtkRS2r0NgguwjxqgoOiPJjoyjlF54vWm8pLr9t3lof//N4iDm', 'guard', '2025-08-10 11:05:25');
INSERT INTO `users` VALUES ('14', 'jaja', '$2y$10$13IkLGDRVc.VApU8NIMFh.4WLYf/1T276iKJ0.71h2gv7EmS2SQIC', 'guard', '2025-08-10 11:34:42');
INSERT INTO `users` VALUES ('15', 'ydnAr', '$2y$10$b80Wy/bGXNs50rl.OwUE8uX.ap7Pm9P3AyG7UMT5n.ICPJKDwEgiG', 'admin', '2025-10-31 15:08:52');
INSERT INTO `users` VALUES ('16', 'Bato', '$2y$10$e2MK08iPh3yjl7CvFPYp3et1NvD2uaeCRxzQaB8bGSFovzUBcNtXK', 'guard', '2025-11-01 14:02:23');
INSERT INTO `users` VALUES ('17', 'Duterte', '$2y$10$Hl15uCFltazaxegRcITsF.u0D3Ulcyc0xgigMblTcAoMiNAu/fBPy', 'owner', '2025-11-01 14:02:32');
INSERT INTO `users` VALUES ('18', 'Guard1', '$2y$10$CR8ZiyF9heVY7vwtHFXkA.a8neuQuQyIMoHGihPJYRTlY5nUOEd36', 'guard', '2025-12-04 12:49:05');

DROP TABLE IF EXISTS `visitor_auth_tokens`;
CREATE TABLE `visitor_auth_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_pass_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_accessed` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `visitor_pass_id` (`visitor_pass_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `visitor_auth_tokens_ibfk_1` FOREIGN KEY (`visitor_pass_id`) REFERENCES `visitor_passes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `status` enum('pending','approved','active','rejected','used','expired','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `qr_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `homeowner_id` (`homeowner_id`),
  KEY `idx_plate` (`visitor_plate`),
  KEY `idx_status` (`status`),
  KEY `idx_valid` (`valid_from`,`valid_until`),
  KEY `idx_visitor_passes_status` (`status`),
  KEY `idx_visitor_passes_valid_from` (`valid_from`),
  KEY `idx_visitor_passes_valid_until` (`valid_until`),
  KEY `idx_visitor_passes_qr_token` (`qr_token`),
  KEY `fk_visitor_passes_approved_by` (`approved_by`),
  CONSTRAINT `fk_visitor_passes_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `super_admin` (`id`) ON DELETE SET NULL,
  CONSTRAINT `visitor_passes_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `homeowners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `visitor_passes` VALUES ('1', '19', 'Jha', 'KUSH-420', 'Service Provider', '2025-11-12 22:23:00', '2025-11-13 00:23:00', '0', 'cancelled', NULL, NULL, NULL, NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASYAAAEmAQMAAAD1Cq+ZAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACmklEQVRoge2a4a3DIAyELTEAI7E6I2WASH74zkfS6i1gVBRFlHz9UWPss1Oz3zhjdF/jtnGbrXtb8x5Xu4b1Gc981qfitpb9Wsi8e1xYHO1a30ugOrV+a9jgGr6WF2UWc59BhVXOobDtszlsECZx3s+h8InuvAZs8KwfQIVHp1Mb3NmNd/v2+7IUIlOf/15f8asqlWNYBOA4rIYotaxCG3yMohR/dDx3nFdnTDLG4I55eco5ZRaFGQzfMJpEHl2ZWvkE0Ze5Jbw4tjouOnXz+hTjq0LvMslKofhSqiTtdmVKKm878o3F8PErzVOeii31tAcjE5RRXLCEHUHBEpMqj2qdWi+OLPy9PIW4u6YRkODOZiMzDBB5dG0Kmj1OZxMOd6aRDqHMGI0MJnEe3IY9j6R6AIUUyroEu439x85jt8+gPFMlSi4Le7DQNERff2XRspQSplSts/BqlxJpP4GCGSbxJuW+12WJ0lQm0pR7nkKeCefRE6Up1c3sbHFOxPPjLE8h6GLNaZJshGQ15mdQ6bY9VS1jEsUCi87yVAahiSDkRoUrifSK0ZUp9glYUEKw3yys1W+WnqhMZQENp85Oz8DiQANvSN9Xp8YOwFTudza6TPqoOKUuCJvKTmEL2/DlzwlUh5hNgWDKNmxuyelPoAaVHd/jxZztrt0mqU6pH5DiPVuSY1/STLUpg9Br7BakSYwxGPaY9amBEhN9Aozd8uEhfvJQXQpSaFddlD/5HEMdysqUNlxP5q7AJBbqU+qa87XkxukFrMnqU3Hjlqrq8pYK92WJ4pRvha4KrGXOyUN8CIW9jUXE4IxM/BvIMRQsoVa6qQibSqrlKXj0YP/DWXvpjxKv3S5NITJlBBr+vJVldaIKrDT1GyeMP7A47T17WkrmAAAAAElFTkSuQmCC', '742974a37c33e4c05843fff61ce05a42d1e71dbeddae84fe73d00d2c227c8582', '2025-11-13 05:23:49');
INSERT INTO `visitor_passes` VALUES ('2', '49', 'Monitor', 'QWE-222', 'Delivery', '2025-11-20 11:09:00', '2025-11-20 13:09:00', '1', 'active', NULL, NULL, NULL, NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASYAAAEmAQMAAAD1Cq+ZAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACsElEQVRoge2a0Y3EIAxELaUASqJ1SqKASD48M2a51TVgdGgV7YaXD4I9DGbN/tsdrflqr3Vr45ndrL/N181nrq4RfT7qU3F520DneFxf1s0FvglUp9ZY1y+f3TH6N/r7uhlU8/cias3wmvMHc7uu6wm+lXuomO0RYAS43oFfREVER7IO5i4/yODvuC9LQZna+PPzpV9VKbUY97oTEb2em8aItq9WlOKgV77OjqzdEb2n/QLKzSRCUl/kMb6vOz0jujJlsZjQLMQks03kMVZXzXlpCqlp+hWzHRM+wxNBrjxjojTl8AUx+mdq/QzfFy+GknwBFbH8xGybhu4R4wthBvu8gYJ7jbh+ZBni3SCJA0ynUJpyLCxuEGCOG0GNhMZSU57KTvo+LqGkaBZSvypTTfkKDabpC78QEjVzy1KewvaLmZo5iqyNq3xueQrZOS0LBnqCUXC4ocoUFJdO4d3fWRSB+urNFKfg1gdsEW5Ld9PVZm4Xplpuv0zrDH0QkcNPVKbQs0UXnaFJ1CptrMtTXEjD2x6FkJER7TdQzXdQbyna268Hy84F1KvyQOcSijJeRIH9cgqVKfbAqpv87KdagCqClada7kvgYSnDJnHKQLiAQl46SpLaqUwpFk1EeYruQIcDEqfUYM5/fapxuBHIWmSMguTcge2qT2VKnj3l1lm9e9IyvK0+ZZpeenZ4hM7jSpafFRPFKUMBDzUtVCVVlB0odLkcX22qs3xOceKBD/yR82m7gIIXYEQjtOX4ENSH4ytNaaRIVugTrV+6+A9SmNI2WvbW8uAOZXW9kvpUXJiarl0XCs/t95soTvmhQ5Yn6nL0fpydVKc42zwuOM6Zs+R8BcXKK3aZ/KeAttSfOmZtysz21oRF9G11j9kuTUGZzsoWnsiTWH8O/apK/bcb2g+A128Z72GaGwAAAABJRU5ErkJggg==', 'd8eec966661bf3b2858c5b56c43875d069556e9f08a9cb6b477419bde9241cd5', '2025-11-20 18:09:53');
INSERT INTO `visitor_passes` VALUES ('3', '20', 'Rider', 'ZXC - 222', 'Delivery', '2025-11-21 00:49:00', '2025-11-21 02:49:00', '1', 'active', NULL, NULL, NULL, NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASYAAAEmAQMAAAD1Cq+ZAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACrklEQVRoge1aiYnEMAwUuICU5NZTkgsI6KSZkbN7XAMyZ0LwOpMF6x3JMfsfZ4zLYzw2LS97rpsrw2Ny5ywm7VF5e8x8WTyJe1zxhq85Vq4T0B0Ve32weyykSOJnXInKyTEoySAmIY8Rb1gK5jBUTJ60aOfPmhyCgrOGbmfaNZw1HXdNWrqdgEJkuu4/r1/xqyuK4wOb+g8xwLTte3RFYbsPFLu3TouG8vPeHmVToYjBKSCRXkoGXGmPCn/F7ge1HRnmcoarVHik1mXtUbndsl9omxadrzLnWH9UWu5MBkSXxZzuO0iIFH07o5L73GRABnZAJRfJdYmrNaoiENIm6J7eM+bVV159UWIHcNw1i7YbpAL6cACqIhNTiin0zs+k2h6l+nIy4mL3GavIZwcJb3cUHjr0PJBCN2WgYJSHWqOo8EW+cKN6pu/Coa+X1XZGzZrO9FpYN7OoijD+U2sU/XLX04xSIAicl0V3RqkrADILncNZnTxXwak9aj4ljLE2U5ivVPwEFDKMy1+VTkkAc11xqTWKukX/w74sWvFYubY1CgGpuj5isozEXFSGaY26toM6syhDL2VQRVp/FDvolzrNn2SBlt4ehYU8K0gHVQAGOZqUiiy6N2qq0kLbgM1mJBwjDfQPu++KQkPLwAuGbFk2zoOgzdwbo2i/kIftPiUaWjtEtUdd2jqrTIVhNbdu9kXao2yTglu5lK6M2st3LdobNYsdZGliasTmE6+82h5V3slCpCqw7HWxU0JW2xulnfrbq7tYT5PuuS9rj9pfRtByL1Vj1e66j0DlQ1aWOlvmHCdCrySao1xfRqCtVQfpW0jKMCegNkmfdN/nV/PrCBR5OtXrdXA33kq6N8pMdTOVjG+Uqnu3T/B6oxCZ8jmikc5/4MdL9Vl71P84YfwA0JePyzeVNpAAAAAASUVORK5CYII=', '6431096cae60fde580eed199b6e6e4afbb6275f38cd526cff1ebbcfd61ba391f', '2025-11-21 07:50:09');
INSERT INTO `visitor_passes` VALUES ('4', '19', 'Test', 'ASD - 12312', 'Delivery', '2025-12-02 18:35:00', '2025-12-10 22:35:00', '0', 'approved', '1', '2025-12-03 10:41:06', NULL, NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPYAAAD2AQMAAADf3OiLAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAAB20lEQVRYhe2YUY4EIQhESTyAR/LqHqkPYMJSFDK7s/s/kKyZdLp9zkdFSkCR//HZMdXGmVvmHo/Yuz3tHbO6O3A8jsjA17Ll41lGzkX1Ob582qGtXZw5jXhIVPvEi61qxpdtDp6IsqWtOOILshZCbIY/3uKvMA9/v/1++b8qz+GaOH3kj1GUc/phcEEogissshtwaNowRzh78YiKcKvPTZP7wyQO5SkLifqkP6pzpSZ8URPPpx3xVZxjDkvuKuW2eIrbLbjNvVS6Y+xTMv7KcxxJ1xN+1iodjxDrwDOz2Z54iYrk4Bm7A8fOwM1RXOMHr0tUrOV5ivMU4XuClzRKee4pDplNc4SzZw/OasKLa2VmHj+Ko+qc5gZnrnat4gsb8BnNGVd5lr5ej/64Otdo6y0/RIvDdkFDX3Xuu7FOBNfKW4rRgjO+vMtEc+ZCWWtn/VqcjyfM4ZqiUWbR2oC/5zfYwq+ItAcX1qQg3BZWrJk8qnOHJ+8X71nr4bYb8FCxWdyx1hYu1w487ld4M+dHbFyxaJyv1TlE8GTNPfEUt7vwuB+9QtkfYLTiElfU+/b3+i3yyvPJnkwYVrzx1VsfFeceX+EM5rehL/OX59ffsqI59kIv/1qd/49Pji/J6FKlrxRvwQAAAABJRU5ErkJggg==', '63bbf4b0014388841666b91464a64088', '2025-12-02 18:32:45');
INSERT INTO `visitor_passes` VALUES ('5', '19', 'awdaw', '12312', 'Deliverya', '2025-12-02 18:40:00', '2025-12-09 22:40:00', '0', 'approved', '1', '2025-12-03 10:36:50', NULL, NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQ4AAAEOAQMAAABCf2vmAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACJUlEQVRoge2Z0W3EMAxDCWSAjOTVM5IHMKBapOSmuAWOQIUgNZLXDzUyRbnAf3x/3LFj3c81cXGxnyDvT76IxxDJ29rgHDGxMLTez1e/tUN2ZjtXPeabkdnO/L3liwDXTroWqLs1wmLchXnFk5dwWyQLk/WYuW5qjpM6LJESj8/rQ19MkA5+yZ332OnuiImPMEFYjypDpjhwS++HatYU4f1h0uD2KrBr1w2ZWNV1dQXUuCj8hkhQJKKDX3WmeOhL+iHcWztFuSR1LS0E+iFZgBT1WqDbFxtyKaYVguq3NLPMmPuMTnCUKJohoJXIRGUAK/VSEUdkqN9yY4UWS5b2t3bNECrEaOWQm9iKCN39kPx0UD2G5kIawKCfPSOUE4IScpXnEULWKbXfEEFZJLXiTJf7jHapzawXon6lMjxrteIOK4QNSmNu5y1/NGyRZ7X7Y8sax1BI9R0RWVfcpfEa39WKPZHSPzS+M9YsdfqwGcKWtWTPszbTH2XvZfZwRHoEpDEfasIURZ24mCIox1eWPGocfNeuE6LCzJf6jD1RPZqAHREeSWYce1677a6uZYccRZTd6wOw3nOGSKarTlXnEENTr/4MfojiNbVrk+k8DI5IJRct83UM1nYJhkj+1Fx7lL7l8HkJvBOiB3VohOrD3cRgjcSZDmeJB3wRHbHwqFXqnhJCV+uIZGFmbvTjr9Oj9cd5GCEl5G3SR9S/NGtY9EP+49vjBzoJ3GAGHUoMAAAAAElFTkSuQmCC', '305e61ddf4acc078e308829f4e4ae462', '2025-12-02 18:39:42');
INSERT INTO `visitor_passes` VALUES ('6', '19', 'test2', 'QWE-123', 'Delivery', '2025-12-03 12:25:00', '2025-12-04 16:25:00', '0', 'approved', '1', '2025-12-03 12:23:05', NULL, NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQ4AAAEOAQMAAABCf2vmAAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAACQ0lEQVRoge2Z66nDMAyFBR4gI3l1j5QBArrSOZLd9i7QAzUhpPbXH4reitlvff+6PNZz+WNz3Dbyl4972rXwuASRvMV5/IwHv2c844rNOpVDQrL4BfnyxG+Ld5D/izehi0CZodKR90U1iiMpcRhmUFYSCyPU2PCVEkOTANeH7eogDB7r//UZX1QQLggd2+MY6TkUQ+IR8iWVhpnIjvRb01KIw8kmw6G3twWSScwVEeotHStlzWsiwE/DjiiyxR2oKZi44uFFaB0EmkShZF5VUhYXmYrfXFEHgZ8NRj442YPojmBv/V7EEEMlC0tsn0NOzhhfhqmHUHXUZFZJZHNTEEldOeVjDWgobGGYs01TDOlAPk8qviuVPZWKpRB4Fb3tYS+VCqQapybikHIyX9HhYKrsehWRMkkGdazJzYHMLIhw8MBekFlrOSuL3UKJIavkRjg0q9yb1RMdTg/xdCx3bzNkQeE1MZJEOrpnLNyV4K5t9ZAKFeVhrGq94n0JrYYYyr0+RFDf5dLoskIJwRClWsByuDyHqZ6eRAlBtODYlRm42kR4Xr8XKYTWd1V9hDRVeZjBXg8xduqrmtqe+jO0H9tVQ6ymERR3VUFx6l0pBFKmb/VHGL4DKnHnACWEC0X6y8lkuWEmiPQgeQ8huM0vAK1GLSRvT7W27N1xUuNYSYT9OhMv83ClrLzLItBnfTHz7jy6YNdE0K8jD/PzBT83vQmtgxjnK9BejfHwPz+NuxbC4IE5ZYeQPEf/UVM/LeS3vn39AfBgRpyah0B2AAAAAElFTkSuQmCC', 'fcd8ecd77fdad9d2bbb5632aab878bd4', '2025-12-03 12:22:56');

