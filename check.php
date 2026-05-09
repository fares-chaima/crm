<?php
// ================================================================
//  CRM – Diagnostic Page
//  IMPORTANT : Supprimez ce fichier après avoir résolu les problèmes
//  DELETE this file after fixing issues (security risk!)
// ================================================================

// Simple password protection – change this before uploading!
define('CHECK_PASSWORD', 'crm_check_2024');

if (!isset($_GET['key']) || $_GET['key'] !== CHECK_PASSWORD) {
    http_response_code(403);
    die('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Accès refusé</title>
    <style>body{font-family:sans-serif;background:#08090a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .box{background:#1a1b1e;border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:40px;text-align:center;}
    input{background:#111;border:1px solid rgba(255,255,255,.15);color:#fff;padding:10px 16px;border-radius:8px;margin-right:8px;}
    button{background:linear-gradient(135deg,#8b5cf6,#d946ef);border:none;color:#fff;padding:10px 20px;border-radius:8px;cursor:pointer;}</style>
    </head><body><div class="box"><h2>🔒 Diagnostic CRM</h2>
    <form><input type="text" name="key" placeholder="Mot de passe diagnostic"><button type="submit">Accéder</button></form>
    </div></body></html>');
}

$results = [];

// ── 1. PHP Version ────────────────────────────────────────────
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$results[] = [
    'label' => 'Version PHP',
    'value' => $phpVersion,
    'ok'    => $phpOk,
    'hint'  => $phpOk ? '' : 'PHP 7.4+ requis. Changez la version PHP dans cPanel > PHP Selector.',
];

// ── 2. Required Extensions ────────────────────────────────────
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'fileinfo'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $results[] = [
        'label' => "Extension : $ext",
        'value' => $loaded ? 'Activée' : 'MANQUANTE',
        'ok'    => $loaded,
        'hint'  => $loaded ? '' : "Activez l'extension $ext dans cPanel > PHP Selector > Extensions.",
    ];
}

// ── 3. Database Connection ────────────────────────────────────
$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
    try {
        $testPdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $results[] = [
            'label' => 'Connexion MySQL',
            'value' => "OK — Connecté à " . DB_HOST . " / " . DB_NAME,
            'ok'    => true,
            'hint'  => '',
        ];

        // Check tables
        $tables = ['profiles', 'visits', 'cities', 'response_types', 'user_city_access'];
        $existing = $testPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $exists = in_array($table, $existing, true);
            $results[] = [
                'label' => "Table : $table",
                'value' => $exists ? 'Existe' : 'MANQUANTE',
                'ok'    => $exists,
                'hint'  => $exists ? '' : "Exécutez db_setup.sql dans cPanel > phpMyAdmin.",
            ];
        }
        unset($testPdo);
    } catch (PDOException $e) {
        $results[] = [
            'label' => 'Connexion MySQL',
            'value' => 'ÉCHEC',
            'ok'    => false,
            'hint'  => 'Erreur : ' . htmlspecialchars($e->getMessage()) .
                       '<br>👉 Ouvrez <strong>config.php</strong> et mettez à jour DB_HOST, DB_NAME, DB_USER, DB_PASS avec les valeurs cPanel.',
        ];
    }
} else {
    $results[] = [
        'label' => 'Fichier config.php',
        'value' => 'INTROUVABLE',
        'ok'    => false,
        'hint'  => 'Le fichier config.php est manquant. Vérifiez que tous les fichiers ont bien été uploadés.',
    ];
}

// ── 4. File Permissions ───────────────────────────────────────
$paths = [
    'uploads/'  => ['path' => __DIR__ . '/uploads', 'need_write' => true],
    'config.php' => ['path' => __DIR__ . '/config.php', 'need_write' => false],
];
foreach ($paths as $name => $info) {
    $exists = file_exists($info['path']);
    $writable = $exists && is_writable($info['path']);
    if ($info['need_write']) {
        $ok = $writable;
        $hint = $ok ? '' : "Le dossier $name doit être accessible en écriture. Mettez les permissions à 755 ou 777 via FTP / cPanel > File Manager.";
        $value = !$exists ? 'MANQUANT' : ($writable ? 'Accessible en écriture (OK)' : 'NON accessible en écriture');
    } else {
        $ok = $exists;
        $hint = $ok ? '' : "$name est introuvable. Vérifiez l'upload des fichiers.";
        $value = $exists ? 'Présent' : 'MANQUANT';
    }
    $results[] = [
        'label' => "Chemin : $name",
        'value' => $value,
        'ok'    => $ok,
        'hint'  => $hint,
    ];
}

// ── 5. Session Test ───────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$sessionOk = session_status() === PHP_SESSION_ACTIVE;
$_SESSION['crm_test'] = 1;
$sessionWrite = isset($_SESSION['crm_test']);
$results[] = [
    'label' => 'Sessions PHP',
    'value' => $sessionOk && $sessionWrite ? 'Fonctionnelles' : 'PROBLÈME DÉTECTÉ',
    'ok'    => $sessionOk && $sessionWrite,
    'hint'  => $sessionOk && $sessionWrite ? '' : 'Les sessions PHP ne fonctionnent pas. Contactez votre hébergeur pour vérifier session.save_path.',
];

// ── 6. Server Info ────────────────────────────────────────────
$serverInfo = [
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
    'DOCUMENT_ROOT'   => $_SERVER['DOCUMENT_ROOT'] ?? 'Inconnu',
    'PHP_SELF'        => $_SERVER['PHP_SELF'] ?? 'Inconnu',
    '__DIR__'         => __DIR__,
    'APP_ENV'         => defined('APP_ENV') ? APP_ENV : 'config.php non chargé',
];

$allOk = !in_array(false, array_column($results, 'ok'), true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM – Diagnostic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #08090a; --card: #1a1b1e; --border: rgba(255,255,255,.08);
            --purple: #8b5cf6; --pink: #d946ef;
        }
        body { background: var(--bg); color: #fff; font-family: 'Segoe UI', sans-serif; padding: 40px 20px; }
        h1 span { background: linear-gradient(135deg, var(--purple), var(--pink)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; }
        .check-row { display: flex; align-items: flex-start; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .check-row:last-child { border-bottom: none; }
        .icon { font-size: 1.3rem; flex-shrink: 0; margin-top: 2px; }
        .label { font-weight: 600; font-size: .95rem; }
        .value { font-size: .85rem; color: #9ca3af; margin-top: 2px; }
        .hint { font-size: .8rem; color: #fbbf24; margin-top: 6px; background: rgba(251,191,36,.08); border-radius: 8px; padding: 8px 12px; }
        .badge-ok  { background: rgba(34,197,94,.15); color: #4ade80; border-radius: 20px; padding: 3px 10px; font-size: .75rem; }
        .badge-err { background: rgba(239,68,68,.15);  color: #f87171; border-radius: 20px; padding: 3px 10px; font-size: .75rem; }
        .server-table td { padding: 6px 12px; font-size: .82rem; color: #9ca3af; border-bottom: 1px solid var(--border); }
        .server-table td:first-child { color: #fff; font-weight: 600; width: 180px; }
        .warning-box { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; }
        .success-box { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; }
    </style>
</head>
<body>
<div style="max-width: 800px; margin: 0 auto;">

    <h1 class="mb-1">Docto<span>lik</span> CRM</h1>
    <p class="text-secondary mb-4">Page de diagnostic — serveur de production</p>

    <?php if ($allOk): ?>
    <div class="success-box">
        <strong>✅ Tout est opérationnel !</strong><br>
        <small>Supprimez ce fichier (<code>check.php</code>) maintenant qu'il n'est plus nécessaire.</small>
    </div>
    <?php else: ?>
    <div class="warning-box">
        <strong>⚠️ Des problèmes ont été détectés.</strong><br>
        <small>Consultez les détails ci-dessous et corrigez chaque point marqué ❌ avant d'utiliser le CRM.</small>
    </div>
    <?php endif; ?>

    <!-- Results -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">Résultats des vérifications</h5>
        <?php foreach ($results as $r): ?>
        <div class="check-row">
            <div class="icon"><?= $r['ok'] ? '✅' : '❌' ?></div>
            <div style="flex:1">
                <div class="d-flex align-items-center gap-2">
                    <span class="label"><?= $r['label'] ?></span>
                    <span class="<?= $r['ok'] ? 'badge-ok' : 'badge-err' ?>"><?= $r['ok'] ? 'OK' : 'ERREUR' ?></span>
                </div>
                <div class="value"><?= $r['value'] ?></div>
                <?php if ($r['hint']): ?>
                <div class="hint">💡 <?= $r['hint'] ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Server Info -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">Informations serveur</h5>
        <table class="server-table w-100">
            <?php foreach ($serverInfo as $k => $v): ?>
            <tr><td><?= $k ?></td><td><?= htmlspecialchars($v) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Instructions -->
    <div class="card p-4 mb-4">
        <h5 class="mb-3">📋 Checklist déploiement</h5>
        <ol style="color:#9ca3af; font-size:.9rem; line-height:2;">
            <li>Ouvrir <code>config.php</code> et renseigner les identifiants MySQL de votre hébergeur</li>
            <li>Créer la base de données dans <strong>cPanel → MySQL Databases</strong></li>
            <li>Importer <code>db_setup.sql</code> via <strong>phpMyAdmin</strong></li>
            <li>Vérifier que le dossier <code>uploads/</code> a les permissions <strong>755</strong></li>
            <li>Relancer cette page et confirmer que tout est ✅</li>
            <li><strong style="color:#f87171;">Supprimer ce fichier check.php une fois tout OK !</strong></li>
        </ol>
    </div>

    <p class="text-center" style="font-size:.8rem; color:#4b5563;">
        ⚠️ Supprimez <code>check.php</code> après utilisation — ce fichier expose des informations sensibles.
    </p>
</div>
</body>
</html>
