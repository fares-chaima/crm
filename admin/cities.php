<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_city'])) {
    $name = $_POST['name'];
    $stmt = $pdo->prepare("INSERT INTO cities (name) VALUES (?)");
    $stmt->execute([$name]);
    header('Location: cities.php');
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM cities WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: cities.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM cities ORDER BY name ASC");
$cities = $stmt->fetchAll();
?>
<div class="row">
    <div class="col-md-4">
        <h3>Ajouter Ville</h3>
        <form action="cities.php" method="POST">
            <input type="hidden" name="add_city" value="1">
            <input type="text" name="name" placeholder="Nom Ville" class="form-control mb-2" required>
            <button class="btn btn-primary w-100">Ajouter</button>
        </form>
    </div>
    <div class="col-md-8">
        <h3>Villes Existantes</h3>
        <table class="table table-dark table-striped table-hover align-middle">
            <thead><tr><th>Nom</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach($cities as $city): ?>
                    <tr>
                        <td><?= $city['name'] ?></td>
                        <td>
                            <a href="cities.php?delete=<?= $city['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'footer.php'; ?>
