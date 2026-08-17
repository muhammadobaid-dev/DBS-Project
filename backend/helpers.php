<?php
/**
 * ShopVerse Backend — Helper Functions
 * Developed by MUHAMMAD OBAID
 */

function basePath(): string {
    static $base = null;
    if ($base !== null) return $base;
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    return $base;
}

function url(string $path = ''): string {
    $base = basePath();
    $path = ltrim($path, '/');
    return $path ? ($base ? "$base/$path" : "/$path") : ($base ?: '/');
}

function getCartCount(): int {
    if (!isset($_SESSION['cart'])) return 0;
    return (int) array_sum(array_column($_SESSION['cart'], 'quantity'));
}

function formatPrice(float|int|string $price): string {
    return '$' . number_format((float) $price, 2);
}

function sanitize(mysqli $conn, ?string $input): string {
    return mysqli_real_escape_string($conn, trim($input ?? ''));
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function redirect(string $path): never {
    header('Location: ' . url($path));
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['customer_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login first to continue.');
        redirect('login.php');
    }
}

function isAdmin(): bool {
    return !empty($_SESSION['is_admin']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        redirect('admin.php');
    }
}
