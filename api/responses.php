<?php
require_once '../includes/api_helpers.php';

// Public endpoint: returns response types with colors (no auth required)
apiJsonResponse([
    'success' => true,
    'responses' => repGetResponses($pdo),
]);