-- Committee and other organisation details from parlparse members/posts.
-- One row per organisation per language: Senedd committees have an 'en' and a
-- 'cy' row, everything else has a single 'en' row.

CREATE TABLE `organization` (
  `org_id` varchar(150) NOT NULL,
  `language` varchar(5) NOT NULL default 'en',
  `parliament` varchar(20) NOT NULL default '',
  `classification` varchar(50) NOT NULL default '',
  `slug` varchar(150) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `url` varchar(255) NOT NULL default '',
  `tags` varchar(255) NOT NULL default '',
  `parent_org_id` varchar(150) default NULL,
  `loader` varchar(30) NOT NULL default '',
  PRIMARY KEY (`org_id`, `language`),
  UNIQUE KEY `language_parliament_slug` (`language`, `parliament`, `slug`),
  KEY `parliament` (`parliament`)
);
