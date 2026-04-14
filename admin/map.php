<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

// Fetch options for filters
$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$responses = $pdo->query("SELECT * FROM response_types ORDER BY label ASC")->fetchAll();
$reps = $pdo->query("SELECT * FROM profiles WHERE role = 'rep' ORDER BY full_name ASC")->fetchAll();
?>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card p-3 shadow-sm">
            <form id="filterForm" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small">Période</label>
                    <select name="period" class="form-select form-select-sm">
                        <option value="all">Toutes</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="yesterday">Hier</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois-ci</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ville</label>
                    <select name="city_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach($cities as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Réponse</label>
                    <select name="response_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach($responses as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= $r['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Commercial</label>
                    <select name="rep_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach($reps as $rep): ?>
                            <option value="<?= $rep['id'] ?>"><?= $rep['full_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="button" onclick="loadMarkers()" class="btn btn-primary btn-sm flex-grow-1">Appliquer</button>
                    <button type="reset" onclick="setTimeout(loadMarkers, 100)" class="btn btn-secondary btn-sm">Reset</button>
                    <button type="button" onclick="centerOnMe()" class="btn btn-info btn-sm text-white flex-grow-1" style="background-color: var(--accent-cyan); border: none;">Centrer sur moi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="map" style="height: 600px; border-radius: 10px;"></div>

<script>
let map;
let markerLayer = L.layerGroup();

document.addEventListener('DOMContentLoaded', function() {
    map = L.map('map').setView([33.5731, -7.5898], 6);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 20
    }).addTo(map);
    markerLayer.addTo(map);
    loadMarkers();
});

function loadMarkers() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData).toString();
    
    fetch('api_visits.php?' + params)
        .then(res => res.json())
        .then(visits => {
            markerLayer.clearLayers();
            const markers = [];
            
            visits.forEach(v => {
                const marker = L.marker([v.latitude, v.longitude], {
                    icon: L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color:${v.response_color}; color:${v.response_color};"></div>`
                    })
                }).bindPopup(`
                    <div style="min-width: 150px;">
                        <h6 class="mb-1">${v.doctor_name}</h6>
                        <p class="small mb-1"><b>Réponse:</b> ${v.response_label}</p>
                        <p class="small mb-1"><b>Ville:</b> ${v.city_name}</p>
                        <p class="small mb-1"><b>Commercial:</b> ${v.rep_name}</p>
                        <p class="small mb-1"><b>Date:</b> ${v.created_at}</p>
                        <a href="edit_visit.php?id=${v.id}" class="btn btn-xs btn-primary py-0 px-2 mt-1" style="font-size: 10px;">Editer</a>
                    </div>
                `);
                markerLayer.addLayer(marker);
                markers.push(marker);
            });

            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        });
}

function centerOnMe() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 13);
        });
    }
}
</script>

<?php require_once 'footer.php'; ?>
