<?php
require_once '../includes/db_connect.php';
require_once 'header.php';
$rep_id = $_SESSION['user_id'];
$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$responses = $pdo->query("SELECT * FROM response_types ORDER BY label ASC")->fetchAll();
$where = ["v.created_by = ?"];
$params = [$rep_id];
if (!empty($_GET['city_id'])) { $where[] = "v.city_id = ?"; $params[] = $_GET['city_id']; }
if (!empty($_GET['response_id'])) { $where[] = "v.response_id = ?"; $params[] = $_GET['response_id']; }
if (!empty($_GET['period'])) {
    switch ($_GET['period']) {
        case 'today': $where[] = "DATE(v.created_at) = CURDATE()"; break;
        case 'yesterday': $where[] = "DATE(v.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; break;
        case 'week': $where[] = "v.created_at >= DATE_SUB(CURDATE(), INTERVAL(WEEKDAY(CURDATE())) DAY)"; break;
        case 'month': $where[] = "v.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"; break;
    }
}
$where_clause = implode(" AND ", $where);
$stmt = $pdo->prepare("SELECT v.*, p.email as created_by_email, c.name as city_name, r.label as response_label, r.color as response_color 
                     FROM visits v JOIN profiles p ON v.created_by = p.id JOIN cities c ON v.city_id = c.id 
                     JOIN response_types r ON v.response_id = r.id WHERE $where_clause ORDER BY v.created_at DESC");
$stmt->execute($params);
$visits = $stmt->fetchAll();
?>
<div class="row"><div class="col-md-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Tableau de <span style="color: var(--accent-purple)">Bord</span></h2>
        <a href="add_visit.php" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nouvelle Visite</a>
    </div>

    <!-- Filters Card -->
    <div class="card p-3 mb-4">
        <form method="GET" class="row g-2">
            <div class="col-md-3"><label class="form-label small">Période</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="all" <?= ($_GET['period'] ?? '') == 'all' ? 'selected' : '' ?>>Toutes</option>
                    <option value="today" <?= ($_GET['period'] ?? '') == 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
                    <option value="yesterday" <?= ($_GET['period'] ?? '') == 'yesterday' ? 'selected' : '' ?>>Hier</option>
                    <option value="week" <?= ($_GET['period'] ?? '') == 'week' ? 'selected' : '' ?>>Cette semaine</option>
                    <option value="month" <?= ($_GET['period'] ?? '') == 'month' ? 'selected' : '' ?>>Ce mois-ci</option>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small">Ville</label>
                <select name="city_id" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <?php foreach($cities as $c): ?><option value="<?= $c['id'] ?>" <?= ($_GET['city_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $c['name'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label small">Réponse</label>
                <select name="response_id" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <?php foreach($responses as $r): ?><option value="<?= $r['id'] ?>" <?= ($_GET['response_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= $r['label'] ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2"><button type="submit" class="btn btn-primary btn-sm flex-grow-1">Appliquer</button><a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a></div>
        </form>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button" role="tab"><i class="bi bi-list-ul me-2"></i>Liste</button></li>
        <li class="nav-item"><button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#mapView" type="button" role="tab"><i class="bi bi-map me-2"></i>Carte</button></li>
    </ul>

    <div class="tab-content" id="dashboardTabsContent">
        <div class="tab-pane fade show active" id="listView" role="tabpanel">
            <div class="table-responsive">
                <table class="table table-dark table-striped table-hover align-middle">
                    <thead><tr><th>Photo</th><th>Docteur / Tél</th><th>Adresse</th><th>Ville</th><th>Réponse</th><th>Commentaire</th><th>Dates</th><th>Actions</th></tr></thead>
                    <tbody><?php foreach ($visits as $visit): ?>
                        <tr><td><?php if ($visit['photo_url']): ?><img src="../<?= htmlspecialchars($visit['photo_url']) ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#photoModal" data-photo="../<?= htmlspecialchars($visit['photo_url']) ?>"><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
                            <td><div class="fw-bold text-white"><?= htmlspecialchars($visit['doctor_name']) ?></div><div class="small text-white opacity-75"><?= htmlspecialchars($visit['phone_number'] ?? '-') ?></div></td>
                            <td class="small"><div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($visit['address'] ?? '-') ?></div></td>
                            <td><?= htmlspecialchars($visit['city_name']) ?></td>
                            <td><span class="badge" style="background-color: <?= $visit['response_color'] ?>"><?= htmlspecialchars($visit['response_label']) ?></span></td>
                            <td class="small"><div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($visit['comment'] ?? '-') ?></div></td>
                            <td class="small"><div>Créé: <?= $visit['created_at'] ?></div><div>Modif: <?= $visit['last_edited_at'] ?? 'Jamais' ?></div></td>
                            <td><div class="btn-group btn-group-sm"><a href="edit_visit.php?id=<?= $visit['id'] ?>" class="btn btn-warning"><i class="bi bi-pencil"></i></a><a href="delete_visit.php?id=<?= $visit['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer?')"><i class="bi bi-trash"></i></a></div></td></tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="mapView" role="tabpanel"><div id="map" style="height: 600px; border-radius: 24px;"></div></div>
    </div>
</div></div>

<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0 pb-0"><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body p-0"><img id="modalImg" src="" class="img-fluid w-100" style="border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;"></div></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) { photoModal.addEventListener('show.bs.modal', function (e) { photoModal.querySelector('#modalImg').src = e.relatedTarget.getAttribute('data-photo'); }); }
    const map = L.map('map').setView([33.5731, -7.5898], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', { subdomains: 'abcd', maxZoom: 20 }).addTo(map);
    const visits = <?= json_encode($visits) ?>;
    visits.forEach(v => { L.marker([v.latitude, v.longitude], { icon: L.divIcon({ className: 'custom-div-icon', html: `<div style="background-color:${v.response_color}; color:${v.response_color};"></div>` }) }).addTo(map).bindPopup(`<b>${v.doctor_name}</b><br>${v.address}<br>${v.response_label}<br>${v.comment}<br>${v.last_edited_at || ''}`); });
    if (navigator.geolocation) { navigator.geolocation.getCurrentPosition(pos => { map.setView([pos.coords.latitude, pos.coords.longitude], 13); }); }
    document.getElementById('map-tab').addEventListener('shown.bs.tab', function() { setTimeout(() => { map.invalidateSize(); }, 100); });
});
</script>
<?php require_once 'footer.php'; ?>
