-- The application talks to two databases:
--   * the app database (created by MYSQL_DATABASE / MYSQL_USER in compose)
--   * the scraper database, written by the separate scraper project and read
--     through the `mysql_scraper` connection in config/database.php
CREATE DATABASE IF NOT EXISTS `fif_scraper`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'fif_scraper'@'%' IDENTIFIED BY 'fif_scraper';
GRANT ALL PRIVILEGES ON `fif_scraper`.* TO 'fif_scraper'@'%';

-- The app user needs the scraper database too: Process/Dataset queries join
-- across both connections and migration 2020_03_29_144025 alters `kerndaten`.
GRANT ALL PRIVILEGES ON `fif_scraper`.* TO 'offenevergaben'@'%';

FLUSH PRIVILEGES;
