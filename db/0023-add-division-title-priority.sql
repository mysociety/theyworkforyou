ALTER TABLE `divisions`
ADD COLUMN title_priority ENUM('ORIGINAL_HEADER', 'PARLIAMENT_DESCRIBED', 'MANUAL') default 'ORIGINAL_HEADER';

UPDATE `divisions`
SET title_priority = CASE
  WHEN yes_text IS NOT NULL AND yes_text <> '' THEN 'MANUAL'
  ELSE title_priority
END;