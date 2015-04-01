ALTER TABLE hansard ADD person_id int(11) NOT NULL default '0',
    ADD KEY person_id (person_id),
    ADD KEY hansard_person_id_hdate_hpos (person_id, hdate, hpos),
    DROP KEY hansard_speaker_id_hdate_hpos;
UPDATE hansard SET person_id = (SELECT person_id FROM member WHERE member_id=speaker_id) WHERE speaker_id!=0;
ALTER TABLE hansard DROP speaker_id;

ALTER TABLE pbc_members ADD person_id int(11) NOT NULL,
    ADD KEY person_id (person_id);
UPDATE pbc_members SET person_id = (SELECT person_id FROM member WHERE member.member_id=pbc_members.member_id);
ALTER TABLE pbc_members DROP member_id;
