<?php
session_start();
require_once 'includes/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'rep/dashboard.php'));
        exit;
    }
    header('Location: index.php?error=Identifiants invalides');
    exit;
}
?>
