<?php
// logout.php
session_start();

// Hapus semua data sesi
$_SESSION = array();
session_unset();
session_destroy();

// Metode 1: Redirect menggunakan PHP murni (Akan bekerja jika tidak ada error/spasi sebelumnya)
if (!headers_sent()) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=index.php">
    <title>Keluar dari Te-Bar...</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8fafc;
            color: #033D36;
        }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <h2>Memproses log out...</h2>
        <p>Jika Anda tidak dialihkan otomatis, <a href="index.php" style="color: #14c7c0;">klik di sini</a>.</p>
    </div>
    
    <script>
        // Redirect paksa menggunakan JavaScript
        window.location.replace("index.php");
    </script>
</body>
</html>