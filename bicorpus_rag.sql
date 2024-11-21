-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 21, 2024 at 04:55 PM
-- Server version: 5.7.32
-- PHP Version: 7.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bicorpus_rag`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(5) NOT NULL,
  `sourcefilename` varchar(200) DEFAULT NULL,
  `savefilepath` varchar(200) DEFAULT NULL,
  `savefilename` varchar(200) DEFAULT NULL,
  `uploaduser` varchar(50) DEFAULT NULL,
  `uploadtime` varchar(50) DEFAULT NULL,
  `source_lang` varchar(100) NOT NULL COMMENT '源语言',
  `target_lang` varchar(100) NOT NULL COMMENT '目标语言',
  `field` varchar(50) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `comments` varchar(500) NOT NULL,
  `status` int(5) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `sourcefilename`, `savefilepath`, `savefilename`, `uploaduser`, `uploadtime`, `source_lang`, `target_lang`, `field`, `description`, `comments`, `status`) VALUES
(5, 'Demo.tmx', 'upload/240723072749787810861.tmx', '240723072749787810861.tmx', '1', '240723072749', 'zh-CN', 'en-US', '本地测试', '', '', 0),
(6, '中文_zh-CN_en-US.tmx', 'upload/240728093915783538473.tmx', '240728093915783538473.tmx', '1', '240728093915', 'zh-CN', 'en-US', '外交语料', '外交部答记者问（7月26日）', '', 0),
(7, '西翻_中文_zh-CN_en-US.tmx', 'upload/241102034905947031314.tmx', '241102034905947031314.tmx', '1', '241102034905', 'zh-CN', 'en-US', '西安翻译学院', '西安翻译学院语料库', '', 0),
(8, '高举中国特色社会主义伟大旗帜_zh-CN_en-US.tmx', 'upload/24111511214447756911.tmx', '24111511214447756911.tmx', '1', '241115112144', 'zh-CN', 'en-US', '时政', '二十大报告中英双语语料库', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `metadata`
--

CREATE TABLE `metadata` (
  `id` int(5) NOT NULL,
  `file_id` int(5) NOT NULL,
  `type` varchar(255) NOT NULL,
  `source_title` varchar(255) NOT NULL,
  `target_title` varchar(255) NOT NULL,
  `source_link` varchar(255) NOT NULL,
  `target_link` varchar(255) NOT NULL,
  `source_date` varchar(255) NOT NULL,
  `target_date` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `termdata`
--

CREATE TABLE `termdata` (
  `ID` int(5) NOT NULL,
  `zh_CN` varchar(50) DEFAULT NULL,
  `en_US` varchar(50) DEFAULT NULL,
  `length` int(5) NOT NULL,
  `sentence_id` int(5) NOT NULL,
  `segment_id` int(5) NOT NULL,
  `position_id` int(5) NOT NULL,
  `pos` varchar(100) NOT NULL,
  `isterm` int(5) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `text_chunks`
--

CREATE TABLE `text_chunks` (
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `embedding` json DEFAULT NULL,
  `upload_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `upload_user` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tmdata`
--

CREATE TABLE `tmdata` (
  `id` int(5) NOT NULL,
  `source_content` varchar(2000) NOT NULL,
  `target_content` varchar(2000) NOT NULL,
  `embedding` text NOT NULL,
  `source_lang` varchar(100) NOT NULL,
  `target_lang` varchar(100) NOT NULL,
  `status` int(5) NOT NULL DEFAULT '0',
  `file_id` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `university` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `anythingllmkey` varchar(255) NOT NULL,
  `type` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `fullname`, `university`, `password`, `anythingllmkey`, `type`) VALUES
(1, 'admin', 'BiCorpus', 'BiCorpus', 'BiCorpus2021!', 'YD44S65-DAD49XN-PWZ8FJC-QDQZ36R', 1),
(2, 'test', '测试用户', 'BiCorpus', 'BiCorpus2021!', '', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `metadata`
--
ALTER TABLE `metadata`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `termdata`
--
ALTER TABLE `termdata`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `text_chunks`
--
ALTER TABLE `text_chunks`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `text_chunks` ADD FULLTEXT KEY `content_index` (`content`);

--
-- Indexes for table `tmdata`
--
ALTER TABLE `tmdata`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `metadata`
--
ALTER TABLE `metadata`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `termdata`
--
ALTER TABLE `termdata`
  MODIFY `ID` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `text_chunks`
--
ALTER TABLE `text_chunks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tmdata`
--
ALTER TABLE `tmdata`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
