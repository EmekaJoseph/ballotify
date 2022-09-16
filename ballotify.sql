-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2022 at 07:28 PM
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
-- Table structure for table `tbl_activities`
--

CREATE TABLE `tbl_activities` (
  `id` int(50) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `activity` varchar(255) NOT NULL,
  `date_done` varchar(255) NOT NULL,
  `done_by` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_activities`
--

INSERT INTO `tbl_activities` (`id`, `org_id`, `activity`, `date_done`, `done_by`) VALUES
(1, 'BBHK', 'Added a new group', '2022-09-14 19:06:39', '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_candidates`
--

CREATE TABLE `tbl_candidates` (
  `id` int(10) NOT NULL,
  `event_id` varchar(10) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `position_id` varchar(255) NOT NULL,
  `votes` int(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_candidates`
--

INSERT INTO `tbl_candidates` (`id`, `event_id`, `member_id`, `position_id`, `votes`, `photo`) VALUES
(72, 'BBHK-E18', '1', '239', 0, NULL);

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

--
-- Dumping data for table `tbl_events`
--

INSERT INTO `tbl_events` (`id`, `org_id`, `event_id`, `event_name`, `event_type`, `event_start`, `event_expiry`, `event_description`, `created`) VALUES
(8, 'BBHK', 'BBHK-E18', 'mild election', 'Candidacy', '2022-09-08T17:09:00.000Z', '2023-01-29T17:09:00.000Z', 'made for mild minds', '2022-08-26 04:35:34'),
(11, 'BBHK', 'BBHK-E21', 'sapa community decide', 'Multi-Choice', '2022-09-13T14:31:04.803Z', '2022-09-30T14:31:00.000Z', 'no desc for sapa biko', '2022-09-13 09:52:02');

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
(63, 'BBHK', 'adaptors ', '2022-09-09 10:01:19'),
(64, 'BBHK', 'makkerss', '2022-09-09 10:15:14'),
(65, 'BBHK', 'Music group', '2022-09-16 07:27:24');

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
(1, 'BBHK', 'Oluwafredrick', 'Johnson', 'john@gmail.com', '09087634527', '1', '0', 'F', '8-25'),
(2, 'BBHK', 'aliya', 'moses', 'aliya02@mail.com', '07012547896', '1', '63', 'F', '11-24'),
(3, 'BBHK', 'Buchi', 'Samuel', 'sammybu09@gmail.com', '09087675890', '0', '63', 'M', '9-24'),
(4, 'BBHK', 'Taye', 'Joseph', 'joseph_56@gmail.com', '05021458795', '1', '63', 'F', '7-22'),
(5, 'BBHK', 'Abijan', 'Atutu', 'abijan@gmail.com', '09034567893', '1', '63', 'M', '8-2'),
(10, 'BBHK', 'meeka', 'meeka', 'meeka@mail.com', '07025698745', '1', '63', 'M', '9-6'),
(13, 'BBHK', 'Sharon', 'Davidson', 'seroqiqare@mailinator.com', '08009878675', '1', '63', 'M', '9-9'),
(14, 'BBHK', 'Keane', 'Tate', 'woqaxexex@mailinator.com', '065456732456', '1', '0', 'M', '9-9'),
(15, 'BBHK', 'Leila', 'Edwards', 'risa@mailinator.com', '08025478569', '1', '63', 'F', '8-27'),
(16, 'BBHK', 'Kylie', 'Dudley', 'tydepaziti@mailinator.com', '08089756733', '1', '0', 'M', '8-25'),
(17, 'BBHK', 'Abraham', 'Bush', 'hogytuxusa@mailinator.com', '08045879658', '1', '0', 'M', '7-20'),
(18, 'BBHK', 'Ria', 'Castillo', 'ginuviryqe@mailinator.com', '203455555555', '1', '63', 'F', '9-9'),
(19, 'BBHK', 'Jin', 'Suarez', 'xonudumoge@mailinator.com', '080569874521', '1', '63', 'M', '9-9'),
(20, 'BBHK', 'Abel', 'Taylor', 'lozyqe@mailinator.com', '225666666666', '1', '63', 'M', '5-27'),
(21, 'BBHK', 'Cailin', 'Deleon', 'wawibop@mailinator.com', '09032548547', '1', '63', 'M', '7-21'),
(22, 'BBHK', 'Scarlet', 'Campos', 'mygus@mailinator.com', '07045896521', '1', '63', 'F', '8-17'),
(23, 'BBHK', 'Ifeoma', 'Williamson', 'wanu@mailinator.com', '058796548412', '1', '0', 'F', '5-19'),
(24, 'BBHK', 'Barclay', 'Moody', 'judofyroby@mailinator.com', '45879652331', '1', '0', 'M', '8-25'),
(25, 'BBHK', 'Blossom', 'Jacobson', 'citadicab@mailinator.com', '7896554125658', '1', '0', 'M', '12-31'),
(26, 'BBHK', 'Zena', 'Henderson', 'mutibe@mailinator.com', '05021458796', '1', '0', 'F', '11-25'),
(27, 'BBHK', 'Jerome', 'Turner', 'xawek@mailinator.com', '05896521458', '1', '0', 'M', '12-1'),
(28, 'BBHK', 'Jeanette', 'Rosales', 'roqydixyf@mailinator.com', '02548565895', '1', '0', 'F', '8-27'),
(29, 'BBHK', 'Jakeem', 'Bush', 'zoqoqiqi@mailinator.com', '09045821456', '1', '0', 'M', '11-25'),
(30, 'BBHK', 'Xyla', 'Ferrell', 'jeca@mailinator.com', '0125487575', '1', '0', 'F', '12-25');

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
(1, 'BBHK', 'Proffix Academy', NULL, '11, Sweet Address London street, Uk, London', 21),
(10, 'BEFN', 'emeka\'s heritage', NULL, 'No address', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_positions`
--

CREATE TABLE `tbl_positions` (
  `id` int(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_positions`
--

INSERT INTO `tbl_positions` (`id`, `org_id`, `event_id`, `name`) VALUES
(239, 'BBHK', 'BBHK-E18', 'reders');

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
(1, 'BBHK', 'Proffix', 'Joseph', 'josephiwuji@gmail.com', NULL, 'master', '1', '$2y$10$ZkY6L0dz9TosKIr0OYXgGurUUC/LWmKcykp5T3RYPefhRMZSF/qQ.'),
(10, 'BEFN', 'emeka', 'demo', 'emeka@demo.com', NULL, 'master', '1', '$2y$10$fGIC3GGLcvZzJ76K82VVE.f/2bo1i1cZFhjHtBdn2CazSFq.Z4PqG');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voters`
--

CREATE TABLE `tbl_voters` (
  `id` int(10) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `member_id` varchar(255) NOT NULL,
  `voted_status` int(10) NOT NULL,
  `voted_date` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_voters`
--

INSERT INTO `tbl_voters` (`id`, `event_id`, `member_id`, `voted_status`, `voted_date`, `code`) VALUES
(186, 'BBHK-E18', '2', 0, '', 'BBHK-E18-VHLA8');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_activities`
--
ALTER TABLE `tbl_activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_candidates`
--
ALTER TABLE `tbl_candidates`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `tbl_positions`
--
ALTER TABLE `tbl_positions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_voters`
--
ALTER TABLE `tbl_voters`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_activities`
--
ALTER TABLE `tbl_activities`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_candidates`
--
ALTER TABLE `tbl_candidates`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `tbl_events`
--
ALTER TABLE `tbl_events`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_groups`
--
ALTER TABLE `tbl_groups`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `tbl_members`
--
ALTER TABLE `tbl_members`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tbl_org`
--
ALTER TABLE `tbl_org`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_positions`
--
ALTER TABLE `tbl_positions`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_voters`
--
ALTER TABLE `tbl_voters`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
