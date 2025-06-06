-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 05:28 AM
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
-- Database: `truckingsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts_payable`
--

CREATE TABLE `accounts_payable` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts_payable`
--

INSERT INTO `accounts_payable` (`id`, `title`, `description`, `amount`, `due_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'gass', 'expenses', 1000.00, '2025-05-10', 'Paid', '2025-05-10 15:12:57', '2025-05-10 15:24:24');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_roles`
--

CREATE TABLE `announcement_roles` (
  `announcement_id` int(11) NOT NULL,
  `role` enum('dispatcher','admin','bookkeeper','driver') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`) VALUES
(1, 'Gas'),
(2, 'Maintenance'),
(3, 'Utilities'),
(4, 'Supplies'),
(5, 'Other');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `delivery_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `status` varchar(100) DEFAULT NULL,
  `dispatch_date` date DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`delivery_id`, `product_name`, `status`, `dispatch_date`, `driver_id`) VALUES
(1, 'sugar', 'Delivered', '2025-05-20', 4),
(2, 'rice', 'Delivered', '2025-05-23', 4),
(3, 'Gulong', 'Pending', '2025-05-23', 4);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `FullName` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `FullName`, `address`, `contact_number`) VALUES
(1, 'earl jade tumanda', 'PUROK Faustino Javier', '098765432112'),
(2, 'Alyana Pon-an', 'alnamang', '908983787');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date_added` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_records`
--

CREATE TABLE `finance_records` (
  `id` int(11) NOT NULL,
  `type` enum('Income','Expense') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `date` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `finance_records`
--

INSERT INTO `finance_records` (`id`, `type`, `description`, `amount`, `date`) VALUES
(1, 'Income', 'howling', 1689.00, '2025-05-10'),
(2, 'Income', 'adasd', 10233.00, '2025-05-15');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_address` varchar(255) DEFAULT NULL,
  `business_style` varchar(100) DEFAULT NULL,
  `osca_pwd_id` varchar(50) DEFAULT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `terms` varchar(100) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'PHP',
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_percent` decimal(5,2) NOT NULL DEFAULT 12.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `customer_name`, `customer_address`, `business_style`, `osca_pwd_id`, `tin`, `invoice_date`, `terms`, `currency`, `subtotal`, `vat_percent`, `vat_amount`, `discount`, `total_amount`, `status`, `payment_method`, `payment_reference`, `payment_date`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV-00001', 'Sample Customer', 'Angeles City', 'Sample Business', NULL, NULL, '2023-05-15', 'Due on receipt', 'PHP', 1000.00, 12.00, 120.00, 0.00, 1120.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-13 02:33:06', NULL),
(2, 'INV-00002', 'Tayo Trucking Services', 'Purok Malabarbas Polomolok, South Cotabato', 'Trucking', '000983652', '', '2025-05-13', '', 'PHP', 3001530.00, 12.00, 360183.60, 0.00, 3361713.60, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-13 03:04:52', NULL),
(3, 'INV-00003', 'Tayo Trucking Services', 'Purok Malabarbas Polomolok, South Cotabato', 'Trucking', '000983652', '', '2025-05-13', '', 'PHP', 3001530.00, 12.00, 360183.60, 0.00, 3361713.60, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-13 03:06:10', NULL),
(4, 'INV-00004', 'Tayo Trucking Services', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983652', '', '2025-05-13', '', 'PHP', 60583.00, 12.00, 7269.96, 0.00, 67852.96, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-13 04:30:53', NULL),
(5, 'INV-00005', 'Tayo Trucking Services', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983652', '', '2025-05-13', '', 'PHP', 60583.00, 12.00, 7269.96, 0.00, 67852.96, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-13 04:51:07', NULL),
(6, 'INV-00006', 'Tayo Trucking Services', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983652', '', '2025-05-13', '', 'PHP', 60583.00, 12.00, 7269.96, 0.00, 67852.96, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-13 04:51:19', NULL),
(7, 'INV-00007', 'Tayo Trucking Services', 'asdasd', 'asdad', '000983653', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 466123.00, 12.00, 55934.76, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 03:23:42', NULL),
(8, 'INV-00008', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:36:39', NULL),
(9, 'INV-00009', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:43:56', NULL),
(10, 'INV-00010', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:44:05', NULL),
(11, 'INV-00011', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:44:54', NULL),
(12, 'INV-00012', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:45:07', NULL),
(13, 'INV-00013', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:45:11', NULL),
(14, 'INV-00014', 'dole Philippines ', 'Purok Malabarbas Polomolok, South Cotabato', 'trucking', '000983654', 'asdasdasd', '2025-05-15', 'asd', 'PHP', 5688.00, 12.00, 682.56, 0.00, 0.00, 'pending', NULL, NULL, NULL, NULL, NULL, '2025-05-15 11:45:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_history`
--

CREATE TABLE `invoice_history` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_history`
--

INSERT INTO `invoice_history` (`id`, `invoice_id`, `action`, `performed_by`, `notes`, `created_at`) VALUES
(1, 1, 'created', NULL, 'Invoice created with number INV-00001', '2025-05-13 02:33:06'),
(2, 2, 'created', 2, 'Invoice created with number INV-00002', '2025-05-13 03:04:52'),
(3, 3, 'created', 2, 'Invoice created with number INV-00003', '2025-05-13 03:06:10'),
(4, 4, 'created', 2, 'Invoice created with number INV-00004', '2025-05-13 04:30:53'),
(5, 5, 'created', 2, 'Invoice created with number INV-00005', '2025-05-13 04:51:07'),
(6, 6, 'created', 2, 'Invoice created with number INV-00006', '2025-05-13 04:51:19'),
(7, 7, 'created', 3, 'Invoice created with number INV-00007', '2025-05-15 03:23:42'),
(8, 8, 'created', 2, 'Invoice created with number INV-00008', '2025-05-15 11:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit`, `unit_price`, `amount`, `created_at`) VALUES
(1, 1, 'Trucking service - Manila to Cebu', 1.00, 'Service', 1000.00, 1000.00, '2025-05-13 02:33:06'),
(2, 2, 'howling', 30.00, 'Trip', 100051.00, 3001530.00, '2025-05-13 03:04:52'),
(3, 3, 'howling', 30.00, 'Trip', 100051.00, 3001530.00, '2025-05-13 03:06:10'),
(4, 4, 'howling', 1.00, 'Each', 60583.00, 60583.00, '2025-05-13 04:30:53'),
(5, 5, 'howling', 1.00, 'Each', 60583.00, 60583.00, '2025-05-13 04:51:07'),
(6, 6, 'howling', 1.00, 'Each', 60583.00, 60583.00, '2025-05-13 04:51:19'),
(7, 7, 'howling', 1.00, 'Each', 456123.00, 456123.00, '2025-05-15 03:23:42'),
(8, 7, 'howling', 1.00, 'Each', 10000.00, 10000.00, '2025-05-15 03:23:42'),
(9, 8, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:36:39'),
(10, 9, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:43:56'),
(11, 10, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:44:05'),
(12, 11, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:44:54'),
(13, 12, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:45:07'),
(14, 13, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:45:11'),
(15, 14, 'howling', 1.00, 'Each', 5688.00, 5688.00, '2025-05-15 11:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `truck_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('ongoing','finish') DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `finish_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedule`
--

CREATE TABLE `maintenance_schedule` (
  `id` int(11) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `description` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completion_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_schedule`
--

INSERT INTO `maintenance_schedule` (`id`, `truck_id`, `scheduled_date`, `scheduled_time`, `description`, `created_at`, `completion_date`, `status`) VALUES
(1, 1, '2025-05-11', '11:00:00', 'change tire', '2025-05-11 11:27:28', '2025-05-11', 'Completed'),
(3, 2, '2025-05-15', '11:00:00', 'change tire', '2025-05-15 11:14:24', '2025-05-15', 'Completed'),
(4, 2, '2025-05-22', '00:00:00', 're contract', '2025-05-23 00:33:02', NULL, 'In Progress'),
(5, 1, '2025-05-22', '03:00:00', 'Chasses Repair', '2025-05-23 03:39:53', NULL, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_status`
--

CREATE TABLE `notification_status` (
  `status_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_status` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_status`
--

INSERT INTO `notification_status` (`status_id`, `report_id`, `user_id`, `read_status`, `read_at`) VALUES
(1, 4, 2, 1, '2025-05-22 07:49:10'),
(2, 5, 2, 1, '2025-05-22 07:51:35'),
(3, 3, 3, 1, '2025-05-22 07:59:25'),
(4, 2, 4, 1, '2025-05-22 08:12:19'),
(5, 1, 4, 1, '2025-05-22 08:12:20');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `title`, `content`, `created_by`, `created_at`) VALUES
(1, 'driver', 'nasaan na ang product', 1, '2025-05-20 15:49:17'),
(2, 'driver', 'nasaan na ang product', 1, '2025-05-20 15:49:31'),
(3, 'dispatcher', 'na transfer naba lahat', 1, '2025-05-20 17:31:04'),
(4, 'emilyn', 'gawa ka nang report pasa mo sakin mamaya', 1, '2025-05-22 06:19:17'),
(5, 'report', 'bigyan moko ng mga record ng mga truck indicidually', 1, '2025-05-22 07:51:08'),
(6, 'Reminded', 'Please Status Ng Delivery ng Rice', 1, '2025-05-22 19:33:41'),
(7, 'Reminded', 'Yong gulong kailangan na ma deliver', 1, '2025-05-22 19:36:06');

-- --------------------------------------------------------

--
-- Table structure for table `report_visibility`
--

CREATE TABLE `report_visibility` (
  `visibility_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report_visibility`
--

INSERT INTO `report_visibility` (`visibility_id`, `report_id`, `role`, `user_id`) VALUES
(1, 1, 'driver', NULL),
(2, 2, 'driver', NULL),
(3, 2, NULL, 4),
(4, 3, 'dispatcher', NULL),
(5, 3, NULL, 3),
(6, 4, 'bookkeeper', NULL),
(7, 4, NULL, 2),
(8, 5, 'bookkeeper', NULL),
(9, 5, NULL, 2),
(10, 6, 'dispatcher', NULL),
(11, 6, 'driver', NULL),
(12, 6, NULL, 3),
(13, 6, NULL, 4),
(14, 7, 'dispatcher', NULL),
(15, 7, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `trucks`
--

CREATE TABLE `trucks` (
  `id` int(11) NOT NULL,
  `truck_type` enum('prime mover','bin truck','10 wheeler truck','dump truck') DEFAULT NULL,
  `unit_number` varchar(50) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trucks`
--

INSERT INTO `trucks` (`id`, `truck_type`, `unit_number`, `plate_number`) VALUES
(1, 'dump truck', '135-05', 'mVD765'),
(2, '10 wheeler truck', '135-46', 'mvu8765'),
(3, 'prime mover', '135-07', 'MNB 7876'),
(4, 'bin truck', '135-08', 'NJK 7654'),
(5, '10 wheeler truck', '135-10', 'JKL 7623');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `FirstName` varchar(100) DEFAULT NULL,
  `LastName` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','dispatcher','driver','bookkeeper','auditor','secretary') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `FirstName`, `LastName`, `email`, `password`, `role`) VALUES
(1, 'James', 'Tayo', 'tts135@gmail.com', 'owner135', 'admin'),
(2, 'Emilyn', 'Amaba', 'emilyn@gmail.com', 'bookkeeper123', 'bookkeeper'),
(3, 'earl jade', 'tumanda', 'dispatcher@gmail.com', 'dispatcher123', 'dispatcher'),
(4, 'driver', '1', 'driver1@gmail.com', 'driver123', 'driver'),
(5, 'System', 'Administrator', 'admin@tayo.com', '$2y$10$XnY8O2zPOvPzm7X.qMCMfO9kFIPSjOkb.TlkH2QUOALfGsQaKLWQa', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `announcement_roles`
--
ALTER TABLE `announcement_roles`
  ADD PRIMARY KEY (`announcement_id`,`role`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`delivery_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `finance_records`
--
ALTER TABLE `finance_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `invoice_history`
--
ALTER TABLE `invoice_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `truck_id` (`truck_id`);

--
-- Indexes for table `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `truck_id` (`truck_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_status`
--
ALTER TABLE `notification_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `report_user` (`report_id`,`user_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `report_visibility`
--
ALTER TABLE `report_visibility`
  ADD PRIMARY KEY (`visibility_id`),
  ADD KEY `report_id` (`report_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trucks`
--
ALTER TABLE `trucks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts_payable`
--
ALTER TABLE `accounts_payable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_records`
--
ALTER TABLE `finance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `invoice_history`
--
ALTER TABLE `invoice_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_status`
--
ALTER TABLE `notification_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `report_visibility`
--
ALTER TABLE `report_visibility`
  MODIFY `visibility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `trucks`
--
ALTER TABLE `trucks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcement_roles`
--
ALTER TABLE `announcement_roles`
  ADD CONSTRAINT `announcement_roles_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_history`
--
ALTER TABLE `invoice_history`
  ADD CONSTRAINT `invoice_history_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_history_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance_schedule`
--
ALTER TABLE `maintenance_schedule`
  ADD CONSTRAINT `maintenance_schedule_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_visibility`
--
ALTER TABLE `report_visibility`
  ADD CONSTRAINT `report_visibility_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_visibility_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
