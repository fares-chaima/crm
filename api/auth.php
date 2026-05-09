<?php
require_once __DIR__ . "/../includes/db_connect.php";

header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["email"]) || !isset($data["password"])) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "error" => "Email et mot de passe requis",
    ]);
    exit();
}

$email = $data["email"];
$password = $data["password"];

$stmt = $pdo->prepare("SELECT * FROM profiles WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user["password"])) {
    $token = base64_encode($user["id"] . ":" . bin2hex(random_bytes(16)));

    echo json_encode([
        "success" => true,
        "token" => $token,
        "user" => [
            "id" => (int) $user["id"],
            "email" => $user["email"],
            "full_name" => $user["full_name"],
            "role" => $user["role"],
        ],
    ]);
} else {
    http_response_code(401);
    echo json_encode(["success" => false, "error" => "Identifiants invalides"]);
}
exit();
