<?php
// api/update_profile.php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Akses ditolak");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $user_id = $_SESSION['user_id'];
    $allowed = ['jpg', 'jpeg', 'png'];
    $filename = $_FILES['foto']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $foto_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $destination = '../assets/uploads/' . $foto_name;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
            // Update nama foto di database
            $stmt = $pdo->prepare("UPDATE users SET foto = ? WHERE id = ?");
            $stmt->execute([$foto_name, $user_id]);
            
            // Redirect kembali ke halaman profil
            header("Location: ../pages/map.php?tab=profil");
            exit;
        }
    }
    echo "<script>alert('Gagal upload. Pastikan format JPG/PNG.'); window.location.href='../pages/map.php?tab=profil';</script>";
}
?>