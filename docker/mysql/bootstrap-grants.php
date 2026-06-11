<?php

/**
 * Bootstrap MySQL databases and grants for Hadir (run as root before migrate).
 */

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$rootUser = getenv('MYSQL_ROOT_USER') ?: 'root';
$rootPassword = getenv('MYSQL_ROOT_PASSWORD') ?: '';
$appUser = getenv('DB_USERNAME') ?: 'mysql';
$mainDb = getenv('DB_DATABASE') ?: 'kedatangan';
$ssoDb = getenv('SSO_DB_DATABASE') ?: 'sso_hadir';

if ($rootPassword === '') {
    echo "MYSQL_ROOT_PASSWORD not set — skipping grant bootstrap.\n";
    exit(0);
}

$dsn = sprintf('mysql:host=%s;port=%s', $host, $port);

try {
    $pdo = new PDO($dsn, $rootUser, $rootPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $mainDb),
    ));
    $pdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $ssoDb),
    ));

    $escapedUser = str_replace("'", "''", $appUser);
    $pdo->exec(sprintf("GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'", str_replace('`', '``', $mainDb), $escapedUser));
    $pdo->exec(sprintf("GRANT ALL PRIVILEGES ON `%s`.* TO '%s'@'%%'", str_replace('`', '``', $ssoDb), $escapedUser));
    $pdo->exec('FLUSH PRIVILEGES');

    echo "MySQL grants bootstrapped for user '{$appUser}' on '{$mainDb}' and '{$ssoDb}'.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'MySQL grant bootstrap failed: '.$e->getMessage()."\n");
    exit(1);
}
