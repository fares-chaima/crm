<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');
$repId = (int) $user['id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    apiJsonResponse(['success' => false, 'error' => 'Methode non autorisee'], 405);
}

$data = apiGetRequestData();
repValidateRequiredFields($data, ['id']);

$visitId = (int) $data['id'];
$stmt = $pdo->prepare('DELETE FROM visits WHERE id = ? AND created_by = ?');
$stmt->execute([$visitId, $repId]);

if ($stmt->rowCount() === 0) {
    apiJsonResponse(['success' => false, 'error' => 'Visite introuvable'], 404);
}

apiJsonResponse([
    'success' => true,
    'id' => $visitId,
]);
