<?php
// api/update_location.php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['driver_id']) && isset($data['lat']) && isset($data['lng'])) {
    $driver_id = intval($data['driver_id']);
    $lat = floatval($data['lat']);
    $lng = floatval($data['lng']);

    try {
        $stmt = $pdo->prepare("UPDATE drivers SET latitude = ?, longitude = ? WHERE id = ?");
        $stmt->execute([$lat, $lng, $driver_id]);
        
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error']);
    }
}
?>