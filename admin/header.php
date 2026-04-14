<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="../assets/style.css?v=<?= time() ?>">
    <style>
        #map { height: 400px; width: 100%; }
        :root {
            --bg-color: #08090a;
            --sidebar-bg: #111111;
            --card-bg: #1a1b1e;
            --text-color: #ffffff;
            --text-muted: #9ca3af;
            --accent-purple: #8b5cf6;
            --accent-violet: #d946ef;
            --accent-gradient: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%);
            --border-color: rgba(255, 255, 255, 0.08);
            --glass-bg: rgba(26, 27, 30, 0.7);
        }
        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'DM Sans', sans-serif;
            margin: 0;
        }
        .sidebar {
            width: 280px;
            height: 100vh;
            background-color: var(--sidebar-bg);
            position: fixed;
            left: 0;
            top: 0;
            padding: 24px;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
        }
        .main-content {
            margin-left: 280px;
            padding: 40px;
            min-height: 100vh;
        }
        .nav-link {
            color: var(--text-muted);
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            text-decoration: none !important;
        }
        .nav-link.active {
            background: var(--accent-gradient);
            color: white;
        }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            color: white;
        }
    </style>
</head>
<body>
<div class="mobile-header d-lg-none">
    <h4 class="text-white mb-0 fw-bold">Docto<span style="color: var(--accent-purple)">lik</span></h4>
    <button class="btn btn-dark sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
</div>
<div class="sidebar" id="sidebar">
    <div class="mb-5 px-3">
        <h3 class="text-white fw-bold">Docto<span style="color: var(--accent-purple)">lik</span></h3>
    </div>
    <div class="navbar-nav">
        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : '' ?>" href="dashboard.php">
            <i class="bi bi-list-ul"></i> Liste Visites
        </a>
        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'map.php') !== false ? 'active' : '' ?>" href="map.php">
            <i class="bi bi-map"></i> Carte Visites
        </a>
        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'users.php') !== false ? 'active' : '' ?>" href="users.php">
            <i class="bi bi-people"></i> Utilisateurs
        </a>
        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'cities.php') !== false ? 'active' : '' ?>" href="cities.php">
            <i class="bi bi-geo-alt"></i> Villes
        </a>
        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'responses.php') !== false ? 'active' : '' ?>" href="responses.php">
            <i class="bi bi-chat-left-text"></i> Réponses
        </a>
        <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'import.php') !== false ? 'active' : '' ?>" href="import.php">
            <i class="bi bi-cloud-upload"></i> Import
        </a>
        <hr class="my-4" style="border-color: var(--border-color)">
        <a class="nav-link text-danger mt-auto" href="../logout.php">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
        </a>
    </div>
</div>
<div class="main-content">
