-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2019 at 08:15 PM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 7.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `homestead`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_mapping_department_keyword`
--

CREATE TABLE `cb2_mapping_department_keyword` (
  `id` int(11) NOT NULL,
  `department_` varchar(500) DEFAULT NULL,
  `department` varchar(500) DEFAULT NULL,
  `product_key` varchar(200) DEFAULT NULL,
  `LS_ID` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_mapping_direct`
--

CREATE TABLE `cb2_mapping_direct` (
  `id` int(11) NOT NULL,
  `product_category_` varchar(500) NOT NULL,
  `product_category` varchar(500) NOT NULL,
  `LS_ID` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_mapping_keyword_category`
--

CREATE TABLE `cb2_mapping_keyword_category` (
  `id` int(11) NOT NULL,
  `product_category_` varchar(500) DEFAULT NULL,
  `product_category` varchar(500) DEFAULT NULL,
  `product_key` varchar(500) DEFAULT NULL,
  `LS_ID` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_mapping_multi_dept`
--

CREATE TABLE `cb2_mapping_multi_dept` (
  `id` int(11) NOT NULL,
  `product_sec_category` varchar(500) DEFAULT NULL,
  `product_category` varchar(500) DEFAULT NULL,
  `product_key` varchar(500) NOT NULL,
  `LS_ID` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_master_products`
--

CREATE TABLE `cb2_master_products` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `sku_hash` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `model_code` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_url` text CHARACTER SET latin1,
  `model_name` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `images` text CHARACTER SET latin1,
  `thumb` text CHARACTER SET latin1,
  `product_dimension` varchar(800) CHARACTER SET latin1 DEFAULT NULL,
  `color` tinytext CHARACTER SET latin1,
  `price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `min_price` double NOT NULL DEFAULT '0',
  `max_price` double NOT NULL DEFAULT '0',
  `was_price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `product_name` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_feature` text CHARACTER SET latin1,
  `collection` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_set` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `product_condition` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `product_description` text CHARACTER SET latin1,
  `created_date` datetime NOT NULL,
  `updated_date` datetime DEFAULT NULL,
  `product_images` text CHARACTER SET latin1,
  `main_product_images` text CHARACTER SET latin1,
  `site_name` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reviews` int(11) DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `master_id` varchar(255) DEFAULT NULL,
  `product_status` varchar(10) NOT NULL DEFAULT 'active',
  `LS_ID` varchar(100) DEFAULT NULL,
  `popularity` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_products_new`
--

CREATE TABLE `cb2_products_new` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `sku_hash` varchar(100) CHARACTER SET latin1 NOT NULL,
  `model_code` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_url` text CHARACTER SET latin1 NOT NULL,
  `model_name` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `images` text CHARACTER SET latin1,
  `thumb` text CHARACTER SET latin1 NOT NULL,
  `product_dimension` varchar(800) CHARACTER SET latin1 DEFAULT NULL,
  `color` tinytext CHARACTER SET latin1,
  `price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `was_price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `parent_category` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_category` text CHARACTER SET latin1 NOT NULL,
  `product_name` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `department` text CHARACTER SET latin1,
  `product_feature` text CHARACTER SET latin1,
  `collection` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_set` varchar(255) CHARACTER SET latin1 NOT NULL,
  `product_condition` varchar(100) CHARACTER SET latin1 NOT NULL,
  `product_description` text CHARACTER SET latin1,
  `product_status` enum('active','inactive') CHARACTER SET latin1 DEFAULT 'active',
  `created_date` datetime NOT NULL,
  `updated_date` datetime DEFAULT NULL,
  `is_moved` tinyint(4) NOT NULL,
  `update_status` int(1) NOT NULL,
  `product_images` text CHARACTER SET latin1 NOT NULL,
  `main_product_images` text CHARACTER SET latin1 NOT NULL,
  `site_name` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reviews` int(11) DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `master_id` varchar(255) DEFAULT NULL,
  `LS_ID` varchar(100) DEFAULT NULL,
  `has_variations` int(11) NOT NULL DEFAULT '0',
  `product_thumbs` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_products_new_backup`
--

CREATE TABLE `cb2_products_new_backup` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `sku_hash` varchar(100) CHARACTER SET latin1 NOT NULL,
  `model_code` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_url` text CHARACTER SET latin1 NOT NULL,
  `model_name` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `images` text CHARACTER SET latin1,
  `thumb` text CHARACTER SET latin1 NOT NULL,
  `product_dimension` varchar(800) CHARACTER SET latin1 DEFAULT NULL,
  `color` tinytext CHARACTER SET latin1,
  `price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `was_price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `parent_category` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_category` text CHARACTER SET latin1 NOT NULL,
  `product_name` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `department` text CHARACTER SET latin1,
  `product_feature` text CHARACTER SET latin1,
  `collection` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_set` varchar(255) CHARACTER SET latin1 NOT NULL,
  `product_condition` varchar(100) CHARACTER SET latin1 NOT NULL,
  `product_description` text CHARACTER SET latin1,
  `product_status` enum('active','inactive') CHARACTER SET latin1 DEFAULT 'active',
  `created_date` datetime NOT NULL,
  `updated_date` datetime DEFAULT NULL,
  `is_moved` tinyint(4) NOT NULL,
  `update_status` int(1) NOT NULL,
  `product_images` text CHARACTER SET latin1 NOT NULL,
  `main_product_images` text CHARACTER SET latin1 NOT NULL,
  `site_name` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reviews` int(11) DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `master_id` varchar(255) DEFAULT NULL,
  `LS_ID` varchar(100) DEFAULT NULL,
  `has_variations` int(11) NOT NULL DEFAULT '0',
  `product_thumbs` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `cb2_products_variations`
--

CREATE TABLE `cb2_products_variations` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(50) NOT NULL,
  `variation_sku` varchar(50) NOT NULL,
  `variation_name` text,
  `swatch_image` varchar(500) DEFAULT NULL,
  `variation_image` varchar(500) DEFAULT NULL,
  `option_code` varchar(100) DEFAULT NULL,
  `choice_code` varchar(100) DEFAULT NULL,
  `has_parent_sku` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_categories`
--

CREATE TABLE `department_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_mapping`
--

CREATE TABLE `department_mapping` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mapping_core`
--

CREATE TABLE `mapping_core` (
  `LS_ID` int(11) NOT NULL,
  `department` varchar(200) NOT NULL,
  `department_` varchar(100) NOT NULL,
  `product_category` varchar(200) DEFAULT NULL,
  `product_category_` varchar(100) NOT NULL,
  `product_sub_category` varchar(200) DEFAULT NULL,
  `product_sub_category_` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `master_brands`
--

CREATE TABLE `master_brands` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `value` varchar(50) DEFAULT NULL,
  `url` varchar(300) DEFAULT NULL,
  `logo` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `master_data`
--

CREATE TABLE `master_data` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `sku_hash` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `model_code` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_url` text CHARACTER SET latin1,
  `model_name` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `images` text CHARACTER SET latin1,
  `thumb` text CHARACTER SET latin1,
  `product_dimension` varchar(800) CHARACTER SET latin1 DEFAULT NULL,
  `color` tinytext CHARACTER SET latin1,
  `price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `min_price` double NOT NULL DEFAULT '0',
  `max_price` double NOT NULL DEFAULT '0',
  `was_price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `product_name` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_feature` text CHARACTER SET latin1,
  `collection` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_set` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `product_condition` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `product_description` text CHARACTER SET latin1,
  `created_date` datetime NOT NULL,
  `updated_date` datetime DEFAULT NULL,
  `product_images` text CHARACTER SET latin1,
  `main_product_images` text CHARACTER SET latin1,
  `site_name` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reviews` int(11) DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `master_id` varchar(255) DEFAULT NULL,
  `LS_ID` varchar(100) DEFAULT NULL,
  `popularity` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nw_mapping_direct`
--

CREATE TABLE `nw_mapping_direct` (
  `product_category` varchar(100) DEFAULT NULL,
  `LS_ID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nw_mapping_keyword`
--

CREATE TABLE `nw_mapping_keyword` (
  `product_category` varchar(100) DEFAULT NULL,
  `product_key` varchar(100) DEFAULT NULL,
  `LS_ID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `nw_products`
--

CREATE TABLE `nw_products` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `sku_hash` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `model_code` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_url` text CHARACTER SET latin1,
  `model_name` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `images` text CHARACTER SET latin1,
  `thumb` text CHARACTER SET latin1,
  `product_dimension` varchar(800) CHARACTER SET latin1 DEFAULT NULL,
  `color` tinytext CHARACTER SET latin1,
  `price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `was_price` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `parent_category` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_category` text CHARACTER SET latin1,
  `product_name` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `department` text CHARACTER SET latin1,
  `product_feature` text CHARACTER SET latin1,
  `collection` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `product_set` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `product_condition` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `product_description` text CHARACTER SET latin1,
  `product_status` enum('active','inactive') CHARACTER SET latin1 DEFAULT 'active',
  `created_date` datetime NOT NULL,
  `updated_date` datetime DEFAULT NULL,
  `is_moved` tinyint(4) DEFAULT NULL,
  `update_status` int(1) DEFAULT NULL,
  `product_images` text CHARACTER SET latin1,
  `main_product_images` text CHARACTER SET latin1,
  `site_name` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reviews` int(11) DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `master_id` varchar(255) DEFAULT NULL,
  `LS_ID` varchar(100) DEFAULT NULL,
  `has_variations` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pier1_mapping_categories`
--

CREATE TABLE `pier1_mapping_categories` (
  `id` int(11) NOT NULL,
  `product_category` varchar(200) NOT NULL,
  `product_key` varchar(100) NOT NULL,
  `LS_ID` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pier1_mapping_departments`
--

CREATE TABLE `pier1_mapping_departments` (
  `id` int(11) NOT NULL,
  `product_dept` varchar(200) DEFAULT NULL,
  `product_key` varchar(100) DEFAULT NULL,
  `LS_ID` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pier1_mapping_direct`
--

CREATE TABLE `pier1_mapping_direct` (
  `id` int(11) NOT NULL,
  `product_category` varchar(200) NOT NULL,
  `LS_ID` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `pier1_products`
--

CREATE TABLE `pier1_products` (
  `id` int(11) NOT NULL,
  `product_sku` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  `sku_hash` varchar(100) CHARACTER SET latin1 NOT NULL,
  `model_code` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `product_url` text CHARACTER SET latin1 NOT NULL,
  `model_name` varchar(200) CHARACTER SET latin1 DEFAULT NULL,
  `images` text CHARACTER SET latin1,
  `thumb` text CHARACTER SET latin1 NOT NULL,
  `product_dimension` varchar(800) CHARACTER SET latin1 DEFAULT NULL,
  `color` tinytext CHARACTER SET latin1,
  `price` varchar(100) CHARACTER SET latin1 NOT NULL,
  `was_price` varchar(100) CHARACTER SET latin1 NOT NULL,
  `parent_category` varchar(200) CHARACTER SET latin1 NOT NULL,
  `product_category` text CHARACTER SET latin1 NOT NULL,
  `product_name` varchar(250) CHARACTER SET latin1 DEFAULT NULL,
  `department` text CHARACTER SET latin1 NOT NULL,
  `product_feature` text CHARACTER SET latin1 NOT NULL,
  `collection` varchar(250) CHARACTER SET latin1 NOT NULL,
  `product_set` varchar(255) CHARACTER SET latin1 NOT NULL,
  `product_condition` varchar(100) CHARACTER SET latin1 NOT NULL,
  `product_description` text CHARACTER SET latin1,
  `product_status` enum('active','inactive') CHARACTER SET latin1 DEFAULT 'active',
  `created_date` datetime NOT NULL,
  `updated_date` datetime DEFAULT NULL,
  `is_moved` tinyint(4) NOT NULL,
  `update_status` int(1) NOT NULL,
  `product_images` text CHARACTER SET latin1 NOT NULL,
  `main_product_images` text CHARACTER SET latin1 NOT NULL,
  `site_name` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reviews` int(11) DEFAULT NULL,
  `rating` varchar(20) DEFAULT NULL,
  `master_id` varchar(255) DEFAULT NULL,
  `LS_ID` varchar(100) DEFAULT NULL,
  `has_variations` int(11) NOT NULL DEFAULT '0',
  `product_thumbs` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_sku` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku_hash` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `images` text COLLATE utf8mb4_unicode_ci,
  `thumb` text COLLATE utf8mb4_unicode_ci,
  `product_dimension` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mrp` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_feature` text COLLATE utf8mb4_unicode_ci,
  `collection` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_set` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_condition` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_status` tinyint(1) DEFAULT NULL,
  `level` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_moved` tinyint(1) DEFAULT NULL,
  `update_status` tinyint(1) DEFAULT NULL,
  `product_images` text COLLATE utf8mb4_unicode_ci,
  `main_product_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviews` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ratings` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `master_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ls_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_impressions`
--

CREATE TABLE `product_impressions` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `social_identities`
--

CREATE TABLE `social_identities` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `provider_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oauth_provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oauth_uid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `picture` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_wishlists`
--

CREATE TABLE `user_wishlists` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` int(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `westelm_mapping_direct`
--

CREATE TABLE `westelm_mapping_direct` (
  `id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `LS_ID` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `westelm_mapping_direct_sub_cat`
--

CREATE TABLE `westelm_mapping_direct_sub_cat` (
  `department` varchar(100) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `product_sub_category` varchar(100) DEFAULT NULL,
  `LS_ID` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `westelm_mapping_keyword`
--

CREATE TABLE `westelm_mapping_keyword` (
  `department` varchar(100) DEFAULT NULL,
  `product_category` varchar(100) DEFAULT NULL,
  `product_sub_category` varchar(100) DEFAULT NULL,
  `product_key` varchar(100) DEFAULT NULL,
  `LS_ID` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `westelm_products_parents`
--

CREATE TABLE `westelm_products_parents` (
  `id` int(11) NOT NULL,
  `product_id` varchar(150) DEFAULT NULL,
  `product_id_hash` varchar(255) DEFAULT NULL,
  `product_url` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `main_image` text,
  `main_image_path` text,
  `product_images` text,
  `product_images_path` text,
  `thumb` text,
  `thumb_path` text,
  `product_dimension` varchar(255) DEFAULT NULL,
  `description_overview` text,
  `description_details` text,
  `description_shipping` text,
  `product_category` varchar(255) DEFAULT NULL,
  `product_sub_category` varchar(255) DEFAULT NULL,
  `collection` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `created_date` datetime DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `site_name` varchar(20) DEFAULT NULL,
  `product_status` enum('active','inactive') DEFAULT 'active',
  `price` varchar(13) DEFAULT NULL,
  `was_price` varchar(13) DEFAULT NULL,
  `LS_ID` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `westelm_products_skus`
--

CREATE TABLE `westelm_products_skus` (
  `id` int(11) NOT NULL,
  `product_id` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `price` varchar(255) DEFAULT NULL,
  `was_price` varchar(255) DEFAULT NULL,
  `attribute_1` varchar(255) DEFAULT NULL,
  `attribute_2` varchar(255) DEFAULT NULL,
  `attribute_3` varchar(255) DEFAULT NULL,
  `attribute_4` varchar(255) DEFAULT NULL,
  `attribute_5` varchar(255) DEFAULT NULL,
  `attribute_6` varchar(255) DEFAULT NULL,
  `image` text,
  `image_path` text,
  `swatch_image` text,
  `swatch_image_path` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cb2_mapping_department_keyword`
--
ALTER TABLE `cb2_mapping_department_keyword`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cb2_mapping_direct`
--
ALTER TABLE `cb2_mapping_direct`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cb2_mapping_keyword_category`
--
ALTER TABLE `cb2_mapping_keyword_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cb2_mapping_multi_dept`
--
ALTER TABLE `cb2_mapping_multi_dept`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cb2_master_products`
--
ALTER TABLE `cb2_master_products`
  ADD UNIQUE KEY `product_sku` (`product_sku`);

--
-- Indexes for table `cb2_products_new`
--
ALTER TABLE `cb2_products_new`
  ADD PRIMARY KEY (`id`),
  ADD KEY `master_id` (`master_id`);

--
-- Indexes for table `cb2_products_new_backup`
--
ALTER TABLE `cb2_products_new_backup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `master_id` (`master_id`);

--
-- Indexes for table `cb2_products_variations`
--
ALTER TABLE `cb2_products_variations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_categories`
--
ALTER TABLE `department_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_categories_department_id_foreign` (`department_id`),
  ADD KEY `department_categories_category_id_foreign` (`category_id`);

--
-- Indexes for table `department_mapping`
--
ALTER TABLE `department_mapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mapping_core`
--
ALTER TABLE `mapping_core`
  ADD PRIMARY KEY (`LS_ID`);

--
-- Indexes for table `master_brands`
--
ALTER TABLE `master_brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_data`
--
ALTER TABLE `master_data`
  ADD KEY `LS_ID` (`LS_ID`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nw_products`
--
ALTER TABLE `nw_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_sku` (`product_sku`);

--
-- Indexes for table `pier1_mapping_categories`
--
ALTER TABLE `pier1_mapping_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pier1_mapping_departments`
--
ALTER TABLE `pier1_mapping_departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pier1_mapping_direct`
--
ALTER TABLE `pier1_mapping_direct`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pier1_products`
--
ALTER TABLE `pier1_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `master_id` (`master_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_impressions`
--
ALTER TABLE `product_impressions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `social_identities`
--
ALTER TABLE `social_identities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `social_identities_provider_id_unique` (`provider_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_wishlists`
--
ALTER TABLE `user_wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_wishlists_user_id_foreign` (`user_id`),
  ADD KEY `user_wishlists_product_id_foreign` (`product_id`);

--
-- Indexes for table `westelm_mapping_direct`
--
ALTER TABLE `westelm_mapping_direct`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `westelm_products_parents`
--
ALTER TABLE `westelm_products_parents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_id` (`product_id`);

--
-- Indexes for table `westelm_products_skus`
--
ALTER TABLE `westelm_products_skus`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_mapping_department_keyword`
--
ALTER TABLE `cb2_mapping_department_keyword`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_mapping_direct`
--
ALTER TABLE `cb2_mapping_direct`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_mapping_keyword_category`
--
ALTER TABLE `cb2_mapping_keyword_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_mapping_multi_dept`
--
ALTER TABLE `cb2_mapping_multi_dept`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_products_new`
--
ALTER TABLE `cb2_products_new`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_products_new_backup`
--
ALTER TABLE `cb2_products_new_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cb2_products_variations`
--
ALTER TABLE `cb2_products_variations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_categories`
--
ALTER TABLE `department_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_mapping`
--
ALTER TABLE `department_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_brands`
--
ALTER TABLE `master_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nw_products`
--
ALTER TABLE `nw_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pier1_mapping_categories`
--
ALTER TABLE `pier1_mapping_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pier1_mapping_departments`
--
ALTER TABLE `pier1_mapping_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pier1_mapping_direct`
--
ALTER TABLE `pier1_mapping_direct`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pier1_products`
--
ALTER TABLE `pier1_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_impressions`
--
ALTER TABLE `product_impressions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `social_identities`
--
ALTER TABLE `social_identities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_wishlists`
--
ALTER TABLE `user_wishlists`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `westelm_mapping_direct`
--
ALTER TABLE `westelm_mapping_direct`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `westelm_products_parents`
--
ALTER TABLE `westelm_products_parents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `westelm_products_skus`
--
ALTER TABLE `westelm_products_skus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `department_categories`
--
ALTER TABLE `department_categories`
  ADD CONSTRAINT `department_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `department_categories_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_wishlists`
--
ALTER TABLE `user_wishlists`
  ADD CONSTRAINT `user_wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
