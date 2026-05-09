<?php
// =============================================================
//  CRM – Configuration
//  Modifiez ces valeurs selon votre hébergement
// =============================================================

// ── Base de données ──────────────────────────────────────────
// Sur hébergement mutualisé (cPanel) :
//   DB_HOST  → généralement "localhost"
//   DB_NAME  → format  "cpanelusername_nomdelabase"
//   DB_USER  → format  "cpanelusername_nomutilisateur"
//   DB_PASS  → le mot de passe défini dans cPanel > MySQL Databases
define('DB_HOST',    'localhost');
define('DB_NAME',    'u111673431_crm');   // ← à modifier en production
define('DB_USER',    'u111673431_crm');         // ← à modifier en production
define('DB_PASS',    'CChaima.24');             // ← à modifier en production
define('DB_CHARSET', 'utf8mb4');

// ── Environnement ────────────────────────────────────────────
// 'development' → affiche les erreurs à l'écran
// 'production'  → masque les erreurs, les enregistre dans error_log
define('APP_ENV', 'production');

// ── URL de base ──────────────────────────────────────────────
// Laissez vide pour une détection automatique.
// Ou définissez l'URL racine de votre site :  'https://monsite.com'
define('APP_BASE_URL', '');

// ── Dossier uploads ──────────────────────────────────────────
// Chemin ABSOLU vers le dossier uploads (doit être accessible en écriture)
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// =============================================================
//  Ne rien modifier en dessous de cette ligne
// =============================================================

// Gestion des erreurs selon l'environnement
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
