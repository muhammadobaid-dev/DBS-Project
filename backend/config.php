<?php
/**
 * ShopVerse Backend — Database Configuration
 * Developed by MUHAMMAD OBAID
 */

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'shopverse');
define('DB_PASS', 'Obaid@2026');
define('DB_NAME', 'dbs_shopverse');

define('APP_NAME', 'ShopVerse');
define('DEVELOPER', 'MUHAMMAD OBAID');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('<div style="font-family:Inter,sans-serif;padding:2rem;background:#fef2f2;color:#991b1b;border-radius:12px;max-width:600px;margin:2rem auto;border:1px solid #fecaca;">
        <h2 style="margin:0 0 .5rem;">Database Connection Failed</h2>
        <p style="margin:0 0 .5rem;">' . htmlspecialchars(mysqli_connect_error()) . '</p>
        <p style="margin:0;font-size:.875rem;">Run <a href="setup.php" style="color:#4c6ef5;">setup.php</a> to create the database, or start MySQL service.</p>
    </div>');
}

mysqli_set_charset($conn, 'utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
