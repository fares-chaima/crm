<?php
require_once '../../includes/api_helpers.php';

$user = apiRequireBearerUser($pdo, 'rep');
$repId = (int) $user['id'];
$visits = repFetchVisits($pdo, $_GET, $repId);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="rep_visits_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['doctor_name', 'phone_number', 'address', 'latitude', 'longitude', 'city', 'response', 'comment', 'photo_url', 'created_by_email', 'created_at', 'last_edited_at']);

foreach ($visits as $visit) {
    fputcsv($output, [
        $visit['doctor_name'],
        $visit['phone_number'],
        $visit['address'],
        $visit['latitude'],
        $visit['longitude'],
        $visit['city_name'],
        $visit['response_label'],
        $visit['comment'],
        $visit['photo_url'],
        $visit['created_by_email'],
        $visit['created_at'],
        $visit['last_edited_at'],
    ]);
}

fclose($output);
exit;
