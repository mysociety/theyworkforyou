ALTER TABLE users
  ADD COLUMN `facebook_id` char(24) default NULL,
  ADD COLUMN `facebook_token` char(200) default NULL
