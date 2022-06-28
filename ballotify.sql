-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2022 at 10:56 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ballotify`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_events`
--

CREATE TABLE `tbl_events` (
  `id` int(50) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `event_start` varchar(255) NOT NULL,
  `event_expiry` varchar(255) NOT NULL,
  `event_description` varchar(255) NOT NULL,
  `created` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_groups`
--

CREATE TABLE `tbl_groups` (
  `id` int(50) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `created` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_groups`
--

INSERT INTO `tbl_groups` (`id`, `org_id`, `group_name`, `created`) VALUES
(1, 'BBHK', 'family', '2022-06-28 15:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_members`
--

CREATE TABLE `tbl_members` (
  `id` int(50) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `verified` varchar(10) NOT NULL,
  `group_id` varchar(50) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `birthday` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_members`
--

INSERT INTO `tbl_members` (`id`, `org_id`, `firstname`, `lastname`, `email`, `phone`, `verified`, `group_id`, `gender`, `birthday`) VALUES
(1, 'BBHK', 'fredrick', 'Johnson', 'john@gmail.com', '09087634527', '1', '1', 'M', '8-20'),
(2, 'BBHK', 'aliya', 'Faridah', 'aliya02@mail.com', '07012547896', '1', '1', 'F', '11-24');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_org`
--

CREATE TABLE `tbl_org` (
  `id` int(50) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `org_name` varchar(255) NOT NULL,
  `org_logo` varchar(255) DEFAULT NULL,
  `org_address` varchar(255) NOT NULL,
  `org_events` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_org`
--

INSERT INTO `tbl_org` (`id`, `org_id`, `org_name`, `org_logo`, `org_address`, `org_events`) VALUES
(1, 'BBHK', 'Proffix Academy', NULL, 'proffix address', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(50) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `email` varchar(2555) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL,
  `isActive` varchar(2) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `org_id`, `firstname`, `lastname`, `email`, `image`, `role`, `isActive`, `password`) VALUES
(1, 'BBHK', 'emeka', 'iwuji', 'emeka@mail.com', NULL, 'master', '1', '$2y$10$ZkY6L0dz9TosKIr0OYXgGurUUC/LWmKcykp5T3RYPefhRMZSF/qQ.');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_events`
--
ALTER TABLE `tbl_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_groups`
--
ALTER TABLE `tbl_groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_members`
--
ALTER TABLE `tbl_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_org`
--
ALTER TABLE `tbl_org`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_events`
--
ALTER TABLE `tbl_events`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_groups`
--
ALTER TABLE `tbl_groups`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_members`
--
ALTER TABLE `tbl_members`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_org`
--
ALTER TABLE `tbl_org`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
