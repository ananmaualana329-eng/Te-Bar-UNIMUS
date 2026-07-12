<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header("Location: map.php"); exit; }

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT o.*, d.motor, d.plat_nomor, u.nama as nama_driver, u.foto 
                       FROM orders o 
                       LEFT JOIN drivers d ON o.driver_id = d.id 
                       LEFT JOIN users u ON d.user_id = u.id 
                       WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();
if (!$order) die("Pesanan tidak valid.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking - Penumpang</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        #map { flex: 1; width: 100%; z-index: 1; }

        .status-badge { position: absolute; top: 20px; left: 50%; transform: translateX(-50%); background: #033D36; color: white; padding: 12px 25px; border-radius: 20px; font-weight: 800; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .driver-card { position: absolute; bottom: 0; left: 0; width: 100%; background: white; border-radius: 30px 30px 0 0; padding: 30px; box-shadow: 0 -10px 30px rgba(0,0,0,0.1); z-index: 1000; }
        .driver-info { display: flex; align-items: center; gap: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .driver-info img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #14c7c0; }
        
        .action-group { display: flex; gap: 10px; margin-top: 15px; }
        .btn-chat { background: #10b981; color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 800; flex: 1; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 1rem; transition: 0.3s; }
        .btn-chat:hover { background: #059669; }
        .btn-sos { background: #ef4444; color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 800; cursor: pointer; width: 60px; transition: 0.3s; }

        /* MODAL CHAT ALA GRAB */
        .chat-modal { position: fixed; bottom: -100%; left: 0; width: 100%; height: 75vh; background: white; z-index: 3000; border-radius: 30px 30px 0 0; box-shadow: 0 -10px 40px rgba(0,0,0,0.2); transition: bottom 0.4s cubic-bezier(0.25, 1, 0.5, 1); display: flex; flex-direction: column; }
        .chat-modal.active { bottom: 0; }
        .chat-header { background: #033D36; color: white; padding: 20px; border-radius: 30px 30px 0 0; display: flex; justify-content: space-between; align-items: center; font-weight: 800; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px; }
        .bubble { max-width: 80%; padding: 12px 16px; border-radius: 16px; font-size: 0.85rem; font-weight: 600; line-height: 1.4; }
        .bubble.me { background: #14c7c0; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .bubble.other { background: #e2e8f0; color: #333; align-self: flex-start; border-bottom-left-radius: 4px; }
        .chat-input { display: flex; padding: 15px; background: white; border-top: 1px solid #eee; }
        .chat-input input { flex: 1; padding: 14px; border: 2px solid #eee; border-radius: 20px; outline: none; font-weight: 600; font-family: 'Montserrat'; }
        .chat-input input:focus { border-color: #14c7c0; }
        .chat-input button { background: #14c7c0; color: white; border: none; width: 50px; height: 50px; border-radius: 50%; margin-left: 10px; font-size: 1.2rem; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <div class="status-badge" id="order-status"><?= $order['status'] ?></div>
    <div id="map"></div>

    <div class="driver-card">
        <div class="driver-info">
            <img src="../assets/uploads/<?= $order['foto'] ?? 'default.jpg' ?>" alt="Driver">
            <div>
                <h3 id="driver-name" style="color:#033D36;"><?= $order['nama_driver'] ?? 'Mencari Driver...' ?></h3>
                <p><?= $order['motor'] ?? '-' ?> • <strong><?= $order['plat_nomor'] ?? '-' ?></strong></p>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; font-weight: 800;">
            <span style="color: #64748b;">Rp <?= number_format($order['harga'], 0, ',', '.') ?></span>
            <span style="color: #14c7c0;"><?= $order['jarak_km'] ?> KM</span>
        </div>
        
        <?php if($order['status'] != 'Selesai' && $order['status'] != 'Menunggu Driver'): ?>
            <div class="action-group">
                <button class="btn-chat" onclick="document.getElementById('chatModal').classList.add('active'); muatPesan();">💬 Chat Driver</button>
                <button class="btn-sos" onclick="alert('Lapor SOS')">🚨</button>
            </div>
        <?php endif; ?>
    </div>
    <script>
        const orderId = <?= $order_id ?>;
        const myUserId = <?= $_SESSION['user_id'] ?>;
        const map = L.map('map', { zoomControl: false }).setView([<?= $order['pickup_lat'] ?>, <?= $order['pickup_lng'] ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        const iconDriver = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png', iconSize: [25, 41], iconAnchor: [12, 41] });
        L.marker([<?= $order['pickup_lat'] ?>, <?= $order['pickup_lng'] ?>]).addTo(map).bindPopup("Lokasi Jemput");
        let driverMarker = null;

    </script>
</body>
</html>