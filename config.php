<?php
// ── Base de données ──────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'u111673431_crm');   // ← à modifier en production
define('DB_USER',    'u111673431_crm');         // ← à modifier en production
define('DB_PASS',    'CChaima.24');
define('DB_CHARSET', 'utf8mb4');

// ── Environnement ────────────────────────────────────────────
define('APP_ENV', 'production');

// ── URL de base ──────────────────────────────────────────────
define('APP_BASE_URL', '');

// ── Dossier uploads ──────────────────────────────────────────
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// ── Gestion des erreurs ──────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
}