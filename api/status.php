<?php
// api/status.php
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$order_id = intval($_GET['id']);

// Ambil status pesanan dan koordinat driver terkini
$stmt = $pdo->prepare("SELECT o.status, d.latitude, d.longitude 
                       FROM orders o 
                       LEFT JOIN drivers d ON o.driver_id = d.id 
                       WHERE o.id = ?");
$stmt->execute([$order_id]);
$data = $stmt->fetch();

if ($data) {
    echo json_encode([
        'status_order' => $data['status'],
        'driver_lat' => $data['latitude'],
        'driver_lng' => $data['longitude']
    ]);
} else {
    echo json_encode(['error' => 'Data tidak ditemukan']);
}
?>