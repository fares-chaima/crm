<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Simple Token Verification Helper
function verifyToken($pdo) {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return false;
    }
    
    $auth = $headers['Authorization'];
    if (strpos($auth, 'Bearer ') === 0) {
        $token = substr($auth, 7);
        $parts = explode(':', base64_decode($token));
        if (count($parts) === 2) {
            $userId = $parts[0];
            $stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        }
    }
    return false;
}

$user = verifyToken($pdo);
if (!$user) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $where = ["1=1"];
    $params = [];
    
    if ($user['role'] !== 'admin') {
        $where[] = "v.created_by = ?";
        $params[] = $user['id'];
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
    echo json_encode($visits);
    
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Check required fields
    if (!isset($data['doctor_name']) || !isset($data['latitude']) || !isset($data['longitude']) || !isset($data['city_id']) || !isset($data['response_id'])) {
        echo json_encode(['error' => 'Champs manquants']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO visits (doctor_name, phone_number, address, latitude, longitude, city_id, response_id, comment, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $success = $stmt->execute([
        $data['doctor_name'],
        $data['phone_number'] ?? null,
        $data['address'] ?? null,
        $data['latitude'],
        $data['longitude'],
        $data['city_id'],
        $data['response_id'],
        $data['comment'] ?? null,
        $user['id']
    ]);
    
    echo json_encode(['success' => $success, 'id' => $pdo->lastInsertId()]);
}
exit;
