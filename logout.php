<?php
require_once 'config.php';
require_once 'backend/auth.php';
logoutCustomer();
session_destroy();
header('Location: ' . url('index.php'));
exit;
