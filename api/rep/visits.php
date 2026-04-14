<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');
$repId = (int) $user['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    apiJsonResponse([
        'success' => true,
        'visits' => repFetchVisits($pdo, $_GET, $repId),
    ]);
}

if ($method === 'POST') {
    $data = apiGetRequestData();

    repValidateRequiredFields($data, ['doctor_name', 'latitude', 'longitude', 'city_id', 'response_id']);
    repEnsureCityAllowed($pdo, $repId, (int) $data['city_id']);

    $stmt = $pdo->prepare(
        'INSERT INTO visits (doctor_name, phone_number, address, latitude, longitude, city_id, response_id, comment, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['doctor_name'],
        $data['phone_number'] ?? null,
        $data['address'] ?? null,
        $data['latitude'],
        $data['longitude'],
        (int) $data['city_id'],
        (int) $data['response_id'],
        $data['comment'] ?? null,
        $repId,
    ]);

    apiJsonResponse([
        'success' => true,
        'id' => (int) $pdo->lastInsertId(),
    ], 201);
}

apiJsonResponse(['success' => false, 'error' => 'Methode non autorisee'], 405);
