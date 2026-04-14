<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

$visit_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM visits WHERE id = ?");
$stmt->execute([$visit_id]);
$visit = $stmt->fetch();

if (!$visit) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM cities WHERE is_active = 1 ORDER BY name ASC");
$all_cities = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM response_types WHERE is_active = 1 ORDER BY sort_order ASC");
$responses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_name = $_POST['doctor_name'];
    $phone = $_POST['phone_number'];
    $address = $_POST['address'];
    $city_id = $_POST['city_id'];
    $response_id = $_POST['response_id'];
    $comment = $_POST['comment'];
    
    $photo_url = $visit['photo_url'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/' . $filename)) {
            $photo_url = 'uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("UPDATE visits SET doctor_name = ?, phone_number = ?, address = ?, city_id = ?, response_id = ?, comment = ?, photo_url = ?, last_edited_at = NOW() WHERE id = ?");
    $stmt->execute([$doctor_name, $phone, $address, $city_id, $response_id, $comment, $photo_url, $visit_id]);
    
    header('Location: dashboard.php?msg=Visit updated');
    exit;
}
?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h3>Editer Visite (Admin)</h3>
        <p class="text-muted small">Créé le: <?= $visit['created_at'] ?> | Par: <?= $visit['created_by'] ?></p>
        <form action="edit_visit.php?id=<?= $visit_id ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-2">
                <label>Nom du Docteur *</label>
                <input type="text" name="doctor_name" class="form-control" value="<?= htmlspecialchars($visit['doctor_name']) ?>" required>
            </div>
            <div class="mb-2">
                <label>Téléphone</label>
                <input type="text" name="phone_number" class="form-control" value="<?= htmlspecialchars($visit['phone_number']) ?>">
            </div>
            <div class="mb-2">
                <label>Adresse</label>
                <textarea name="address" class="form-control"><?= htmlspecialchars($visit['address']) ?></textarea>
            </div>
            <div class="card p-3 mb-3 border-0" style="background: rgba(139, 92, 246, 0.05); border-radius: 20px;">
                <label class="fw-bold mb-2"><i class="bi bi-geo-alt me-2"></i>Localisation enregistrée</label>
                <div id="map-view" style="height: 250px; border-radius: 15px; border: 1px solid var(--border-color);"></div>
            </div>
            
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const lat = <?= $visit['latitude'] ?>;
                const lng = <?= $visit['longitude'] ?>;
                const map = L.map('map-view').setView([lat, lng], 15);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png').addTo(map);
                L.marker([lat, lng]).addTo(map);
            });
            </script>
            <div class="mb-2">
                <label>Ville *</label>
                <select name="city_id" class="form-select" required>
                    <?php foreach($all_cities as $city): ?>
                        <option value="<?= $city['id'] ?>" <?= $city['id'] == $visit['city_id'] ? 'selected' : '' ?>><?= htmlspecialchars($city['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label>Réponse *</label>
                <select name="response_id" class="form-select" required>
                    <?php foreach($responses as $resp): ?>
                        <option value="<?= $resp['id'] ?>" <?= $resp['id'] == $visit['response_id'] ? 'selected' : '' ?>><?= htmlspecialchars($resp['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label>Commentaire</label>
                <textarea name="comment" class="form-control"><?= htmlspecialchars($visit['comment']) ?></textarea>
            </div>
            <div class="mb-3">
                <label>Photo</label>
                <?php if ($visit['photo_url']): ?>
                    <div class="mb-2"><img src="../<?= $visit['photo_url'] ?>" width="100"></div>
                <?php endif; ?>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
            <a href="dashboard.php" class="btn btn-secondary w-100 mt-2">Retour</a>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>
