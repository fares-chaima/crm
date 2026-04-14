<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'rep/dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= time() ?>">
</head>
<body class="login-container">
<div class="login-card">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-white">Docto<span style="color: var(--accent-purple)">lik</span></h2>
        <p class="text-white">Entrez vos accès pour continuer</p>
    </div>
    <?php if(isset($_GET['error'])) echo "<div class='alert alert-danger border-0 bg-danger bg-opacity-10 text-danger'>".$_GET['error']."</div>"; ?>
    <form action="login.php" method="POST">
        <div class="mb-3">
            <label class="form-label text-white small">Email</label>
            <input type="email" name="email" placeholder="nom@exemple.com" class="form-control" required>
        </div>
        <div class="mb-4">
            <label class="form-label text-white small">Mot de passe</label>
            <input type="password" name="password" placeholder="••••••••" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100 py-3">Se connecter</button>
    </form>
</div>
</body>
</html>
