-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 25, 2022 at 09:50 PM
-- Server version: 5.7.38
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `stingle`
--

-- --------------------------------------------------------

--
-- Table structure for table `sp_stats`
--

CREATE TABLE IF NOT EXISTS `sp_stats` (
                                          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                          `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `new_users_today` int(10) UNSIGNED NOT NULL,
                                          `new_users_7days` int(10) UNSIGNED NOT NULL,
                                          `new_users_31days` int(10) UNSIGNED NOT NULL,
                                          `active_users_today` int(10) UNSIGNED NOT NULL,
                                          `active_users_7days` int(10) UNSIGNED NOT NULL,
                                          `active_users_31days` int(10) UNSIGNED NOT NULL,
                                          `paid_users` int(10) UNSIGNED NOT NULL,
                                          `mrr` double UNSIGNED NOT NULL,
                                          `total_users` int(10) UNSIGNED NOT NULL,
                                          PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
COMMIT;
