<?php
require_once '../includes/db_connect.php';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    $stmt = $pdo->prepare("INSERT INTO profiles (email, password, full_name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $password, $full_name, $role]);
    header('Location: users.php?msg=User added');
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM profiles WHERE id = ? AND role = 'rep'");
    $stmt->execute([$_GET['delete']]);
    header('Location: users.php?msg=User deleted');
    exit;
}

$stmt = $pdo->query("SELECT * FROM profiles WHERE role = 'rep' ORDER BY id DESC");
$reps = $stmt->fetchAll();
?>
<div class="row">
    <div class="col-md-4">
        <h3>Ajouter Rep</h3>
        <form action="users.php" method="POST">
            <input type="hidden" name="add_user" value="1">
            <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
            <input type="password" name="password" placeholder="Mot de passe" class="form-control mb-2" required>
            <input type="text" name="full_name" placeholder="Nom Complet" class="form-control mb-2" required>
            <input type="hidden" name="role" value="rep">
            <button class="btn btn-primary w-100">Ajouter</button>
        </form>
    </div>
    <div class="col-md-8">
        <h3>Reps Existants</h3>
        <table class="table table-dark table-striped table-hover align-middle">
            <thead><tr><th>Email</th><th>Nom</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach($reps as $rep): ?>
                    <tr>
                        <td><?= $rep['email'] ?></td>
                        <td><?= $rep['full_name'] ?></td>
                        <td>
                            <a href="assign_cities.php?id=<?= $rep['id'] ?>" class="btn btn-sm btn-info">Villes</a>
                            <a href="users.php?delete=<?= $rep['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer?')">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'footer.php'; ?>
