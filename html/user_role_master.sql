-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2023 at 07:59 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `trans_workflow_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_role_master`
--

CREATE TABLE `user_role_master` (
  `user_role_id` int(11) NOT NULL,
  `user_role_name` varchar(45) DEFAULT NULL,
  `user_role_description` varchar(250) DEFAULT NULL,
  `menu_id` text DEFAULT NULL,
  `smenu_id` text DEFAULT NULL,
  `crud_access` text DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_role_master`
--

INSERT INTO `user_role_master` (`user_role_id`, `user_role_name`, `user_role_description`, `menu_id`, `smenu_id`, `crud_access`, `created_on`, `created_by`) VALUES
(1, 'Portal Admin', 'Portal Admin', '1,2,3,4,5,6,7,9,10', '1,2,7,8,9,10,12,13,14,17,20,23,24,25,26,27,31,32,33,37,55,56,72,74', 'add,edit,delete', '0000-00-00 00:00:00', 0),
(2, 'Manager', 'Manager', NULL, NULL, NULL, '0000-00-00 00:00:00', 0),
(3, 'Employee', 'Employee', '9,10', '56,60,61,70,74', 'add,edit,delete', '0000-00-00 00:00:00', 0),
(4, 'Vendor', 'Vendor', '1,2,3,4,5,6,7,9,10', '1,10,14,56', 'delete', '0000-00-00 00:00:00', 0),
(5, 'Co-ordinator', 'Co-ordinator', NULL, NULL, NULL, '2019-11-20 07:34:33', 54),
(6, 'Team Leader (PE/XML)', 'Team Leader (PE/XML)', NULL, NULL, NULL, '2021-11-05 15:22:09', 190),
(7, 'Team Leader (PAG)', 'Team Leader (PAG)- Tex, 3B2', NULL, NULL, NULL, '2023-04-25 17:11:06', 192),
(9, 'Tech Lead', 'IT', NULL, NULL, NULL, '2023-06-01 12:17:31', 54),
(10, 'test', 'test', ',,,,,,,,', '1,2,7,8,9,10,12,13,14,17,18,20,23,24,25,26,27,28,29,31,32,33,37,38,39,40,41,42,43,44,55,56,57,58,59,60,61,62,63,64,70,71,72,73,74,75,76,77,78', 'add,delete', '2023-12-15 11:10:36', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_role_master`
--
ALTER TABLE `user_role_master`
  ADD PRIMARY KEY (`user_role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_role_master`
--
ALTER TABLE `user_role_master`
  MODIFY `user_role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
