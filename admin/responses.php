<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_response'])) {
    $label = $_POST['label'];
    $color = $_POST['color'];
    $stmt = $pdo->prepare("INSERT INTO response_types (label, color) VALUES (?, ?)");
    $stmt->execute([$label, $color]);
    header('Location: responses.php');
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM response_types WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: responses.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM response_types ORDER BY sort_order ASC, label ASC");
$responses = $stmt->fetchAll();
?>
<div class="row">
    <div class="col-md-4">
        <h3>Ajouter Réponse</h3>
        <form action="responses.php" method="POST">
            <input type="hidden" name="add_response" value="1">
            <input type="text" name="label" placeholder="Libellé" class="form-control mb-2" required>
            <input type="color" name="color" value="#000000" class="form-control form-control-color mb-2 w-100" required>
            <button class="btn btn-primary w-100">Ajouter</button>
        </form>
    </div>
    <div class="col-md-8">
        <h3>Réponses Existantes</h3>
        <table class="table table-dark table-striped table-hover align-middle">
            <thead><tr><th>Libellé</th><th>Couleur</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach($responses as $resp): ?>
                    <tr>
                        <td><?= $resp['label'] ?></td>
                        <td><div style="background-color: <?= $resp['color'] ?>; width: 30px; height: 30px; border-radius: 5px;"></div></td>
                        <td>
                            <a href="responses.php?delete=<?= $resp['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'footer.php'; ?>
