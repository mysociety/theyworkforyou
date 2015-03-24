ALTER TABLE hansard ADD person_id int(11) NOT NULL default '0';
ALTER TABLE hansard ADD KEY person_id (person_id);
ALTER TABLE hansard ADD KEY hansard_person_id_hdate_hpos (person_id, hdate, hpos);
UPDATE hansard SET person_id = COALESCE((SELECT person_id FROM member WHERE member_id=speaker_id), 0);
ALTER TABLE hansard DROP speaker_id;

ALTER TABLE pbc_members ADD person_id int(11) NOT NULL default '0';
ALTER TABLE pbc_members ADD KEY person_id (person_id);
UPDATE pbc_members SET person_id = COALESCE((SELECT person_id FROM member WHERE member_id=member_id), 0);
ALTER TABLE pbc_members DROP member_id;
