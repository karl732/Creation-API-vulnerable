SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `bankingtraining`
--

CREATE DATABASE IF NOT EXISTS `bankingtraining` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `bankingtraining`;

-- --------------------------------------------------------
--
-- Table structure for table `dim_user`
--

DROP TABLE IF EXISTS `dim_user`;
CREATE TABLE IF NOT EXISTS `dim_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) NOT NULL,
  `password` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `country` varchar(50) NOT NULL,
  `signup_date` date NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=10;

--
-- Dumping data for table `dim_user`
--

INSERT INTO `dim_user`
(`user_id`, `username`, `password`, `full_name`, `user_role`, `country`, `signup_date`)
VALUES
(1, 'admin', 'PULL', 'System Admin', 'Admin', 'FR', '2023-01-01'),
(2, 'alice', 'pink', 'Alice Martin', 'Customer', 'FR', '2023-02-01'),
(3, 'bob', 'B2026', 'Bob Dupont', 'Customer', 'FR', '2023-03-15'),
(4, 'analyst1', 'DATA', 'Marie Leroy', 'Analyst', 'FR', '2022-11-20'),
(5, 'susan.laroche-mercier', 'X@9FKo', 'Susan Laroche-Mercier', 'Customer', 'CA', '2024-03-24'),
(6, 'nicolas.lenoir', '+6T2eE', 'Nicolas Lenoir', 'Analyst', 'FR', '2024-06-21'),
(7, 'michel.fernandez.de.boutin', 'd)7QXz', 'Michel Fernandez de Boutin', 'Customer', 'CH', '2025-04-24'),
(8, 'nathalie.aubry', '&7VvJP', 'Nathalie Aubry', 'Customer', 'CH', '2026-01-18'),
(9, 'noël.vallée-vaillant', '+2OCyn', 'Noël Vallée-Vaillant', 'Customer', 'BE', '2024-09-27'),
(10, 'gabriel.carpentier', '%2p2Ww', 'Gabriel Carpentier', 'Customer', 'BE', '2024-07-11'),
(11, 'monique-édith.gaillard', '(#1wBp', 'Monique-Édith Gaillard', 'Analyst', 'FR', '2024-04-07'),
(12, 'maggie.gilles', 'u^9IEm', 'Maggie Gilles', 'Customer', 'FR', '2024-06-14'),
(13, 'charles.le.gillet', '(E5PfG', 'Charles Le Gillet', 'Customer', 'FR', '2025-04-05'),
(14, 'rené.lejeune', '@99Igq', 'René Lejeune', 'Customer', 'CH', '2024-08-21'),
(15, 'antoine.barre', '#62Xrt', 'Antoine Barre', 'Customer', 'BE', '2026-02-01'),
(16, 'virginie.de.la.martinez', '+#3Vr)', 'Virginie de la Martinez', 'Customer', 'BE', '2024-11-13'),
(17, 'daniel.marchal', 'i*N3Rq', 'Daniel Marchal', 'Customer', 'BE', '2024-04-13'),
(18, 'roland.bazin', '#1mXOq', 'Roland Bazin', 'Customer', 'CA', '2024-11-03'),
(19, 'sylvie.turpin', '(89Sih', 'Sylvie Turpin', 'Analyst', 'CA', '2026-02-01'),
(20, 'arthur.le.didier', '@2Sr5A', 'Arthur Le Didier', 'Customer', 'FR', '2026-02-24'),
(21, 'brigitte.de.morin', '&S%6Cp', 'Brigitte de Morin', 'Analyst', 'BE', '2024-08-09'),
(22, 'louis.vidal', '%w4Uw^', 'Louis Vidal', 'Customer', 'CA', '2024-09-12'),
(23, 'eugène.chevalier', 'e(3Fqs', 'Eugène Chevalier', 'Customer', 'FR', '2024-08-11'),
(24, 'timothée.mallet-laurent', 'J)6IJk', 'Timothée Mallet-Laurent', 'Analyst', 'CH', '2025-10-21'),
(25, 'mathilde.ribeiro-renard', '!3E$tC', 'Mathilde Ribeiro-Renard', 'Customer', 'CA', '2024-04-13'),
(26, 'éléonore.de.lebreton', '%3L!v0', 'Éléonore de Lebreton', 'Customer', 'CH', '2025-02-15'),
(27, 'hugues.leleu', '^6Hr8l', 'Hugues Leleu', 'Analyst', 'CH', '2025-07-20'),
(28, 'gabrielle.dupuis', '+F4kUj', 'Gabrielle Dupuis', 'Customer', 'BE', '2025-11-03'),
(29, 'gilles.hebert', '!2XeXk', 'Gilles Hebert', 'Analyst', 'CH', '2025-10-26'),
(30, 'robert.françois', '^17oZh', 'Robert François', 'Analyst', 'FR', '2024-10-17');

-- --------------------------------------------------------
-- Tables attendues par l'application PHP (création dès que dim_user existe)
-- users : id, username, password, role
-- --------------------------------------------------------
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `users` (`id`, `username`, `password`, `role`)
SELECT `user_id`, `username`, `password`, `user_role` FROM `dim_user`;

-- --------------------------------------------------------
--
-- Table structure for table `dim_account`
--

DROP TABLE IF EXISTS `dim_account`;
CREATE TABLE IF NOT EXISTS `dim_account` (
  `account_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_type` varchar(50) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `open_date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `balance` decimal(10,2),
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=10;

--
-- Dumping data for table `dim_account`
--

INSERT INTO `dim_account`
(`account_id`, `account_type`, `currency`, `open_date`, `status`, `balance`)
VALUES
(1, 'Savings', 'EUR', '2023-02-01', 'Active', -50),
(2, 'Checking', 'EUR', '2023-03-15', 'Active', 2500),
(3, 'Business', 'EUR', '2024-01-01', 'Suspended', 12000),
(4, 'Business', 'USD', '2026-01-25', 'Suspended', 23484.88),
(5, 'Checking', 'EUR', '2023-06-28', 'Active', 46135.53),
(6, 'Business', 'EUR', '2024-07-08', 'Suspended', 12303.59),
(7, 'Savings', 'EUR', '2023-09-15', 'Active', 2926.27),
(8, 'Savings', 'USD', '2024-03-11', 'Active', 9371.28),
(9, 'Business', 'EUR', '2025-08-31', 'Suspended', 38469.0),
(10, 'Savings', 'EUR', '2025-03-01', 'Active', 30287.9),
(11, 'Checking', 'EUR', '2024-03-09', 'Active', 45538.59),
(12, 'Business', 'GBP', '2023-11-23', 'Active', 31060.69),
(13, 'Savings', 'USD', '2023-11-29', 'Closed', 31474.51),
(14, 'Business', 'GBP', '2025-01-01', 'Active', 38445.28),
(15, 'Checking', 'USD', '2023-07-08', 'Active', 31678.15),
(16, 'Savings', 'EUR', '2025-01-16', 'Active', 27443.24),
(17, 'Checking', 'USD', '2025-09-10', 'Active', 12345.13),
(18, 'Savings', 'EUR', '2023-11-09', 'Active', 38301.84),
(19, 'Savings', 'EUR', '2024-05-24', 'Closed', 37907.41),
(20, 'Savings', 'EUR', '2024-12-04', 'Closed', 22053.13),
(21, 'Checking', 'GBP', '2023-07-03', 'Closed', 37555.27),
(22, 'Savings', 'USD', '2025-10-04', 'Active', 43343.8),
(23, 'Business', 'USD', '2025-09-03', 'Active', 319.63),
(24, 'Checking', 'USD', '2025-02-18', 'Active', 48929.45),
(25, 'Business', 'EUR', '2025-04-07', 'Closed', 27952.34),
(26, 'Savings', 'USD', '2024-02-28', 'Suspended', 564.75),
(27, 'Savings', 'EUR', '2024-06-24', 'Suspended', 43360.88),
(28, 'Savings', 'EUR', '2025-11-24', 'Active', 4511.94),
(29, 'Savings', 'EUR', '2025-02-07', 'Active', 15795.34),
(30, 'Savings', 'EUR', '2025-01-01', 'Active', 10000.00);

-- --------------------------------------------------------
--
-- Table structure for table `fact_transactions`
--

DROP TABLE IF EXISTS `fact_transactions`;
CREATE TABLE IF NOT EXISTS `fact_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `user_id` (`user_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `fk_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `dim_user` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_account`
    FOREIGN KEY (`account_id`)
    REFERENCES `dim_account` (`account_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=20;

--
-- Dumping data for table `fact_transactions`
--

INSERT INTO `fact_transactions`
(`transaction_id`, `user_id`, `account_id`, `transaction_date`, `transaction_type`, `amount`)
VALUES
(1, 2, 1, '2024-01-10 10:15:00', 'Deposit', 500.00),
(2, 2, 1, '2024-01-11 09:00:00', 'Withdrawal', -200.00),
(3, 3, 2, '2024-01-12 14:30:00', 'Transfer', -100.00),
(4, 3, 2, '2024-01-13 16:45:00', 'Deposit', 800.00),
(5, 10, 14, '2025-03-12 03:02:26', 'Withdrawal', -1236.5),
(6, 10, 10, '2025-07-16 08:57:31', 'Withdrawal', -869.52),
(7, 21, 1, '2025-09-28 09:03:31', 'Withdrawal', -1777.18),
(8, 12, 15, '2025-10-10 12:27:26', 'Transfer', -1644.0),
(9, 30, 13, '2026-02-16 18:05:56', 'Transfer', -1205.93),
(10, 18, 16, '2025-08-24 18:13:06', 'Deposit', 1156.97),
(11, 2, 2, '2025-10-22 14:40:21', 'Withdrawal', -791.83),
(12, 18, 18, '2025-06-06 01:59:21', 'Transfer', -1898.43),
(13, 1, 18, '2025-10-12 16:29:21', 'Withdrawal', -473.9),
(14, 7, 23, '2025-11-08 21:03:00', 'Withdrawal', -1130.55),
(15, 3, 30, '2025-12-06 07:38:36', 'Deposit', 1277.36),
(16, 29, 3, '2025-09-11 15:19:05', 'Transfer', -376.79),
(17, 14, 11, '2025-03-13 00:11:45', 'Deposit', 1633.34),
(18, 15, 4, '2025-05-13 04:32:02', 'Transfer', -1860.74),
(19, 24, 17, '2025-10-30 12:07:47', 'Transfer', -1254.2),
(20, 5, 28, '2026-02-16 02:34:57', 'Transfer', -1361.21),
(21, 25, 25, '2025-08-30 17:21:49', 'Withdrawal', -122.16),
(22, 18, 2, '2025-07-13 12:44:01', 'Transfer', -486.17),
(23, 4, 28, '2025-05-01 15:16:36', 'Withdrawal', -318.92),
(24, 18, 13, '2025-04-06 03:07:11', 'Deposit', 332.29),
(25, 25, 15, '2025-11-11 03:27:44', 'Deposit', 97.67),
(26, 18, 9, '2026-02-19 14:19:16', 'Deposit', 1924.44),
(27, 29, 28, '2026-01-14 20:31:00', 'Transfer', -666.67),
(28, 18, 29, '2025-12-20 09:43:23', 'Deposit', 121.97),
(29, 22, 5, '2026-01-29 18:21:05', 'Withdrawal', -328.62),
(30, 21, 30, '2025-06-24 14:21:53', 'Transfer', -1295.13);

-- --------------------------------------------------------
-- Table accounts attendue par l'application PHP : id, user_id, balance
-- --------------------------------------------------------
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `accounts` (`id`, `user_id`, `balance`)
SELECT DISTINCT ft.account_id, ft.user_id, da.balance
FROM fact_transactions ft
JOIN dim_account da ON ft.account_id = da.account_id;

-- --------------------------------------------------------
-- Utilisateurs avec mot de passe haché (section "Encryptage")
-- Les mots de passe sont stockés via password_hash(PASSWORD_BCRYPT) côté PHP (script seed)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users_hashed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;