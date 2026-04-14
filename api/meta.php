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

$cities = $pdo->query("SELECT * FROM cities WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$responses = $pdo->query("SELECT * FROM response_types WHERE is_active = 1 ORDER BY sort_order ASC, label ASC")->fetchAll();

echo json_encode([
    'cities' => $cities,
    'responses' => $responses
]);
exit;
