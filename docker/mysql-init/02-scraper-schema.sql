-- Placeholder schema for the scraper database.
--
-- These tables belong to the separate scraper project, not to this repository.
-- They are created empty here so that `php artisan migrate` succeeds (migration
-- 2020_03_29_144025_add_app_columns_to_fif_scraper_db_tables adds app_processed_at /
-- app_dataset_id to `kerndaten`) and so `fif:process` can run against real data once
-- the scraper dump is imported.
--
-- The columns below are the ones this application reads (see App\ScraperKerndaten,
-- App\ScraperQuelle and App\Jobs\Process). If you import a real scraper dump, drop
-- these tables first and let the dump create them.

USE `fif_scraper`;

CREATE TABLE IF NOT EXISTS `quellen` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `alias`      VARCHAR(191) NOT NULL,
    `name`       VARCHAR(191) DEFAULT NULL,
    `active`     TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `quellen_alias_unique` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kerndaten` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- joined against quellen.alias (App\ScraperKerndaten::rel_quelle)
    `quelle`       VARCHAR(191) NOT NULL,
    `item_id`      VARCHAR(191) NOT NULL,
    -- read with Carbon::createFromFormat('Y-m-d H:i:s.u', ...)
    `item_lastmod` DATETIME(6) DEFAULT NULL,
    `version`      INT DEFAULT NULL,
    `xml`          LONGTEXT,
    PRIMARY KEY (`id`),
    KEY `kerndaten_quelle_item_id_index` (`quelle`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
