WITH RECURSIVE ancestry AS (
  SELECT *
  FROM folders
  WHERE id = :folder_id
  UNION ALL
  SELECT folders.*
  FROM folders
         INNER JOIN ancestry ON ancestry.folder_id = folders.id
)
SELECT * FROM ancestry