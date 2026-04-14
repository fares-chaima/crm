<?php
require_once __DIR__ . '/db_connect.php';

function apiJsonResponse($payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

function apiGetBearerToken(): ?string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authorization = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$authorization && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
    }

    if (!$authorization || strpos($authorization, 'Bearer ') !== 0) {
        return null;
    }

    return substr($authorization, 7);
}

function apiFindUserFromToken(PDO $pdo, ?string $token): ?array
{
    if (!$token) {
        return null;
    }

    $decodedToken = base64_decode($token, true);
    if ($decodedToken === false) {
        return null;
    }

    $parts = explode(':', $decodedToken, 2);
    if (count($parts) !== 2 || !ctype_digit((string) $parts[0])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM profiles WHERE id = ?');
    $stmt->execute([(int) $parts[0]]);

    return $stmt->fetch() ?: null;
}

function apiRequireBearerUser(PDO $pdo, ?string $requiredRole = null): array
{
    $user = apiFindUserFromToken($pdo, apiGetBearerToken());

    if (!$user) {
        apiJsonResponse(['success' => false, 'error' => 'Non autorise'], 401);
    }

    if ($requiredRole !== null && ($user['role'] ?? null) !== $requiredRole) {
        apiJsonResponse(['success' => false, 'error' => 'Acces interdit'], 403);
    }

    return $user;
}

function apiRequireSessionUser(?string $requiredRole = null): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        apiJsonResponse(['success' => false, 'error' => 'Non autorise'], 401);
    }

    if ($requiredRole !== null && $_SESSION['role'] !== $requiredRole) {
        apiJsonResponse(['success' => false, 'error' => 'Acces interdit'], 403);
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'] ?? null,
    ];
}

function apiGetRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    return $_POST ?: [];
}

function repGetAssignedCities(PDO $pdo, int $repId): array
{
    $stmt = $pdo->prepare(
        'SELECT c.* 
         FROM cities c
         JOIN user_city_access uca ON c.id = uca.city_id
         WHERE uca.rep_id = ? AND c.is_active = 1
         ORDER BY c.name ASC'
    );
    $stmt->execute([$repId]);

    return $stmt->fetchAll();
}

function repGetAssignedCityIds(PDO $pdo, int $repId): array
{
    return array_map('intval', array_column(repGetAssignedCities($pdo, $repId), 'id'));
}

function repGetResponses(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM response_types WHERE is_active = 1 ORDER BY sort_order ASC, label ASC');
    return $stmt->fetchAll();
}

function repBuildVisitsFilters(array $queryParams, int $repId): array
{
    $where = ['v.created_by = ?'];
    $params = [$repId];

    if (!empty($queryParams['city_id'])) {
        $where[] = 'v.city_id = ?';
        $params[] = (int) $queryParams['city_id'];
    }

    if (!empty($queryParams['response_id'])) {
        $where[] = 'v.response_id = ?';
        $params[] = (int) $queryParams['response_id'];
    }

    if (!empty($queryParams['period'])) {
        switch ($queryParams['period']) {
            case 'today':
                $where[] = 'DATE(v.created_at) = CURDATE()';
                break;
            case 'yesterday':
                $where[] = 'DATE(v.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
                break;
            case 'week':
                $where[] = 'v.created_at >= DATE_SUB(CURDATE(), INTERVAL(WEEKDAY(CURDATE())) DAY)';
                break;
            case 'month':
                $where[] = "v.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                break;
        }
    }

    return [$where, $params];
}

function repFetchVisits(PDO $pdo, array $queryParams, int $repId): array
{
    [$where, $params] = repBuildVisitsFilters($queryParams, $repId);

    $query = 'SELECT v.*, p.full_name AS rep_name, p.email AS created_by_email, c.name AS city_name, r.label AS response_label, r.color AS response_color
              FROM visits v
              JOIN profiles p ON v.created_by = p.id
              JOIN cities c ON v.city_id = c.id
              JOIN response_types r ON v.response_id = r.id
              WHERE ' . implode(' AND ', $where) . '
              ORDER BY v.created_at DESC';

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function repFetchVisitById(PDO $pdo, int $visitId, int $repId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM visits WHERE id = ? AND created_by = ?');
    $stmt->execute([$visitId, $repId]);

    return $stmt->fetch() ?: null;
}

function repValidateRequiredFields(array $data, array $requiredFields): void
{
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            apiJsonResponse(['success' => false, 'error' => 'Champs manquants', 'field' => $field], 422);
        }
    }
}

function repEnsureCityAllowed(PDO $pdo, int $repId, int $cityId): void
{
    if (!in_array($cityId, repGetAssignedCityIds($pdo, $repId), true)) {
        apiJsonResponse(['success' => false, 'error' => 'Ville non autorisee'], 403);
    }
}
