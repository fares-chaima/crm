<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $file = $_FILES['csv_file']['tmp_name'];
        if (!is_uploaded_file($file)) {
            throw new Exception("Erreur lors du téléchargement du fichier.");
        }

        // Load and normalize the entire file content
        $content = file_get_contents($file);
        
        // Remove BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        
        // Check if it's the "weird" format (starts with triple quotes)
        if (substr(trim($content), 0, 3) === '"""') {
            // Normalize triple-quote format to standard CSV
            $content = str_replace('"",""', '","', $content);
            $content = str_replace('"""', '"', $content);
            $content = str_replace('""', '"', $content);
            $delimiter = ',';
        } else {
            // Standard CSV - detect delimiter
            $firstLine = explode("\n", $content)[0];
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        }

        // Save normalized content to a temporary file for robust parsing
        $tmpFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tmpFile, $content);
        
        $handle = fopen($tmpFile, 'r');
        $lines = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            $lines[] = $data;
        }
        fclose($handle);
        unlink($tmpFile);

        if (empty($lines)) {
            throw new Exception("Fichier CSV vide ou illisible.");
        }

        $first_row = $lines[0];
        $potential_header = array_map(function($h) { return strtolower(trim($h, " \t\n\r\0\x0B\"'")); }, $first_row);

        $required = ['doctor_name', 'city', 'response'];
        $has_header = !empty(array_intersect($required, $potential_header));

        if ($has_header) {
            $header = $potential_header;
            array_shift($lines); // Remove header row
        } else {
            // Default order if no header found
            $header = ['doctor_name', 'phone_number', 'address', 'latitude', 'longitude', 'city', 'response', 'comment'];
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        
        // Cache cities and response types
        $cities_map = [];
        $stmt = $pdo->query("SELECT id, name FROM cities");
        while($row = $stmt->fetch()) $cities_map[strtolower(trim($row['name']))] = $row['id'];
        
        $responses_map = [];
        $stmt = $pdo->query("SELECT id, label FROM response_types");
        while($row = $stmt->fetch()) $responses_map[strtolower(trim($row['label']))] = $row['id'];
        
        $row_num = 1;
        foreach ($lines as $data) {
            $row_num++;
            
            // Clean each field
            $data = array_map(function($v) {
                return trim($v, " \t\n\r\0\x0B\"'");
            }, $data);

            // Skip empty rows
            if (empty($data) || (count($data) === 1 && empty($data[0]))) continue;

            // Handle the header repetition if any
            if (strtolower($data[0]) === 'doctor_name') continue;

            // Fix column count mismatch
            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            } elseif (count($data) > count($header)) {
                // If extra columns, it's likely part of the comment that had delimiters
                $comment_index = array_search('comment', $header);
                if ($comment_index !== false) {
                    $extra_parts = array_slice($data, $comment_index);
                    $data = array_slice($data, 0, $comment_index);
                    $data[] = implode(' ', $extra_parts);
                }
            }

            // Ensure exact match for array_combine
            $data = array_slice(array_pad($data, count($header), ''), 0, count($header));

            $row = array_combine($header, $data);
            if (!$row) {
                $errors[] = "Ligne $row_num : Erreur de lecture.";
                continue;
            }
            
            $doctor_name = $row['doctor_name'] ?? '';
            $city_name = $row['city'] ?? 'Inconnu';
            $response_label = $row['response'] ?? 'Inconnu';
            $lat = $row['latitude'] ?? '0';
            $lng = $row['longitude'] ?? '0';
            
            $row_preview = "<strong>" . htmlspecialchars($doctor_name ?: 'Nom inconnu') . "</strong>";

            // Accept everything, no validation blocks
            $lat = (float)str_replace(',', '.', $lat);
            $lng = (float)str_replace(',', '.', $lng);
            
            $city_id = $cities_map[strtolower(trim($city_name))] ?? null;
            $response_id = $responses_map[strtolower(trim($response_label))] ?? null;
            
            // AUTO-CREATE City if not exists
            if (!$city_id && !empty($city_name)) {
                $stmt_city = $pdo->prepare("INSERT INTO cities (name) VALUES (?)");
                try {
                    $stmt_city->execute([$city_name]);
                    $city_id = $pdo->lastInsertId();
                    $cities_map[strtolower(trim($city_name))] = $city_id;
                } catch (Exception $e) {
                    $stmt_f = $pdo->prepare("SELECT id FROM cities WHERE LOWER(name) = LOWER(?)");
                    $stmt_f->execute([$city_name]);
                    $city_id = $stmt_f->fetchColumn();
                }
            }

            // AUTO-CREATE Response if not exists
            if (!$response_id && !empty($response_label)) {
                $stmt_resp = $pdo->prepare("INSERT INTO response_types (label, color) VALUES (?, ?)");
                try {
                    $stmt_resp->execute([$response_label, '#cccccc']);
                    $response_id = $pdo->lastInsertId();
                    $responses_map[strtolower(trim($response_label))] = $response_id;
                } catch (Exception $e) {
                    $stmt_f = $pdo->prepare("SELECT id FROM response_types WHERE LOWER(label) = LOWER(?)");
                    $stmt_f->execute([$response_label]);
                    $response_id = $stmt_f->fetchColumn();
                }
            }

            // If still no ID, use a fallback (e.g., ID 1 or skip but without strict numeric check)
            if (!$city_id || !$response_id) {
                $errors[] = "Ligne $row_num : $row_preview <br>&nbsp;&nbsp;&nbsp;❌ Erreur technique de création (Ville: $city_name, Réponse: $response_label)";
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
                    (float)$lat,
                    (float)$lng,
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
                    (float)$lat,
                    (float)$lng,
                    $city_id,
                    $response_id,
                    $row['comment'] ?? '',
                    $row['photo_url'] ?? '',
                    $_SESSION['user_id']
                ]);
                $imported++;
            }
        }
        $results = ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    } catch (Exception $e) {
        $results = ['imported' => 0, 'updated' => 0, 'errors' => ["Erreur critique: " . $e->getMessage()]];
    }
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
                    <div style="max-height: 300px; overflow-y: auto;">
                        <ul class="mb-0">
                            <?php foreach($results['errors'] as $err): ?>
                                <li class="small"><?= $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
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
