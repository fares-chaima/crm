<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle); // Read header
    
    $imported = 0;
    $updated = 0;
    $errors = [];
    
    // Cache cities and response types for performance
    $cities_map = [];
    $stmt = $pdo->query("SELECT id, name FROM cities");
    while($row = $stmt->fetch()) $cities_map[strtolower($row['name'])] = $row['id'];
    
    $responses_map = [];
    $stmt = $pdo->query("SELECT id, label FROM response_types");
    while($row = $stmt->fetch()) $responses_map[strtolower($row['label'])] = $row['id'];
    
    $row_num = 1;
    while (($data = fgetcsv($handle)) !== FALSE) {
        $row_num++;
        // map data to associative array based on header
        $row = array_combine($header, $data);
        
        $doctor_name = $row['doctor_name'] ?? '';
        $city_name = $row['city'] ?? '';
        $response_label = $row['response'] ?? '';
        $lat = $row['latitude'] ?? '';
        $lng = $row['longitude'] ?? '';
        
        // Basic validation
        if (!$doctor_name || !$city_name || !$response_label || !is_numeric($lat) || !is_numeric($lng)) {
            $errors[] = "Ligne $row_num: Données manquantes ou invalides.";
            continue;
        }
        
        $city_id = $cities_map[strtolower($city_name)] ?? null;
        $response_id = $responses_map[strtolower($response_label)] ?? null;
        
        if (!$city_id) {
            $errors[] = "Ligne $row_num: Ville '$city_name' non trouvée.";
            continue;
        }
        if (!$response_id) {
            $errors[] = "Ligne $row_num: Réponse '$response_label' non trouvée.";
            continue;
        }
        
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM visits WHERE LOWER(doctor_name) = LOWER(?) AND city_id = ?");
        $stmt->execute([$doctor_name, $city_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update
            $stmt = $pdo->prepare("UPDATE visits SET phone_number = ?, address = ?, latitude = ?, longitude = ?, response_id = ?, comment = ?, photo_url = ?, last_edited_at = NOW() WHERE id = ?");
            $stmt->execute([
                $row['phone_number'] ?? '',
                $row['address'] ?? '',
                $lat,
                $lng,
                $response_id,
                $row['comment'] ?? '',
                $row['photo_url'] ?? '',
                $existing['id']
            ]);
            $updated++;
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO visits (doctor_name, phone_number, address, latitude, longitude, city_id, response_id, comment, photo_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $doctor_name,
                $row['phone_number'] ?? '',
                $row['address'] ?? '',
                $lat,
                $lng,
                $city_id,
                $response_id,
                $row['comment'] ?? '',
                $row['photo_url'] ?? '',
                $_SESSION['user_id']
            ]);
            $imported++;
        }
    }
    fclose($handle);
    $results = ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <h3>Importer Visites (CSV)</h3>
        <?php if ($results): ?>
            <div class="alert alert-info">
                ✅ <?= $results['imported'] ?> importés, <?= $results['updated'] ?> mis à jour, <?= count($results['errors']) ?> erreurs.
                <?php if ($results['errors']): ?>
                    <hr>
                    <ul>
                        <?php foreach(array_slice($results['errors'], 0, 5) as $err): ?>
                            <li><?= $err ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form action="import.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Fichier CSV</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <small class="text-muted">Format: doctor_name,phone_number,address,latitude,longitude,city,response,comment</small>
            </div>
            <button class="btn btn-primary w-100">Démarrer l'importation</button>
            <a href="dashboard.php" class="btn btn-secondary w-100 mt-2">Retour</a>
        </form>
    </div>
</div>
<?php require_once 'footer.php'; ?>
