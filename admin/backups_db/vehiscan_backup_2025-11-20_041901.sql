-- VehiScan Database Backup
-- Date: 2025-11-20 04:19:01

DROP TABLE IF EXISTS `audit_logs`;
;

INSERT INTO `audit_logs` VALUES ('1', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: CAMERA', '::1', '2025-11-08 04:50:24');
INSERT INTO `audit_logs` VALUES ('2', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: BBO-111', '::1', '2025-11-08 04:51:08');
INSERT INTO `audit_logs` VALUES ('3', NULL, 'ydnAr', 'RFID_SCAN_SIMULATED', 'rfid_simulator', NULL, 'Simulated scan for plate: BBO-111', '::1', '2025-11-08 17:52:23');
INSERT INTO `audit_logs` VALUES ('4', NULL, 'ydnAr', 'DELETE', 'recent_logs', '56', 'Deleted access log', '::1', '2025-11-13 19:54:35');
INSERT INTO `audit_logs` VALUES ('5', NULL, 'admin', 'DELETE', 'recent_logs', '66', 'Deleted access log', '::1', '2025-11-15 05:53:58');
INSERT INTO `audit_logs` VALUES ('6', NULL, 'Bato', 'DELETE_ALL', 'recent_logs', NULL, 'Cleared all logs (55 records)', '::1', '2025-11-16 06:24:14');
INSERT INTO `audit_logs` VALUES ('7', NULL, 'Bato', 'DELETE_ALL', 'recent_logs', NULL, 'Cleared all logs (7 records)', '::1', '2025-11-16 18:00:25');

DROP TABLE IF EXISTS `homeowners`;
;

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

DROP TABLE IF EXISTS `rate_limits`;
;

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

DROP TABLE IF EXISTS `recent_logs`;
;

INSERT INTO `recent_logs` VALUES ('86', 'CAMERA', 'IN', '15:04:35', '2025-11-17 15:04:35');
INSERT INTO `recent_logs` VALUES ('87', 'wds-111', 'IN', '15:04:39', '2025-11-17 15:04:39');
INSERT INTO `recent_logs` VALUES ('88', 'CAMERA', 'OUT', '15:04:44', '2025-11-17 15:04:44');
INSERT INTO `recent_logs` VALUES ('89', 'wds-111', 'OUT', '15:04:49', '2025-11-17 15:04:49');

DROP TABLE IF EXISTS `rfid_simulator`;
;

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

DROP TABLE IF EXISTS `users`;
;

INSERT INTO `users` VALUES ('8', 'admin', '$2y$10$1Wo10UnXn2YktvZUXa/zie5gyjtnZ97cJ0B8TlgZSmCTapRUISjz.', 'admin', '2025-08-10 10:58:49');
INSERT INTO `users` VALUES ('11', 'jojo', '$2y$10$WRQJtkRS2r0NgguwjxqgoOiPJjoyjlF54vWm8pLr9t3lof//N4iDm', 'guard', '2025-08-10 11:05:25');
INSERT INTO `users` VALUES ('14', 'jaja', '$2y$10$13IkLGDRVc.VApU8NIMFh.4WLYf/1T276iKJ0.71h2gv7EmS2SQIC', 'guard', '2025-08-10 11:34:42');
INSERT INTO `users` VALUES ('15', 'ydnAr', '$2y$10$b80Wy/bGXNs50rl.OwUE8uX.ap7Pm9P3AyG7UMT5n.ICPJKDwEgiG', 'admin', '2025-10-31 15:08:52');
INSERT INTO `users` VALUES ('16', 'Bato', '$2y$10$e2MK08iPh3yjl7CvFPYp3et1NvD2uaeCRxzQaB8bGSFovzUBcNtXK', 'guard', '2025-11-01 14:02:23');
INSERT INTO `users` VALUES ('17', 'Duterte', '$2y$10$Hl15uCFltazaxegRcITsF.u0D3Ulcyc0xgigMblTcAoMiNAu/fBPy', 'owner', '2025-11-01 14:02:32');

DROP TABLE IF EXISTS `visitor_passes`;
;

INSERT INTO `visitor_passes` VALUES ('1', '19', 'Jha', 'KUSH-420', 'Service Provider', '2025-11-12 22:23:00', '2025-11-13 00:23:00', '0', 'active', '2025-11-13 05:23:49');

