-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 01, 2025 at 03:05 PM
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
-- Database: `ditechpay`
--

-- --------------------------------------------------------

--
-- Table structure for table `ovri_logs`
--

CREATE TABLE `ovri_logs` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `request_type` varchar(50) NOT NULL,
  `request_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`request_body`)),
  `response_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`response_body`)),
  `http_code` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `token` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ovri_logs`
--

INSERT INTO `ovri_logs` (`id`, `transaction_id`, `request_type`, `request_body`, `response_body`, `http_code`, `created_at`, `token`) VALUES
(1, 'OVRI-20250201131957-679e116dc714f', 'via card', '{\"MerchantKey\":\"wcEP8J0OQ7QHwl416qrc3eAIXQ6lOoSG\",\"amount\":\"15.00\",\"RefOrder\":\"re11f782\",\"Customer_Email\":\"customer@email.com\",\"Customer_Phone\":\"33123456789123\",\"Customer_Name\":\"John\",\"Customer_FirstName\":\"Doe\",\"country\":\"France\",\"userIP\":\"192.168.1.1\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/payblis\\/paymentSuccess.php\",\"urlKO\":\"http:\\/\\/localhost\\/payblis\\/paymentFailed.php\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"80309ea4-b7a8-467e-8b7b-8947d418b951\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250201131957-679e116dc714f\",\"Captured\":true,\"receipt\":{\"date\":\"2025-02-01\",\"time\":\"13:20:03\",\"authorization\":\"000000\",\"archive\":\"AG09XU0PGC0Y\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"424242-----4242\",\"amount\":\"15,00 EUR\",\"amountFRF\":\"98,39 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-02-01 12:20:03', 'c1LOsEW1wvFMnK0r9lWxend5VItyCLOm');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `country` varchar(50) NOT NULL,
  `status` enum('pending','paid','failed','refunded') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `token` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `name`, `email`, `first_name`, `amount`, `country`, `status`, `created_at`, `token`) VALUES
(4, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-28 11:22:10', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(5, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-28 11:22:10', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(6, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-28 11:22:10', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(7, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'pending', '2025-01-28 11:31:57', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(8, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-28 11:22:10', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(9, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-28 11:30:50', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(10, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'pending', '2025-01-28 11:32:09', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(11, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'pending', '2025-01-28 11:32:52', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(12, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-28 12:19:41', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(15, 'John Doe', 'example@gmail.com', 'John', 123.00, 'Pakustan', 'paid', '2025-01-29 07:15:12', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(16, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 11:26:23', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(17, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 11:26:58', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(18, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 11:27:38', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(19, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 11:27:57', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(20, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 11:38:14', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(21, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 12:30:09', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(22, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 12:58:09', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(23, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 13:08:45', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(24, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 13:15:34', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(25, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 13:18:46', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(26, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 13:45:27', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(27, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 14:30:39', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(28, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'pending', '2025-01-30 14:33:41', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(29, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'failed', '2025-01-30 14:39:36', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(30, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'paid', '2025-01-30 14:40:16', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(31, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:07:09', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(32, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:09:49', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(33, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:16:42', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(34, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:16:52', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(35, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:22:20', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(36, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:30:45', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(37, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:31:18', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(38, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'pending', '2025-02-01 10:32:09', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(39, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'failed', '2025-02-01 10:39:04', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(40, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'failed', '2025-02-01 10:43:25', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(41, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'paid', '2025-02-01 10:56:18', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(42, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'paid', '2025-02-01 11:56:46', 'c1LOsEW1wvFMnK0r9lWxend5VItyCLOm'),
(43, 'John', 'customer@email.com', 'Doe', 15.00, 'France', 'paid', '2025-02-01 12:20:03', 'c1LOsEW1wvFMnK0r9lWxend5VItyCLOm');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'merchant',
  `api_key` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `api_key`, `status`) VALUES
(1, 'admin', '$2y$10$eERVTc4y4BAi0WPwvZr9Vukb7rHFUtpGQce63DZ53B7C35r7T9HUq', 'admin', '', 1),
(14, 'Test', '$2y$10$NczKFclrYRNipmmDfcd4QezOkoCjZl7T54Ui1qE8DCQfj/wgX0ESC', 'merchant', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR', 1),
(15, 'merchant', '$2y$10$WGw0Ywp7dN9Gk5Ia0rQ//OllRb..6NMEJqythWjmo4npkDyP4ct4G', 'merchant', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB', 1),
(16, 'user', '$2y$10$d05CIUbwmXlEFYUlT/qXmu/Zra03zH0m5phSecpF/Tsep8wR7e7n6', 'merchant', 'c1LOsEW1wvFMnK0r9lWxend5VItyCLOm', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ovri_logs`
--
ALTER TABLE `ovri_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ovri_logs_ibfk_1` (`token`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_ibfk_1` (`token`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `api_key` (`api_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ovri_logs`
--
ALTER TABLE `ovri_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ovri_logs`
--
ALTER TABLE `ovri_logs`
  ADD CONSTRAINT `ovri_logs_ibfk_1` FOREIGN KEY (`token`) REFERENCES `users` (`api_key`) ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`token`) REFERENCES `users` (`api_key`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
