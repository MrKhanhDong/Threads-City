-- Run in TiDB Cloud SQL Editor with the production database selected.
USE `test`;

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `lock_reason` TEXT NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `lock_expires_at` TIMESTAMP NULL DEFAULT NULL;
CREATE INDEX IF NOT EXISTS `users_lock_expires_at_index` ON `users` (`lock_expires_at`);
ALTER TABLE `posts` ADD COLUMN IF NOT EXISTS `moderation_hidden_at` TIMESTAMP NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `moderation_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `admin_id` BIGINT UNSIGNED NULL,
    `target_type` VARCHAR(30) NOT NULL,
    `target_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(40) NOT NULL,
    `reason` TEXT NOT NULL,
    `before_state` JSON NOT NULL,
    `after_state` JSON NOT NULL,
    `created_at` TIMESTAMP NOT NULL,
    INDEX `moderation_logs_target_type_target_id_created_at_index` (`target_type`, `target_id`, `created_at`),
    CONSTRAINT `moderation_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND ((TABLE_NAME = 'users' AND COLUMN_NAME IN ('lock_reason', 'lock_expires_at'))
    OR (TABLE_NAME = 'posts' AND COLUMN_NAME = 'moderation_hidden_at'));
SHOW TABLES LIKE 'moderation_logs';
