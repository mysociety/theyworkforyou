ALTER TABLE partypolicy
  ADD COLUMN divisions int(11) NOT NULL,
  ADD COLUMN date_min date NOT NULL,
  ADD COLUMN date_max date NOT NULL;

