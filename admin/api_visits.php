<?php
require_once '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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

// Time Period Logic
if (!empty($_GET['period'])) {
    switch ($_GET['period']) {
        case 'today':
            $where[] = "DATE(v.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $where[] = "DATE(v.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $where[] = "v.created_at >= DATE_SUB(CURDATE(), INTERVAL(WEEKDAY(CURDATE())) DAY)";
            break;
        case 'month':
            $where[] = "v.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            break;
    }
}

$query = "SELECT v.*, p.full_name as rep_name, c.name as city_name, r.label as response_label, r.color as response_color 
          FROM visits v 
          JOIN profiles p ON v.created_by = p.id 
          JOIN cities c ON v.city_id = c.id 
          JOIN response_types r ON v.response_id = r.id 
          WHERE " . implode(" AND ", $where) . "
          ORDER BY v.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$visits = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($visits);
exit;
?>
