/*
SQLyog Ultimate v13.1.1 (32 bit)
MySQL - 8.4.7 : Database - ecommerce
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`ecommerce` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `ecommerce`;

/*Table structure for table `cart` */

DROP TABLE IF EXISTS `cart`;

CREATE TABLE `cart` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cart` */

LOCK TABLES `cart` WRITE;

UNLOCK TABLES;

/*Table structure for table `cart_items` */

DROP TABLE IF EXISTS `cart_items`;

CREATE TABLE `cart_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cart_items` */

LOCK TABLES `cart_items` WRITE;

UNLOCK TABLES;

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `additional_information` text COLLATE utf8mb4_unicode_ci,
  `shipping_returns` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `categories` */

LOCK TABLES `categories` WRITE;

insert  into `categories`(`id`,`name`,`slug`,`image`,`description`,`additional_information`,`shipping_returns`,`status`,`created_at`) values 
(1,'Women','women','women category.jpg','Discover our collection of women products designed with style, comfort and quality in mind.','This category includes products selected for everyday use and different occasions. Product details, prices and availability are based on the current stock.','Orders are processed after confirmation. Please contact us for information about returns, exchanges and shipping-related queries.',1,'2026-09-18 15:37:37'),
(2,'Men','men','men category.jpg','Explore our collection of men products offering a combination of style, comfort and quality for everyday use.','This category includes products selected for different needs and occasions. Product details, prices and availability are based on the current stock.','Orders are processed after confirmation. Please contact us for information about returns, exchanges and shipping-related queries.',1,'2026-09-18 15:37:37'),
(3,'Accessories','accessories','accessories category.jpg','Browse our accessories collection featuring products that complement different styles and everyday needs.','This category includes a variety of accessories selected for practical use and style.','Orders are processed after confirmation. Please contact us for information about returns, exchanges and shipping-related queries.',1,'2026-09-18 15:37:37');

UNLOCK TABLES;

/*Table structure for table `customer_details` */

DROP TABLE IF EXISTS `customer_details`;

CREATE TABLE `customer_details` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `customer_details` */

LOCK TABLES `customer_details` WRITE;

insert  into `customer_details`(`id`,`user_id`,`first_name`,`last_name`,`company_name`,`country`,`address`,`address_2`,`city`,`state`,`postcode`,`phone`,`email`,`created_at`,`updated_at`) values 
(1,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','', 'Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','2026-09-23 16:27:25','2026-09-23 16:33:56'),
(2,3,'Demo','Customer Two','Demo Company','Pakistan','Demo Address 2','', 'Lahore','Punjab','0002','0000000001','customer2@clothwear-demo.test','2026-09-23 16:41:37','2026-09-23 16:41:37'),
(3,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','', 'Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','2026-09-25 10:21:30','2026-09-25 10:21:30');

UNLOCK TABLES;

/*Table structure for table `deals` */

DROP TABLE IF EXISTS `deals`;

CREATE TABLE `deals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_deals_product` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `deals` */

LOCK TABLES `deals` WRITE;

insert  into `deals`(`id`,`product_id`,`discount_percentage`,`start_date`,`end_date`,`status`,`created_at`) values 
(1,7,20.00,'2026-09-22 11:23:40','2026-09-22 21:23:40',1,'2026-09-22 11:23:40'),
(2,8,15.00,'2026-09-22 11:23:40','2026-10-03 11:23:40',1,'2026-09-22 11:23:40');

UNLOCK TABLES;

/*Table structure for table `order_items` */

DROP TABLE IF EXISTS `order_items`;

CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `order_items` */

LOCK TABLES `order_items` WRITE;

insert  into `order_items`(`id`,`order_id`,`product_id`,`quantity`,`unit_price`,`subtotal`) values 
(44,33,28,1,8000.00,8000.00),
(45,34,13,1,1000.00,1000.00),
(46,34,30,1,9999.00,9999.00),
(47,35,23,2,9000.00,18000.00),
(48,36,29,1,1100.00,1100.00),
(49,37,27,1,1100.00,1100.00),
(50,38,14,1,7000.00,7000.00),
(51,39,16,1,7000.00,7000.00),
(52,40,20,1,6500.00,6500.00),
(53,41,19,1,7800.00,7800.00),
(54,42,22,1,2500.00,2500.00),
(55,43,15,1,7500.00,7500.00),
(56,44,18,1,7000.00,7000.00),
(62,50,21,1,8000.00,8000.00),
(63,51,11,1,1850.00,1850.00),
(64,52,25,1,2500.00,2500.00),
(65,53,19,2,7800.00,15600.00),
(66,54,27,1,1100.00,1100.00),
(67,55,8,1,1500.00,1500.00),
(68,56,10,1,1800.00,1800.00),
(69,56,19,1,7800.00,7800.00),
(70,56,26,1,9000.00,9000.00),
(71,56,29,1,1100.00,1100.00),
(72,56,31,1,12000.00,12000.00),
(73,57,30,1,9999.00,9999.00),
(74,58,27,1,1100.00,1100.00),
(75,59,20,1,6500.00,6500.00),
(76,60,16,1,7000.00,7000.00),
(77,61,11,1,1850.00,1850.00),
(78,62,13,1,1000.00,1000.00),
(79,63,25,1,2500.00,2500.00),
(80,63,27,1,1100.00,1100.00),
(81,64,27,1,1100.00,1100.00),
(82,64,28,1,8000.00,8000.00),
(83,65,21,1,8000.00,8000.00),
(84,66,19,1,7800.00,7800.00),
(85,67,26,1,9000.00,9000.00),
(86,68,22,1,2500.00,2500.00);

UNLOCK TABLES;

/*Table structure for table `orders` */

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postcode` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_notes` text COLLATE utf8mb4_unicode_ci,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','paypal','stripe') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` enum('pending','paid','failed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `order_status` enum('processing','shipped','delivered','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'processing',
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `orders` */

LOCK TABLES `orders` WRITE;

insert  into `orders`(`id`,`user_id`,`first_name`,`last_name`,`company_name`,`country`,`address`,`address_2`,`city`,`state`,`postcode`,`phone`,`email`,`order_notes`,`order_number`,`total_amount`,`payment_method`,`payment_status`,`order_status`,`transaction_id`,`created_at`) values 
(33,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00033',8000.00,'cod','pending','processing',NULL,'2026-09-25 15:30:47'),
(34,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00034',10999.00,'cod','pending','processing',NULL,'2026-09-25 15:31:27'),
(35,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00035',18000.00,'cod','pending','processing',NULL,'2026-09-25 15:33:52'),
(36,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00036',1100.00,'cod','pending','processing',NULL,'2026-09-25 15:39:49'),
(37,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00037',1100.00,'cod','pending','processing',NULL,'2026-09-29 10:07:34'),
(38,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00038',7000.00,'cod','pending','processing',NULL,'2026-09-29 10:26:06'),
(39,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00039',7010.00,'cod','paid','delivered',NULL,'2026-09-29 10:34:07'),
(40,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00040',6520.00,'cod','pending','shipped',NULL,'2026-09-29 10:47:38'),
(41,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00041',7820.00,'cod','pending','processing',NULL,'2026-09-29 10:52:02'),
(42,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00042',2510.00,'cod','pending','processing',NULL,'2026-09-29 10:52:31'),
(43,3,'Demo','Customer Two','Demo Company','Pakistan','Demo Address 2','','Lahore','Punjab','0002','0000000001','customer2@clothwear-demo.test','','DEMO-ORD-00043',7510.00,'','cancelled','cancelled',NULL,'2026-09-29 12:14:55'),
(44,3,'Demo','Customer Two','Demo Company','Pakistan','Demo Address 2','','Lahore','Punjab','0002','0000000001','customer2@clothwear-demo.test','','DEMO-ORD-00044',7020.00,'','cancelled','cancelled',NULL,'2026-09-29 12:23:02'),
(50,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00050',8000.00,'stripe','paid','processing',NULL,'2026-09-29 12:37:50'),
(51,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00051',1860.00,'stripe','paid','processing',NULL,'2026-09-29 12:46:08'),
(52,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00052',2510.00,'cod','paid','delivered',NULL,'2026-09-29 15:54:03'),
(53,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00053',15610.00,'stripe','paid','processing',NULL,'2026-09-29 16:02:12'),
(54,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00054',1100.00,'cod','pending','processing',NULL,'2026-09-30 11:48:40'),
(55,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00055',1500.00,'cod','pending','processing',NULL,'2026-09-30 15:48:02'),
(56,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00056',31710.00,'cod','pending','processing',NULL,'2026-09-30 16:10:11'),
(57,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00057',10009.00,'cod','pending','processing',NULL,'2026-09-30 16:12:05'),
(58,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00058',1110.00,'cod','pending','processing',NULL,'2026-09-30 16:33:58'),
(59,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00059',6510.00,'cod','cancelled','cancelled',NULL,'2026-09-30 16:34:23'),
(60,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00060',7000.00,'cod','paid','delivered',NULL,'2026-09-30 16:36:27'),
(61,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00061',1850.00,'cod','paid','delivered',NULL,'2026-09-30 16:49:51'),
(62,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00062',1000.00,'cod','paid','delivered',NULL,'2026-10-01 15:24:15'),
(63,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00063',3600.00,'cod','paid','delivered',NULL,'2026-10-02 09:15:41'),
(64,3,'Demo','Customer Two','Demo Company','Pakistan','Demo Address 2','','Lahore','Punjab','0002','0000000001','customer2@clothwear-demo.test','','DEMO-ORD-00064',9100.00,'stripe','cancelled','cancelled',NULL,'2026-10-02 15:01:14'),
(65,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00065',8000.00,'stripe','cancelled','cancelled',NULL,'2026-10-02 15:54:54'),
(66,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00066',7800.00,'stripe','cancelled','cancelled',NULL,'2026-10-02 16:02:55'),
(67,2,'Demo','Customer One','Demo Company','Pakistan','Demo Address 1','','Karachi','Sindh','0001','0000000000','customer1@clothwear-demo.test','','DEMO-ORD-00067',9000.00,'stripe','cancelled','cancelled',NULL,'2026-10-02 16:20:42'),
(68,4,'Demo','Customer Three','Demo Company','Pakistan','Demo Address 3','','Islamabad','Islamabad Capital Territory','0003','0000000002','customer3@clothwear-demo.test','','DEMO-ORD-00068',2510.00,'cod','pending','processing',NULL,'2026-10-05 15:37:45');

UNLOCK TABLES;

/*Table structure for table `products` */

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(220) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL,
  `stock` int unsigned DEFAULT '0',
  `image` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sold` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `products` */

LOCK TABLES `products` WRITE;

insert  into `products`(`id`,`category_id`,`name`,`slug`,`description`,`price`,`stock`,`image`,`status`,`created_at`,`updated_at`,`sold`) values 
(8,3,'Elegant Charm Bracelet','elegant-charm-bracelet','Beautiful charm bracelet designed to add a stylish and elegant touch to any outfit.',1500.00,0,'product_6aad164a8ea200.64070502.png',1,'2026-09-18 15:45:30','2026-09-30 15:48:02',0),
(10,3,'Classic Chain Bracelet','classic-chain-bracelet','A simple and elegant chain bracelet designed for everyday wear. Its polished finish adds a refined touch to both casual and formal outfits.',1800.00,24,'product_6ab6238e062442.07880926.jpg',1,'2026-09-25 12:32:30','2026-09-30 16:10:11',0),
(11,3,'Pearl Charm Bracelet','pearl-charm-bracelet','A delicate bracelet featuring pearl-inspired charms and a graceful design. Perfect for adding a subtle and elegant accent to your look.',1850.00,23,'product_6ab623cd88ac58.05034342.jpg',1,'2026-09-25 12:33:33','2026-09-30 16:49:51',0),
(13,3,'Classic Gold Band Ring','classic-gold-band-ring','A timeless gold-tone band with a smooth polished finish. Its simple design makes it suitable for everyday styling.',1000.00,23,'product_6ab6297e898712.61879295.jpg',1,'2026-09-25 12:57:50','2026-10-01 15:24:15',0),
(14,2,'Classic Beige Blazer Set','classic-beige-blazer-set','A sophisticated combination featuring a tailored beige blazer, a crisp white shirt, and cream trousers. Perfect for formal and semi-formal occasions, giving a sharp and polished look.',7000.00,29,'product_6ab62b07d7d851.68372702.jpg',1,'2026-09-25 13:04:23','2026-09-29 10:26:06',0),
(15,2,'Brown Suede Casual Jacket','brown-suede-casual-jacket','A stylish brown suede jacket paired with a black turtleneck and matching black pants. It offers a modern, edgy, and comfortable look for cooler days.',7500.00,19,'product_6ab63eeb9e6823.90680679.jpg',1,'2026-09-25 14:29:15','2026-09-29 12:14:55',0),
(16,1,'Olive Green Utility Jumpsuit','olive-green-utility-jumpsuit','A stylish olive green jumpsuit designed with a front zipper and an adjustable drawstring waist. It blends effortless style with everyday comfort.',7000.00,18,'product_6ab63f2396d030.25131746.jpg',1,'2026-09-25 14:30:11','2026-09-30 16:36:27',0),
(18,1,'Grey Cropped Sweater Dress Combo','grey-cropped-sweater-dress-combo','A chic combination of a grey cropped sweater and a sleek black long dress. A stylish and warm choice designed to create a modern layered look.',7000.00,19,'product_6ab63f87701a05.19031575.jpg',1,'2026-09-25 14:31:51','2026-09-29 12:23:02',0),
(19,2,'Olive Striped Polo Sweatshirt','olive-striped-polo-sweatshirt','A comfortable olive and cream striped polo sweatshirt paired with casual trousers. A relaxed and trendy choice for everyday casual wear.',7800.00,20,'product_6ab64079b33381.55219673.jpg',1,'2026-09-25 14:35:53','2026-10-02 16:02:55',0),
(20,1,'Maroon Lace Crop Top Set','maroon-lace-crop-top-set','An intricate maroon lace crop top paired with a light-colored pleated skirt. Perfect for special occasions and adding a delicate, feminine touch to your wardrobe.',6500.00,23,'product_6ab640a81e6588.35035116.jpg',1,'2026-09-25 14:36:40','2026-09-30 16:34:23',0),
(21,2,'Brown Colorblock Raglan T-Shirt','brown-colorblock-raglan-t-shirt','A casual brown and beige raglan-sleeve t-shirt featuring a subtle text print on the chest. Ideal for casual outings and streetwear styling.',8000.00,23,'product_6ab640e6549ca2.68588269.jpg',1,'2026-09-25 14:37:42','2026-10-02 15:54:54',0),
(22,3,'Star & Crystal Charm Gold Necklace','star-crystal-charm-gold-necklace','A delicate gold-tone chain necklace embellished with tiny star trinkets and sparkling crystals. It adds a graceful, charming accent to any neckline',2500.00,23,'product_6ab6411299f0a5.71714285.jpg',1,'2026-09-25 14:38:26','2026-10-05 15:37:45',0),
(23,2,'Black Utility Cargo Jacket','black-utility-cargo-jacket','A modern black jacket equipped with utility pockets and paired with matching cargo pants. A bold, practical choice for urban and outdoor styling.',9000.00,30,'product_6ab641968878f5.76034429.jpg',1,'2026-09-25 14:40:38','2026-09-25 15:33:52',0),
(25,3,'Gold Minimalist Geometric Charm Necklace','gold-minimalist-geometric-charm-necklace','A delicate gold chain with small hanging metal bars and clear crystal charms. It has a simple, clean design that looks great for everyday wear.',2500.00,18,'product_6ab644549335c8.34037806.jpg',1,'2026-09-25 14:52:20','2026-10-02 09:15:41',0),
(26,1,'Classic Navy Blue Suit Set','classic-navy-blue-suit-set','A sophisticated navy blue blazer paired with matching high-waisted wide-leg trousers. It features a simple white square-neck top underneath for a clean contrast.',9000.00,23,'product_6ab64554d23907.22014038.jpg',1,'2026-09-25 14:56:36','2026-10-02 16:20:42',0),
(27,3,'Crystal Olive Branch Ring Combo','crystal-olive-branch-ring-combo','A beautiful pair of gold-plated rings featuring delicate, nature-inspired leaf and vine motifs. One ring has an adjustable open band tipped with sparkling marquise-cut crystal leaves.',1100.00,20,'product_6ab6459fe4c377.63319979.jpg',1,'2026-09-25 14:57:51','2026-10-02 15:01:14',0),
(28,1,'Cropped Olive Trench & Black Pants Set','cropped-olive-trench-black-pants-set','An edgy ensemble featuring an olive green cropped trench jacket worn over a snug black turtleneck. It is paired with high-waisted black wide-leg trousers cinched with a matching black belt.',8000.00,23,'product_6ab645f5d01319.75488740.jpg',1,'2026-09-25 14:59:17','2026-10-02 15:01:14',0),
(29,3,'Open Band Ring','open-band-ring','A minimalist  ring shaped like a twin leaf vine with a sleek, split-front structure. The open layout creates a stylish, airy gap that catches the eye while keeping the design clean and modern.',1100.00,30,'product_6ab6465547bd65.81190302.jpg',1,'2026-09-25 15:00:53','2026-09-30 16:10:11',0),
(30,1,'Cropped Suede Jacket & Wide-Leg Denim Set','cropped-suede-jacket-wide-leg-denim-set','A stylish fall outfit featuring a cropped dark brown suede jacket over a fitted white turtleneck top. It is paired with high-waisted, deep blue wide-leg jeans cinched by a dark belt with a gold buckle.',9999.00,28,'product_6ab646f4a524f0.20771083.jpg',1,'2026-09-25 15:03:32','2026-09-30 16:12:05',0),
(31,2,'Charcoal Slim-Fit Suit Set','charcoal-slim-fit-suit-set','A tailored charcoal gray blazer paired with matching slim-fit dress pants and a solid black crewneck top underneath. It is accessorized with a black leather watch and stacked leather wristbands. This sleek and modern look is perfect for semi-formal events or evening galas.',12000.00,32,'product_6ab6483cbeeeb6.97143142.jpg',1,'2026-09-25 15:09:00','2026-09-30 16:10:11',0),
(32,1,'test 1','test-1','multilple iamge dress test',5000.00,10,'[\"32/image2.jpg\",\"32/image3.jpg\",\"32/image1.jpg\"]',1,'2026-10-02 09:05:13','2026-10-02 12:01:25',0);

UNLOCK TABLES;

/*Table structure for table `sliders` */

DROP TABLE IF EXISTS `sliders`;

CREATE TABLE `sliders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `button_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Shop Now',
  `button_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'products.php',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sliders` */

LOCK TABLES `sliders` WRITE;

insert  into `sliders`(`id`,`title`,`subtitle`,`description`,`button_text`,`button_link`,`image`,`sort_order`,`status`,`created_at`,`updated_at`) values 
(1,'New Fashion Collection','Style Made for You','Discover our latest fashion collection.','Shop Now','products.php','fashion banner.png',1,1,'2026-09-23 11:54:13','2026-09-23 11:54:13'),
(2,'Perfect Style for Two','Together in Style','Explore stylish looks for every special moment.','Shop Now','products.php','banner.png',2,1,'2026-09-23 11:54:13','2026-09-23 11:54:13'),
(3,'Complete Your Look','Elegant Accessories','Discover beautiful accessories to complete your style.','Shop Now','products.php','accessories banner.png',3,1,'2026-09-23 11:54:13','2026-09-23 11:54:13');

UNLOCK TABLES;

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','customer') COLLATE utf8mb4_unicode_ci DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

LOCK TABLES `users` WRITE;

insert  into `users`(`id`,`name`,`email`,`password`,`role`,`is_active`,`created_at`,`updated_at`) values 
(1,'Administrator','admin@clothwear-demo.test','$2y$12$Lc4VW3mgXhxXuCF7ABlLzemizjJdNx2TkNdM4xotlk55dgJN/CkA6','admin',1,'2026-09-15 11:12:34','2026-09-15 11:12:34'),
(2,'Demo Customer One','customer1@clothwear-demo.test','$2y$12$6AZ9eaOl0i0uW4Yy8NGcg.HuF8ORDgpKs/iuatvA4D.O8fEK3jqa2','customer',1,'2026-09-17 16:24:40','2026-09-17 16:24:40'),
(3,'Demo Customer Two','customer2@clothwear-demo.test','$2y$12$6AZ9eaOl0i0uW4Yy8NGcg.HuF8ORDgpKs/iuatvA4D.O8fEK3jqa2','customer',1,'2026-09-17 16:39:22','2026-09-17 16:39:22'),
(4,'Demo Customer Three','customer3@clothwear-demo.test','$2y$12$6AZ9eaOl0i0uW4Yy8NGcg.HuF8ORDgpKs/iuatvA4D.O8fEK3jqa2','customer',1,'2026-09-29 15:28:54','2026-09-29 15:28:54');

UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
