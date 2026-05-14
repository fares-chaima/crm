<?php
// Simple public ping endpoint to verify access to this folder
http_response_code(200);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['ok' => true, 'time' => date('c')]);
exit;
