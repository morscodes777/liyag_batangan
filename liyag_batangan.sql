



-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 03:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `liyag_batangan`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_account`
--

CREATE TABLE `admin_account` (
  `admin_id` int(11) NOT NULL,
  `role` enum('SuperAdmin','SupportAdmin') DEFAULT 'SuperAdmin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_item`
--

CREATE TABLE `cart_item` (
  `cart_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_item`
--

INSERT INTO `cart_item` (`cart_item_id`, `user_id`, `product_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(21, 35, 25, 1, 130.00, '2025-09-29 15:25:23', NULL),
(22, 35, 31, 1, 300.00, '2025-09-29 15:25:23', NULL),
(30, 42, 37, 1, 50.00, '2025-09-29 15:25:23', NULL),
(31, 42, 31, 1, 300.00, '2025-09-29 15:25:23', NULL),
(34, 42, 26, 1, 290.00, '2025-09-29 15:33:33', NULL),
(35, 42, 25, 2, 260.00, '2025-09-29 15:36:21', NULL),
(36, 42, 39, 2, 300.00, '2025-09-29 15:37:17', NULL),
(37, 42, 33, 2, 360.00, '2025-09-29 15:37:46', NULL),
(57, 48, 26, 2, 580.00, '2025-10-23 01:54:12', NULL),
(58, 48, 28, 3, 540.00, '2025-10-23 01:59:40', NULL),
(71, 45, 40, 1, 100.00, '2025-10-27 22:59:35', '2025-10-27 22:59:35'),
(87, 45, 28, 1, 180.00, '2025-10-29 12:00:47', '2025-10-29 12:00:47'),
(89, 45, 34, 5, 1150.00, '2025-10-29 15:22:03', '2025-10-29 16:00:46'),
(90, 45, 29, 5, 1000.00, '2025-10-29 15:27:10', '2025-10-29 15:57:25'),
(91, 45, 44, 1, 12.00, '2025-10-29 15:28:08', '2025-10-29 15:28:08'),
(92, 45, 31, 6, 1800.00, '2025-10-29 15:35:42', '2025-11-01 12:07:25'),
(94, 45, 39, 3, 450.00, '2025-10-29 15:42:19', '2025-10-29 15:42:19'),
(95, 45, 26, 2, 580.00, '2025-10-29 15:42:51', '2025-10-29 15:55:15'),
(96, 45, 27, 1, 280.00, '2025-10-29 15:44:17', '2025-10-29 15:44:17'),
(97, 45, 25, 1, 130.00, '2025-10-29 15:45:22', '2025-10-29 15:45:22'),
(98, 45, 33, 3, 540.00, '2025-10-29 15:50:47', '2025-10-29 15:57:56'),
(99, 45, 37, 6, 300.00, '2025-10-29 15:55:45', '2025-10-29 16:15:13'),
(100, 32, 39, 2, 300.00, '2025-10-29 18:35:41', '2025-10-29 18:35:41'),
(104, 50, 27, 3, 840.00, '2025-11-01 22:05:41', '2025-11-01 22:05:41'),
(105, 50, 32, 3, 750.00, '2025-11-01 22:19:29', '2025-11-01 22:19:29'),
(106, 50, 29, 3, 600.00, '2025-11-01 22:31:58', '2025-11-01 22:31:58'),
(107, 32, 25, 1, 130.00, '2025-11-02 03:25:46', '2025-11-02 03:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL COMMENT 'Links to the parent conversation thread',
  `sender_user_id` int(11) NOT NULL COMMENT 'The user_id of the person who sent the message (customer or vendor)',
  `message_content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 for unread, 1 for read',
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `thread_id`, `sender_user_id`, `message_content`, `is_read`, `sent_at`) VALUES
(1, 1, 32, 'hi', 0, '2025-10-11 05:48:27'),
(2, 1, 32, 'hello', 0, '2025-10-11 07:54:26'),
(3, 3, 45, 'hello', 0, '2025-10-11 07:57:20'),
(4, 3, 32, 'hi', 0, '2025-10-11 07:58:02'),
(5, 1, 32, 'hello', 0, '2025-10-14 06:11:03'),
(6, 1, 32, 'sad', 0, '2025-10-14 06:16:01'),
(7, 1, 32, 'hello', 0, '2025-10-14 06:26:52'),
(8, 1, 32, 'hi', 0, '2025-10-14 06:27:00'),
(9, 1, 32, 'yo', 0, '2025-10-14 06:28:35'),
(10, 1, 32, 'hello', 0, '2025-10-14 06:29:00'),
(11, 4, 48, 'hi', 0, '2025-10-23 01:56:36'),
(12, 4, 48, 'hi', 0, '2025-10-23 19:49:05');

-- --------------------------------------------------------

--
-- Table structure for table `chat_threads`
--

CREATE TABLE `chat_threads` (
  `thread_id` int(11) NOT NULL,
  `customer_user_id` int(11) NOT NULL COMMENT 'The ID of the customer who started the chat',
  `vendor_user_id` int(11) NOT NULL COMMENT 'The ID of the vendor user being messaged',
  `last_message_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_threads`
--

INSERT INTO `chat_threads` (`thread_id`, `customer_user_id`, `vendor_user_id`, `last_message_at`, `created_at`) VALUES
(1, 32, 32, '2025-10-14 06:29:00', '2025-10-11 05:46:07'),
(2, 32, 33, '2025-10-11 05:49:13', '2025-10-11 05:49:13'),
(3, 45, 32, '2025-10-11 07:58:02', '2025-10-11 07:57:17'),
(4, 48, 32, '2025-10-23 19:49:05', '2025-10-23 01:56:28'),
(5, 48, 33, '2025-10-23 19:48:51', '2025-10-23 19:48:51'),
(6, 45, 33, '2025-10-24 00:23:35', '2025-10-24 00:23:35');

-- --------------------------------------------------------

--
-- Table structure for table `customer_account`
--

CREATE TABLE `customer_account` (
  `customer_id` int(11) NOT NULL,
  `preferred_shipping_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `courier_name` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `delivery_status` enum('Preparing','Out for Delivery','Delivered') DEFAULT 'Preparing',
  `estimated_arrival` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verification`
--

CREATE TABLE `email_verification` (
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verification`
--

INSERT INTO `email_verification` (`email`, `otp_code`, `otp_expiry`) VALUES
('22-37092@g.batstate-u.ph', '582299', '2025-11-01 15:02:35'),
('22-37092@g.batstate-u.php', '816939', '2025-11-01 15:01:33'),
('alexandraannonuevo@gmail.com', '669505', '2025-07-30 08:31:25'),
('ariescanubasasi@gmail.com', '195533', '2025-07-30 10:25:47'),
('christian.loowis@gmail.com', '107185', '2025-07-30 03:00:32'),
('florenciasaludaga1968@gmail.com', '883247', '2025-07-25 21:20:01'),
('florsaludaga1968@gmail.com', '912870', '2025-07-25 21:22:36'),
('gilbertsaludaga89@gmail.com', '645759', '2025-07-16 06:29:59'),
('gilbertsaludaga9@gmail.com', '878368', '2025-11-01 03:06:45'),
('liyagbatangan@gmail.com', '611778', '2025-11-01 03:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 5, 'Welcome to Liyag Batangan!', 'Hi there! We’re thrilled to have you onboard. Start browsing delicious food, unique pasalubongs, and amazing local stores. Thank you for supporting our community!', NULL, 0, '2025-07-02 16:50:20'),
(2, 13, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-02 17:13:43'),
(10, 5, '🛍️ Business Submission Received', 'Thank you for submitting \"hsgsfsffafs\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-02 18:51:45'),
(12, 5, '🛍️ Business Submission Received', 'Thank you for submitting \"my euli\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-02 19:01:20'),
(13, 5, '✅ Business Approved!', 'Congratulations! Your business \"my euli\" has been approved.', NULL, 0, '2025-07-02 19:01:30'),
(14, 13, '🛍️ Business Submission Received', 'Thank you for submitting \"Santo\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-02 19:57:51'),
(15, 13, '✅ Business Approved!', 'Congratulations! Your business \"Santo\" has been approved.', NULL, 0, '2025-07-02 19:58:03'),
(17, 5, '🛍️ Business Submission Received', 'Thank you for submitting \"Beach House\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-02 20:14:56'),
(18, 5, '✅ Business Approved!', 'Congratulations! Your business \"Beach House\" has been approved.', NULL, 0, '2025-07-02 20:15:14'),
(19, 15, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-03 14:00:19'),
(22, 17, '🛍️ Business Submission Received', 'Thank you for submitting \"Louis\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-04 17:44:15'),
(23, 17, '❌ Business Rejected', 'We\'re sorry. Your business \"Louis\" has been rejected.', NULL, 0, '2025-07-04 17:46:33'),
(24, 17, '🛍️ Business Submission Received', 'Thank you for submitting \"Louis\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-04 17:47:04'),
(25, 17, '✅ Business Approved!', 'Congratulations! Your business \"Louis\" has been approved.', NULL, 0, '2025-07-04 17:47:17'),
(26, 18, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-05 12:18:47'),
(27, 18, '🛍️ Business Submission Received', 'Thank you for submitting \"sisig123\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-05 12:19:59'),
(28, 18, '❌ Business Rejected', 'We\'re sorry. Your business \"sisig123\" has been rejected.', NULL, 0, '2025-07-05 12:21:02'),
(29, 18, '🛍️ Business Submission Received', 'Thank you for submitting \"sisig nga\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-05 12:21:37'),
(30, 18, '✅ Business Approved!', 'Congratulations! Your business \"sisig nga\" has been approved.', NULL, 0, '2025-07-05 12:21:45'),
(31, 25, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-15 14:14:33'),
(36, 32, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-16 04:25:23'),
(37, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"Balisong\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-23 06:32:27'),
(38, 32, '✅ Business Approved!', 'Congratulations! Your business \"Balisong\" has been approved.', NULL, 1, '2025-07-23 06:36:29'),
(39, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"Cat\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-23 06:42:25'),
(40, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"Liyag\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 1, '2025-07-23 06:46:47'),
(41, 32, '❌ Business Rejected', 'We\'re sorry. Your business \"Cat\" has been rejected.', NULL, 1, '2025-07-23 06:47:07'),
(42, 32, '✅ Business Approved!', 'Congratulations! Your business \"Liyag\" has been approved.', NULL, 1, '2025-07-23 06:47:14'),
(43, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"Liyag\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 1, '2025-07-23 06:53:18'),
(44, 32, '❌ Business Rejected', 'We\'re sorry. Your business \"Liyag\" has been rejected.', NULL, 1, '2025-07-23 06:53:27'),
(45, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"ff\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 1, '2025-07-23 06:58:48'),
(46, 32, '❌ Business Rejected', 'We\'re sorry. Your business \"ff\" has been rejected.', NULL, 1, '2025-07-23 06:59:06'),
(47, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"Balisong\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 1, '2025-07-23 06:59:39'),
(48, 32, '✅ Business Approved!', 'Congratulations! Your business \"Balisong\" has been approved.', NULL, 1, '2025-07-23 06:59:46'),
(49, 32, '🛍️ Business Submission Received', 'Thank you for submitting \"Batangan Pasalubong\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 1, '2025-07-25 17:47:33'),
(51, 33, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-25 19:18:05'),
(52, 33, '🛍️ Business Submission Received', 'Thank you for submitting \"Black\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-29 17:04:15'),
(53, 33, '🛍️ Business Submission Received', 'Thank you for submitting \"Coffee\". Your business is under review. We\'ll notify you once it\'s approved.', NULL, 0, '2025-07-29 17:05:11'),
(54, 33, '🛍️ Business Submission Received', 'Thank you for submitting \"Bagx\". Your business is under review.', NULL, 0, '2025-07-29 19:22:16'),
(55, 33, '🛍️ Business Submission Received', 'Thank you for submitting \"Lomi\". Your business is under review.', NULL, 0, '2025-07-29 19:22:52'),
(56, 33, '❌ Business Rejected', 'We\'re sorry. Your business \"Lomi\" has been rejected.', NULL, 0, '2025-07-29 19:48:57'),
(57, 33, '❌ Business Rejected', 'We\'re sorry. Your business \"Bagx\" has been rejected.', NULL, 0, '2025-07-29 19:49:03'),
(58, 34, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-30 00:57:06'),
(59, 33, '🛍️ Business Submission Received', 'Thank you for submitting \"Coffeee\". Your business is under review.', NULL, 0, '2025-07-30 01:07:14'),
(61, 33, '✅ Business Approved!', 'Congratulations! Your business \"Black\" has been approved.', NULL, 0, '2025-07-30 07:37:48'),
(62, 33, '✅ Business Approved!', 'Congratulations! Your business \"Coffeee\" has been approved.', NULL, 0, '2025-07-30 07:39:27'),
(63, 36, 'Welcome to Liyag Batangan!', 'Thank you for registering! Start exploring local delicacies and offers today.', NULL, 0, '2025-07-30 08:21:06'),
(64, 32, '✅ Order Placed Successfully!', 'Your order #13 has been confirmed. Total amount: ₱350.00. Thank you for shopping with us!', NULL, 1, '2025-10-24 00:56:21'),
(65, 32, '✅ Order Placed Successfully!', 'Your order #14 has been confirmed. Total amount: ₱200.00. Thank you for shopping with us!', NULL, 0, '2025-10-24 14:43:02'),
(66, 32, '✅ Order Placed Successfully!', 'Your order #15 has been confirmed. Total amount: ₱230.00. Thank you for shopping with us!', NULL, 0, '2025-10-24 14:44:14'),
(67, 32, '✅ Order Placed Successfully!', 'Your order #16 has been confirmed. Total amount: ₱600.00. Thank you for shopping with us!', NULL, 0, '2025-10-24 15:50:32'),
(68, 32, '✅ Order Placed Successfully!', 'Your order #17 has been confirmed. Total amount: ₱1,200.00. Thank you for shopping with us!', NULL, 0, '2025-10-24 17:01:08'),
(69, 32, '✅ Order Placed Successfully!', 'Your order #18 has been confirmed. Total amount: ₱62.00. Thank you for shopping with us!', NULL, 1, '2025-10-24 17:22:21'),
(70, 32, 'Order #18 Status Update', 'Your order status has been updated to **Approved** by the vendor.', 'index.php?action=track_orders', 1, '2025-10-24 17:39:58'),
(71, 32, 'Order #18 Status Update', 'Your order status has been updated to **Delivered** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-24 17:46:01'),
(72, 32, '✅ Order Placed Successfully!', 'Your order #19 has been confirmed. Total amount: ₱3,050.00. Thank you for shopping with us!', NULL, 0, '2025-10-26 04:00:37'),
(73, 32, 'Order #17 Status Update', 'Your order status has been updated to **Shipped** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-26 04:04:15'),
(74, 32, '✅ Order Placed Successfully!', 'Your order #22 has been confirmed. Total amount: ₱350.00. Thank you for shopping with us!', NULL, 0, '2025-10-27 00:03:26'),
(75, 32, '✅ Order Placed Successfully!', 'Your order #25 has been confirmed. Total amount: ₱800.00. Thank you for shopping with us!', NULL, 0, '2025-10-27 00:04:14'),
(76, 32, '✅ Order Placed Successfully!', 'Your order #26 has been confirmed. Total amount: ₱98.00. Thank you for shopping with us!', NULL, 0, '2025-10-27 00:05:34'),
(77, 32, '✅ Order Placed Successfully!', 'Your order #27 has been confirmed. Total amount: ₱1,310.00. Thank you for shopping with us!', NULL, 0, '2025-10-27 00:58:41'),
(78, 32, '✅ Order Placed Successfully!', 'Your order #28 has been confirmed. Total amount: ₱250.00. Thank you for shopping with us!', NULL, 0, '2025-10-27 16:07:10'),
(79, 32, 'Order #28 Status Update', 'Your order status has been updated to **Approved** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-27 16:55:47'),
(80, 32, 'Order #27 Status Update', 'Your order status has been updated to **Delivered** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-27 16:56:11'),
(81, 32, 'Order #26 Status Update', 'Your order status has been updated to **Delivered** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-27 17:40:22'),
(82, 32, 'Order #25 Status Update', 'Your order status has been updated to **Delivered** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-27 17:41:01'),
(83, 33, 'Your Store Has Been Approved 🎉', 'Congratulations! Your business \'Lomi\' has been approved by the Liyag Batangan Admin.', NULL, 0, '2025-10-27 18:35:57'),
(84, 48, 'Your Store Has Been Approved 🎉', 'Congratulations! Your business \'Batangan\' has been approved by the Liyag Batangan Admin.', NULL, 0, '2025-10-27 18:40:30'),
(85, 25, 'Store Approved! 🎉', 'Congratulations! Your store registration has been approved. You can now start selling!', NULL, 0, '2025-10-27 20:00:34'),
(86, 44, 'Store Rejected ❌', 'Your store registration has been rejected. Reason: no data', NULL, 0, '2025-10-27 20:11:11'),
(87, 48, 'Store Rejected ❌', 'Your store registration has been rejected. Reason: no dataaa', NULL, 0, '2025-10-27 20:14:29'),
(88, 48, 'Store Approved! 🎉', 'Congratulations! Your store registration has been approved. You can now start selling!', NULL, 0, '2025-10-27 20:18:41'),
(89, 48, 'Store Approved! 🎉', 'Congratulations! Your store registration has been approved. You can now start selling!', NULL, 0, '2025-10-27 20:24:58'),
(90, 33, 'Store Rejected ❌', 'Your store registration has been rejected. Reason: as', NULL, 0, '2025-10-28 01:09:22'),
(91, 32, '✅ Order Placed Successfully!', 'Your order #29 has been confirmed. Total amount: ₱450.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 19:00:23'),
(92, 32, '✅ Order Placed Successfully!', 'Your order #30 has been confirmed. Total amount: ₱200.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 19:08:38'),
(93, 32, '✅ Order Placed Successfully!', 'Your order #34 has been confirmed. Total amount: ₱62.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 19:57:55'),
(94, 32, '✅ Order Placed Successfully!', 'Your order #38 has been confirmed. Total amount: ₱340.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 20:00:16'),
(95, 32, '✅ Order Placed Successfully!', 'Your order #40 has been confirmed. Total amount: ₱340.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 20:08:12'),
(96, 32, '✅ Order Placed Successfully!', 'Your order #41 has been confirmed. Total amount: ₱250.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 20:21:08'),
(97, 32, '✅ Order Placed Successfully!', 'Your order #42 has been confirmed. Total amount: ₱200.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 20:27:12'),
(98, 32, 'Order #42 Status Update', 'Your order status has been updated to **Delivered** by the vendor.', 'index.php?action=track_orders', 0, '2025-10-28 20:29:20'),
(99, 32, '✅ Order Placed Successfully!', 'Your order #43 has been confirmed. Total amount: ₱200.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 21:05:28'),
(100, 32, '✅ Order Placed Successfully!', 'Your COD order #44 has been confirmed. Total amount: ₱150.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 21:28:03'),
(101, 32, '✅ Order Placed Successfully!', 'Your COD order #57 has been confirmed. Total amount: ₱150.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 22:24:56'),
(102, 32, '⏳ Payment Initiated', 'We\'re waiting for your GCash payment for Order #58. Please complete the payment after redirection.', NULL, 0, '2025-10-28 22:31:04'),
(103, 32, '⏳ Payment Initiated', 'Temporary order #59 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-28 22:37:10'),
(104, 32, '⏳ Payment Initiated', 'We\'re waiting for your GCash payment for Order #59. Please complete the payment after redirection.', NULL, 0, '2025-10-28 22:37:11'),
(105, 32, '⏳ Payment Initiated', 'Temporary order #60 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-28 22:37:11'),
(106, 32, '⏳ Payment Initiated', 'We\'re waiting for your GCash payment for Order #60. Please complete the payment after redirection.', NULL, 0, '2025-10-28 22:37:11'),
(107, 32, '⏳ Payment Initiated', 'Temporary order #61 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-28 22:39:54'),
(108, 32, '🎉 Order Placed!', 'Your order #62 has been successfully placed. Status: Pending.', NULL, 0, '2025-10-28 22:46:54'),
(109, 32, '✅ Order Placed Successfully!', 'Your COD order #62 has been confirmed. Total amount: ₱1,450.00. Thank you for shopping with us!', NULL, 0, '2025-10-28 22:46:54'),
(110, 32, '⏳ Payment Initiated', 'Temporary order #63 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-28 22:47:20'),
(111, 32, '⏳ Payment Initiated', 'Temporary order #64 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-28 22:56:00'),
(112, 32, '⏳ Payment Initiated', 'Temporary order #65 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-28 22:58:22'),
(113, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #65 was successful. Order status is now Approved.', NULL, 0, '2025-10-28 22:58:35'),
(114, 32, '⏳ Payment Initiated', 'Temporary order #66 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-29 02:39:04'),
(115, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #66 was successful. Order status is now Approved.', NULL, 0, '2025-10-29 02:44:18'),
(116, 32, '⏳ Payment Initiated', 'Temporary order #67 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-29 03:14:37'),
(117, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #67 was successful. Order status is now Approved.', NULL, 0, '2025-10-29 03:15:10'),
(118, 32, '⏳ Payment Initiated', 'Temporary order #68 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-29 03:54:28'),
(119, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #68 was successful. Order status is now Approved.', NULL, 0, '2025-10-29 03:54:53'),
(120, 45, 'Store Approved! 🎉', 'Congratulations! Your store registration has been approved. You can now start selling!', NULL, 0, '2025-10-29 04:09:15'),
(121, 32, '🎉 Order Placed!', 'Your order #69 has been successfully placed. Status: Pending.', NULL, 0, '2025-10-29 04:29:38'),
(122, 32, '✅ Order Placed Successfully!', 'Your COD order #69 has been confirmed. Total amount: ₱62.00. Thank you for shopping with us!', NULL, 0, '2025-10-29 04:29:38'),
(123, 32, '⏳ Payment Initiated', 'Temporary order #70 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-29 10:36:22'),
(124, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #70 was successful. Order status is now Approved.', NULL, 0, '2025-10-29 10:36:47'),
(125, 32, '⏳ Payment Initiated', 'Temporary order #71 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-10-31 04:20:59'),
(126, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #71 was successful. Order status is now Approved.', NULL, 0, '2025-10-31 04:21:23'),
(127, 32, '⏳ Payment Initiated', 'Temporary order #72 created. Please complete the GCash payment to confirm your purchase.', NULL, 0, '2025-11-01 04:07:53'),
(128, 32, '✅ Payment Successful! Order Approved!', 'Your payment for order #72 was successful. Order status is now Approved.', NULL, 0, '2025-11-01 04:08:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_address_id` int(11) NOT NULL,
  `payment_method` enum('COD','GCash') NOT NULL,
  `order_total` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `total_commission` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vendor_payout` decimal(10,2) NOT NULL DEFAULT 0.00,
  `order_status` enum('Pending','Approved','Shipped','Out for Delivery','Delivered') NOT NULL DEFAULT 'Pending',
  `paymongo_session_id` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `delivery_address_id`, `payment_method`, `order_total`, `shipping_fee`, `total_commission`, `vendor_payout`, `order_status`, `paymongo_session_id`, `updated_at`) VALUES
(6, 32, '2025-10-23 23:53:30', 1, 'COD', 550.00, 50.00, 0.00, 0.00, 'Delivered', NULL, NULL),
(7, 32, '2025-10-23 23:58:42', 1, 'COD', 1500.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:12:02'),
(8, 32, '2025-10-23 23:59:43', 1, 'COD', 550.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:09:58'),
(9, 32, '2025-10-24 00:02:08', 1, 'COD', 890.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(10, 32, '2025-10-24 00:04:43', 2, 'COD', 300.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:04:23'),
(11, 32, '2025-10-24 00:06:49', 1, 'COD', 770.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:03:12'),
(12, 32, '2025-10-24 00:09:16', 1, 'COD', 2850.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:00:55'),
(13, 32, '2025-10-24 00:56:21', 2, 'GCash', 350.00, 50.00, 0.00, 0.00, 'Approved', NULL, NULL),
(14, 32, '2025-10-24 14:43:02', 1, 'COD', 200.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 00:33:04'),
(15, 32, '2025-10-24 14:44:14', 2, 'COD', 230.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:35:43'),
(16, 32, '2025-10-24 15:50:32', 1, 'COD', 600.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-25 01:33:26'),
(17, 32, '2025-10-24 17:01:08', 2, 'COD', 1200.00, 50.00, 0.00, 0.00, 'Shipped', NULL, '2025-10-26 12:04:15'),
(18, 32, '2025-10-24 17:22:21', 1, 'COD', 62.00, 50.00, 0.00, 0.00, 'Delivered', NULL, '2025-10-25 01:46:01'),
(19, 32, '2025-10-26 04:00:37', 1, 'COD', 3050.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(22, 32, '2025-10-27 00:03:26', 1, 'COD', 350.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(25, 32, '2025-10-27 00:04:14', 2, 'COD', 800.00, 50.00, 0.00, 0.00, 'Delivered', NULL, '2025-10-28 01:41:01'),
(26, 32, '2025-10-27 00:05:34', 1, 'COD', 98.00, 50.00, 0.00, 0.00, 'Delivered', NULL, '2025-10-28 01:40:22'),
(27, 32, '2025-10-27 00:58:41', 2, 'COD', 1310.00, 50.00, 0.00, 0.00, 'Delivered', NULL, '2025-10-28 00:56:11'),
(28, 32, '2025-10-27 16:07:10', 1, 'COD', 250.00, 50.00, 0.00, 0.00, 'Approved', NULL, '2025-10-28 00:55:47'),
(29, 32, '2025-10-28 19:00:23', 1, 'COD', 450.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(30, 32, '2025-10-28 19:08:38', 1, 'COD', 200.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(34, 32, '2025-10-28 19:57:55', 1, 'COD', 62.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(38, 32, '2025-10-28 20:00:16', 1, 'COD', 340.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(40, 32, '2025-10-28 20:08:12', 3, 'COD', 340.00, 50.00, 0.00, 0.00, 'Pending', NULL, NULL),
(41, 32, '2025-10-28 20:21:08', 1, 'COD', 250.00, 50.00, 0.00, 250.00, 'Pending', NULL, NULL),
(42, 32, '2025-10-28 20:27:12', 1, 'COD', 200.00, 50.00, 15.00, 185.00, 'Delivered', NULL, '2025-10-29 04:29:20'),
(43, 32, '2025-10-28 21:05:28', 1, 'COD', 200.00, 50.00, 15.00, 185.00, 'Delivered', NULL, NULL),
(44, 32, '2025-10-28 21:28:03', 1, 'COD', 150.00, 50.00, 10.00, 140.00, 'Pending', NULL, NULL),
(47, 32, '2025-10-28 21:31:50', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, '', 'cs_873fhXdoQoG1giV4S2AmpcMW', NULL),
(48, 32, '2025-10-28 21:36:13', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, '', 'cs_5XYkEXsM1jWGPZKkYJw6SyVK', NULL),
(49, 32, '2025-10-28 21:55:34', 2, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_CwcYAciBXD4oce3dujYALgnV', NULL),
(50, 32, '2025-10-28 21:58:25', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_rgzD7ZkmReR4ph6tx4KkCG5y', NULL),
(51, 32, '2025-10-28 21:58:26', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_8yeP6PofqEvohNsHis2JRswy', NULL),
(52, 32, '2025-10-28 22:00:07', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_qqSRQFRmKq2PSzHXMr5bPUdK', NULL),
(53, 32, '2025-10-28 22:02:12', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_C5qhyGR9yxcsfTX82inR1gkB', NULL),
(54, 32, '2025-10-28 22:09:00', 3, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Pending', 'cs_Ws79eDCTr3ZSiANYN9CWZf9w', NULL),
(55, 32, '2025-10-28 22:19:38', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_1wF1jaerBnf869QUvSC963De', NULL),
(56, 32, '2025-10-28 22:23:44', 1, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_tHtisPHBNU7Tk5XKShmguk9u', NULL),
(57, 32, '2025-10-28 22:24:56', 1, 'COD', 150.00, 50.00, 10.00, 140.00, 'Pending', NULL, NULL),
(58, 32, '2025-10-28 22:31:04', 3, 'GCash', 230.00, 50.00, 18.00, 212.00, 'Approved', 'cs_gCHWuD4NEKRLgo3hAeLZLm6n', NULL),
(59, 32, '2025-10-28 22:37:10', 1, 'GCash', 230.00, 50.00, 18.00, 212.00, 'Approved', 'cs_FCtXqZ7t4irfHg9EBLSu4tpZ', NULL),
(60, 32, '2025-10-28 22:37:11', 1, 'GCash', 230.00, 50.00, 18.00, 212.00, 'Approved', 'cs_7NNSTZfNuGHeU6XyNBpMraDv', NULL),
(61, 32, '2025-10-28 22:39:54', 3, 'GCash', 230.00, 50.00, 18.00, 212.00, 'Approved', 'cs_6KREZG6sB9btwmkvUK9uG1RF', NULL),
(62, 32, '2025-10-28 22:46:54', 1, 'COD', 1450.00, 50.00, 140.00, 1310.00, 'Pending', NULL, NULL),
(63, 32, '2025-10-28 22:47:20', 3, 'GCash', 950.00, 50.00, 90.00, 860.00, 'Approved', 'cs_XLLd3jr3K4v3jzUAPsmw5znh', NULL),
(64, 32, '2025-10-28 22:56:00', 1, 'GCash', 950.00, 50.00, 90.00, 860.00, 'Approved', 'cs_7GkVsHEyJcRT9CRnZikjSVKk', NULL),
(65, 32, '2025-10-28 22:58:22', 1, 'GCash', 950.00, 50.00, 90.00, 860.00, 'Approved', 'cs_7zjx32MusGdkhJbB2gsiiXjq', '2025-10-29 06:58:35'),
(66, 32, '2025-10-29 02:39:04', 1, 'GCash', 350.00, 50.00, 30.00, 320.00, 'Approved', 'cs_uGPKEjprGzEUoQPYNnZZ16RQ', '2025-10-29 10:44:18'),
(67, 32, '2025-10-29 03:14:37', 1, 'GCash', 200.00, 50.00, 15.00, 185.00, 'Approved', 'cs_MfWEAgmc95hXyoTQ83auaPTB', '2025-10-29 11:15:10'),
(68, 32, '2025-10-29 03:54:28', 4, 'GCash', 350.00, 50.00, 30.00, 320.00, 'Approved', 'cs_JkYUR88toApzChr23E9Yq5QS', '2025-10-29 11:54:53'),
(69, 32, '2025-10-29 04:29:38', 1, 'COD', 62.00, 50.00, 1.20, 60.80, 'Pending', NULL, NULL),
(70, 32, '2025-10-29 10:36:22', 2, 'GCash', 150.00, 50.00, 10.00, 140.00, 'Approved', 'cs_9jKx96Pip6BjNf9ZsaidzXho', '2025-10-29 18:36:47'),
(71, 32, '2025-10-31 04:20:59', 5, 'GCash', 74.00, 50.00, 2.40, 71.60, 'Approved', 'cs_n8fANfvGWDowJjqVZYWV5DdN', '2025-10-31 12:21:23'),
(72, 32, '2025-11-01 04:07:53', 5, 'GCash', 590.00, 50.00, 54.00, 536.00, 'Approved', 'cs_4VUzXoBsktoNKkTEN3nYTxko', '2025-11-01 12:08:06');

-- --------------------------------------------------------

--
-- Table structure for table `order_app`
--

CREATE TABLE `order_app` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_app`
--

INSERT INTO `order_app` (`order_id`, `user_id`, `name`, `address`, `contact`, `total_price`, `created_at`) VALUES
(8, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 1500.00, '2025-07-25 15:46:11'),
(9, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 1500.00, '2025-07-25 15:51:50'),
(10, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 1500.00, '2025-07-25 15:52:01'),
(11, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 1500.00, '2025-07-25 15:52:01'),
(12, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 500.00, '2025-07-25 15:52:15'),
(13, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 500.00, '2025-07-25 15:53:22'),
(14, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 800.00, '2025-07-25 15:53:50'),
(15, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 800.00, '2025-07-25 16:19:32'),
(16, 32, 'Gilbert Saludaga', 'Arturo Tanco Drive, Balintawak, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 400.00, '2025-07-25 16:21:23'),
(17, 32, 'Gilbert Saludaga', '6, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 1400.00, '2025-07-25 20:02:54'),
(18, 32, 'Gilbert Saludaga', '6, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 968 307 4206', 1000.00, '2025-07-25 20:03:17'),
(19, 35, 'Alexandra Añonuevo', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 951 965 7113', 380.00, '2025-07-30 06:29:07'),
(20, 34, 'Christian Luis Hiwatig', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 960 271 8018', 250.00, '2025-07-30 07:35:41'),
(21, 36, 'Aries Asi', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', '+63 960 271 8018', 550.00, '2025-07-30 08:22:28');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

  CREATE TABLE `order_items` (
    `order_item_id` int(11) NOT NULL,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) NOT NULL,
    `line_total` decimal(10,2) NOT NULL,
    `commission_rate` decimal(4,2) NOT NULL DEFAULT 0.00
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `line_total`, `commission_rate`) VALUES
(4, 6, 40, 5, 100.00, 500.00, 0.00),
(5, 7, 26, 5, 290.00, 1450.00, 0.00),
(6, 8, 32, 2, 250.00, 500.00, 0.00),
(7, 9, 36, 7, 120.00, 840.00, 0.00),
(8, 10, 37, 5, 50.00, 250.00, 0.00),
(9, 11, 33, 4, 180.00, 720.00, 0.00),
(10, 12, 27, 10, 280.00, 2800.00, 0.00),
(11, 13, 31, 1, 300.00, 300.00, 0.00),
(12, 14, 39, 1, 150.00, 150.00, 0.00),
(13, 15, 28, 1, 180.00, 180.00, 0.00),
(14, 16, 32, 1, 250.00, 250.00, 0.00),
(15, 16, 31, 1, 300.00, 300.00, 0.00),
(16, 17, 34, 5, 230.00, 1150.00, 0.00),
(17, 18, 44, 1, 12.00, 12.00, 0.00),
(18, 19, 32, 12, 250.00, 3000.00, 0.00),
(19, 22, 40, 3, 100.00, 300.00, 0.00),
(20, 25, 32, 3, 250.00, 750.00, 0.00),
(21, 26, 44, 4, 12.00, 48.00, 0.00),
(22, 27, 33, 7, 180.00, 1260.00, 0.00),
(23, 28, 29, 1, 200.00, 200.00, 0.00),
(24, 29, 29, 2, 200.00, 400.00, 0.00),
(25, 30, 39, 1, 150.00, 150.00, 0.00),
(26, 34, 44, 1, 12.00, 12.00, 0.00),
(27, 38, 26, 1, 290.00, 290.00, 0.00),
(28, 40, 26, 1, 290.00, 290.00, 0.00),
(29, 41, 40, 2, 100.00, 200.00, 0.00),
(30, 42, 39, 1, 150.00, 150.00, 10.00),
(31, 43, 39, 1, 150.00, 150.00, 10.00),
(32, 44, 40, 1, 100.00, 100.00, 10.00),
(35, 47, 40, 1, 100.00, 100.00, 10.00),
(36, 48, 40, 1, 100.00, 100.00, 10.00),
(37, 49, 40, 1, 100.00, 100.00, 10.00),
(38, 50, 40, 1, 100.00, 100.00, 10.00),
(39, 51, 40, 1, 100.00, 100.00, 10.00),
(40, 52, 40, 1, 100.00, 100.00, 10.00),
(41, 53, 40, 1, 100.00, 100.00, 10.00),
(42, 54, 40, 1, 100.00, 100.00, 10.00),
(43, 55, 40, 1, 100.00, 100.00, 10.00),
(44, 56, 40, 1, 100.00, 100.00, 10.00),
(45, 57, 40, 1, 100.00, 100.00, 10.00),
(46, 58, 28, 1, 180.00, 180.00, 10.00),
(47, 59, 28, 1, 180.00, 180.00, 10.00),
(48, 60, 28, 1, 180.00, 180.00, 10.00),
(49, 61, 28, 1, 180.00, 180.00, 10.00),
(50, 62, 27, 5, 280.00, 1400.00, 10.00),
(51, 63, 28, 5, 180.00, 900.00, 10.00),
(52, 64, 28, 5, 180.00, 900.00, 10.00),
(53, 65, 28, 5, 180.00, 900.00, 10.00),
(54, 66, 31, 1, 300.00, 300.00, 10.00),
(55, 67, 39, 1, 150.00, 150.00, 10.00),
(56, 68, 39, 2, 150.00, 300.00, 10.00),
(57, 69, 44, 1, 12.00, 12.00, 10.00),
(58, 70, 40, 1, 100.00, 100.00, 10.00),
(59, 71, 44, 2, 12.00, 24.00, 10.00),
(60, 72, 33, 3, 180.00, 540.00, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('COD','GCash') NOT NULL,
  `transaction_ref_id` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Success','Failed') NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method`, `transaction_ref_id`, `amount`, `payment_status`, `payment_date`) VALUES
(1, 6, 'COD', NULL, 550.00, 'Pending', '2025-10-23 23:53:30'),
(2, 7, 'COD', NULL, 1500.00, 'Pending', '2025-10-23 23:58:42'),
(3, 8, 'COD', NULL, 550.00, 'Pending', '2025-10-23 23:59:43'),
(4, 9, 'COD', NULL, 890.00, 'Pending', '2025-10-24 00:02:08'),
(5, 10, 'COD', NULL, 300.00, 'Pending', '2025-10-24 00:04:43'),
(6, 11, 'COD', NULL, 770.00, 'Pending', '2025-10-24 00:06:49'),
(7, 12, 'COD', NULL, 2850.00, 'Pending', '2025-10-24 00:09:16'),
(8, 13, 'GCash', 'GCASH425b86325a', 350.00, '', '2025-10-24 00:56:21'),
(9, 14, 'COD', NULL, 200.00, 'Pending', '2025-10-24 14:43:02'),
(10, 15, 'COD', NULL, 230.00, 'Pending', '2025-10-24 14:44:14'),
(11, 16, 'COD', NULL, 600.00, 'Pending', '2025-10-24 15:50:32'),
(12, 17, 'COD', NULL, 1200.00, 'Pending', '2025-10-24 17:01:08'),
(13, 18, 'COD', NULL, 62.00, 'Pending', '2025-10-24 17:22:21'),
(14, 19, 'COD', NULL, 3050.00, 'Pending', '2025-10-26 04:00:37'),
(15, 22, 'COD', NULL, 350.00, 'Pending', '2025-10-27 00:03:26'),
(16, 25, 'COD', NULL, 800.00, 'Pending', '2025-10-27 00:04:14'),
(17, 26, 'COD', NULL, 98.00, 'Pending', '2025-10-27 00:05:34'),
(18, 27, 'COD', NULL, 1310.00, 'Pending', '2025-10-27 00:58:41'),
(19, 28, 'COD', NULL, 250.00, 'Pending', '2025-10-27 16:07:10'),
(20, 29, 'COD', NULL, 450.00, 'Pending', '2025-10-28 19:00:23'),
(21, 30, 'COD', NULL, 200.00, 'Pending', '2025-10-28 19:08:38'),
(22, 34, 'COD', NULL, 62.00, 'Pending', '2025-10-28 19:57:55'),
(23, 38, 'COD', NULL, 340.00, 'Pending', '2025-10-28 20:00:16'),
(24, 40, 'COD', NULL, 340.00, 'Pending', '2025-10-28 20:08:12'),
(25, 41, 'COD', NULL, 250.00, 'Pending', '2025-10-28 20:21:08'),
(26, 42, 'COD', NULL, 200.00, 'Pending', '2025-10-28 20:27:12'),
(27, 43, 'COD', NULL, 200.00, 'Pending', '2025-10-28 21:05:28'),
(28, 44, 'COD', NULL, 150.00, 'Pending', '2025-10-28 21:28:03'),
(31, 47, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 21:31:50'),
(32, 48, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 21:36:13'),
(33, 49, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 21:55:34'),
(34, 50, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 21:58:25'),
(35, 51, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 21:58:26'),
(36, 52, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 22:00:07'),
(37, 53, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 22:02:12'),
(38, 54, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 22:09:00'),
(39, 55, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 22:19:38'),
(40, 56, 'GCash', NULL, 150.00, 'Pending', '2025-10-28 22:23:44'),
(41, 57, 'COD', NULL, 150.00, 'Pending', '2025-10-28 22:24:56'),
(42, 58, 'GCash', NULL, 230.00, 'Pending', '2025-10-28 22:31:04'),
(43, 59, 'GCash', NULL, 230.00, 'Pending', '2025-10-28 22:37:10'),
(44, 60, 'GCash', NULL, 230.00, 'Pending', '2025-10-28 22:37:11'),
(45, 61, 'GCash', NULL, 230.00, 'Pending', '2025-10-28 22:39:54'),
(46, 62, 'COD', NULL, 1450.00, 'Pending', '2025-10-28 22:46:54'),
(47, 63, 'GCash', NULL, 950.00, 'Pending', '2025-10-28 22:47:20'),
(48, 64, 'GCash', NULL, 950.00, 'Pending', '2025-10-28 22:56:00'),
(49, 65, 'GCash', NULL, 950.00, '', '2025-10-28 22:58:35'),
(50, 66, 'GCash', NULL, 350.00, '', '2025-10-29 02:44:18'),
(51, 67, 'GCash', NULL, 200.00, '', '2025-10-29 03:15:10'),
(52, 68, 'GCash', NULL, 350.00, '', '2025-10-29 03:54:53'),
(53, 69, 'COD', NULL, 62.00, 'Pending', '2025-10-29 04:29:38'),
(54, 70, 'GCash', NULL, 150.00, '', '2025-10-29 10:36:47'),
(55, 71, 'GCash', NULL, 74.00, '', '2025-10-31 04:21:23'),
(56, 72, 'GCash', NULL, 590.00, '', '2025-11-01 04:08:06');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','OutOfStock','Discontinued','Pending') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `commission_rate` decimal(4,2) NOT NULL DEFAULT 10.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `vendor_id`, `category_id`, `name`, `description`, `price`, `stock_quantity`, `image_url`, `status`, `created_at`, `updated_at`, `commission_rate`) VALUES
(25, 21, 1, 'Tablea de Batangas', 'Pure, unsweetened chocolate tablets made from local cacao beans, perfect for hot chocolate (sikwate).', 130.00, 99, 'uploads/products/prod_6883c43b8c7bc_1000002969.jpg', 'Active', '2025-07-25 17:51:55', '2025-10-10 15:26:00', 10.00),
(26, 21, 2, 'Buko Pie', 'A classic Filipino delicacy with a distinct Batangas presence.', 290.00, 500, 'uploads/products/prod_6883c4c7c507e_1000002971.jpg', 'Active', '2025-07-25 17:54:15', '2025-10-27 01:04:35', 10.00),
(27, 21, 1, 'Beef Tapa', 'Marinated beef tapa, a breakfast staple, made from local Batangas beef.', 280.00, 91, 'uploads/products/prod_6883c5ad2d137_1000002972.jpg', 'Active', '2025-07-25 17:58:05', '2025-10-28 22:46:54', 10.00),
(28, 21, 3, 'Embroidered Barong Tagalog/Baro\\\'t Saya Miniatures', 'Small, decorative versions of the traditional Filipino attire, showcasing Taal\\\'s famous embroidery.', 180.00, 495, 'uploads/products/prod_6883c630031ff_1000002973.jpg', 'Active', '2025-07-25 18:00:16', '2025-10-28 22:58:35', 10.00),
(29, 21, 3, 'Handcrafted Balisong/Fan Replicas', 'Miniature, safe replicas of the iconic Batangas balisong (butterfly knife) or beautifully crafted hand fans.', 200.00, 500, 'uploads/products/prod_6883c6b03a2b2_1000002974.jpg', 'Active', '2025-07-25 18:02:24', '2025-07-25 18:02:24', 10.00),
(31, 21, 3, 'Local Artisan Pottery/Ceramics', 'Small, decorative or functional pottery pieces from local Batangas potters.', 300.00, 199, 'uploads/products/prod_6883c7973db11_1000002976.jpg', 'Active', '2025-07-25 18:06:15', '2025-10-29 02:44:18', 10.00),
(32, 21, 2, 'Kapeng Barako (Ground/Beans)', 'The strong, aromatic coffee varietal, a staple of Batangas.', 250.00, 498, 'uploads/products/prod_6883c81163bae_1000002977.jpg', 'Active', '2025-07-25 18:08:17', '2025-07-30 08:22:28', 10.00),
(33, 21, 2, 'Batangas Brewed Tea Blends', 'Locally sourced herbal or fruit-infused teas unique to the region (e.g., calamansi ginger tea blend).', 180.00, 96, 'uploads/products/prod_6883c88f2944c_1000002978.jpg', 'Active', '2025-07-25 18:10:23', '2025-11-01 04:08:06', 10.00),
(34, 21, 2, 'Tsokolate Ah (Traditional Hot Chocolate Mix)', 'A ready-to-mix powder or concentrated paste for making authentic Filipino hot chocolate.', 230.00, 20, 'uploads/products/prod_6883c8fb780ce_1000002979.jpg', 'Active', '2025-07-25 18:12:11', '2025-10-27 16:38:12', 10.00),
(36, 34, 2, 'Vietnamese', 'Drip-based Coffee', 120.00, 9, 'uploads/products/prod_6889ccc9c7760_1000021199.jpg', 'Pending', '2025-07-30 07:42:01', '2025-10-28 06:00:57', 10.00),
(37, 21, 1, 'Lomi', 'Lomi', 50.00, 99, 'uploads/products/prod_6889d6ba7c0e2_1000002967.jpg', 'Active', '2025-07-30 08:24:26', '2025-10-27 00:58:24', 10.00),
(39, 21, 3, 'Ala EH!', 'Shirt', 150.00, 196, 'uploads/products/68bfb472264af_prod_6883c72bab59a_1000002975.jpg', 'Active', '2025-09-09 05:00:34', '2025-10-29 03:54:53', 10.00),
(40, 21, 1, 'Luis Fries ', 'various flavors of fries', 100.00, 49, 'uploads/products/68c7a404b44ce_belgian-frit-shop-french-fries-in-antwerp-belgium-BB40M0.jpg', 'Active', '2025-09-15 05:28:36', '2025-10-29 10:36:47', 10.00),
(44, 21, 1, 'Ala EH!', '12', 12.00, 119, 'uploads/products/68fbb58f2d8e6_Screenshot_2025-10-18_094556-removebg-preview.png', 'Active', '2025-10-24 17:21:19', '2025-10-31 04:21:23', 10.00),
(46, 21, 1, 'Coffee', 'Cofffee', 120.00, 99, 'uploads/products/68ff6b52d5c6e_batangas-coffee.png', 'Pending', '2025-10-27 12:53:38', '2025-10-27 12:53:38', 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`category_id`, `name`, `description`) VALUES
(1, 'Food', 'Food products like snacks, delicacies, etc.'),
(2, 'Beverages', 'Drinks and local refreshments.'),
(3, 'Souvenirs', 'Gifts and keepsakes from Batangas.');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`review_id`, `order_item_id`, `user_id`, `product_id`, `rating`, `comment`, `review_date`) VALUES
(1, 17, 32, 44, 3, '123', '2025-10-26 21:40:28'),
(2, 4, 32, 40, 4, 'nice', '2025-10-26 23:58:40'),
(3, 22, 32, 33, 4, 'Great Product', '2025-10-27 16:57:39'),
(4, 31, 32, 39, 3, 'ghghgg', '2025-10-29 03:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `report_type` enum('Sales','Engagement','Inventory') DEFAULT NULL,
  `report_content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method_id` int(11) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `user_type` enum('User','Vendor') NOT NULL DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `token_expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone_number`, `address`, `latitude`, `longitude`, `user_type`, `created_at`, `profile_picture`, `otp_code`, `otp_expiry`, `token`, `token_expires_at`) VALUES
(1, NULL, NULL, '$2y$10$ptuTeOq0RidOgWNvD9wBCOfAzQy7Gc.L2qbngjDtZwM.7AkJTUSCO', NULL, NULL, NULL, NULL, 'User', '2025-06-24 23:23:47', NULL, NULL, NULL, NULL, NULL),
(2, NULL, NULL, '$2y$10$Uq9ldAXJX89WMQTdIqpLWe.wGmG2SxygfEhQPF3vQHFJP/M6jDqt.', NULL, NULL, NULL, NULL, 'User', '2025-06-24 23:24:08', NULL, NULL, NULL, NULL, NULL),
(3, 'gilbs', 'gilbs@email.com', '$2y$10$TpxQ.DH3rh.ALB3zMhRCNODZkCJZZHR/WwiH25RZhuPxlD0MlGd1m', '094324234434', '831231', NULL, NULL, 'User', '2025-06-24 23:26:30', NULL, NULL, NULL, NULL, NULL),
(4, 'gilbs', 'gilbsssss@email.com', '$2y$10$hdACHibda5FYADw20GfjNOLl2P2QkJinWcrxF82yr/3FZCo2DThuq', '0912121', '121', NULL, NULL, 'User', '2025-06-24 23:48:58', NULL, NULL, NULL, NULL, NULL),
(5, 'euli', 'gils@email.com', '$2y$10$.OpDgKNSKj3okeELVZ8eS..FCHbte6KdIkVJrSqRIPyLxboQSShxy', '098753946464548', 'qwert', NULL, NULL, 'Vendor', '2025-07-01 07:18:41', 'profile_1751471691_6675.jpg', NULL, NULL, NULL, NULL),
(6, 'Ayessa Enderez', 'ayessa@email.com', '$2y$10$pDE4SCWzW1NeoHFLULNsuO2qcA.Vf95jG48X43A.fp7Ur0n9UgAj6', '09271182541', 'Latag,Lipa City', NULL, NULL, 'User', '2025-07-01 07:20:16', NULL, NULL, NULL, NULL, NULL),
(7, 'ashton lemmor', 'lemmor@email.com', '$2y$10$o8Snb0gGktwlP.rxTCZ45eKn8/Fc8PTzLbqO2L81oaRk3o/3nwXaa', '09602714274', 'Banaba, Padre Garcia', NULL, NULL, 'User', '2025-07-01 07:31:39', NULL, NULL, NULL, NULL, NULL),
(8, 'Gilbert', 'gilbs01@gmail.com', '$2y$10$tYLJo64wRAcDsWq0nVP2zO2yIwPSL2/mq7s/BRSfo008Wk5N5gAoO', '09173633', 'bahay ko', NULL, NULL, 'User', '2025-07-01 08:37:48', NULL, NULL, NULL, NULL, NULL),
(9, 'CHRISTIAN LUIS', 'luis@email.com', '$2y$10$lDf/vY8xervr3Qglrs/KDOHhVfXTBkM9b2cUNdPp1S0CVZ.TMUYhS', '09602718018', 'hehejwj', NULL, NULL, 'User', '2025-07-01 08:42:15', NULL, NULL, NULL, NULL, NULL),
(10, 'loy', 'l@gmail.com', '$2y$10$C/WWGfHPXGkrzezcDAzEmO8r1GQBwFKq0ILZm24dWGmETrecf5cB2', '898988', 'hdjd', NULL, NULL, 'User', '2025-07-01 09:03:06', NULL, NULL, NULL, NULL, NULL),
(11, 'Euli', 'euli.ganda@gmail.com', '$2y$10$1zBn/2YSvEQQmkT048Ss..BqIWOz4vUXa/KOKaWJGJYbJLSnUlr2G', '09373736277272', 'San Pablo', NULL, NULL, 'User', '2025-07-01 14:49:02', NULL, NULL, NULL, NULL, NULL),
(12, 'euli', 'euli@gmail.com', '$2y$10$.wC7e6XUUdtjLcOZZthxB.pDqJJy9.zCnIp.x.vCnmn7e8iRXUAnC', '096830722', 'San Pablo', NULL, NULL, 'User', '2025-07-01 17:11:17', NULL, NULL, NULL, NULL, NULL),
(13, 'my euli', 'euliganda@gmail.com', '$2y$10$aW9GQCwSJlWqUycTrjYvFOjft1ngasRPhoMzy8tOB6xbujOJypqfK', '02928826', 'hwywywyw', NULL, NULL, 'Vendor', '2025-07-02 17:13:43', 'profile_1751476580_3110.jpg', NULL, NULL, NULL, NULL),
(14, 'ganda', 'ganda@gmail.com', '$2y$10$taNQwhDCKOBt2o1KGmuHiOS9o89gGKQf.5xPwwvZDc3uGaoxGebgu', '092625252', 'gsgsvsvdvd', NULL, NULL, 'User', '2025-07-02 20:10:55', 'profile_1751487103_3826.jpg', NULL, NULL, NULL, NULL),
(15, 'Gilbert Saludaga', 'gilbs09@gmail.com', '$2y$10$TddJ5Mcrb4wgBbjTpONrVusKNusHleRzPXBhaKv.ua3ESQ22p8h7.', '09683074206', 'Lipa', NULL, NULL, 'User', '2025-07-03 14:00:19', 'profile_1751551271_6000.jpg', NULL, NULL, NULL, NULL),
(17, 'gilbert', 'sikret', '$2y$10$2I3Yg76wC8iHD9bOR3NGheltZa7BDSJCk26pXZ7d4Pzdv5m4tdTRO', '07262820', 'Brgy', NULL, NULL, 'Vendor', '2025-07-04 17:41:53', 'profile_1751650982_8967.png', NULL, NULL, NULL, NULL),
(18, 'Erol Jake Anillo', 'anillo123@gmail.com', '$2y$10$bdAZgNucK/QyWxa0999CJubvullrDq0gCpwtf6uq1Ral1Q/V7ieWi', '0999111326748', 'sa tabi st. tanauan batangas', NULL, NULL, 'Vendor', '2025-07-05 12:18:47', 'profile_1751717969_4272.jpg', NULL, NULL, NULL, NULL),
(19, '', '', '$2y$10$9tF8BAJzeJGgLYzJdXkEduIKkpJm0htUIOq7lOkzawrm8KoXR6EzC', '', '', NULL, NULL, 'User', '2025-07-08 06:57:10', NULL, NULL, NULL, NULL, NULL),
(21, 'Gilbert', 'gilbert@gmail.com', '$2y$10$V80v5ua./ms8XFo9RAJ7pOPe/ADyGR7PwdIAqC4jGwFpElDxL5Ex6', '094324234434', '123', NULL, NULL, 'User', '2025-07-08 10:18:38', NULL, NULL, NULL, NULL, NULL),
(25, 'hilss', 'hils@email.com', '$2y$10$Lo3hps4eKQJo3pgECcaz4OwE.VpVZKXmVNT4Bo.yD7DZcv44tQ4eO', '+639683074206', 'qrtw', NULL, NULL, 'User', '2025-07-15 14:14:33', NULL, NULL, NULL, NULL, NULL),
(32, 'Gilbert Saludaga', 'gilbertsaludaga89@gmail.com', 'bd4076099b2f094ce16531ccb09b8b034f17e2950f1452ef4dd3b2e0b8a04f09', '639683074201', 'Mabini Homes, 7, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, NULL, 'Vendor', '2025-07-16 04:25:23', 'profile_1753813811_6601.webp', NULL, NULL, NULL, NULL),
(33, 'Flor Saludaga', 'florsaludaga1968@gmail.com', '9402f5e7d24f0a786bfb270fd0b695cd4520311c4195c98d895a3b81b1e516c5', '+63 961 880 3530', '6, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, NULL, 'Vendor', '2025-07-25 19:18:05', 'profile_1753841024_1093.webp', '147695', '2025-07-29 20:44:37', NULL, NULL),
(34, 'Christian Luis Hiwatig', 'christian.loowis@gmail.com', '04e92d7920d707fdc4594109105406aea3383c0295efa31507c8a705742f4ded', '+63 960 271 8018', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, NULL, 'User', '2025-07-30 00:57:06', NULL, NULL, NULL, NULL, NULL),
(35, 'Alexandra Añonuevo', 'alexandraannonuevo@gmail.com', 'b00b2139ef336e0db552e8129c8a35e5e77452d62007bb21d7ebb2cc18edd730', '+63 951 965 7113', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, NULL, 'User', '2025-07-30 06:27:05', NULL, NULL, NULL, NULL, NULL),
(36, 'Aries Asi', 'ariescanubasasi@gmail.com', '7272f69f53ed53b664bacb18823893482e25bf2051d33e9aafea9fc7e22f1a9d', '+63 960 271 8018', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, NULL, 'User', '2025-07-30 08:21:06', NULL, NULL, NULL, NULL, NULL),
(37, 'Gilbert Saludaga', 'gilbs1@email.com', '$2y$10$3HPmDUVMmADY12YQCODLFO8UQNVTFaWlFDXOfnW2UI5SY3kBfOXky', '094324234434', 'Marichu R. Tiñga Avenue, Pinagsama, Taguig District 2, Taguig, Southern Manila District, Metro Manila, 1630, Philippines', NULL, NULL, 'User', '2025-09-02 05:16:32', NULL, NULL, NULL, NULL, NULL),
(38, 'Gilbert Saludaga', 'gilbertsaludaga9@gmail.com', '$2y$10$GFYBw2dr8j.sZmLMTzshTunuOMrt0vYu8NkWaRMM/CSyXwrXyRDYC', '0968312312', 'Mabini Homes, 7, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, NULL, 'User', '2025-09-09 00:50:04', 'uploads/68bf79e8d48e8_f76fbe32-29fd-4499-966a-83dd7409ee4f.jpg', NULL, NULL, NULL, NULL),
(39, 'Ysa', 'ayessacamitan04@gmail.com', 'd21434764b72996a12233c4f4975d5e4201c6ee4a6b2d3dcda0c284004509106', '09271182541', '30, Lieutenant Colonel D. Atienza Street, Barangay 8, Poblacion, Batangas City, Batangas, Calabarzon, 4200, Philippines', NULL, NULL, 'User', '2025-09-15 05:22:49', NULL, NULL, NULL, NULL, NULL),
(40, 'Buko Pie', 'gilbertsaludaga1@gmail.com', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', '0968312312', 'Lat: 13.937899, Lng: 121.162977', NULL, NULL, 'User', '2025-09-15 05:42:27', NULL, NULL, NULL, NULL, NULL),
(41, 'ysa', 'enderezayessa06@gmail.com', '21582e66715d6250eea18bf409b6b2a959040af3329783147b7315fcea437c00', '09271182541', 'McDonald\'s, 12, P. Burgos Street, Barangay 9, Poblacion, Batangas City, Batangas, Calabarzon, 4200, Philippines', NULL, NULL, 'User', '2025-09-15 08:39:28', NULL, NULL, NULL, NULL, NULL),
(42, 'Ayessa Jean Enderez', '21-37568@g.batstate-u.edu.ph', '6ea0766856302d0813fb30255e853ff86e0fee0e202573ccf6a41d317ffc1c58', '09271182541', 'McDonald\'s, 12, P. Burgos Street, Barangay 9, Poblacion, Batangas City, Batangas, Calabarzon, 4200, Philippines', NULL, NULL, 'User', '2025-09-29 05:47:18', NULL, NULL, NULL, NULL, NULL),
(44, 'Gilbert Saludaga', 'gilbs2@email.com', '$2y$10$wAAJ132BxdCsJlhoqnXFh.WyffrIZKVtw8BGHt4EmXfWVs7eMXNqq', '+63 912 312 3123', 'Poblacion, Lipa, Batangas', 13.93920000, 121.15972900, 'User', '2025-10-01 16:00:29', NULL, NULL, NULL, NULL, NULL),
(45, 'Gilbert Saludaga', 'gilbertsaludaga891@gmail.com', '1d85b931be62564fb1832c5cf39d7253c4fb9a675880fa3e03557010d939aed4', '+63 901 231 2312', 'Poblacion, Lipa, Batangas', 13.93920000, 121.15972900, 'User', '2025-10-01 16:06:27', NULL, NULL, NULL, NULL, NULL),
(48, 'Beef Tapas', 'liyagbatangan@gmail.com', '35c7d48f115592050b442c8ab9569349e705e7fb570d86b4bfe96549ca202174', '639683074212', '6, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', 13.93906600, 121.15979300, 'User', '2025-10-13 21:34:53', 'uploads/profile_68f9206661864_4_Image.jpg', NULL, NULL, NULL, NULL),
(49, 'Gilbert Saludaga', '22-37092@g.batstate-u.edu.ph', 'bd4076099b2f094ce16531ccb09b8b034f17e2950f1452ef4dd3b2e0b8a04f09', '+63 912 312 3121', 'Unitop Building 5, Nyugan Road, Paliparan 1, Paliparan, Dasmariñas, Cavite, Calabarzon, 4114, Philippines', 14.28694600, 120.98734600, 'User', '2025-11-01 13:58:42', NULL, NULL, NULL, NULL, NULL),
(50, 'Erol Jake Anillo', 'eroljakeanillo@gmail.com', '7f8a7fcbf0bb1d3db764c52979602b793a258bf83ff10b4500f5dd9da51cb59d', '+63 913 131 3131', 'East Boulevard, Nostalji Enclave, Paliparan 1, Paliparan, Dasmariñas, Cavite, Calabarzon, 4114, Philippines', 14.28684800, 120.98601000, 'User', '2025-11-01 14:02:33', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `full_address` text NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `label`, `full_address`, `contact_number`, `latitude`, `longitude`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 32, 'Home', '6, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, 13.93905050, 121.15980900, 1, '2025-10-23 21:36:07', '2025-10-23 21:36:07'),
(2, 32, 'Apartment', 'Kalipulako Extension, Mabini Homes, 7, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, 13.93654549, 121.15865314, 0, '2025-10-23 21:39:02', '2025-10-23 21:39:02'),
(3, 32, 'car', 'San Jose, Tangob, Lipa, Batangas, Calabarzon, 4217, Philippines', NULL, 13.94046660, 121.19394500, 0, '2025-10-28 20:08:12', '2025-10-28 20:08:12'),
(4, 32, 'Home', 'Chipeco Avenue Extension, Real, Calamba, Laguna, Calabarzon, 4027, Philippines', NULL, 14.19487620, 121.15973640, 0, '2025-10-29 03:54:28', '2025-10-29 03:54:28'),
(5, 32, 'adsa', 'Mando &#38; Elvie&#39;s Lutong Bahay, Maharlika Highway, San Miguel, Santo Tomas, Batangas, Calabarzon, 4234, Philippines', NULL, 14.09829529, 121.15999460, 0, '2025-10-31 04:20:59', '2025-10-31 04:20:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_tokens`
--

INSERT INTO `user_tokens` (`user_id`, `token`, `created_at`) VALUES
(32, '660a98180c9bf2a3244bb689f6ff9417bb291684a812cf57d69584c4948a125c', '2025-07-30 15:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `vendor_account`
--

CREATE TABLE `vendor_account` (
  `vendor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_document` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `average_rating` decimal(2,1) DEFAULT NULL,
  `total_reviews` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendor_account`
--

INSERT INTO `vendor_account` (`vendor_id`, `user_id`, `business_name`, `business_address`, `business_description`, `logo_url`, `latitude`, `longitude`, `registration_date`, `verification_document`, `status`, `rejection_reason`, `average_rating`, `total_reviews`) VALUES
(21, 32, 'Batangan Pasalubong', 'V. Malabanan Street, 4, Poblacion, Lipa, Batangas, Calabarzon, 4217, Philippines', 'Batangan Pasalubong is your one-stop destination for the finest local treats and treasures from Batangas. Proudly showcasing the province’s rich culture and flavors, we offer a curated selection of authentic pasalubong items—from classic delicacies like kapeng barako, panutsa, tamales, and sinaing na tulingan to handcrafted souvenirs and artisanal goods. Whether you\\\'re a traveler, a balikbayan, or a local supporter, Batangan Pasalubong brings you closer to home with every bite and keepsake.', 'uploads/business_logos/6883c335abc59_1000002968.jpg', 13.94243900, 121.16039200, '2025-07-25 16:00:00', 'uploads/business_documents/6883c335abc5e_1000002967.jpg', 'Approved', NULL, 0.0, 0),
(25, 33, 'Coffee', 'M. K. Lina Street, San Sebastian, Lipa, Batangas, Calabarzon, 4217, Philippines', 'Coffee', 'uploads/business_logos/6888ff4732f4b_1000002979.jpg', NULL, NULL, '2025-07-29 16:00:00', 'uploads/business_documents/6888ff4732f51_1000002979.jpg', 'Rejected', 'as', 0.0, 0),
(34, 33, 'Batangas Harvest', 'Batangas State University Claro M. Recto Campus, Arturo Tanco Drive, Marauoy, Lipa, Batangas, Calabarzon, 4217, Philippines', 'simple', 'uploads/business_logos/688970423e526_1000003042.jpg', 13.95674600, 121.16325600, '2025-07-29 16:00:00', 'uploads/business_documents/688970423e576_1000003042.jpg', 'Approved', NULL, 0.0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_account`
--
ALTER TABLE `admin_account`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `thread_id` (`thread_id`),
  ADD KEY `sender_user_id` (`sender_user_id`);

--
-- Indexes for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD PRIMARY KEY (`thread_id`),
  ADD UNIQUE KEY `uc_customer_vendor_thread` (`customer_user_id`,`vendor_user_id`),
  ADD KEY `vendor_user_id` (`vendor_user_id`);

--
-- Indexes for table `customer_account`
--
ALTER TABLE `customer_account`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `email_verification`
--
ALTER TABLE `email_verification`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_address` (`delivery_address_id`);

--
-- Indexes for table `order_app`
--
ALTER TABLE `order_app`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `vendor_id` (`vendor_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_item_id` (`order_item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `vendor_account`
--
ALTER TABLE `vendor_account`
  ADD PRIMARY KEY (`vendor_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_item`
--
ALTER TABLE `cart_item`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `chat_threads`
--
ALTER TABLE `chat_threads`
  MODIFY `thread_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `order_app`
--
ALTER TABLE `order_app`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vendor_account`
--
ALTER TABLE `vendor_account`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_account`
--
ALTER TABLE `admin_account`
  ADD CONSTRAINT `admin_account_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `cart_item`
--
ALTER TABLE `cart_item`
  ADD CONSTRAINT `cart_item_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `shopping_cart` (`cart_id`),
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`thread_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_threads`
--
ALTER TABLE `chat_threads`
  ADD CONSTRAINT `chat_threads_ibfk_1` FOREIGN KEY (`customer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_threads_ibfk_2` FOREIGN KEY (`vendor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_account`
--
ALTER TABLE `customer_account`
  ADD CONSTRAINT `customer_account_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_address` FOREIGN KEY (`delivery_address_id`) REFERENCES `user_addresses` (`address_id`),
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_app`
--
ALTER TABLE `order_app`
  ADD CONSTRAINT `order_app_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendor_account` (`vendor_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`),
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `product_reviews_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_account` (`admin_id`);

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer_account` (`customer_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_account`
--
ALTER TABLE `vendor_account`
  ADD CONSTRAINT `vendor_account_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
