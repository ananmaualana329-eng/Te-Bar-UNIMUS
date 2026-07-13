<?php
// api/order.php
session_start();
require_once '../config/database.php';

// Atur header agar merespon dalam format JSON
header('Content-Type: application/json');

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

// Ambil data JSON dari Fetch API JS
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['pickup_lat']) && isset($data['dropoff_lat'])) {
    $user_id = $_SESSION['user_id'];
    $pickup_lat = $data['pickup_lat'];
    $pickup_lng = $data['pickup_lng'];
    $dropoff_lat = $data['dropoff_lat'];
    $dropoff_lng = $data['dropoff_lng'];
    $jarak_km = $data['jarak_km'];
    $harga = $data['harga'];
    
    // Cari dummy driver yang sedang online secara acak
    $stmt_driver = $pdo->query("SELECT id FROM drivers WHERE status_online = '1' ORDER BY RAND() LIMIT 1");
    $driver = $stmt_driver->fetch();
    $driver_id = $driver ? $driver['id'] : NULL; // Boleh NULL jika belum ada driver (status 'Menunggu Driver')

    try {
        // Insert pesanan ke database (Prepared Statement)
        $payment_method = $data['payment'] ?? 'Cash';
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, driver_id, pickup_lat, pickup_lng, dropoff_lat, dropoff_lng, jarak_km, harga, status, metode_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Driver', ?)");
        $stmt->execute([$user_id, $driver_id, $pickup_lat, $pickup_lng, $dropoff_lat, $dropoff_lng, $jarak_km, $harga, $payment_method]);
                
        $order_id = $pdo->lastInsertId();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Pesanan berhasil dibuat!', 
            'order_id' => $order_id
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Data lokasi tidak lengkap.']);
}
?>