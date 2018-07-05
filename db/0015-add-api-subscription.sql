CREATE TABLE `api_subscription` (
  `user_id` int(11) NOT NULL,
  `stripe_id` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`)
);
