<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

$rep_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM profiles WHERE id = ? AND role = 'rep'");
$stmt->execute([$rep_id]);
$rep = $stmt->fetch();

if (!$rep) {
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_cities'])) {
    $cities = $_POST['cities'] ?? [];
    $pdo->prepare("DELETE FROM user_city_access WHERE rep_id = ?")->execute([$rep_id]);
    $stmt = $pdo->prepare("INSERT INTO user_city_access (rep_id, city_id, assigned_by) VALUES (?, ?, ?)");
    foreach ($cities as $city_id) {
        $stmt->execute([$rep_id, $city_id, $_SESSION['user_id']]);
    }
    header('Location: users.php?msg=Cities assigned');
    exit;
}

$stmt = $pdo->query("SELECT * FROM cities ORDER BY name ASC");
$all_cities = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT city_id FROM user_city_access WHERE rep_id = ?");
$stmt->execute([$rep_id]);
$assigned_cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h3>Assigner Villes à <?= htmlspecialchars($rep['full_name']) ?></h3>
        <form action="assign_cities.php?id=<?= $rep_id ?>" method="POST">
            <input type="hidden" name="assign_cities" value="1">
            <div class="mb-3">
                <label class="form-label">Villes</label>
                <div class="row">
                    <?php foreach($all_cities as $city): ?>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cities[]" value="<?= $city['id'] ?>" id="city_<?= $city['id'] ?>" <?= in_array($city['id'], $assigned_cities) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="city_<?= $city['id'] ?>">
                                    <?= htmlspecialchars($city['name']) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="btn btn-primary w-100">Enregistrer</button>
            <a href="users.php" class="btn btn-secondary w-100 mt-2">Retour</a>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>
