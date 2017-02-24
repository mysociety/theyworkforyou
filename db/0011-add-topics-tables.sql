CREATE TABLE `topics` (
    `id` int(11) NOT NULL auto_increment,
    `slug` varchar(100) NOT NULL,
    `title` text NOT NULL,
    `description` text,
    `search_string` text,
    `front_page` bool DEFAULT FALSE,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
);

CREATE TABLE `topic_policysets` (
  `topic_key` int(11) NOT NULL,
  `policyset` varchar(30) NOT NULL,
  UNIQUE KEY `topic_policyset` (`topic_key`, `policyset`)
);

CREATE TABLE `topic_policies` (
  `topic_key` int(11) NOT NULL,
  `policy_id` int(11) NOT NULL,
  UNIQUE KEY `topic_policy` (`topic_key`, `policy_id`)
);

CREATE TABLE `topic_epobjects` (
  `topic_key` int(11) NOT NULL,
  `epobject_id` int(11) NOT NULL,
  UNIQUE KEY `topic_object` (`topic_key`, `epobject_id`)
);

