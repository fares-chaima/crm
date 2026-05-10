<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');
$repId = (int) $user['id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    apiJsonResponse(['success' => false, 'error' => 'Methode non autorisee'], 405);
}

$data = apiGetRequestData();
repValidateRequiredFields($data, ['id', 'doctor_name', 'latitude', 'longitude', 'city_id', 'response_id']);

$visitId = (int) $data['id'];
$visit = repFetchVisitById($pdo, $visitId, $repId);
if (!$visit) {
    apiJsonResponse(['success' => false, 'error' => 'Visite introuvable'], 404);
}

repEnsureCityAllowed($pdo, $repId, (int) $data['city_id']);
$photoUrl = repHandleUploadedPhoto('photo', $visit['photo_url'] ?? null);

$stmt = $pdo->prepare(
    'UPDATE visits
     SET doctor_name = ?, phone_number = ?, address = ?, latitude = ?, longitude = ?, city_id = ?, response_id = ?, comment = ?, photo_url = ?, last_edited_at = NOW()
     WHERE id = ? AND created_by = ?'
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
    $visitId,
    $repId,
]);

apiJsonResponse([
    'success' => true,
    'id' => $visitId,
]);
