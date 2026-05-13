<?php
require_once '../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo);

apiJsonResponse([
    'success' => true,
    'responses' => repGetResponses($pdo),
]);