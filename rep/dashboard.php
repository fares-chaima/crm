<?php
require_once '../includes/db_connect.php';
require_once 'header.php';
$rep_id = $_SESSION['user_id'];

// Fetch user permissions directly from DB to be always up-to-date
$stmt_user = $pdo->prepare("SELECT view_city_visits FROM profiles WHERE id = ?");
$stmt_user->execute([$rep_id]);
$view_city_visits = $stmt_user->fetchColumn();

// Fetch assigned cities for the representative
$stmt_cities = $pdo->prepare("SELECT city_id FROM user_city_access WHERE rep_id = ?");
$stmt_cities->execute([$rep_id]);
$assigned_city_ids = $stmt_cities->fetchAll(PDO::FETCH_COLUMN);

$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$responses = $pdo->query("SELECT * FROM response_types ORDER BY label ASC")->fetchAll();

$where = [];
$params = [];

if ($view_city_visits && !empty($assigned_city_ids)) {
    // If user can view city visits, show visits from all users in their assigned cities
    $placeholders = implode(',', array_fill(0, count($assigned_city_ids), '?'));
    $where[] = "v.city_id IN ($placeholders)";
    $params = array_merge($params, $assigned_city_ids);
} else {
    // Otherwise, only show their own visits
    $where[] = "v.created_by = ?";
    $params[] = $rep_id;
}

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
        <h2 class="fw-bold">Tableau de <span style="color: var(--accent-purple)">Bord</span> <span class="badge bg-secondary ms-2" style="font-size: 0.4em; vertical-align: middle;"><?= count($visits) ?> visites</span></h2>
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
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Appliquer</button>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
                <button type="button" id="selectAllBtn" class="btn btn-outline-light btn-sm">select All</button>
            </div>
        </form>
    </div>

    <div id="selectionBanner" class="alert alert-warning d-none mb-3 py-2 text-center">
        Toutes les visites de cette page sont sélectionnées. 
        <a href="javascript:void(0)" onclick="selectAllFiltered(<?= count($visits) ?>)" class="fw-bold text-decoration-underline ms-2">Sélectionner les <?= count($visits) ?> résultats correspondants</a>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" id="bulkDeleteBtn" class="btn btn-danger d-none" onclick="bulkDelete()">Supprimer la sélection</button>
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
                    <thead><tr><th>Photo</th><th>Docteur / Tél</th><th>Adresse</th><th>Ville</th><th>Réponse</th><th>Commentaire</th><th>Dates</th><th>Actions</th><th class="text-center"><i class="bi bi-check2-square"></i></th></tr></thead>
                    <tbody><?php foreach ($visits as $visit): ?>
                        <tr><td>
                            <?php 
                            $photo = $visit['photo_url'];
                            if ($photo) {
                                $src = (strpos($photo, 'uploads/') === 0) ? "../$photo" : "../uploads/$photo";
                            } else {
                                $src = "https://placehold.co/60x60?text=No+Photo";
                            }
                            ?>
                            <img src="<?= htmlspecialchars($src) ?>" class="rounded" style="width: 60px; height: 60px; object-fit: cover; cursor: pointer; border: 1px solid var(--border-color);" data-bs-toggle="modal" data-bs-target="#photoModal" data-photo="<?= htmlspecialchars($src) ?>" onerror="this.src='https://placehold.co/60x60?text=Error'">
                        </td>
                            <td><div class="fw-bold text-white"><?= htmlspecialchars($visit['doctor_name']) ?></div><div class="small text-white opacity-75"><?= htmlspecialchars($visit['phone_number'] ?? '-') ?></div></td>
                            <td class="small"><div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($visit['address'] ?? '-') ?></div></td>
                            <td><?= htmlspecialchars($visit['city_name']) ?></td>
                            <td><span class="badge" style="background-color: <?= $visit['response_color'] ?>"><?= htmlspecialchars($visit['response_label']) ?></span></td>
                            <td class="small"><div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($visit['comment'] ?? '-') ?></div></td>
                            <td class="small"><div>Créé: <?= $visit['created_at'] ?></div><div>Modif: <?= $visit['last_edited_at'] ?? 'Jamais' ?></div></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($visit['created_by'] == $rep_id): ?>
                                        <a href="edit_visit.php?id=<?= $visit['id'] ?>" class="btn btn-warning"><i class="bi bi-pencil"></i></a>
                                        <a href="delete_visit.php?id=<?= $visit['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer?')"><i class="bi bi-trash"></i></a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled title="Lecture seule"><i class="bi bi-eye"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($visit['created_by'] == $rep_id): ?>
                                    <input type="checkbox" class="form-check-input visit-checkbox" value="<?= $visit['id'] ?>">
                                <?php endif; ?>
                            </td>
                        </tr>
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
    
    const selectAllBtn = document.getElementById('selectAllBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const checkboxes = document.querySelectorAll('.visit-checkbox');
    const selectionBanner = document.getElementById('selectionBanner');

    let isAllFilteredSelected = false;

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.visit-checkbox:checked').length;
        const totalOnPage = checkboxes.length;

        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove('d-none');
            
            if (isAllFilteredSelected) {
                bulkDeleteBtn.innerText = `Supprimer TOUT mes <?= count($visits) ?> résultats`;
                selectionBanner.innerHTML = `Mes <?= count($visits) ?> résultats sont sélectionnés. <a href="javascript:void(0)" onclick="resetSelection()" class="fw-bold text-decoration-underline ms-2">Annuler la sélection</a>`;
                selectionBanner.classList.remove('d-none');
            } else {
                bulkDeleteBtn.innerText = `Supprimer la sélection (${checkedCount})`;
                // Show banner only if all items on current page are checked
                if (checkedCount === totalOnPage && totalOnPage > 0) {
                    // Note: Here we don't have pagination logic like admin yet, so it's simpler
                }
            }
        } else {
            bulkDeleteBtn.classList.add('d-none');
            selectionBanner.classList.add('d-none');
            isAllFilteredSelected = false;
        }
    }

    window.selectAllFiltered = function(total) {
        isAllFilteredSelected = true;
        updateBulkDeleteButton();
    };

    window.resetSelection = function() {
        isAllFilteredSelected = false;
        checkboxes.forEach(cb => cb.checked = false);
        if(selectAllBtn) {
            selectAllBtn.innerText = 'select All';
            selectAllBtn.classList.add('btn-outline-light');
            selectAllBtn.classList.remove('btn-light');
        }
        updateBulkDeleteButton();
    };

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            const isSelectingAll = selectAllBtn.innerText.toLowerCase().includes('select all');
            checkboxes.forEach(cb => cb.checked = isSelectingAll);
            
            selectAllBtn.innerText = isSelectingAll ? 'Unselect All' : 'select All';
            selectAllBtn.classList.toggle('btn-outline-light', !isSelectingAll);
            selectAllBtn.classList.toggle('btn-light', isSelectingAll);
            
            if (!isSelectingAll) isAllFilteredSelected = false;
            updateBulkDeleteButton();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteButton);
    });

    const map = L.map('map').setView([33.5731, -7.5898], 13);
    L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: 'Map data &copy; Google'
    }).addTo(map);
    const visits = <?= json_encode($visits) ?>;
    const currentRepId = <?= json_encode($rep_id) ?>;
    visits.forEach(v => { 
        const creatorText = v.created_by == currentRepId ? 'Moi' : v.created_by_email;
        L.marker([v.latitude, v.longitude], { 
            icon: L.divIcon({ className: 'custom-div-icon', html: `<div style="background-color:${v.response_color}; color:${v.response_color};"></div>` }) 
        }).addTo(map).bindPopup(`<b>${v.doctor_name}</b><br>${v.address}<br>${v.response_label}<br>Par: ${creatorText}<br>${v.comment}<br>${v.last_edited_at || ''}`); 
    });
    if (navigator.geolocation) { navigator.geolocation.getCurrentPosition(pos => { map.setView([pos.coords.latitude, pos.coords.longitude], 13); }); }
    document.getElementById('map-tab').addEventListener('shown.bs.tab', function() { setTimeout(() => { map.invalidateSize(); }, 100); });
});

function bulkDelete() {
    const selectedIds = Array.from(document.querySelectorAll('.visit-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;

    if (confirm(`Êtes-vous sûr de vouloir supprimer les ${selectedIds.length} visites sélectionnées ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'bulk_delete_visits.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids';
        input.value = JSON.stringify(selectedIds);
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php require_once 'footer.php'; ?>
