-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2022 at 12:06 AM
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
(86, 'B-Pro27KL', 'afterLifes', '2022-06-11T11:05:50.199Z'),
(87, 'B-Pro27KL', 'Sars', '2022-06-16T12:04:07.563Z');

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
(5, 'B-Pro27KL', 'emekus', 'josiah', 'joe@mail.com', '0905678976', '1', '87', 'M', '6-17'),
(11, 'B-Pro27KL', 'FeranmiLa', 'Abigirl', 'Abire704@gmail.com', '09182345678', '1', '87', 'F', '5-28'),
(12, 'B-Pro27KL', 'Avrilade', 'Johnny', 'avriload@yahoo.com', '090568976543', '1', '87', 'M', '12-26'),
(28, '', '', '', '', '', '', '0', '', ''),
(29, '', '', '', '', '', '', '0', '', ''),
(30, '', '', '', '', '', '', '0', '', '');

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
(1, 'B-Pro27KL', 'Proffix Entertainment', NULL, '34, Abeluyah street Lawanson.', 0),
(2, 'B-TNK78N', 'emeka\'s company', NULL, 'no street avenues', 0),
(3, 'B-EFA86F', 'emekas', NULL, 'saas', 0),
(4, 'B-LKX92M', 'aaaaa', NULL, 'aaaaa', 0),
(5, 'B-JGW36H', 'Proffix Academy', NULL, 'aslam street aklalaa, Lagos', 0);

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
(1, 'B-Pro27KL', 'Emeka', 'Proffix', 'emeka@mail.com', NULL, 'master', '1', '$2y$10$H.7Ul/Ed.XKu3Ex7sBcTV.1erLVbNxRAemVgOUdfMJWz7qnv8n2kC'),
(2, 'B-TNK78N', 'emekkus', 'Oluwaseun', 'emeka@mail.coms', NULL, 'master', '1', '$2y$10$zI5I2MqAqrgTcarSPAm3vuFPJbcqhbkY3Qf4Xdcp2Z/piKaLCtoC6'),
(3, 'B-EFA86F', 'wwqw', 'uiyyuy', 'emeka@mail.comss', NULL, 'master', '1', '$2y$10$RFnSMug.OcZRzZgFyUXWietH78wmeHnAdBuLU/mqSB9M8pNOSxjxe'),
(4, 'B-LKX92M', 'aaaa', 'aaaaa', 'emeka@mail.comsssssss', NULL, 'master', '1', '$2y$10$0SwFuXeyXdJpoibPrlPoDeTFFjpuMv.oPbLPhKSeVQNjfvW2PC4su'),
(5, 'B-JGW36H', 'Emmy', 'jossey', 'emmy@gmail.com', NULL, 'master', '1', '$2y$10$oxda7F5Kwhqwd/aExCfKFuPdUNrEpV5nPngThmI.TAgI1FoGZa9SS');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `tbl_groups`
--
ALTER TABLE `tbl_groups`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `tbl_members`
--
ALTER TABLE `tbl_members`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_org`
--
ALTER TABLE `tbl_org`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
