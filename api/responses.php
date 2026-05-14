<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/api_helpers.php';

// Pas d'auth, tout le monde peut accéder
$stmt = $pdo->query("SELECT id, label, color FROM response_types ORDER BY sort_order ASC, label ASC");
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

apiJsonResponse([
    'success' => true,
    'responses' => $responses,
]);