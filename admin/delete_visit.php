<?php
require_once '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') exit;

$visit_id = $_GET['id'];
$stmt = $pdo->prepare("DELETE FROM visits WHERE id = ?");
$stmt->execute([$visit_id]);

header('Location: dashboard.php?msg=Visit deleted');
exit;
?>
