<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

// Fetch options for filters
$cities = $pdo->query("SELECT * FROM cities ORDER BY name ASC")->fetchAll();
$responses = $pdo->query("SELECT * FROM response_types ORDER BY label ASC")->fetchAll();
$reps = $pdo->query("SELECT * FROM profiles WHERE role = 'rep' ORDER BY full_name ASC")->fetchAll();

// Pagination
$limit = isset($_GET['limit']) && $_GET['limit'] === 'all' ? 999999 : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($limit === 999999) ? 0 : ($page - 1) * $limit;

// Sorting
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'v.created_at';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Handle Filters
$where = ["1=1"];
$params = [];

if (!empty($_GET['city_id'])) {
    $where[] = "v.city_id = ?";
    $params[] = $_GET['city_id'];
}
if (!empty($_GET['response_id'])) {
    $where[] = "v.response_id = ?";
    $params[] = $_GET['response_id'];
}
if (!empty($_GET['rep_id'])) {
    $where[] = "v.created_by = ?";
    $params[] = $_GET['rep_id'];
}

// Time Period Logic
if (!empty($_GET['period'])) {
    switch ($_GET['period']) {
        case 'today':
            $where[] = "DATE(v.created_at) = CURDATE()";
            break;
        case 'yesterday':
            $where[] = "DATE(v.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'week':
            $where[] = "v.created_at >= DATE_SUB(CURDATE(), INTERVAL(WEEKDAY(CURDATE())) DAY)";
            break;
        case 'month':
            $where[] = "v.created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
            break;
    }
}

$where_clause = implode(" AND ", $where);

// Count for pagination
$count_query = "SELECT COUNT(*) FROM visits v WHERE $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$query = "SELECT v.*, p.full_name as rep_name, c.name as city_name, r.label as response_label, r.color as response_color 
          FROM visits v 
          JOIN profiles p ON v.created_by = p.id 
          JOIN cities c ON v.city_id = c.id 
          JOIN response_types r ON v.response_id = r.id 
          WHERE $where_clause
          ORDER BY $sort_by $sort_order
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$visits = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Liste des visites <span class="badge bg-secondary ms-2" style="font-size: 0.5em; vertical-align: middle;"><?= $total_rows ?> résultats</span></h2>
            <div class="d-flex gap-2">
                <button type="button" id="bulkDeleteBtn" class="btn btn-danger d-none" onclick="bulkDelete()">Supprimer la sélection</button>
                <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-success">Exporter CSV</a>
            </div>
        </div>

        <div id="selectionBanner" class="alert alert-warning d-none mb-3 py-2 text-center">
            Toutes les visites de cette page sont sélectionnées. 
            <a href="javascript:void(0)" onclick="selectAllFiltered(<?= $total_rows ?>)" class="fw-bold text-decoration-underline ms-2">Sélectionner les <?= $total_rows ?> résultats correspondants</a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Les visites sélectionnées ont été supprimées avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card p-3 shadow-sm mb-4">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small">Période</label>
                    <select name="period" class="form-select form-select-sm">
                        <option value="all" <?= ($_GET['period'] ?? '') == 'all' ? 'selected' : '' ?>>Toutes</option>
                        <option value="today" <?= ($_GET['period'] ?? '') == 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
                        <option value="yesterday" <?= ($_GET['period'] ?? '') == 'yesterday' ? 'selected' : '' ?>>Hier</option>
                        <option value="week" <?= ($_GET['period'] ?? '') == 'week' ? 'selected' : '' ?>>Cette semaine</option>
                        <option value="month" <?= ($_GET['period'] ?? '') == 'month' ? 'selected' : '' ?>>Ce mois-ci</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Ville</label>
                    <select name="city_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach($cities as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_GET['city_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $c['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Réponse</label>
                    <select name="response_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach($responses as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= ($_GET['response_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= $r['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Commercial</label>
                    <select name="rep_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach($reps as $rep): ?>
                            <option value="<?= $rep['id'] ?>" <?= ($_GET['rep_id'] ?? '') == $rep['id'] ? 'selected' : '' ?>><?= $rep['full_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Afficher</label>
                    <select name="limit" class="form-select form-select-sm">
                        <option value="20" <?= ($_GET['limit'] ?? '') == '20' ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= ($_GET['limit'] ?? '') == '50' ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($_GET['limit'] ?? '') == '100' ? 'selected' : '' ?>>100</option>
                        <option value="all" <?= ($_GET['limit'] ?? '') == 'all' ? 'selected' : '' ?>>Tout</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filtrer</button>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
                    <button type="button" id="selectAllBtn" class="btn btn-outline-light btn-sm">select All</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Photo</th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'doctor_name', 'sort_order' => $sort_order == 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">Docteur</a></th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'city_name', 'sort_order' => $sort_order == 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">Ville</a></th>
                        <th>Réponse</th>
                        <th>Commentaire</th>
                        <th>Commercial</th>
                        <th><a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'v.created_at', 'sort_order' => $sort_order == 'ASC' ? 'DESC' : 'ASC'])) ?>" class="text-white">Date</a></th>
                        <th>Dernière Modif.</th>
                        <th>Actions</th>
                        <th class="text-center"><i class="bi bi-check2-square"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $visit): ?>
                        <tr>
                            <td>
                                <?php 
                                $photo = $visit['photo_url'];
                                if ($photo) {
                                    $src = (strpos($photo, 'uploads/') === 0) ? "../$photo" : "../uploads/$photo";
                                } else {
                                    $src = "https://placehold.co/60x60?text=No+Photo";
                                }
                                ?>
                                <img src="<?= htmlspecialchars($src) ?>" alt="Photo" class="rounded" style="width: 60px; height: 60px; object-fit: cover; cursor: pointer; border: 1px solid var(--border-color);" data-bs-toggle="modal" data-bs-target="#photoModal" data-photo="<?= htmlspecialchars($src) ?>" onerror="this.src='https://placehold.co/60x60?text=Error'">
                            </td>
                            <td><div class="fw-bold text-white"><?= htmlspecialchars($visit['doctor_name']) ?></div></td>
                            <td class="small text-white opacity-75"><?= htmlspecialchars($visit['phone_number'] ?? '-') ?></td>
                            <td class="small"><?= htmlspecialchars($visit['address'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($visit['city_name']) ?></td>
                            <td><span class="badge" style="background-color: <?= $visit['response_color'] ?>"><?= htmlspecialchars($visit['response_label']) ?></span></td>
                            <td class="small text-white opacity-75" title="<?= htmlspecialchars($visit['comment']) ?>">
                                <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($visit['comment'] ?? '-') ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($visit['rep_name']) ?></td>
                            <td class="small"><?= $visit['created_at'] ?></td>
                            <td class="small text-muted"><?= $visit['last_edited_at'] ?? 'Jamais' ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="edit_visit.php?id=<?= $visit['id'] ?>" class="btn btn-warning"><i class="bi bi-pencil"></i></a>
                                    <a href="delete_visit.php?id=<?= $visit['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input visit-checkbox" value="<?= $visit['id'] ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($limit !== 999999 && $total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination pagination-sm justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <img id="modalImg" src="" class="img-fluid w-100 rounded-bottom">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoModal = document.getElementById('photoModal');
    if (photoModal) {
        photoModal.addEventListener('show.bs.modal', function (event) {
            const img = event.relatedTarget;
            const photoUrl = img.getAttribute('data-photo');
            const modalImg = photoModal.querySelector('#modalImg');
            modalImg.src = photoUrl;
        });
    }

    const selectAllBtn = document.getElementById('selectAllBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const checkboxes = document.querySelectorAll('.visit-checkbox');

    let isAllFilteredSelected = false;

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.visit-checkbox:checked').length;
        const totalOnPage = checkboxes.length;
        const selectionBanner = document.getElementById('selectionBanner');

        if (checkedCount > 0) {
            bulkDeleteBtn.classList.remove('d-none');
            
            if (isAllFilteredSelected) {
                bulkDeleteBtn.innerText = `Supprimer TOUT les <?= $total_rows ?> résultats`;
                selectionBanner.innerHTML = `Les <?= $total_rows ?> résultats sont sélectionnés. <a href="javascript:void(0)" onclick="resetSelection()" class="fw-bold text-decoration-underline ms-2">Annuler la sélection</a>`;
                selectionBanner.classList.remove('d-none');
            } else {
                bulkDeleteBtn.innerText = `Supprimer la sélection (${checkedCount})`;
                // Show banner only if all items on current page are checked and there are more pages
                if (checkedCount === totalOnPage && <?= $total_rows ?> > totalOnPage) {
                    selectionBanner.classList.remove('d-none');
                } else {
                    selectionBanner.classList.add('d-none');
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
        selectAllBtn.innerText = 'select All';
        selectAllBtn.classList.add('btn-outline-light');
        selectAllBtn.classList.remove('btn-light');
        updateBulkDeleteButton();
    };

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            // Check if we are currently in "Select All" or "Unselect All" mode
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
});

function bulkDelete() {
    const selectedIds = Array.from(document.querySelectorAll('.visit-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) return;

    let confirmMsg = `Êtes-vous sûr de vouloir supprimer les ${selectedIds.length} visites sélectionnées ?`;
    if (isAllFilteredSelected) {
        confirmMsg = `ATTENTION : Êtes-vous sûr de vouloir supprimer TOUS les <?= $total_rows ?> résultats correspondant aux filtres ? Cette action est irréversible.`;
    }

    if (confirm(confirmMsg)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'bulk_delete_visits.php';

        const inputIds = document.createElement('input');
        inputIds.type = 'hidden';
        inputIds.name = 'ids';
        inputIds.value = JSON.stringify(selectedIds);
        form.appendChild(inputIds);

        if (isAllFilteredSelected) {
            const inputAll = document.createElement('input');
            inputAll.type = 'hidden';
            inputAll.name = 'delete_all_filtered';
            inputAll.value = '1';
            form.appendChild(inputAll);

            // Pass current filters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.forEach((value, key) => {
                const inputFilter = document.createElement('input');
                inputFilter.type = 'hidden';
                inputFilter.name = 'filter_' + key;
                inputFilter.value = value;
                form.appendChild(inputFilter);
            });
        }

        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>
