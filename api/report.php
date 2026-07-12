<?php
// api/report.php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['order_id']) && isset($data['pesan']) && isset($_SESSION['user_id'])) {
    $order_id = intval($data['order_id']);
    $user_id = $_SESSION['user_id'];
    $pesan = htmlspecialchars($data['pesan']);

    try {
        $stmt = $pdo->prepare("INSERT INTO reports (order_id, pelapor_id, pesan, status) VALUES (?, ?, ?, 'Darurat')");
        $stmt->execute([$order_id, $user_id, $pesan]);
        
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
}
?>