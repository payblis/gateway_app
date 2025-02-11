-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2025 at 12:41 PM
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
(1, 'OVRI-20250128095419-67989b3b4c98e', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"xy2n11m1213n1\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"Customer_FirstName\":\"John\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"f9bc4560-6c25-42a1-b5dc-38484460592f\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128095419-67989b3b4c98e\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"09:54:24\",\"authorization\":\"000000\",\"archive\":\"71SLW8AMGHTN\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 08:54:23', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(2, 'OVRI-20250128095629-67989bbd6137a', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"qpr1\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"Customer_FirstName\":\"John\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"d9bd75cb-85ce-480d-8089-825661d39a79\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128095629-67989bbd6137a\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"09:56:34\",\"authorization\":\"000000\",\"archive\":\"CVJG9XBEOAHD\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 08:56:32', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(3, 'OVRI-20250128100132-67989cec96429', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"qpr212\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"Customer_FirstName\":\"John\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"b3a1fcf9-59cd-479f-abc1-5657d791a8cf\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128100132-67989cec96429\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"10:01:37\",\"authorization\":\"000000\",\"archive\":\"EDPVZ5CYB0BR\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 09:01:55', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(4, 'OVRI-20250128102703-6798a2e7caf93', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"q1pr212\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"Customer_FirstName\":\"John\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"b21f1d41-1ae7-4faf-818d-5656bd23260b\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128102703-6798a2e7caf93\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"10:27:09\",\"authorization\":\"000000\",\"archive\":\"BETEEBMYQKOX\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 09:27:07', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(5, 'OVRI-20250128115353-6798b741ef0b5', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"q1pr22212\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"5132af59-4701-4876-8d22-fec75eb560b6\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128115353-6798b741ef0b5\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"11:53:59\",\"authorization\":\"000000\",\"archive\":\"SJNXZSPZAMDD\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 10:54:01', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(6, 'OVRI-20250128121420-6798bc0c04115', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"pqr1231\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"b4acfe68-9dbf-4cdb-9dc5-c78bd16f3ea3\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128121420-6798bc0c04115\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"12:14:24\",\"authorization\":\"000000\",\"archive\":\"5E8ZB2NCEDHA\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 11:14:28', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(7, 'OVRI-20250128122205-6798bdddeee37', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"pqr124\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"1b45318e-67dc-4439-97a4-bfa74ebc02c6\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128122205-6798bdddeee37\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"12:22:10\",\"authorization\":\"000000\",\"archive\":\"NN4S6VSUIKFP\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 11:22:10', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(8, 'OVRI-20250128123044-6798bfe4bd9e3', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"pqr1224\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"1983ac63-3aac-472e-8ec1-67859e0d3247\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128123044-6798bfe4bd9e3\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"12:30:49\",\"authorization\":\"000000\",\"archive\":\"G1ECP8FWTIYT\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 11:30:50', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(9, 'OVRI-20250128131934-6798cb56e6569', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"pqr2122213214\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"bd1445be-33b3-4a3f-acd2-971e527f5951\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128131934-6798cb56e6569\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"13:19:40\",\"authorization\":\"000000\",\"archive\":\"GKJEHPR8TJPV\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 12:19:41', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR'),
(10, 'OVRI-20250128134126-6798d076b8236', 'Direct', '{\"merchantApi\":\"UAO3s6DzlN5ruNsCZSybA8refSOGEweB\",\"amount\":\"123\",\"RefOrder\":\"pqr2122123214\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"97c136d3-e1d7-41b1-b4bc-f8f640dfc46e\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250128134126-6798d076b8236\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-28\",\"time\":\"13:41:32\",\"authorization\":\"000000\",\"archive\":\"9U8GIDOG60RV\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-28 12:41:32', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB'),
(11, 'OVRI-20250129081506-6799d57adf9fb', 'Direct', '{\"merchantApi\":\"YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR\",\"amount\":\"123\",\"RefOrder\":\"p\",\"cardHolderName\":\"John Doe\",\"cardHolderEmail\":\"example@gmail.com\",\"cardno\":\"4929251897047956\",\"edMonth\":\"12\",\"edYear\":\"25\",\"cvv\":\"123\",\"customerIP\":\"192.168.1.1\",\"Customer_Phone\":\"33123456789123\",\"firstname\":\"John\",\"country\":\"Pakustan\",\"lang\":\"en\",\"urlOK\":\"http:\\/\\/localhost\\/DitechPay\\/merchant\\/paymentSuccess.php\",\"urlKO\":\"https:\\/\\/www.whatsapp.co.uk\\/\"}', '{\"code\":\"success\",\"ThreedType\":\"friction\",\"HighRisk\":false,\"threesecure\":{\"status\":\"Y\",\"acsTransId\":\"dca51050-8f76-4a53-99c9-c2ecaaedc38a\",\"warranty_success\":true,\"warranty_details\":\"100% liability transfer to the bank of the holder\"},\"status\":\"APPROVED\",\"redirectomerchant\":false,\"TransactionId\":\"OVRI-20250129081506-6799d57adf9fb\",\"Captured\":true,\"receipt\":{\"date\":\"2025-01-29\",\"time\":\"08:15:12\",\"authorization\":\"000000\",\"archive\":\"LN6CP5B4IW11\",\"arn\":\"123456\",\"three\":true,\"cardbrand\":\"VISA\",\"cardpan\":\"492925-----7956\",\"amount\":\"123,00 EUR\",\"amountFRF\":\"806,83 FRF\",\"merchantName\":\"UNIVERSAL GAMING\",\"merchantZip\":\"76480\",\"merchantCity\":\"YAINVILLE\",\"merchantCountry\":\"FRA\",\"merchantBrandName\":\"UNLIKD DEV\",\"merchantLink\":\"https:\\/\\/unlikd.com\",\"merchantmid\":\"2991\",\"simulation\":true}}', 200, '2025-01-29 07:15:12', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR');

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
(30, 'John', 'customer@email.com', 'Doe', 15.00, 'Pakistan', 'paid', '2025-01-30 14:40:16', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB');

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
(1, 'admin', '$2y$10$LdUalY.4HftlHKF7CJmLV.Q.vJk1HwaAUgXkR6br7XldJRrBSTQCm', 'admin', '', 1),
(14, 'Test', '$2y$10$NczKFclrYRNipmmDfcd4QezOkoCjZl7T54Ui1qE8DCQfj/wgX0ESC', 'merchant', 'YOZu0LyMiriW5AXXmOh5PMpcwrUMIiqR', 1),
(15, 'merchant', '$2y$10$WGw0Ywp7dN9Gk5Ia0rQ//OllRb..6NMEJqythWjmo4npkDyP4ct4G', 'merchant', 'UAO3s6DzlN5ruNsCZSybA8refSOGEweB', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ovri_logs`
--
ALTER TABLE `ovri_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`token`) REFERENCES `users` (`api_key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
