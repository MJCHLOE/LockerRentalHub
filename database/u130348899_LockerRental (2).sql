-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 16, 2025 at 12:32 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u130348899_LockerRental`
--

-- --------------------------------------------------------

--
-- Table structure for table `lockerunits`
--

CREATE TABLE `lockerunits` (
  `locker_id` varchar(10) NOT NULL,
  `size` enum('Small','Medium','Large') NOT NULL,
  `status` enum('Vacant','Occupied','Maintenance','Reserved') NOT NULL,
  `price_per_month` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lockerunits`
--

INSERT INTO `lockerunits` (`locker_id`, `size`, `status`, `price_per_month`) VALUES
('L1', 'Medium', 'Vacant', 100.00),
('L2', 'Small', 'Vacant', 100.00),
('L3', 'Large', 'Vacant', 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `paymentstatus`
--

CREATE TABLE `paymentstatus` (
  `payment_status_id` int(11) NOT NULL,
  `status_name` enum('unpaid','paid') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `paymentstatus`
--

INSERT INTO `paymentstatus` (`payment_status_id`, `status_name`) VALUES
(1, 'unpaid'),
(2, 'paid');

-- --------------------------------------------------------

--
-- Table structure for table `rental`
--

CREATE TABLE `rental` (
  `rental_id` int(11) NOT NULL,
  `user_id` int(5) NOT NULL,
  `locker_id` varchar(10) NOT NULL,
  `rental_date` datetime NOT NULL DEFAULT current_timestamp(),
  `rent_ended_date` datetime DEFAULT NULL COMMENT 'Date when the rental was completed or cancelled',
  `rental_status` enum('pending','approved','active','denied','cancelled','completed') NOT NULL,
  `payment_status_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `rental`
--

INSERT INTO `rental` (`rental_id`, `user_id`, `locker_id`, `rental_date`, `rent_ended_date`, `rental_status`, `payment_status_id`) VALUES
(5, 6, 'L1', '2025-05-09 07:22:52', NULL, 'cancelled', 1),
(6, 6, 'L3', '2025-05-09 08:21:37', NULL, 'cancelled', 1),
(7, 6, 'L1', '2025-05-09 12:15:26', NULL, 'cancelled', 1),
(8, 6, 'L1', '2025-05-09 13:29:03', '2025-05-09 14:33:23', 'completed', 2);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(5) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `entity_type` enum('user','locker','rental','payment','staff','client','admin') NOT NULL,
  `entity_id` varchar(50) NOT NULL,
  `log_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `description`, `entity_type`, `entity_id`, `log_date`) VALUES
(3, 1, 'Edit Locker', 'Updated locker L1 - Size: Medium, Status: Vacant, Price: ₱100.00', 'locker', 'L1', '2025-05-04 21:39:13'),
(4, 1, 'Delete User', 'Deleted Client: Lang Grail', 'user', '4', '2025-05-06 16:14:20'),
(5, 1, 'Add User', 'Added new Client: Gerald Harold (Username: gerald)', 'user', '6', '2025-05-06 16:40:59'),
(6, 1, 'Add Locker', 'Added new locker L3 - Size: Large, Status: Vacant, Price: â‚±200.00', 'locker', 'L3', '2025-05-06 16:51:05'),
(7, 1, 'Edit Locker', 'Updated locker L1 - Size: Medium, Status: Occupied, Price: â‚±100.00', 'locker', 'L1', '2025-05-06 16:57:28'),
(8, 6, 'PROFILE_UPDATE', 'Updated profile: Changed fullname from \'Gerald Harold\' to \'Gerald Dawn\'', 'client', '4', '2025-05-07 14:03:59'),
(9, 6, 'PROFILE_UPDATE', 'Updated profile: Changed fullname from \'Gerald Dawn\' to \'Gerald Dawn\'', 'client', '4', '2025-05-07 14:05:02'),
(10, 1, 'Edit Locker', 'Updated locker L1 - Size: Medium, Status: Maintenance, Price: â‚±100.00', 'locker', 'L1', '2025-05-07 14:26:43'),
(11, 6, 'PROFILE_UPDATE', 'Updated profile: Changed fullname from \'Gerald Dawn\' to \'Gerald Hale\'', 'client', '4', '2025-05-07 14:30:48'),
(12, 1, 'Edit Locker', 'Updated locker L1 - Size: Medium, Status: Vacant, Price: â‚±100.00', 'locker', 'L1', '2025-05-07 14:45:45'),
(13, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - â‚±100.00/month)', 'locker', 'L1', '2025-05-07 15:43:06'),
(14, 6, 'PROFILE_UPDATE', 'Updated profile: Changed phone from \'0000000000\' to \'09700811982\'', 'client', '4', '2025-05-08 07:46:04'),
(15, 6, 'PROFILE_UPDATE', 'Updated profile: Changed phone from \'09700811982\' to \'09700811981\'', 'client', '4', '2025-05-08 08:47:37'),
(16, 6, 'PROFILE_UPDATE', 'Updated profile: Changed phone from \'09700811981\' to \'09700811982\'', 'client', '4', '2025-05-08 08:51:09'),
(17, 34, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - ₱100.00/month)', 'locker', 'L1', '2025-05-09 03:47:04'),
(18, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - ₱100.00/month)', 'locker', 'L1', '2025-05-09 04:04:50'),
(19, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - ₱100.00/month)', 'locker', 'L1', '2025-05-09 05:45:58'),
(20, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - ₱100.00/month)', 'locker', 'L1', '2025-05-09 07:22:52'),
(21, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L3 (Large - ₱200.00/month)', 'locker', 'L3', '2025-05-09 08:21:37'),
(22, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - ₱100.00/month)', 'locker', 'L1', '2025-05-09 12:15:26'),
(23, 1, 'Update Rental', 'Updated rental #7 status from \'pending\' to \'approved\'', 'rental', '7', '2025-05-09 12:15:38'),
(24, 1, 'Update Rental', 'Updated rental #7 status from \'approved\' to \'active\'', 'rental', '7', '2025-05-09 12:33:31'),
(25, 1, 'Update Rental', 'Updated rental #7 status from \'active\' to \'cancelled\'', 'rental', '7', '2025-05-09 13:27:19'),
(26, 1, 'Edit Locker', 'Updated locker L3 - Size: Large, Status: Vacant, Price: ₱200.00', 'locker', 'L3', '2025-05-09 13:28:21'),
(27, 6, 'RENTAL_REQUEST', 'Rental Request: Client requested to rent Locker #L1 (Medium - ₱100.00/month)', 'locker', 'L1', '2025-05-09 13:29:03'),
(28, 1, 'Update Rental', 'Updated rental #8 status from \'pending\' to \'approved\' and payment status to \'paid\'', 'rental', '8', '2025-05-09 13:29:13'),
(29, 1, 'Update Rental', 'Updated rental #8 status from \'approved\' to \'active\'', 'rental', '8', '2025-05-09 13:29:34'),
(30, 1, 'Update Rental', 'Updated rental #8 status from \'active\' to \'completed\' and marked rental as ended', 'rental', '8', '2025-05-09 14:33:23'),
(34, 1, 'Delete User', 'Deleted Client: Lad Gem', 'user', '35', '2025-05-12 05:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(5) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Client','Staff','Admin') NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `phone_number` varchar(20) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `firstname`, `lastname`, `email`, `phone_number`) VALUES
(1, 'mjchloe', '$2y$10$nAUUIkcP2llaTY1sZ/8PqeztTpbMwgkBPqskRgfgnulQTb4UCyzJm', 'Admin', 'Kirk MJ', 'Lumapas', 'mjchloe@example.com', '0000000000'),
(5, 'airux', '$2y$10$pX.nRv9Rc0Gjpy5iTNsl3ud5b.9QjDn6nt0JTuuQgGAhkHurVaP3C', 'Staff', 'Randy', 'Calunod', 'airux@example.com', '0000000000'),
(6, 'gerald', '$2y$10$YrqiLY4vXC1OC.zc0NqWKumdLhUDmv6LXabUE4rvoZ9bClWJWMe46', 'Client', 'Gerald', 'Hale', 'gerald@example.com', '09700811982'),
(7, 'jen', '$2y$10$LL.I5AVqeQ43nqJPYb6RvOCOihgqYCqXSGCxIzV3tN557UxltcFbK', 'Admin', 'Jenny', 'Grail', 'jen@example.com', '0000000000'),
(34, 'jens', '$2y$10$8iM59eSJZQwqqFMVjhqj0.jDU1omud7cuHagN9uZlT9pzUjbKCZUS', 'Client', 'Jen', 'Liandra', 'jenox1@gmail.com', '09700911980');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lockerunits`
--
ALTER TABLE `lockerunits`
  ADD PRIMARY KEY (`locker_id`);

--
-- Indexes for table `paymentstatus`
--
ALTER TABLE `paymentstatus`
  ADD PRIMARY KEY (`payment_status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `rental`
--
ALTER TABLE `rental`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `locker_id` (`locker_id`),
  ADD KEY `rental_ibfk_3` (`payment_status_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `paymentstatus`
--
ALTER TABLE `paymentstatus`
  MODIFY `payment_status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rental`
--
ALTER TABLE `rental`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `rental`
--
ALTER TABLE `rental`
  ADD CONSTRAINT `rental_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `rental_ibfk_2` FOREIGN KEY (`locker_id`) REFERENCES `lockerunits` (`locker_id`),
  ADD CONSTRAINT `rental_ibfk_3` FOREIGN KEY (`payment_status_id`) REFERENCES `paymentstatus` (`payment_status_id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
