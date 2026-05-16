<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');
$repId = (int) $user['id'];
$method = apiRequireMethod(['GET', 'POST']);

if ($method === 'GET') {
    apiJsonResponse([
        'success' => true,
        'visits' => repFetchVisits($pdo, $_GET, $repId),
    ]);
}

if ($method === 'POST') {
    $data = apiGetRequestData();
    $photoUrl = repHandleUploadedPhoto('photo');

    repValidateRequiredFields($data, ['doctor_name', 'latitude', 'longitude', 'city_id', 'response_id']);
    repEnsureCityAllowed($pdo, $repId, (int) $data['city_id']);
    $visitCount = isset($data['visit_count']) ? max(1, (int) $data['visit_count']) : 1;

    $stmt = $pdo->prepare(
        'INSERT INTO visits (doctor_name, phone_number, address, latitude, longitude, city_id, response_id, comment, photo_url, visit_count, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
        $photoUrl,
        $visitCount,
        $repId,
    ]);

    apiJsonResponse([
        'success' => true,
        'id' => (int) $pdo->lastInsertId(),
    ], 201);
}
