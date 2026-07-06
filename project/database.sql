-- SQL Schema for Sethu Jewellery Project
-- To install: Open phpMyAdmin (http://localhost/phpmyadmin/), create database `sethu_jewellery`, and import this file.

CREATE DATABASE IF NOT EXISTS `sethu_jewellery` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sethu_jewellery`;

-- 1. Admin Table
CREATE TABLE IF NOT EXISTS `admin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(80) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Rates Table (Singleton row where id must always be 1)
CREATE TABLE IF NOT EXISTS `rates` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `gold22` DECIMAL(10, 2) NOT NULL,
    `gold24` DECIMAL(10, 2) NOT NULL,
    `gold18` DECIMAL(10, 2) NOT NULL,
    `silver` DECIMAL(10, 2) NOT NULL,
    `platinum` DECIMAL(10, 2) NOT NULL,
    `fix_gold22` TINYINT(1) DEFAULT 0,
    `fix_gold24` TINYINT(1) DEFAULT 0,
    `fix_gold18` TINYINT(1) DEFAULT 0,
    `fix_silver` TINYINT(1) DEFAULT 0,
    `fix_platinum` TINYINT(1) DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `chk_rates_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB;

-- 3. Settings Table (Singleton row where id must always be 1)
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `site_name` VARCHAR(160) NOT NULL,
    `rate_label` VARCHAR(160) NOT NULL,
    `phone` VARCHAR(40) NOT NULL,
    `whatsapp` VARCHAR(40) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `showroom` VARCHAR(160) NOT NULL,
    `address` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `chk_settings_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB;

-- 4. Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ref_code` VARCHAR(120) NOT NULL UNIQUE,
    `name` VARCHAR(160) NOT NULL,
    `category` VARCHAR(80) NOT NULL,
    `subcategory` VARCHAR(80) NOT NULL,
    `sub_subcategory` VARCHAR(80) NOT NULL DEFAULT '',
    `metal_type` VARCHAR(40) NOT NULL,
    `base_weight` DECIMAL(10, 3) NOT NULL,
    `wastage_percent` DECIMAL(5, 2) DEFAULT 0.00,
    `purity` VARCHAR(120) NOT NULL,
    `style` VARCHAR(120) NOT NULL,
    `image` VARCHAR(255) NOT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `description` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Seed default rates and settings values
INSERT INTO `rates` (`id`, `gold22`, `gold24`, `gold18`, `silver`, `platinum`, `fix_gold22`, `fix_gold24`, `fix_gold18`, `fix_silver`, `fix_platinum`)
VALUES (1, 13195.00, 14395.00, 11342.00, 215.49, 5050.00, 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE `id` = 1;

INSERT INTO `settings` (`id`, `site_name`, `rate_label`, `phone`, `whatsapp`, `email`, `showroom`, `address`)
VALUES (1, 'Sethu Thanga Nagai Maaligai', 'Today\'s Jewelry Rates (Karaikudi)', '9600877706', '9600877706', 'support@sethuthangamaligai.com', 'Karaikudi Showroom', 'Old Shop No: 3, New Shop No: 11, Kallukatti, Karaikudi, Tamil Nadu - 627811, India')
ON DUPLICATE KEY UPDATE `id` = 1;

-- 5. Blogs Table
CREATE TABLE IF NOT EXISTS `blogs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(120) NOT NULL UNIQUE,
    `title` VARCHAR(160) NOT NULL,
    `author` VARCHAR(80) NOT NULL DEFAULT 'Admin',
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `blogs` (`slug`, `title`, `author`, `content`, `image`, `active`) 
VALUES ('gold-savings-guide', '5 Essential Tips for Gold Jewellery Savings', 'Sethu Team', 'Gold has always been a symbol of wealth, security, and beauty. For generations, purchasing gold jewellery has been one of the most trusted ways to save. In this guide, we break down five essential tips for maximizing your gold investments, understanding purity standards, and utilizing jewellery savings schemes like our Thangamagal Scheme.', 'assets/gold_necklace.png', 1)
ON DUPLICATE KEY UPDATE `slug` = VALUES(`slug`);

-- 6. Scheme Registrations Table
CREATE TABLE IF NOT EXISTS `scheme_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `scheme_name` VARCHAR(80) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `name` VARCHAR(160) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(80) NOT NULL,
    `pincode` VARCHAR(20) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(120) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


