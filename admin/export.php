<?php
require_once '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) exit;

$rep_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

if ($is_admin) {
    $where = ["1=1"];
    $params = [];
    if (!empty($_GET['city_id'])) {
        $where[] = "v.city_id = ?";
        $params[] = $_GET['city_id'];
    }
    if (!empty($_GET['response_id'])) {
        $where[] = "v.response_id = ?";
        $params[] = $_GET['response_id'];
    }
    if (!empty($_GET['rep_id'])) {
        $where[] = "v.created_by = ?";
        $params[] = $_GET['rep_id'];
    }
    if (!empty($_GET['date_from'])) {
        $where[] = "DATE(v.created_at) >= ?";
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where[] = "DATE(v.created_at) <= ?";
        $params[] = $_GET['date_to'];
    }

    $query = "SELECT v.*, p.email as created_by_email, c.name as city_name, r.label as response_label 
              FROM visits v 
              JOIN profiles p ON v.created_by = p.id 
              JOIN cities c ON v.city_id = c.id 
              JOIN response_types r ON v.response_id = r.id 
              WHERE " . implode(" AND ", $where) . "
              ORDER BY v.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
} else {
    $stmt = $pdo->prepare("SELECT v.*, p.email as created_by_email, c.name as city_name, r.label as response_label 
                         FROM visits v 
                         JOIN profiles p ON v.created_by = p.id 
                         JOIN cities c ON v.city_id = c.id 
                         JOIN response_types r ON v.response_id = r.id 
                         WHERE v.created_by = ?
                         ORDER BY v.created_at DESC");
    $stmt->execute([$rep_id]);
}

$visits = $stmt->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="visits_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['doctor_name', 'phone_number', 'address', 'latitude', 'longitude', 'city', 'response', 'comment', 'photo_url', 'created_by_email', 'created_at', 'last_edited_at']);

foreach ($visits as $v) {
    fputcsv($output, [
        $v['doctor_name'],
        $v['phone_number'],
        $v['address'],
        $v['latitude'],
        $v['longitude'],
        $v['city_name'],
        $v['response_label'],
        $v['comment'],
        $v['photo_url'],
        $v['created_by_email'],
        $v['created_at'],
        $v['last_edited_at']
    ]);
}
fclose($output);
exit;
?>
