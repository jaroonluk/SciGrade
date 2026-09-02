/*
 Navicat MySQL Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 100432 (10.4.32-MariaDB)
 Source Host           : localhost:3306
 Source Schema         : eoffice

 Target Server Type    : MySQL
 Target Server Version : 100432 (10.4.32-MariaDB)
 File Encoding         : 65001

 Date: 02/09/2026 15:47:45
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for grade_type
-- ----------------------------
DROP TABLE IF EXISTS `grade_type`;
CREATE TABLE `grade_type`  (
  `id` int NOT NULL AUTO_INCREMENT,
  `nameng` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'ชื่อย่อ',
  `namethai` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL COMMENT 'ช่อเต็ม',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 85 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of grade_type
-- ----------------------------
INSERT INTO `grade_type` VALUES (3, 'SC', 'วิทยาศาสตร์');
INSERT INTO `grade_type` VALUES (6, 'AG', 'เกษตรศาสตร์');
INSERT INTO `grade_type` VALUES (9, 'EN', 'วิศวกรรมศาสตร์');
INSERT INTO `grade_type` VALUES (12, 'ED', 'ศึกษาศาสตร์');
INSERT INTO `grade_type` VALUES (15, 'NU', 'พยาบาลศาสตร์');
INSERT INTO `grade_type` VALUES (18, 'MD', 'แพทยศาสตร์');
INSERT INTO `grade_type` VALUES (21, 'HS', 'มนุษยศาสตร์');
INSERT INTO `grade_type` VALUES (24, 'AM', 'เทคนิคการแพทย์');
INSERT INTO `grade_type` VALUES (27, 'GS', 'บัณฑิตวิทยาลัย');
INSERT INTO `grade_type` VALUES (30, 'PH', 'สาธารณสุขศาสตร์');
INSERT INTO `grade_type` VALUES (33, 'DT', 'ทันตแพทย์ศาสตร์');
INSERT INTO `grade_type` VALUES (36, 'PS', 'เภสัชศาสตร์');
INSERT INTO `grade_type` VALUES (39, 'TE', 'เทคโนโลยี');
INSERT INTO `grade_type` VALUES (42, 'VM', 'สัตวแพทยศาสตร์');
INSERT INTO `grade_type` VALUES (45, 'AR', 'สถาปัตยกรรม');
INSERT INTO `grade_type` VALUES (48, 'KKBS', 'บริหารธุรกิจและบัญชี');
INSERT INTO `grade_type` VALUES (51, 'FA', 'ศิลปกรรมศาสตร์');
INSERT INTO `grade_type` VALUES (54, 'LAW', 'นิติศาสตร์');
INSERT INTO `grade_type` VALUES (57, 'COLA', 'วิทยาลัยการปกครองท้องถิ่น');
INSERT INTO `grade_type` VALUES (60, 'IC', 'วิทยาลัยนานาชาติ');
INSERT INTO `grade_type` VALUES (63, 'ECON', 'เศรษฐศาสตร์');
INSERT INTO `grade_type` VALUES (66, 'CP', 'วิทยาลัยการคอมพิวเตอร์');
INSERT INTO `grade_type` VALUES (72, 'IN', 'สหวิทยาการ');
INSERT INTO `grade_type` VALUES (75, 'GE', 'สำนักวิชาศึกษาทั่วไป');
INSERT INTO `grade_type` VALUES (78, 'KKULI', 'สถาบันภาษา');
INSERT INTO `grade_type` VALUES (81, 'MBA', 'วิทยาลัยบัณฑิตศึกษาการจัดการ');
INSERT INTO `grade_type` VALUES (84, 'COPA', 'วิทยาลัยกิจการและนโยบายสาธารณะ');

SET FOREIGN_KEY_CHECKS = 1;
