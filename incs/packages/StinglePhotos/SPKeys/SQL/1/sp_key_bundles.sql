-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 26, 2022 at 08:05 PM
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
-- Table structure for table `sp_key_bundles`
--

CREATE TABLE IF NOT EXISTS `sp_key_bundles` (
                                                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                                `user_id` int(10) UNSIGNED NOT NULL,
                                                `key_bundle` text NOT NULL,
                                                `server_keypair` varchar(256) NOT NULL,
                                                PRIMARY KEY (`id`),
                                                UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sp_key_bundles`
--
ALTER TABLE `sp_key_bundles`
    ADD CONSTRAINT `sp_key_bundles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wum_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
