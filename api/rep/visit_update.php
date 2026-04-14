<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');
$repId = (int) $user['id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    apiJsonResponse(['success' => false, 'error' => 'Methode non autorisee'], 405);
}

$data = apiGetRequestData();
repValidateRequiredFields($data, ['id', 'doctor_name', 'city_id', 'response_id']);

$visitId = (int) $data['id'];
$visit = repFetchVisitById($pdo, $visitId, $repId);
if (!$visit) {
    apiJsonResponse(['success' => false, 'error' => 'Visite introuvable'], 404);
}

repEnsureCityAllowed($pdo, $repId, (int) $data['city_id']);

$stmt = $pdo->prepare(
    'UPDATE visits
     SET doctor_name = ?, phone_number = ?, address = ?, city_id = ?, response_id = ?, comment = ?, last_edited_at = NOW()
     WHERE id = ? AND created_by = ?'
);
$stmt->execute([
    $data['doctor_name'],
    $data['phone_number'] ?? null,
    $data['address'] ?? null,
    (int) $data['city_id'],
    (int) $data['response_id'],
    $data['comment'] ?? null,
    $visitId,
    $repId,
]);

apiJsonResponse([
    'success' => true,
    'id' => $visitId,
]);
