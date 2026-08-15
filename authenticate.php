<?php
// authenticate.php
require_once 'functions.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    header('Location: login.php?error=1');
    exit;
}

// $user = find_user_by_username($pdo, $username);
// if ($user && password_verify($password, $user['password_hash'])) {
//     // remove hash from session
//     unset($user['password_hash']);
//     $_SESSION['user'] = $user;
//     audit($pdo, $user['id'], "login");
//     header('Location: dashboard.php');
//     exit;
// } else {
//     header('Location: login.php?error=1');
//     exit;
// }
$user = find_user_by_username($pdo, $username);
$find_pass = $user['password_hash'];
if ($user && $find_pass===$password) {
    // remove hash from session
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    audit($pdo, $user['id'], "login");
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php?error=1');
    exit;
}
