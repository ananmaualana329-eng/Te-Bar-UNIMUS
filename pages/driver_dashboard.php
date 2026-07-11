<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT d.*, u.nama, u.email FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.user_id = ?");
$stmt->execute([$user_id]);
$driver = $stmt->fetch();
if (!$driver) { die("Akses Ditolak."); }
$driver_id = $driver['id'];

// mengambil Order & Pendapatan Hari Ini
$stmt_order = $pdo->prepare("SELECT o.*, u.nama as nama_penumpang FROM orders o JOIN users u ON o.user_id = u.id WHERE o.status = 'Menunggu Driver' OR (o.status IN ('Driver Menuju Lokasi', 'Driver Tiba', 'Perjalanan Dimulai') AND o.driver_id = ?) ORDER BY o.created_at DESC LIMIT 1");
$stmt_order->execute([$driver_id]);
$active_order = $stmt_order->fetch();

$stmt_income = $pdo->prepare("SELECT SUM(harga) as total_pendapatan, COUNT(id) as total_trip FROM orders WHERE driver_id = ? AND status = 'Selesai' AND DATE(created_at) = CURDATE()");
$stmt_income->execute([$driver_id]);
$income_today = $stmt_income->fetch();
$total_pendapatan = $income_today['total_pendapatan'] ?? 0;
$total_trip = $income_today['total_trip'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Te-Bar</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background: #f8fafc; color: #333; }

        .sidebar { width: 420px; background: white; height: 100vh; overflow-y: auto; padding: 25px; box-shadow: 2px 0 15px rgba(0,0,0,0.05); z-index: 10; display: flex; flex-direction: column; }
        .sidebar::-webkit-scrollbar { width: 6px; } .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .map-container { flex: 1; height: 100vh; position: relative; z-index: 1; }
        #map { width: 100%; height: 100%; }

        /* NAVBAR ATAS DALAM SIDEBAR */
        .sidebar-nav { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .sidebar-nav button { flex: 1; padding: 10px; background: transparent; border: none; font-weight: 800; color: #94a3b8; cursor: pointer; transition: 0.3s; border-bottom: 3px solid transparent; }
        .sidebar-nav button.active { color: #033D36; border-bottom: 3px solid #14c7c0; }
        
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* KARTU PENDAPATAN */
        .income-card { background: linear-gradient(135deg, #14c7c0, #0d9488); color: white; padding: 25px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(20, 199, 192, 0.3); text-align: center;}
        .income-card h3 { font-size: 0.85rem; font-weight: 700; opacity: 0.9; margin-bottom: 5px; text-transform: uppercase; }
        .income-card h2 { font-size: 2.5rem; font-weight: 900; margin-bottom: 15px; }
        .income-stats { display: flex; justify-content: space-around; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px; }
        .income-stats div p { font-size: 0.75rem; font-weight: 600; opacity: 0.9; }
        .income-stats div h4 { font-size: 1.1rem; font-weight: 800; }

        /* ORDER AKTIF & TOMBOL CHAT */
        .active-order-card { background: white; padding: 20px; border-radius: 16px; box-shadow: 0 10px 25px rgba(20, 199, 192, 0.2); border: 2px solid #14c7c0; display: none; margin-bottom: 15px; }
        .active-order-card h3 { color: #033D36; font-size: 1.1rem; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; font-weight: 800; }
        .btn-chat { background: #10b981; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 800; cursor: pointer; width: 100%; margin: 10px 0; font-size: 1rem; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-action { width: 100%; padding: 14px; border: none; border-radius: 10px; font-weight: 800; font-size: 1rem; cursor: pointer; color: white; transition: 0.3s; }
        .btn-terima { background: #033D36; } .btn-selesai { background: #ef4444; }

        /* MODAL CHAT */
        .chat-modal { position: fixed; bottom: -100%; left: 0; width: 420px; height: 60vh; background: white; z-index: 3000; border-radius: 20px 20px 0 0; box-shadow: 0 -10px 40px rgba(0,0,0,0.15); transition: 0.4s; display: flex; flex-direction: column; }
        .chat-modal.active { bottom: 0; }
        .chat-header { background: #033D36; color: white; padding: 15px 20px; border-radius: 20px 20px 0 0; display: flex; justify-content: space-between; font-weight: 800; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px; }
        .bubble { max-width: 80%; padding: 12px 16px; border-radius: 16px; font-size: 0.85rem; font-weight: 600; }
        .bubble.me { background: #14c7c0; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .bubble.other { background: #e2e8f0; color: #333; align-self: flex-start; border-bottom-left-radius: 4px; }
        .chat-input { display: flex; padding: 15px; border-top: 1px solid #eee; }
        .chat-input input { flex: 1; padding: 12px; border: 2px solid #eee; border-radius: 15px; outline: none; font-family: 'Montserrat'; font-weight: 600; }
        .chat-input button { background: #14c7c0; color: white; border: none; padding: 0 20px; border-radius: 15px; margin-left: 10px; cursor: pointer; font-weight: bold; }

        /* MODAL WITHDRAW */
        .withdraw-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100vh; background: rgba(0,0,0,0.5); z-index: 4000; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .withdraw-modal.active { display: flex; }
        .withdraw-card { background: white; padding: 30px; border-radius: 20px; width: 90%; max-width: 400px; box-shadow: 0 15px 40px rgba(0,0,0,0.2); }
        .withdraw-card h3 { color: #033D36; font-weight: 800; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .w-input { width: 100%; padding: 12px; margin-bottom: 15px; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; font-family: 'Montserrat'; font-weight: 600; }
        .w-input:focus { border-color: #14c7c0; }
        .btn-w-submit { width: 100%; padding: 14px; background: #14c7c0; color: white; border: none; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s; margin-top: 10px;}
        .btn-w-submit:hover { background: #033D36; }

        @media (max-width: 768px) {
            body { flex-direction: column-reverse; }
            .sidebar { width: 100%; height: 50vh; border-top-left-radius: 24px; border-top-right-radius: 24px; padding-bottom: 30px; }
            .map-container { height: 50vh; }
            .chat-modal { width: 100%; height: 75vh; }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 1.1rem; color: #033D36; font-weight: 800;"><?= $driver['nama'] ?></h2>
                <p style="font-size: 0.8rem; color: #64748b; font-weight: 600;"><?= $driver['plat_nomor'] ?></p>
            </div>
            <?php $isOnline = $driver['status_online'] == '1'; ?>
            <button id="btnStatus" onclick="toggleStatus()" style="background: <?= $isOnline ? '#10b981' : '#ef4444' ?>; color: white; border: none; padding: 8px 15px; border-radius: 20px; font-weight: 800; cursor: pointer;">
                <span id="statusText"><?= $isOnline ? 'Online' : 'Offline' ?></span>
            </button>
        </div>

        <div class="sidebar-nav">
            <button class="active" onclick="gantiTab('order', this)">Tugas</button>
            <button onclick="gantiTab('pendapatan', this)">Pendapatan</button>
            <button onclick="gantiTab('profil', this)">Profil</button>
        </div>

        <div id="tab-order" class="tab-content active">
            <div id="waitingBox" style="<?= $active_order ? 'display:none;' : 'display:block;' ?> padding: 30px 20px; border: 2px dashed #cbd5e1; border-radius: 16px; text-align: center; background: #f8fafc;">
                <h3 style="color: #033D36; font-size: 1rem; font-weight: 800;">Mencari Penumpang...</h3>
                <p style="font-size: 0.8rem; color: #64748b;">Pastikan status Anda Online.</p>
            </div>

            <div class="active-order-card" id="activeOrderBox" style="<?= $active_order ? 'display:block;' : 'display:none;' ?>">
                <h3 id="ao-nama">🔔 Order: <?= $active_order['nama_penumpang'] ?? '' ?></h3>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.85rem; font-weight: 800;">
                    <span style="color: #64748b;" id="ao-status"><?= $active_order['status'] ?? '' ?></span>
                    <span style="color: #14c7c0;" id="ao-harga">Rp <?= number_format($active_order['harga'] ?? 0, 0, ',', '.') ?></span>
                </div>
                
                <div id="action-buttons">
                    </div>
            </div>
        </div>

        <div id="tab-pendapatan" class="tab-content">
            <div class="income-card">
                <h3>Saldo Aktif (QRIS & Transfer)</h3>
                <h2>Rp <?= number_format($total_pendapatan * 0.7, 0, ',', '.') ?></h2>
                <div class="income-stats">
                    <div><p>Total Tarikan</p><h4><?= $total_trip ?> Order</h4></div>
                    <div><p>Performa</p><h4>⭐ 4.9</h4></div>
                </div>
            </div>
            
            <button onclick="document.getElementById('modalWithdraw').classList.add('active')" style="width: 100%; padding: 15px; background: white; color: #033D36; border: 2px solid #033D36; border-radius: 15px; font-weight: 800; font-size: 1rem; cursor: pointer; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">💸 Cairkan Pendapatan</button>
            
            <p style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-align: center; line-height: 1.5;">Pendapatan dari tunai/cash langsung masuk ke saku Anda. Tombol ini khusus mencairkan dana dari pembayaran non-tunai (QRIS) ke e-wallet Anda.</p>
        </div>

        <div id="tab-profil" class="tab-content">
            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 15px;">
                <p style="font-size: 0.75rem; color: #64748b; font-weight: 800; margin-bottom: 5px;">NOMOR SIM C</p>
                <p style="font-weight: 700; color: #033D36;"><?= $driver['sim'] ?: 'Belum Diisi' ?></p>
            </div>
            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 30px;">
                <p style="font-size: 0.75rem; color: #64748b; font-weight: 800; margin-bottom: 5px;">STNK KENDARAAN</p>
                <p style="font-weight: 700; color: #033D36;"><?= $driver['stnk'] ?: 'Belum Diisi' ?></p>
            </div>
            <a href="../logout.php" style="display: block; text-align: center; font-weight: 700; color: #ef4444; text-decoration: none; padding: 15px; background: #fef2f2; border-radius: 12px;">Keluar Akun</a>
        </div>
    </div>

    <div class="map-container" id="map"></div>

    <div class="chat-modal" id="chatModal">
        <div class="chat-header">
            <span>💬 Live Chat Penumpang</span>
            <span style="cursor:pointer; font-size:1.5rem;" onclick="document.getElementById('chatModal').classList.remove('active')">×</span>
        </div>
        <div class="chat-body" id="chatBody"></div>
        <div class="chat-input">
            <input type="text" id="pesanInput" placeholder="Ketik pesan..." onkeypress="if(event.key === 'Enter') kirimPesan()">
            <button onclick="kirimPesan()">➤</button>
        </div>
    </div>

    <div class="withdraw-modal" id="modalWithdraw">
        <div class="withdraw-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Cairkan Saldo ke E-Wallet</h3>
                <span style="font-size: 1.5rem; cursor: pointer; margin-top: -30px;" onclick="document.getElementById('modalWithdraw').classList.remove('active')">×</span>
            </div>
            
            <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 5px; display: block;">Jumlah Penarikan (Rp)</label>
            <input type="number" id="w-jumlah" class="w-input" placeholder="Minimal Rp 10.000" value="<?= $total_pendapatan * 0.7 ?>">
            
            <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 5px; display: block;">Pilih E-Wallet / Bank</label>
            <select id="w-metode" class="w-input">
                <option value="GoPay">GoPay</option>
                <option value="DANA">DANA</option>
                <option value="OVO">OVO</option>
                <option value="ShopeePay">ShopeePay</option>
            </select>
            
            <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 5px; display: block;">Nomor HP / Rekening</label>
            <input type="text" id="w-rekening" class="w-input" placeholder="08123456789">
            
            <button class="btn-w-submit" onclick="prosesWithdraw()">Tarik Saldo Sekarang</button>
        </div>
    </div>
</body>
</html>