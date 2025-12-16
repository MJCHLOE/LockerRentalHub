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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(5) NOT NULL,
  `user_id` int(5) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `full_name`) VALUES
(1, 1, 'Kirk MJ Lumapas'),
(2, 7, 'Jenny Grail');

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `admin_log_id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `admin_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`admin_log_id`, `log_id`, `admin_id`) VALUES
(3, 3, 1),
(4, 4, 1),
(5, 5, 1),
(6, 6, 1),
(7, 7, 1),
(8, 10, 1),
(9, 12, 1),
(10, 23, 1),
(11, 24, 1),
(12, 25, 1),
(13, 26, 1),
(14, 28, 1),
(15, 29, 1),
(16, 30, 1),
(20, 34, 1);

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(5) NOT NULL,
  `user_id` int(5) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `user_id`, `full_name`) VALUES
(4, 6, 'Gerald Harold'),
(5, 33, 'Test User'),
(6, 34, 'Jen Liandra');

-- --------------------------------------------------------

--
-- Table structure for table `client_logs`
--

CREATE TABLE `client_logs` (
  `client_log_id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `client_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `client_logs`
--

INSERT INTO `client_logs` (`client_log_id`, `log_id`, `client_id`) VALUES
(1, 8, 4),
(2, 9, 4),
(3, 11, 4),
(4, 13, 4),
(5, 14, 4),
(6, 15, 4),
(7, 16, 4),
(8, 17, 6),
(9, 18, 4),
(10, 19, 4),
(11, 20, 4),
(12, 21, 4),
(13, 22, 4),
(14, 27, 4);

-- --------------------------------------------------------

--
-- Table structure for table `lockersizes`
--

CREATE TABLE `lockersizes` (
  `size_id` int(5) NOT NULL,
  `size_name` enum('Small','Medium','Large') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lockersizes`
--

INSERT INTO `lockersizes` (`size_id`, `size_name`) VALUES
(1, 'Small'),
(2, 'Medium'),
(3, 'Large');

-- --------------------------------------------------------

--
-- Table structure for table `lockerstatuses`
--

CREATE TABLE `lockerstatuses` (
  `status_id` int(5) NOT NULL,
  `status_name` enum('Vacant','Occupied','Maintenance','Reserved') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lockerstatuses`
--

INSERT INTO `lockerstatuses` (`status_id`, `status_name`) VALUES
(1, 'Vacant'),
(2, 'Occupied'),
(3, 'Maintenance'),
(4, 'Reserved');

-- --------------------------------------------------------

--
-- Table structure for table `lockerunits`
--

CREATE TABLE `lockerunits` (
  `locker_id` varchar(10) NOT NULL,
  `size_id` int(5) NOT NULL,
  `status_id` int(5) NOT NULL,
  `price_per_month` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `lockerunits`
--

INSERT INTO `lockerunits` (`locker_id`, `size_id`, `status_id`, `price_per_month`) VALUES
('L1', 2, 1, 100.00),
('L2', 1, 1, 100.00),
('L3', 3, 1, 200.00);

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
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(5) NOT NULL,
  `user_id` int(5) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `user_id`, `full_name`) VALUES
(2, 5, 'Randy Calunod');

-- --------------------------------------------------------

--
-- Table structure for table `staff_logs`
--

CREATE TABLE `staff_logs` (
  `staff_log_id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `staff_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

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
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
  IF NEW.role = 'Admin' THEN
    INSERT INTO admins (user_id, full_name)
    VALUES (NEW.user_id, CONCAT(NEW.firstname, ' ', NEW.lastname));
  ELSEIF NEW.role = 'Staff' THEN
    INSERT INTO staff (user_id, full_name)
    VALUES (NEW.user_id, CONCAT(NEW.firstname, ' ', NEW.lastname));
  ELSE
    INSERT INTO clients (user_id, full_name)
    VALUES (NEW.user_id, CONCAT(NEW.firstname, ' ', NEW.lastname));
  END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`admin_log_id`),
  ADD KEY `log_id` (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `client_logs`
--
ALTER TABLE `client_logs`
  ADD PRIMARY KEY (`client_log_id`),
  ADD KEY `log_id` (`log_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `lockersizes`
--
ALTER TABLE `lockersizes`
  ADD PRIMARY KEY (`size_id`);

--
-- Indexes for table `lockerstatuses`
--
ALTER TABLE `lockerstatuses`
  ADD PRIMARY KEY (`status_id`);

--
-- Indexes for table `lockerunits`
--
ALTER TABLE `lockerunits`
  ADD PRIMARY KEY (`locker_id`),
  ADD KEY `size_id` (`size_id`),
  ADD KEY `status_id` (`status_id`);

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
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `staff_logs`
--
ALTER TABLE `staff_logs`
  ADD PRIMARY KEY (`staff_log_id`),
  ADD KEY `log_id` (`log_id`),
  ADD KEY `staff_id` (`staff_id`);

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
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `admin_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `client_logs`
--
ALTER TABLE `client_logs`
  MODIFY `client_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `lockersizes`
--
ALTER TABLE `lockersizes`
  MODIFY `size_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lockerstatuses`
--
ALTER TABLE `lockerstatuses`
  MODIFY `status_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff_logs`
--
ALTER TABLE `staff_logs`
  MODIFY `staff_log_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `system_logs` (`log_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_logs_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`);

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `client_logs`
--
ALTER TABLE `client_logs`
  ADD CONSTRAINT `client_logs_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `system_logs` (`log_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `client_logs_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`);

--
-- Constraints for table `lockerunits`
--
ALTER TABLE `lockerunits`
  ADD CONSTRAINT `lockerunits_ibfk_1` FOREIGN KEY (`size_id`) REFERENCES `lockersizes` (`size_id`),
  ADD CONSTRAINT `lockerunits_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `lockerstatuses` (`status_id`);

--
-- Constraints for table `rental`
--
ALTER TABLE `rental`
  ADD CONSTRAINT `rental_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `rental_ibfk_2` FOREIGN KEY (`locker_id`) REFERENCES `lockerunits` (`locker_id`),
  ADD CONSTRAINT `rental_ibfk_3` FOREIGN KEY (`payment_status_id`) REFERENCES `paymentstatus` (`payment_status_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `staff_logs`
--
ALTER TABLE `staff_logs`
  ADD CONSTRAINT `staff_logs_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `system_logs` (`log_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_logs_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
