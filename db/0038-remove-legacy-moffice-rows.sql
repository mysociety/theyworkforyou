-- The office rows now come from parlparse members/posts via scripts/posts.
-- Drop what the retired load-people loader wrote from ministers.json and
-- ministers-2010.json and the importer has not taken over - it claims a row by
-- id and stamps its own loader, and the Westminster file reuses the same
-- uk.parliament.data ids, so loader is what tells the two apart. Older
-- chgpages-era rows have numeric ids and are left be.

DELETE FROM moffice
  WHERE loader = ''
    AND (moffice_id LIKE 'uk.parliament.data/%'
      OR moffice_id LIKE 'uk.org.publicwhip/moffice/%');
