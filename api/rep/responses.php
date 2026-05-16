<?php
require_once '../../includes/api_helpers.php';

apiRequireMethod(['GET']);

apiJsonResponse([
    'success' => true,
    'responses' => repGetResponses($pdo),
]);
