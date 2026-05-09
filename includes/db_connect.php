<?php
require_once __DIR__ . "/../config.php";

try {
    $pdo = new PDO(
        "mysql:host=" .
            DB_HOST .
            ";dbname=" .
            DB_NAME .
            ";charset=" .
            DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
} catch (PDOException $e) {
    error_log("[CRM] Erreur de connexion BDD : " . $e->getMessage());

    if (APP_ENV === "development") {
        $detail = htmlspecialchars($e->getMessage());
    } else {
        $detail = "Vérifiez les identifiants dans config.php";
    }

    // If the request expects JSON (API calls), return JSON error
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    $accept = $_SERVER["HTTP_ACCEPT"] ?? "";
    if (
        strpos($contentType, "application/json") !== false ||
        strpos($accept, "application/json") !== false
    ) {
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode([
            "success" => false,
            "error" => "Erreur de connexion à la base de données.",
        ]);
        exit();
    }

    http_response_code(500);
    die(
        '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">' .
            "<title>Erreur de connexion</title>" .
            "<style>body{font-family:sans-serif;background:#08090a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}" .
            ".box{background:#1a1b1e;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:40px;max-width:500px;text-align:center;}" .
            "h2{color:#ef4444;margin-top:0;}code{background:#111;padding:8px 12px;border-radius:8px;display:block;margin-top:12px;font-size:.85rem;color:#f87171;word-break:break-all;}</style>" .
            '</head><body><div class="box">' .
            "<h2>&#9888; Erreur de connexion à la base de données</h2>" .
            "<p>Impossible de se connecter à MySQL.<br>Veuillez mettre à jour <strong>config.php</strong> avec les bons identifiants.</p>" .
            "<code>" .
            $detail .
            "</code>" .
            "</div></body></html>"
    );
}
