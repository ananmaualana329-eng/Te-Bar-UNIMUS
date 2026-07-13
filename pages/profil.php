<?php
// pages/profile.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Ambil riwayat order
$stmt_history = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt_history->execute([$user_id]);
$histories = $stmt_history->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil - Te-Bar</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background: #f8fafc; color: #333; }
        .header-bg { background: #033D36; padding: 60px 20px 100px; text-align: center; color: white; }
        .container { max-width: 800px; margin: -60px auto 40px; padding: 0 20px; }
        .profile-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 30px; text-align: center; }
        .profile-avatar { width: 100px; height: 100px; background: #14c7c0; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; margin: 0 auto 15px; border: 4px solid white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .profile-card h2 { color: #033D36; font-weight: 800; font-size: 1.5rem; }
        .profile-card p { color: #64748b; font-weight: 600; }
        
        .history-list { display: flex; flex-direction: column; gap: 15px; }
        .history-card { background: white; padding: 20px; border-radius: 15px; border-left: 5px solid #14c7c0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .h-details h4 { color: #033D36; font-weight: 800; margin-bottom: 5px; }
        .h-details p { font-size: 0.85rem; color: #64748b; font-weight: 600; }
        .h-price { text-align: right; }
        .h-price span { display: block; font-size: 1.2rem; color: #033D36; font-weight: 800; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: #e2e8f0; color: #475569; margin-top: 5px; }
        .badge.selesai { background: #dcfce7; color: #166534; }
        .badge.batal { background: #fee2e2; color: #991b1b; }
        
        .nav-bar { display: flex; justify-content: space-between; padding: 20px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .nav-bar a { text-decoration: none; color: #033D36; font-weight: 700; }
    </style>
</head>
<body>
    <div class="nav-bar">
        <a href="map.php">← Kembali ke Peta</a>
        <a href="../logout.php" style="color: #ef4444;">Logout</a>
    </div>

    <div class="header-bg">
        <h1>Profil Mahasiswa</h1>
    </div>

    <div class="container">
        <div class="profile-card">
            <div class="profile-avatar"><?= substr($user['nama'], 0, 1) ?></div>
            <h2><?= $user['nama'] ?></h2>
            <p><?= $user['nim'] ?> • <?= $user['email'] ?></p>
        </div>

        <h3 style="color: #033D36; margin-bottom: 15px; font-weight: 800;">Riwayat Perjalanan</h3>
        
        <div class="history-list">
            <?php if (count($histories) > 0): ?>
                <?php foreach ($histories as $h): ?>
                    <div class="history-card">
                        <div class="h-details">
                            <h4><?= date('d M Y - H:i', strtotime($h['created_at'])) ?></h4>
                            <p>Jarak: <?= $h['jarak_km'] ?> KM</p>
                            <?php 
                                $badgeClass = '';
                                if ($h['status'] == 'Selesai') $badgeClass = 'selesai';
                                if ($h['status'] == 'Batal') $badgeClass = 'batal';
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $h['status'] ?></span>
                        </div>
                        <div class="h-price">
                            <span>Rp <?= number_format($h['harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 20px; color:#64748b; font-weight:600;">Belum ada riwayat pesanan.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>