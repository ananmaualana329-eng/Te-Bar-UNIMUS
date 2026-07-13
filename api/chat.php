<?php
// api/chat.php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) exit;

$method = $_SERVER['REQUEST_METHOD'];

// GET: MENGAMBIL PESAN UNTUK DITAMPILKAN
if ($method === 'GET' && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $stmt = $pdo->prepare("SELECT * FROM chats WHERE order_id = ? ORDER BY created_at ASC");
    $stmt->execute([$order_id]);
    echo json_encode($stmt->fetchAll());
}

// POST: MENGIRIM PESAN BARU
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['order_id']) && isset($data['pesan'])) {
        $order_id = intval($data['order_id']);
        $sender_id = $_SESSION['user_id'];
        $pesan = htmlspecialchars($data['pesan']);

        $stmt = $pdo->prepare("INSERT INTO chats (order_id, sender_id, pesan) VALUES (?, ?, ?)");
        if ($stmt->execute([$order_id, $sender_id, $pesan])) {
            echo json_encode(['status' => 'success']);
        }
    }
}
?>