<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

$rep_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT c.* FROM cities c JOIN user_city_access uca ON c.id = uca.city_id WHERE uca.rep_id = ? AND c.is_active = 1");
$stmt->execute([$rep_id]);
$assigned_cities = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM response_types WHERE is_active = 1 ORDER BY sort_order ASC");
$responses = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_name = $_POST['doctor_name'];
    $phone = $_POST['phone_number'];
    $address = $_POST['address'];
    $lat = $_POST['latitude'];
    $lng = $_POST['longitude'];
    $city_id = $_POST['city_id'];
    $response_id = $_POST['response_id'];
    $comment = $_POST['comment'];
    
    // Server-side check: Ensure the rep is adding to an assigned city
    $assigned_city_ids = array_column($assigned_cities, 'id');
    if (!in_array($city_id, $assigned_city_ids)) {
        header('Location: dashboard.php?error=Ville non autorisée');
        exit;
    }
    
    $photo_url = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/' . $filename)) {
            $photo_url = 'uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO visits (doctor_name, phone_number, address, latitude, longitude, city_id, response_id, comment, photo_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$doctor_name, $phone, $address, $lat, $lng, $city_id, $response_id, $comment, $photo_url, $rep_id]);
    
    header('Location: dashboard.php?msg=Visit added');
    exit;
}
?>
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h3>Nouvelle Visite</h3>
        <form action="add_visit.php" method="POST" enctype="multipart/form-data">
            <div class="mb-2">
                <label>Nom du Docteur *</label>
                <input type="text" name="doctor_name" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Téléphone</label>
                <input type="text" name="phone_number" class="form-control">
            </div>
            <div class="mb-2">
                <label>Adresse</label>
                <textarea name="address" class="form-control"></textarea>
            </div>
            <input type="hidden" name="latitude" id="lat" required>
            <input type="hidden" name="longitude" id="lng" required>
            
            <div class="card p-3 mb-3 border-0" style="background: rgba(139, 92, 246, 0.05); border-radius: 20px;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="fw-bold mb-0"><i class="bi bi-geo-alt me-2"></i>Localisation</label>
                    <button type="button" class="btn btn-primary btn-sm" onclick="getLocation()">
                        <i class="bi bi-crosshair me-1"></i>Capturer ma position
                    </button>
                </div>
                <div id="gps-status" class="small text-muted mb-2">Cliquez sur le bouton pour capturer votre position GPS</div>
                <div id="map-picker" style="height: 250px; border-radius: 15px; border: 1px solid var(--border-color);"></div>
            </div>

            <div class="mb-2">
                <label>Ville *</label>
                <select name="city_id" class="form-select" required>
                    <?php foreach($assigned_cities as $city): ?>
                        <option value="<?= $city['id'] ?>"><?= htmlspecialchars($city['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label>Réponse *</label>
                <select name="response_id" class="form-select" required>
                    <?php foreach($responses as $resp): ?>
                        <option value="<?= $resp['id'] ?>"><?= htmlspecialchars($resp['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <label>Commentaire</label>
                <textarea name="comment" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label>Photo</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
        </form>
    </div>
</div>

<script>
let map;
let marker;

function initMap() {
    map = L.map('map-picker').setView([33.5731, -7.5898], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    map.on('click', function(e) {
        setPoint(e.latlng.lat, e.latlng.lng);
    });
    
    // Tentative de localisation auto au chargement
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            map.setView([lat, lng], 15);
            setPoint(lat, lng);
        });
    }
}

function setPoint(lat, lng) {
    document.getElementById('lat').value = lat.toFixed(8);
    document.getElementById('lng').value = lng.toFixed(8);
    
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], {draggable: true}).addTo(map);
        marker.on('dragend', function(e) {
            const newPos = marker.getLatLng();
            document.getElementById('lat').value = newPos.lat.toFixed(8);
            document.getElementById('lng').value = newPos.lng.toFixed(8);
        });
    }
}

function getLocation() {
    const status = document.getElementById('gps-status');
    status.innerText = "Localisation en cours...";
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                map.setView([lat, lng], 15);
                setPoint(lat, lng);
                status.innerText = "GPS capturé avec succès.";
            },
            (err) => {
                status.innerText = "Erreur GPS: " + err.message;
            },
            { enableHighAccuracy: true }
        );
    } else {
        status.innerText = "Géolocalisation non supportée.";
    }
}

// Initialiser la carte après le chargement du DOM
document.addEventListener('DOMContentLoaded', initMap);
</script>
<?php require_once 'footer.php'; ?>
