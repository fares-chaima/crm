<?php
require_once '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) exit;

$visit_id = $_GET['id'];
$rep_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("DELETE FROM visits WHERE id = ? AND created_by = ?");
$stmt->execute([$visit_id, $rep_id]);

header('Location: dashboard.php?msg=Visit deleted');
exit;
?>
