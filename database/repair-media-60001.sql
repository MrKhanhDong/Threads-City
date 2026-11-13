USE `test`;

SELECT `id`, `path`, `created_at` FROM `media` WHERE `id` = 1;

UPDATE `media`
SET `path` = 'threads-city/media/ad3e66b2-52aa-45b6-8c88-dbb8c1a5e31f'
WHERE `id` = 1
  AND `path` NOT LIKE 'https://res.cloudinary.com/%'
  AND EXISTS (
      SELECT 1 FROM `post_media`
      WHERE `post_media`.`media_id` = `media`.`id`
        AND `post_media`.`post_id` = 60001
  );

SELECT `id`, `path` FROM `media` WHERE `id` = 1;
