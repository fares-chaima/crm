<?php
require_once '../includes/db_connect.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $where = ["1=1"];
        $params = [];
        $isAllFiltered = isset($_POST['delete_all_filtered']) && $_POST['delete_all_filtered'] === '1';

        if ($isAllFiltered) {
            // Reconstruct filters from POST
            if (!empty($_POST['filter_city_id'])) {
                $where[] = "city_id = ?";
                $params[] = $_POST['filter_city_id'];
            }
            if (!empty($_POST['filter_response_id'])) {
                $where[] = "response_id = ?";
                $params[] = $_POST['filter_response_id'];
            }
            if (!empty($_POST['filter_rep_id'])) {
                $where[] = "created_by = ?";
                $params[] = $_POST['filter_rep_id'];
            }
            if (!empty($_POST['filter_period'])) {
                switch ($_POST['filter_period']) {
                    case 'today':
                        $where[] = "DATE(created_at) = CURDATE()";
                        break;
                    case 'yesterday':
                        $where[] = "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                        break;
                    case 'week':
                        $where[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL(WEEKDAY(CURDATE())) DAY)";
                        break;
                    case 'month':
                        $where[] = "created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                        break;
                }
            }
            
            $where_clause = implode(" AND ", $where);
            
            // Get photos for deletion
            $stmt = $pdo->prepare("SELECT photo_url FROM visits WHERE $where_clause");
            $stmt->execute($params);
            $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete records
            $stmt = $pdo->prepare("DELETE FROM visits WHERE $where_clause");
            $stmt->execute($params);
        } else {
            // Standard selection delete
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (empty($ids)) {
                header('Location: dashboard.php');
                exit;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // Get photos
            $stmt = $pdo->prepare("SELECT photo_url FROM visits WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $photos = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete records
            $stmt = $pdo->prepare("DELETE FROM visits WHERE id IN ($placeholders)");
            $stmt->execute($ids);
        }

        // Clean up photo files
        foreach ($photos as $photo) {
            if ($photo) {
                // Handle both relative and absolute paths if necessary
                $filePath = __DIR__ . '/../' . (strpos($photo, 'uploads/') === 0 ? '' : 'uploads/') . $photo;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }

        header('Location: dashboard.php?msg=deleted');
        exit;
    } catch (PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

header('Location: dashboard.php');
exit;
