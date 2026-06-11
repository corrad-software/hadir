-- Run once as MySQL root (Coolify terminal or MySQL Workbench).
-- Grants app user access to kedatangan + sso_hadir databases.

CREATE DATABASE IF NOT EXISTS kedatangan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS sso_hadir
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON kedatangan.* TO 'mysql'@'%';
GRANT ALL PRIVILEGES ON sso_hadir.* TO 'mysql'@'%';
FLUSH PRIVILEGES;
