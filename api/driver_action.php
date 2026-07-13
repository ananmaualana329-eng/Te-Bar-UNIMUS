<?php
// api/driver_action.php
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['order_id']) && isset($data['driver_id']) && isset($data['status'])) {
    $order_id = intval($data['order_id']);
    $driver_id = intval($data['driver_id']);
    $status = htmlspecialchars($data['status']);

    try {
        // Update status order dan set driver_id yang mengambil pesanan ini
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, driver_id = ? WHERE id = ?");
        $stmt->execute([$status, $driver_id, $order_id]);

        echo json_encode(['status' => 'success', 'message' => 'Status berhasil diubah']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
}
?>