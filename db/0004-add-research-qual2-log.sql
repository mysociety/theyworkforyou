CREATE TABLE `research_qual2_log` (
  `time` int(10) NOT NULL,
  `page` varchar(200) NOT NULL DEFAULT '',
  `bucket` tinyint(4) NOT NULL,
  `event` enum('view','show_popup','surpressed_popup','click_nav_link','click_popup_link') NOT NULL DEFAULT 'view',
  `data` varchar(100) DEFAULT NULL,
  `timer` int(11) DEFAULT NULL
);
