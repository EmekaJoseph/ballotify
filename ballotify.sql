-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 14, 2022 at 12:15 AM
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
(4, 'BBHK', 'Created new group, \'new group\'', '2022-10-07 07:54:56', ''),
(5, 'BBHK', 'Added new member,', '2022-10-07 08:01:16', ''),
(6, 'BBHK', 'Created new group, \'united\'', '2022-10-07 08:01:44', ''),
(7, 'BBHK', 'Added new member,', '2022-10-07 08:01:46', ''),
(8, 'BBHK', 'Created new group, \'sasasasasa\'', '2022-10-07 19:00:07', ''),
(9, 'BBHK', 'Created new group, \'Dollars\'', '2022-10-12 09:14:10', ''),
(10, 'BBHK', 'Created new group, \'Dollars\'', '2022-10-12 09:15:35', ''),
(11, 'BBHK', 'Created new group, \'dddd\'', '2022-10-13 03:33:45', '');

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
(101, 'BBHK-E21', '3', '252', 0, NULL),
(110, 'BBHK-E18', '5', '261', 0, NULL),
(112, 'BBHK-E18', '4', '261', 0, NULL),
(117, 'BBHK-E18', '4', '264', 0, NULL),
(121, 'BBHK-E21', '2', '266', 0, NULL),
(123, 'BBHK-E21', '10', '268', 0, NULL),
(125, 'BBHK-E21', '20', '266', 0, NULL),
(127, 'BBHK-E21', '33', '267', 0, NULL),
(128, 'BBHK-E21', '27', '267', 0, NULL),
(129, 'BBHK-E21', '2', '268', 0, NULL),
(130, 'BBHK-E21', '16', '266', 0, NULL),
(131, 'BBHK-E21', '4', '269', 0, NULL),
(132, 'BBHK-E21', '14', '269', 0, NULL),
(133, 'BBHK-E21', '13', '269', 0, NULL);

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
(82, 'BBHK', 'Sanity', '2022-10-05 16:51:22'),
(83, 'BBHK', 'creatives', '2022-10-05 16:52:00'),
(86, 'BBHK', 'Managers', '2022-10-07 07:54:56');

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
(2, 'BBHK', 'aliyah', 'moses', 'aliya02@mail.com', '07012547896', '1', '86', 'F', '11-24'),
(4, 'BBHK', 'Taye', 'Joseph', 'joseph_56@gmail.com', '05021458795', '1', '86', 'F', '7-22'),
(5, 'BBHK', 'Abijan', 'Atutu', 'abijan@gmail.com', '09034567893', '1', '86', 'M', '8-2'),
(10, 'BBHK', 'meeka', 'meeka', 'meeka@mail.com', '07025698745', '1', '86', 'M', '9-6'),
(13, 'BBHK', 'Sharon', 'Davidson', 'seroqiqare@mailinator.com', '0800987867', '1', '86', 'M', '9-9'),
(14, 'BBHK', 'Keane', 'Tate', 'woqaxexex@mailinator.com', '065456732456', '1', '82', 'M', '9-9'),
(15, 'BBHK', 'Leila', 'Edwards', 'risa@mailinator.com', '08025478569', '1', '82', 'F', '8-27'),
(16, 'BBHK', 'Kylie', 'Dudley', 'tydepaziti@mailinator.com', '08089756733', '1', '82', 'M', '8-25'),
(17, 'BBHK', 'Abraham', 'Bush', 'hogytuxusa@mailinator.com', '08045879658', '1', '86', 'M', '7-20'),
(20, 'BBHK', 'Abel', 'Taylor', 'lozyqe@mailinator.com', '22566666455', '1', '86', 'M', '5-27'),
(21, 'BBHK', 'Cailin', 'Deleon', 'wawibop@mailinator.com', '09032548547', '1', '86', 'M', '7-21'),
(22, 'BBHK', 'Scarlet', 'Campos', 'mygus@mailinator.com', '07045896521', '1', '82', 'F', '8-17'),
(23, 'BBHK', 'Ifeoma', 'Williamson', 'wanu@mailinator.com', '058796548412', '1', '82', 'F', '5-19'),
(24, 'BBHK', 'Barclay', 'Moody', 'judofyroby@mailinator.com', '45879652331', '1', '86', 'M', '8-25'),
(25, 'BBHK', 'Blossom', 'Jacobson', 'citadicab@mailinator.com', '7896554125658', '1', '86', 'M', '12-31'),
(26, 'BBHK', 'Zena', 'Henderson', 'mutibe@mailinator.com', '05021458796', '1', '86', 'F', '11-25'),
(27, 'BBHK', 'Jerome', 'Turner', 'xawek@mailinator.com', '05896521458', '1', '86', 'M', '12-1'),
(28, 'BBHK', 'Jeanette', 'Rosales', 'roqydixyf@mailinator.com', '02548565895', '1', '86', 'F', '8-27'),
(29, 'BBHK', 'Jakeem', 'Bush', 'zoqoqiqi@mailinator.com', '09045821456', '1', '83', 'M', '11-25'),
(30, 'BBHK', 'Xyla', 'Ferrell', 'jeca@mailinator.com', '0125487575', '1', '83', 'F', '12-25'),
(31, 'BBHK', 'Edan', 'Santos', 'kibesedy@mailinator.com', '08052458965', '1', '83', 'F', '9-24'),
(32, 'BBHK', 'Quail', 'Cantu', 'suticomyj@mailinator.com', '12548756213', '1', '83', 'M', '9-28'),
(33, 'BBHK', 'Mira', 'Jackson', 'sotuhat@mailinator.com', '458796521215', '1', '83', 'M', '9-28'),
(34, 'BBHK', 'Anika', 'Monroe', 'tygobopuzi@mailinator.com', '458965584521', '1', '83', 'M', '9-28'),
(35, 'BBHK', 'Donna', 'Giles', 'milyfem@mailinator.com', '1111111111111', '1', '83', 'M', '9-28'),
(36, 'BBHK', 'Valentine', 'Ewing', 'ninaxoh@mailinator.com', '070145896521', '1', '83', 'F', '8-24'),
(37, 'BBHK', 'Inez', 'Pope', 'kenynoje@mailinator.com', '26458896547', '1', '83', 'M', '12-23'),
(38, 'BBHK', 'Cedric', 'Dodson', 'dyvusa@mailinator.com', '09034567893', '1', '83', 'M', '10-13'),
(40, 'BBHK', 'Winter', 'Goodman', 'lybigebac@mailinator.com', '7767889064455', '1', '83', 'M', '10-7');

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
(1, 'BBHK', 'Proffix Academy', NULL, '11, Sweet Address London street, Uk', 21),
(10, 'BEFN', 'emeka\'s heritage', NULL, '241, demo street', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_positions`
--

CREATE TABLE `tbl_positions` (
  `position_id` int(255) NOT NULL,
  `org_id` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `position_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_positions`
--

INSERT INTO `tbl_positions` (`position_id`, `org_id`, `event_id`, `position_name`) VALUES
(261, 'BBHK', 'BBHK-E18', 'prsedo'),
(264, 'BBHK', 'BBHK-E18', 'secc2'),
(266, 'BBHK', 'BBHK-E21', 'president'),
(267, 'BBHK', 'BBHK-E21', 'vice president'),
(268, 'BBHK', 'BBHK-E21', 'provost'),
(269, 'BBHK', 'BBHK-E21', 'Food cooker');

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
(1, 'BBHK', 'Proffix', 'Josephs', 'josephiwuji@gmail.com', NULL, 'master', '1', '$2y$10$ZkY6L0dz9TosKIr0OYXgGurUUC/LWmKcykp5T3RYPefhRMZSF/qQ.'),
(10, 'BEFN', 'Emeka', 'demoz', 'emeka@demo.com', NULL, 'master', '1', '$2y$10$fGIC3GGLcvZzJ76K82VVE.f/2bo1i1cZFhjHtBdn2CazSFq.Z4PqG');

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
(521, 'BBHK-E18', '3', 0, '', 'BBHK-E18-VMGNQ'),
(547, 'BBHK-E18', '21', 0, '', 'BBHK-E18-VN52W'),
(549, 'BBHK-E18', '24', 0, '', 'BBHK-E18-V27GM'),
(550, 'BBHK-E18', '26', 0, '', 'BBHK-E18-VTFGB'),
(596, 'BBHK-E21', '17', 0, '', 'BBHK-E21-VFRZQ'),
(600, 'BBHK-E21', '21', 0, '', 'BBHK-E21-VPM3F'),
(601, 'BBHK-E21', '16', 0, '', 'BBHK-E21-VG4F6'),
(603, 'BBHK-E21', '26', 0, '', 'BBHK-E21-VXGT2'),
(606, 'BBHK-E21', '31', 0, '', 'BBHK-E21-V72YZ'),
(608, 'BBHK-E21', '32', 0, '', 'BBHK-E21-VPFQJ'),
(609, 'BBHK-E21', '30', 0, '', 'BBHK-E21-VYQD5'),
(610, 'BBHK-E21', '34', 0, '', 'BBHK-E21-VPL8F');

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
  ADD PRIMARY KEY (`position_id`);

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
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_candidates`
--
ALTER TABLE `tbl_candidates`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `tbl_events`
--
ALTER TABLE `tbl_events`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_groups`
--
ALTER TABLE `tbl_groups`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `tbl_members`
--
ALTER TABLE `tbl_members`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `tbl_org`
--
ALTER TABLE `tbl_org`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_positions`
--
ALTER TABLE `tbl_positions`
  MODIFY `position_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_voters`
--
ALTER TABLE `tbl_voters`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=611;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
