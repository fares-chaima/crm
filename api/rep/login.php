<?php
require_once '../../includes/api_helpers.php';

apiRequireMethod(['POST']);
$data = apiGetRequestData();

repValidateRequiredFields($data, ['email', 'password']);

$stmt = $pdo->prepare('SELECT * FROM profiles WHERE email = ?');
$stmt->execute([$data['email']]);
$user = $stmt->fetch();

if (!$user || !password_verify($data['password'], $user['password'])) {
    apiJsonResponse(['success' => false, 'error' => 'Identifiants invalides'], 401);
}

if (($user['role'] ?? null) !== 'rep') {
    apiJsonResponse(['success' => false, 'error' => 'Ce compte nest pas un commercial'], 403);
}

$token = apiIssueUserToken($user);

apiJsonResponse([
    'success' => true,
    'token' => $token,
    'user' => [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
        'view_city_visits' => (bool) $user['view_city_visits'],
    ],
]);
