CREATE TABLE `vector_search_suggestions` (
  `search_term` varchar(100) NOT NULL default '',
  `search_suggestion` varchar(100) NOT NULL default '',
  KEY `search_term` (`search_term`)
);
