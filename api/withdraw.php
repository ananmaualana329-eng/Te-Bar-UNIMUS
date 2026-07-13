<?php
// api/withdraw.php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['driver_id']) && isset($data['jumlah']) && isset($data['metode']) && isset($data['rekening'])) {
    $driver_id = intval($data['driver_id']);
    $jumlah = intval($data['jumlah']);
    $metode = htmlspecialchars($data['metode']);
    $rekening = htmlspecialchars($data['rekening']);

    // Cek apakah ada penarikan yang masih pending agar tidak spam
    $stmt_check = $pdo->prepare("SELECT id FROM withdrawals WHERE driver_id = ? AND status = 'Pending'");
    $stmt_check->execute([$driver_id]);
    
    if ($stmt_check->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Anda masih memiliki permintaan penarikan yang sedang diproses Admin.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO withdrawals (driver_id, jumlah, metode, nomor_rekening, status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$driver_id, $jumlah, $metode, $rekening]);
        
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data formulir tidak lengkap.']);
}
?>