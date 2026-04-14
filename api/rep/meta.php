<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');

apiJsonResponse([
    'success' => true,
    'cities' => repGetAssignedCities($pdo, (int) $user['id']),
    'responses' => repGetResponses($pdo),
]);
