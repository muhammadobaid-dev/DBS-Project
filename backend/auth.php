<?php
/**
 * ShopVerse Backend — Authentication
 * Developed by MUHAMMAD OBAID
 */

require_once __DIR__ . '/helpers.php';

function loginCustomer(mysqli $conn, string $email, string $password): array {
    $email = sanitize($conn, $email);
    $stmt = mysqli_prepare($conn, "SELECT customer_id, first_name, last_name, password_hash FROM Customer WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    $_SESSION['customer_id']    = (int) $row['customer_id'];
    $_SESSION['customer_name']  = trim($row['first_name'] . ' ' . $row['last_name']);
    $_SESSION['customer_email'] = $email;
    $_SESSION['customer_fname'] = $row['first_name'];
    $_SESSION['customer_lname'] = $row['last_name'];

    return ['success' => true, 'name' => $row['first_name']];
}

function registerCustomer(mysqli $conn, array $data): array {
    $first = sanitize($conn, $data['first_name'] ?? '');
    $last  = sanitize($conn, $data['last_name'] ?? '');
    $email = sanitize($conn, $data['email'] ?? '');
    $phone = sanitize($conn, $data['phone'] ?? '');
    $pass  = $data['password'] ?? '';
    $pass2 = $data['password2'] ?? '';

    $errors = [];
    if (!$first || !$email || !$pass) $errors[] = 'First name, email and password are required.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';
    if (strlen($pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if ($errors) return ['success' => false, 'errors' => $errors];

    $stmt = mysqli_prepare($conn, "SELECT customer_id FROM Customer WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
        return ['success' => false, 'errors' => ['An account with this email already exists.']];
    }

    $nextId   = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(customer_id),0)+1 AS n FROM Customer"))['n'];
    $passHash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn, "INSERT INTO Customer (customer_id, first_name, last_name, email, password_hash, mobile_number, total_spent) VALUES (?, ?, ?, ?, ?, ?, 0)");
    mysqli_stmt_bind_param($stmt, 'isssss', $nextId, $first, $last, $email, $passHash, $phone);
    mysqli_stmt_execute($stmt);

    $_SESSION['customer_id']    = $nextId;
    $_SESSION['customer_name']  = trim("$first $last");
    $_SESSION['customer_email'] = $email;
    $_SESSION['customer_fname'] = $first;
    $_SESSION['customer_lname'] = $last;

    return ['success' => true, 'name' => $first];
}

function loginAdmin(mysqli $conn, string $username, string $password): array {
    $username = sanitize($conn, $username);
    $stmt = mysqli_prepare($conn, "SELECT admin_id, username, password_hash, full_name FROM Admin WHERE username = ? AND is_active = 1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid admin credentials.'];
    }

    $_SESSION['is_admin']      = true;
    $_SESSION['admin_id']      = (int) $row['admin_id'];
    $_SESSION['admin_name']    = $row['full_name'];
    $_SESSION['admin_username'] = $row['username'];

    mysqli_query($conn, "UPDATE Admin SET last_login = NOW() WHERE admin_id = {$row['admin_id']}");

    return ['success' => true];
}

function logoutCustomer(): void {
    unset(
        $_SESSION['customer_id'], $_SESSION['customer_name'],
        $_SESSION['customer_email'], $_SESSION['customer_fname'], $_SESSION['customer_lname']
    );
}

function logoutAdmin(): void {
    unset($_SESSION['is_admin'], $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_username']);
}
