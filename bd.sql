-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `evstbot`
--

-- --------------------------------------------------------

--
-- Структура таблицы `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL,
  `vk_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `agents`
--

CREATE TABLE IF NOT EXISTS `agents` (
  `id` int(11) NOT NULL,
  `vk_id` int(12) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `android_data`
--

CREATE TABLE IF NOT EXISTS `android_data` (
  `id` int(14) NOT NULL,
  `user_id` int(14) NOT NULL DEFAULT '0',
  `codename` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(85) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activated` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `android_peers`
--

CREATE TABLE IF NOT EXISTS `android_peers` (
  `id` int(20) NOT NULL,
  `user_id` int(15) NOT NULL,
  `c_name` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `android_settings`
--

CREATE TABLE IF NOT EXISTS `android_settings` (
  `id` int(14) NOT NULL,
  `user_id` int(14) NOT NULL,
  `address` varchar(535) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `bans`
--

CREATE TABLE IF NOT EXISTS `bans` (
  `id` int(255) NOT NULL,
  `peer_id` int(255) NOT NULL,
  `vk_id` int(255) NOT NULL,
  `moder_id` int(14) NOT NULL,
  `timeban` int(255) NOT NULL,
  `reason` varchar(2048) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `callback_data`
--

CREATE TABLE IF NOT EXISTS `callback_data` (
  `id` int(11) NOT NULL,
  `e_id` int(11) NOT NULL,
  `data` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `capt_own`
--

CREATE TABLE IF NOT EXISTS `capt_own` (
  `id` int(10) NOT NULL,
  `peer_id` int(14) NOT NULL,
  `vk_id` int(14) NOT NULL,
  `own_id` int(15) NOT NULL,
  `captcha` int(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `gban`
--

CREATE TABLE IF NOT EXISTS `gban` (
  `id` int(11) NOT NULL,
  `vk_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id` int(10) NOT NULL,
  `vk_id` int(13) NOT NULL,
  `command` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `param_command` varchar(2048) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `mutes`
--

CREATE TABLE IF NOT EXISTS `mutes` (
  `id` int(11) NOT NULL,
  `vk_id` int(255) NOT NULL,
  `moder_id` int(14) NOT NULL,
  `peer_id` int(255) NOT NULL,
  `timemute` int(255) NOT NULL,
  `reason` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `peers`
--

CREATE TABLE IF NOT EXISTS `peers` (
  `id` int(100) NOT NULL,
  `peer_id` int(13) NOT NULL,
  `autokick` int(1) NOT NULL DEFAULT '0',
  `gbots` int(1) NOT NULL DEFAULT '0',
  `disable_mentions` int(1) DEFAULT '0',
  `mailing` int(1) NOT NULL DEFAULT '1',
  `limit_warns` int(2) NOT NULL DEFAULT '3',
  `name_moder` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Модератор',
  `name_m_adm` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Младший администратор',
  `name_adm` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Администратор',
  `name_s_adm` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Старший администратор',
  `name_own` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Основатель',
  `welcome` varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `rules` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `android` int(14) NOT NULL DEFAULT '0',
  `statusvk` varchar(35) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'nact',
  `silent_mode` int(1) NOT NULL DEFAULT '0',
  `silent_mute` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `rang_commands`
--

CREATE TABLE IF NOT EXISTS `rang_commands` (
  `id` int(10) NOT NULL,
  `peer_id` int(14) NOT NULL,
  `ping` int(1) NOT NULL DEFAULT '0',
  `commands` int(1) NOT NULL DEFAULT '0',
  `kick` int(1) NOT NULL DEFAULT '3',
  `new_welcome` int(1) NOT NULL DEFAULT '3',
  `welcome` int(1) NOT NULL DEFAULT '0',
  `roulette` int(1) NOT NULL DEFAULT '0',
  `autokick` int(1) NOT NULL DEFAULT '4',
  `write` int(1) NOT NULL DEFAULT '0',
  `rang_up` int(1) NOT NULL DEFAULT '4',
  `rang_dw` int(1) NOT NULL DEFAULT '4',
  `update` int(1) NOT NULL DEFAULT '3',
  `set_new_rang` int(1) NOT NULL DEFAULT '4',
  `easter_egg` int(1) NOT NULL DEFAULT '0',
  `gbots` int(1) NOT NULL DEFAULT '4',
  `whoami` int(1) NOT NULL DEFAULT '0',
  `give_warn` int(1) NOT NULL DEFAULT '3',
  `unwarn` int(1) NOT NULL DEFAULT '3',
  `warns` int(1) NOT NULL DEFAULT '2',
  `rules` int(1) NOT NULL DEFAULT '3',
  `mentions` int(1) NOT NULL DEFAULT '3',
  `snick` int(1) NOT NULL DEFAULT '0',
  `mailing` int(1) NOT NULL DEFAULT '2',
  `toplist` int(1) NOT NULL DEFAULT '0',
  `ban` int(1) NOT NULL DEFAULT '3',
  `unban` int(1) NOT NULL DEFAULT '3',
  `mute` int(1) NOT NULL DEFAULT '4',
  `unmute` int(1) NOT NULL DEFAULT '4',
  `getBans` int(1) NOT NULL DEFAULT '3',
  `getMutes` int(1) NOT NULL DEFAULT '3',
  `set_calls` int(1) NOT NULL DEFAULT '4',
  `delete_msg` int(1) NOT NULL DEFAULT '4',
  `set_silent` int(1) NOT NULL DEFAULT '4',
  `set_android_trial` int(1) NOT NULL DEFAULT '4',
  `execute_android_command` int(1) NOT NULL DEFAULT '2',
  `return_user` int(1) NOT NULL DEFAULT '3'
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `report`
--

CREATE TABLE IF NOT EXISTS `report` (
  `id` int(6) NOT NULL,
  `vk_id` int(12) NOT NULL,
  `peer_id` int(12) NOT NULL,
  `ask` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none'
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `unitpay`
--

CREATE TABLE IF NOT EXISTS `unitpay` (
  `id` int(255) NOT NULL,
  `uid` int(255) NOT NULL,
  `amount` float NOT NULL,
  `product` int(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed` varchar(4) NOT NULL DEFAULT 'none'
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;

--
-- Объяснение структуры
-- id - айди у бд, uid - вк_айди чела который оплачивает, amount - сумма покупки, product - кол-во покупаемых коинов
--

INSERT INTO `unitpay` (`id`, `uid`, `amount`, `product`, `created_at`, `completed`) VALUES
(1, 1, 228, 100, '2020-08-03 13:15:27', 'none')

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(5) NOT NULL,
  `vk_id` int(15) NOT NULL,
  `nick` varchar(20) NOT NULL DEFAULT 'kgszbkzxfklbzjomdgbq',
  `rang` int(1) NOT NULL DEFAULT '0',
  `peer_id` int(12) NOT NULL,
  `ecoins` int(255) NOT NULL DEFAULT '0',
  `sms_day` int(255) NOT NULL DEFAULT '0',
  `sms_all` int(255) NOT NULL DEFAULT '0',
  `smsmin` int(100) NOT NULL DEFAULT '0',
  `warns` int(1) NOT NULL DEFAULT '0',
  `spammed` int(1) NOT NULL DEFAULT '0',
  `spam_reason` varchar(1024) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `timeroulette` int(255) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=515 DEFAULT CHARSET=utf8mb4;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `android_data`
--
ALTER TABLE `android_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `android_peers`
--
ALTER TABLE `android_peers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `android_settings`
--
ALTER TABLE `android_settings`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `bans`
--
ALTER TABLE `bans`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `callback_data`
--
ALTER TABLE `callback_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `capt_own`
--
ALTER TABLE `capt_own`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `gban`
--
ALTER TABLE `gban`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `mutes`
--
ALTER TABLE `mutes`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `peers`
--
ALTER TABLE `peers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `rang_commands`
--
ALTER TABLE `rang_commands`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `roomssecret`
--
ALTER TABLE `roomssecret`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `unitpay`
--
ALTER TABLE `unitpay`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `userbot_bind`
--
ALTER TABLE `userbot_bind`
  ADD PRIMARY KEY (`id_user`,`code`);

--
-- Индексы таблицы `userbot_data`
--
ALTER TABLE `userbot_data`
  ADD PRIMARY KEY (`id_user`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT для таблицы `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT для таблицы `android_data`
--
ALTER TABLE `android_data`
  MODIFY `id` int(14) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=9;
--
-- AUTO_INCREMENT для таблицы `android_peers`
--
ALTER TABLE `android_peers`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT для таблицы `android_settings`
--
ALTER TABLE `android_settings`
  MODIFY `id` int(14) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT для таблицы `bans`
--
ALTER TABLE `bans`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `callback_data`
--
ALTER TABLE `callback_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `capt_own`
--
ALTER TABLE `capt_own`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `gban`
--
ALTER TABLE `gban`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=83;
--
-- AUTO_INCREMENT для таблицы `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT для таблицы `mutes`
--
ALTER TABLE `mutes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `peers`
--
ALTER TABLE `peers`
  MODIFY `id` int(100) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=81;
--
-- AUTO_INCREMENT для таблицы `rang_commands`
--
ALTER TABLE `rang_commands`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=38;
--
-- AUTO_INCREMENT для таблицы `report`
--
ALTER TABLE `report`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT для таблицы `roomssecret`
--
ALTER TABLE `roomssecret`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `unitpay`
--
ALTER TABLE `unitpay`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=60;
--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=515;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
