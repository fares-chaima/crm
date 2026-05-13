<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');

apiJsonResponse([
    'success' => true,
    'responses' => repGetResponses($pdo),
]);