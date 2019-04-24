-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2019-04-24 06:48:15
-- 服务器版本： 10.1.36-MariaDB
-- PHP 版本： 7.0.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `dev_online_report`
--

-- --------------------------------------------------------

--
-- 表的结构 `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2016_06_01_000001_create_oauth_auth_codes_table', 1),
(2, '2016_06_01_000002_create_oauth_access_tokens_table', 1),
(3, '2016_06_01_000003_create_oauth_refresh_tokens_table', 1),
(4, '2016_06_01_000004_create_oauth_clients_table', 1),
(5, '2016_06_01_000005_create_oauth_personal_access_clients_table', 1),
(6, '2014_10_12_000000_create_users_table', 2),
(7, '2014_10_12_100000_create_password_resets_table', 2);

-- --------------------------------------------------------

--
-- 表的结构 `oauth_access_tokens`
--

CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `oauth_access_tokens`
--

INSERT INTO `oauth_access_tokens` (`id`, `user_id`, `client_id`, `name`, `scopes`, `revoked`, `created_at`, `updated_at`, `expires_at`) VALUES
('20c02e9028fec3d839ffdd69652eeb5ba5cf245c35c3eb49aa3ce8d8dd02742a75bdc383eadafb62', 2, 1, 'Personal Access Token', '[]', 0, '2019-04-23 16:37:45', '2019-04-23 16:37:45', '2019-05-01 02:37:46'),
('22fcf910994171db30b35e0cfa6532f44117859c4326eb319df682fdf2f5e9c9cec5661d90a9d041', 2, 1, 'Personal Access Token', '[]', 0, '2019-04-23 16:42:21', '2019-04-23 16:42:21', '2020-04-24 02:42:21'),
('4c84cd53305f64f2e94f3a463bb543902e5ae9681358c0d12d27d99b68061ad2b6fd7c390b4b0a1d', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-23 15:49:32', '2019-04-23 15:49:32', '2019-05-01 01:49:33'),
('542b87742c285bcfc6f325037163a457d1919c24b6b4e3b9674de022e81c0abf599d6c5e584b0921', 2, 1, 'Personal Access Token', '[]', 0, '2019-04-23 16:41:18', '2019-04-23 16:41:18', '2020-04-24 02:41:18'),
('54851491e7293b0ce4986c8b2a673693d3ce6f458df305b03c85abf2bd8bf63b11c0ac83292dc084', 2, 1, 'Personal Access Token', '[]', 0, '2019-04-23 15:59:30', '2019-04-23 15:59:30', '2019-05-01 01:59:30'),
('583daaff1e0b17e5048b5337fb0c20497cf9fce95f02ee01d33ac072106fa07fd3c387f1ada7b2fc', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:22:53', '2019-04-22 19:22:53', '2019-04-30 05:22:53'),
('701c3bf455abd000154d13494c223ae22c0619f7fceb77edcaebe6a8de5a174b65356ca3811de379', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-22 20:01:05', '2019-04-22 20:01:05', '2019-04-30 06:01:05'),
('8ffe99ca60f3d38244aea9dec71ce52ded062f5b8cff7bb6d1fb180e9451beac9e6e1a76a832641d', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:22:07', '2019-04-22 19:22:07', '2019-04-30 05:22:07'),
('9cffd355fd66bae82aba1f07c5ebdd68f662e0a3dd178425a871350c62f750753d833d3773a48a5c', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-23 14:04:22', '2019-04-23 14:04:22', '2019-05-01 00:04:22'),
('cebe309e3de32d94c7642ea8465b0c6c3925445fde4fbbf7bc70483a7cdd349d8c4d20f7d4639240', 3, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:18:29', '2019-04-22 19:18:29', '2019-04-30 05:18:29'),
('d687044af5fa1bde5a4a8aa166611734b0e9e22e3cc2a3ad549a58cfbc8c40ae6a7ef9e91969ffce', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-23 14:03:17', '2019-04-23 14:03:17', '2019-05-01 00:03:18'),
('de34d4a5293d5b02d59bfa2ab3027555da199e12866c3afd94a01c1e4fdf1bfdc8a14741bb2a4c1f', 3, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:21:23', '2019-04-22 19:21:23', '2019-04-30 05:21:23'),
('de7bd01fb561e6843b199b00bbfd36dbdae3c28889003a8ba7482d51892a0adfda2fdee6ec410b7b', 3, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:18:04', '2019-04-22 19:18:04', '2020-04-23 05:18:04'),
('df4c55f798de351f4467d0373c737a0f74d5a349c256238834475c6f8b3512bdd81e4074a2c031a2', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:57:40', '2019-04-22 19:57:40', '2019-04-30 05:57:40'),
('e001357caa9b39cf579838f21f53a77862fead76a17b0d5905f6c08d5cd4cdf60ea55e5beaf86dbc', 3, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:21:38', '2019-04-22 19:21:38', '2019-04-30 05:21:38'),
('e3635ef8261e692e8b51282060004a08088718c92b077cd5c97028ffadf7f7041d444fa0cd4c3ef5', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-23 14:06:34', '2019-04-23 14:06:34', '2019-05-01 00:06:34'),
('f64262968585489d9b273543610f3aeda7a190b7746daf8111120911afc87eb658200190426a1bdd', 1, 1, 'Personal Access Token', '[]', 0, '2019-04-22 19:59:56', '2019-04-22 19:59:56', '2019-04-30 05:59:56');

-- --------------------------------------------------------

--
-- 表的结构 `oauth_auth_codes`
--

CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `scopes` text COLLATE utf8mb4_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_clients`
--

CREATE TABLE `oauth_clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `redirect` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `oauth_clients`
--

INSERT INTO `oauth_clients` (`id`, `user_id`, `name`, `secret`, `redirect`, `personal_access_client`, `password_client`, `revoked`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Laravel Personal Access Client', 'aa5rrW1AbKsg0Rc0FyPKYxxeiqbAILdWvniubCWs', 'http://localhost', 1, 0, 0, '2019-04-22 19:16:51', '2019-04-22 19:16:51'),
(2, NULL, 'Laravel Password Grant Client', 'E5J8IIBn51UxLcpzM0sPtohp14Dfm5azlRdvjfrN', 'http://localhost', 0, 1, 0, '2019-04-22 19:16:51', '2019-04-22 19:16:51');

-- --------------------------------------------------------

--
-- 表的结构 `oauth_personal_access_clients`
--

CREATE TABLE `oauth_personal_access_clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `oauth_personal_access_clients`
--

INSERT INTO `oauth_personal_access_clients` (`id`, `client_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2019-04-22 19:16:51', '2019-04-22 19:16:51');

-- --------------------------------------------------------

--
-- 表的结构 `oauth_refresh_tokens`
--

CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_token_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `shops`
--

CREATE TABLE `shops` (
  `shop_id` int(11) NOT NULL,
  `shop_name` varchar(50) DEFAULT NULL,
  `database_ip` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `shops`
--

INSERT INTO `shops` (`shop_id`, `shop_name`, `database_ip`) VALUES
(1, '有米酸奶', '110.141.242.248'),
(2, '豆捞', '58.171.104.68');

-- --------------------------------------------------------

--
-- 表的结构 `shoptouser`
--

CREATE TABLE `shoptouser` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `shoptouser`
--

INSERT INTO `shoptouser` (`id`, `user_id`, `shop_id`) VALUES
(1, 1, 1),
(2, 2, 2);

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Jhon', 'Jhon@google.com', '$2y$10$z1vvW0hb.V6PaNcocIm9C.CIfQKSgRsFa2DAwTMeHsigX5CoRrQVG', NULL, '2019-04-22 19:14:00', '2019-04-22 19:14:00'),
(2, 'Tom', 'Tom@google.com', '$2y$10$SfZToX9L9pVqlLERXvT8zeZIfhfSpxJ/5YDLMTALT6ssKIXQ/t4c.', NULL, '2019-04-23 15:59:19', '2019-04-23 15:59:19');

--
-- 转储表的索引
--

--
-- 表的索引 `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `oauth_access_tokens`
--
ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_access_tokens_user_id_index` (`user_id`);

--
-- 表的索引 `oauth_auth_codes`
--
ALTER TABLE `oauth_auth_codes`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `oauth_clients`
--
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_clients_user_id_index` (`user_id`);

--
-- 表的索引 `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_personal_access_clients_client_id_index` (`client_id`);

--
-- 表的索引 `oauth_refresh_tokens`
--
ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`);

--
-- 表的索引 `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- 表的索引 `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`shop_id`);

--
-- 表的索引 `shoptouser`
--
ALTER TABLE `shoptouser`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `oauth_clients`
--
ALTER TABLE `oauth_clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `oauth_personal_access_clients`
--
ALTER TABLE `oauth_personal_access_clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `shops`
--
ALTER TABLE `shops`
  MODIFY `shop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `shoptouser`
--
ALTER TABLE `shoptouser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
