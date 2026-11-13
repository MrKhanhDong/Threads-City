USE `test`;

CREATE TABLE IF NOT EXISTS `chat_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_user_id` BIGINT UNSIGNED NOT NULL,
    `second_user_id` BIGINT UNSIGNED NOT NULL,
    `first_read_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `second_read_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `first_typing_at` TIMESTAMP NULL DEFAULT NULL,
    `second_typing_at` TIMESTAMP NULL DEFAULT NULL,
    `last_message_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `chat_conversations_first_user_id_second_user_id_unique` (`first_user_id`, `second_user_id`),
    KEY `chat_conversations_last_message_at_index` (`last_message_at`),
    CONSTRAINT `chat_conversations_first_user_id_foreign` FOREIGN KEY (`first_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chat_conversations_second_user_id_foreign` FOREIGN KEY (`second_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `chat_conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_id` BIGINT UNSIGNED NOT NULL,
    `client_token` CHAR(36) NOT NULL,
    `reply_to_id` BIGINT UNSIGNED NULL,
    `body` TEXT NULL,
    `kind` VARCHAR(12) NOT NULL DEFAULT 'text',
    `attachment_path` VARCHAR(255) NULL,
    `attachment_mime` VARCHAR(80) NULL,
    `duration_ms` INT UNSIGNED NULL,
    `sticker` VARCHAR(30) NULL,
    `reactions` JSON NULL,
    `edited_at` TIMESTAMP NULL DEFAULT NULL,
    `recalled_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `chat_message_token_unique` (`chat_conversation_id`, `sender_id`, `client_token`),
    KEY `chat_messages_chat_conversation_id_id_index` (`chat_conversation_id`, `id`),
    CONSTRAINT `chat_messages_chat_conversation_id_foreign` FOREIGN KEY (`chat_conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chat_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chat_messages_reply_to_id_foreign` FOREIGN KEY (`reply_to_id`) REFERENCES `chat_messages` (`id`) ON DELETE SET NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @chat_migration_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_13_152313_create_chat_conversations_table', @chat_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_13_152313_create_chat_conversations_table'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_13_152314_create_chat_messages_table', @chat_migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_13_152314_create_chat_messages_table'
);

SHOW TABLES LIKE 'chat_%';
