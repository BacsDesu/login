-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 03:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `login_system`
--
CREATE DATABASE IF NOT EXISTS `login_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `login_system`;

-- --------------------------------------------------------

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `AttemptLogin`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `AttemptLogin` (IN `p_email` VARCHAR(255), IN `p_password` VARCHAR(255), IN `p_ip_address` VARCHAR(45), IN `p_user_agent` TEXT)   BEGIN
    DECLARE user_exists INT;
    DECLARE user_password VARCHAR(255);
    DECLARE user_id INT;
    DECLARE is_success BOOLEAN DEFAULT FALSE;
    DECLARE attempt_id INT;
    
    -- Check if user exists and get password
    SELECT id, password INTO user_id, user_password 
    FROM users 
    WHERE email = p_email AND is_active = TRUE;
    
    SET user_exists = (SELECT COUNT(*) FROM users WHERE email = p_email AND is_active = TRUE);
    
    -- Record login attempt
    INSERT INTO login_attempts (email, ip_address, success, user_agent)
    VALUES (p_email, p_ip_address, is_success, p_user_agent);
    
    -- Get the ID of the just-inserted attempt
    SET attempt_id = LAST_INSERT_ID();
    
    IF user_exists = 1 THEN
        -- Verify password (using bcrypt)
        IF user_password = p_password THEN
            SET is_success = TRUE;
            
            -- Update last login
            UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = user_id;
            
            -- Update login attempt to success using the attempt_id
            UPDATE login_attempts 
            SET success = TRUE 
            WHERE id = attempt_id;
            
            SELECT 'Login successful' AS message, user_id AS user_id, TRUE AS success;
        ELSE
            SELECT 'Invalid password' AS message, NULL AS user_id, FALSE AS success;
        END IF;
    ELSE
        SELECT 'User not found' AS message, NULL AS user_id, FALSE AS success;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email` (`email`),
  KEY `idx_login_attempts_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `login_attempts`
--
DELIMITER $$
DROP TRIGGER IF EXISTS `clean_old_login_attempts`$$
CREATE TRIGGER `clean_old_login_attempts` BEFORE INSERT ON `login_attempts` FOR EACH ROW BEGIN
    -- Delete attempts older than 30 days
    DELETE FROM login_attempts 
    WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `remember_token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `created_at`, `updated_at`, `last_login`, `is_active`, `remember_token`) VALUES
(1, 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '2026-02-12 14:17:50', '2026-02-12 14:17:50', NULL, 1, NULL),
(2, 'preciousgracebagaforo@gmail.com', '$2y$10$w4ts/VnBKjVXP8sNZxrowO8itQ7Tn/P0GiHQoYZlAmqi/po61Ybze', 'Babiii', '2026-02-12 15:18:53', '2026-02-13 02:21:05', '2026-02-13 02:21:05', 1, NULL),
(3, 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', '2026-02-12 16:15:11', '2026-02-12 16:15:11', NULL, 1, NULL),
(4, 'bacalingmarkjefferson@gmail.com', '$2y$10$8rSdJzqRN8uhpUPOvgZKU.pjgvAjJQib.aNV.A2RogQRmtrlTClmC', 'Mark Jefferson Bacaling', '2026-02-12 16:19:43', '2026-02-13 02:53:47', '2026-02-13 02:53:47', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_resets_token` (`token`),
  KEY `idx_password_resets_email` (`email`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `created_at`, `expires_at`) VALUES
(17, 'preciousgracebagaforo@gmail.com', '414644', '2026-02-13 02:34:59', '2026-02-12 19:49:59'),
(20, 'bacalingmarkjefferson@gmail.com', '839292', '2026-02-13 02:49:24', '2026-02-12 20:04:24');

-- --------------------------------------------------------

--
-- Structure for view `active_users`
--
DROP VIEW IF EXISTS `active_users`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_users`  AS SELECT `users`.`id` AS `id`, `users`.`email` AS `email`, `users`.`full_name` AS `full_name`, `users`.`created_at` AS `created_at`, `users`.`last_login` AS `last_login` FROM `users` WHERE `users`.`is_active` = 1 ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
