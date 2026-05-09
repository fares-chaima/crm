<?php
require_once '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids'])) {
    $ids = json_decode($_POST['ids'], true);

    if (!empty($ids)) {
        try {
            // Verify ownership for reps
            if (!$is_admin) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT id FROM visits WHERE id IN ($placeholders) AND created_by = ?");
                $stmt->execute(array_merge($ids, [$user_id]));
                $valid_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($valid_ids)) {
                    header('Location: dashboard.php?error=no_permission');
                    exit;
                }
                $ids_to_delete = $valid_ids;
            } else {
                $ids_to_delete = $ids;
            }

            $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));

            // Get photo URLs to delete files
            $stmt = $pdo->prepare("SELECT photo_url FROM visits WHERE id IN ($placeholders)");
            $stmt->execute($ids_to_delete);
            $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($photos as $photo) {
                if ($photo) {
                    $filePath = __DIR__ . '/../' . (strpos($photo, 'uploads/') === 0 ? '' : 'uploads/') . $photo;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Delete visits
            $stmt = $pdo->prepare("DELETE FROM visits WHERE id IN ($placeholders)");
            $stmt->execute($ids_to_delete);

            header('Location: dashboard.php?msg=deleted');
            exit;
        } catch (PDOException $e) {
            die("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
}

header('Location: dashboard.php');
exit;
