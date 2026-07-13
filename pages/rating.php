<?php
// pages/rating.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: map.php");
    exit;
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Proses ketika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bintang = intval($_POST['bintang']);
    $review = htmlspecialchars($_POST['review']);

    // Simpan ke tabel ratings
    $stmt = $pdo->prepare("INSERT INTO ratings (order_id, bintang, review) VALUES (?, ?, ?)");
    if ($stmt->execute([$order_id, $bintang, $review])) {
        echo "<script>alert('Terima kasih atas ulasan Anda!'); window.location.href='profile.php';</script>";
        exit;
    }
}

// Ambil detail driver
$stmt = $pdo->prepare("SELECT u.nama, u.foto FROM orders o JOIN drivers d ON o.driver_id = d.id JOIN users u ON d.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$driver = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beri Ulasan - Te-Bar</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background: linear-gradient(135deg, #14c7c0 0%, #033D36 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .rating-card { background: white; padding: 40px; border-radius: 20px; width: 100%; max-width: 500px; text-align: center; box-shadow: 0 15px 30px rgba(0,0,0,0.2); }
        .rating-card h2 { color: #033D36; margin-bottom: 10px; font-weight: 800; }
        .avatar { width: 80px; height: 80px; background: #eee; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .stars { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; margin-bottom: 20px; }
        .stars input { display: none; }
        .stars label { font-size: 3rem; color: #ccc; cursor: pointer; transition: 0.2s; }
        .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color: #fbbf24; }
        textarea { width: 100%; padding: 15px; border: 2px solid #eee; border-radius: 12px; margin-bottom: 20px; resize: vertical; min-height: 100px; font-weight: 500; outline: none; }
        textarea:focus { border-color: #14c7c0; }
        .btn-submit { width: 100%; padding: 15px; background: #033D36; color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #022622; }
    </style>
</head>
<body>
    <div class="rating-card">
        <div class="avatar">🛵</div>
        <h2>Perjalanan Selesai!</h2>
        <p style="color: #666; margin-bottom: 30px;">Bagaimana pengalamanmu berkendara bersama <strong><?= $driver['nama'] ?? 'Driver' ?></strong>?</p>
        
        <form method="POST">
            <div class="stars">
                <input type="radio" id="star5" name="bintang" value="5" required><label for="star5">★</label>
                <input type="radio" id="star4" name="bintang" value="4"><label for="star4">★</label>
                <input type="radio" id="star3" name="bintang" value="3"><label for="star3">★</label>
                <input type="radio" id="star2" name="bintang" value="2"><label for="star2">★</label>
                <input type="radio" id="star1" name="bintang" value="1"><label for="star1">★</label>
            </div>
            <textarea name="review" placeholder="Tuliskan kesan pesanmu di sini..." required></textarea>
            <button type="submit" class="btn-submit">Kirim Ulasan</button>
        </form>
    </div>
</body>
</html>