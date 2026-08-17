<?php
/**
 * ShopVerse — Database Setup Script
 * Developed by MUHAMMAD OBAID
 * Run once: http://localhost:8000/setup.php
 */
$host = '127.0.0.1';
$user = 'shopverse';
$pass = 'Obaid@2026';
$dbName = 'dbs_shopverse';

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die('<h2>MySQL not running</h2><p>Start MySQL service first, then refresh this page.</p>');
}

$sqlFile = __DIR__ . '/database.sql';
$sql = file_get_contents($sqlFile);

// Execute multi-query
if (mysqli_multi_query($conn, $sql)) {
    do { if ($result = mysqli_store_result($conn)) mysqli_free_result($result); } while (mysqli_next_result($conn));
}

mysqli_select_db($conn, $dbName);

// Seed MUHAMMAD OBAID accounts
$adminPass = password_hash('Obaid@2026', PASSWORD_DEFAULT);
$custPass  = password_hash('Obaid@2026', PASSWORD_DEFAULT);

mysqli_query($conn, "DELETE FROM Admin WHERE username IN ('obaid','admin')");
$stmt = mysqli_prepare($conn, "INSERT INTO Admin (username, password_hash, full_name, email) VALUES ('obaid', ?, 'MUHAMMAD OBAID', 'obaid@shopverse.pk')");
mysqli_stmt_bind_param($stmt, 's', $adminPass);
mysqli_stmt_execute($stmt);

mysqli_query($conn, "DELETE FROM Customer WHERE email = 'obaid@shopverse.pk'");
$stmt = mysqli_prepare($conn, "INSERT INTO Customer (customer_id, first_name, last_name, email, password_hash, mobile_number, total_spent) VALUES (1, 'MUHAMMAD', 'OBAID', 'obaid@shopverse.pk', ?, '0300-0000000', 0)");
mysqli_stmt_bind_param($stmt, 's', $custPass);
mysqli_stmt_execute($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Complete — ShopVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif;}</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full border border-gray-100">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">✅</div>
            <h1 class="text-2xl font-bold text-gray-900">Setup Complete!</h1>
            <p class="text-gray-500 mt-1">Database <strong>dbs_shopverse</strong> is ready.</p>
        </div>

        <div class="bg-brand-50 rounded-xl p-5 mb-5 space-y-3 text-sm">
            <h2 class="font-bold text-brand-800">Your Accounts</h2>
            <div class="bg-white rounded-lg p-3 border border-brand-100">
                <p class="font-semibold text-gray-800">Customer Login</p>
                <p class="text-gray-600">Email: <code class="bg-gray-100 px-1 rounded">obaid@shopverse.pk</code></p>
                <p class="text-gray-600">Password: <code class="bg-gray-100 px-1 rounded">Obaid@2026</code></p>
            </div>
            <div class="bg-white rounded-lg p-3 border border-brand-100">
                <p class="font-semibold text-gray-800">Admin Login</p>
                <p class="text-gray-600">Username: <code class="bg-gray-100 px-1 rounded">obaid</code></p>
                <p class="text-gray-600">Password: <code class="bg-gray-100 px-1 rounded">Obaid@2026</code></p>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mb-4">Developed by MUHAMMAD OBAID</p>
        <a href="index.php" class="block w-full text-center bg-gradient-to-r from-indigo-600 to-indigo-800 text-white py-3 rounded-xl font-bold hover:opacity-90 transition">
            Open ShopVerse →
        </a>
    </div>
</body>
</html>
