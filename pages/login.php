<?php
session_start();
require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: map.php");
    exit;
}

$error = '';
$success = '';

// REGISTER
if (isset($_POST['register'])) {
    $nama = htmlspecialchars($_POST['nama']);
    $email = htmlspecialchars($_POST['email']);
    $role = $_POST['role']; 
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // 1. MENGECEK KONFIRMASI PASSWORD
    if ($password !== $konfirmasi_password) {
        $error = "Konfirmasi password tidak cocok! Harap periksa kembali.";
    } else {
        // 2. MENGECEK EMAIL GANDA
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email/NIM sudah terdaftar! Silakan login.";
        } else {
            // MENGENKRIPSI PASSWORD & INSERT KE TABEL USERS
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$nama, $email, $hashed_password, $role])) {
                $user_id = $pdo->lastInsertId(); // Ambil ID user yang baru mendaftar
                
                if ($role === 'driver') {
                    $motor = htmlspecialchars($_POST['motor']);
                    $plat = htmlspecialchars($_POST['plat']);
                    
                    // Proses Upload Foto
                    $foto_nama = 'default.jpg';
                    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                        $foto_nama = 'driver_' . time() . '.' . $ext;
                        move_uploaded_file($_FILES['foto']['tmp_name'], '../assets/uploads/' . $foto_nama);
                        
                        // mengupdate nama foto ke tabel users
                        $pdo->prepare("UPDATE users SET foto = ? WHERE id = ?")->execute([$foto_nama, $user_id]);
                    }
                    
                    // menyimpan data kendaraan ke tabel drivers
                    $stmt_driver = $pdo->prepare("INSERT INTO drivers (user_id, motor, plat_nomor) VALUES (?, ?, ?)");
                    $stmt_driver->execute([$user_id, $motor, $plat]);
                }
                
                $success = "Pendaftaran berhasil! Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat mendaftar ke database.";
            }
        }
    }
}
/// 2. LOGIN
    if (isset($_POST['login'])) {
        $nim = htmlspecialchars($_POST['nim']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = ?");
        $stmt->execute([$nim]);
        $user = $stmt->fetch();

        // Verifikasi password hash
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['nim'] = $user['nim'];
            
            // mengecek apakah user ini terdaftar sebagai driver
            $stmt_driver = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
            $stmt_driver->execute([$user['id']]);
            
            if ($stmt_driver->rowCount() > 0) {
                // Jika dia driver, arahkan ke dashboard driver
                header("Location: driver_dashboard.php");
            } else {
                // Jika dia penumpang biasa, arahkan ke peta
                header("Location: map.php");
            }
            exit;
        } else {
            $error = "NIM atau Password yang Anda masukkan salah!";
        }
    }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Daftar - Te-Bar UNIMUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* --- RESET & BASE --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { background: linear-gradient(135deg, #00FFFF 0%, #00BFFF 100%); color: #333; min-height: 100vh; display: flex; flex-direction: column; }

        /* --- NAVBAR --- */
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 80px; background-color: rgba(255, 255, 255, 0.25); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.3); position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 1.8rem; font-weight: 800; color: #033D36; text-decoration: none; }
        .nav-links a { text-decoration: none; color: #033D36; font-weight: 700; transition: opacity 0.3s; }
        .nav-links a:hover { opacity: 0.7; }

        /* --- MAIN CONTAINER & CARD --- */
        .auth-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .auth-card { background: rgba(255, 255, 255, 0.95); width: 100%; max-width: 500px; border-radius: 24px; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); overflow: hidden; animation: fadeUp 0.6s ease-out forwards; padding: 30px; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* --- WADAH TOGGLE MENU --- */
        .toggle-container { display: flex; background-color: #f1f5f9; border-radius: 30px; padding: 6px; margin-bottom: 30px; }
        .toggle-btn { flex: 1; padding: 12px 20px; border: none; background: transparent; font-size: 1rem; font-weight: 700; color: #94a3b8; cursor: pointer; border-radius: 25px; transition: all 0.3s ease; }
        .toggle-btn.active { background-color: #ffffff; color: #033D36; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); }
        .toggle-btn:hover:not(.active) { color: #033D36; }

        /* --- FORM STYLES --- */
        .form-section { display: none; animation: fadeIn 0.4s ease-in-out; }
        .form-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { color: #033D36; font-weight: 800; margin-bottom: 8px; }
        .form-header p { font-size: 0.9rem; color: #666; font-weight: 500; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 0.8rem; font-weight: 700; color: #033D36; margin-bottom: 8px; text-transform: uppercase; }
        .input-group input { width: 100%; padding: 14px; border: 2px solid #e1e1e1; border-radius: 12px; font-size: 1rem; font-weight: 500; outline: none; transition: border-color 0.3s; }
        .input-group input:focus { border-color: #033D36; }
        .btn-submit { width: 100%; padding: 15px; background-color: #033D36; color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { background-color: #022622; transform: translateY(-2px); }

        /* --- ROLE SELECTION --- */
        .role-selector { display: flex; gap: 15px; margin-bottom: 20px; }
        .role-option { flex: 1; padding: 12px; border: 2px solid #e1e1e1; border-radius: 12px; text-align: center; font-weight: 600; color: #666; cursor: pointer; transition: 0.3s; }
        .role-option.selected { border-color: #033D36; background-color: rgba(3, 61, 54, 0.05); color: #033D36; }
        .role-option input[type="radio"] { display: none; }

        /* --- ALERT --- */
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #4ade80; }

        @media (max-width: 768px) { header { padding: 15px 20px; } .auth-card { padding: 25px 20px; } }
    </style>
</head>
<body>

    <header>
        <a href="../index.php" class="logo">Te-Bar.</a>
        <nav class="nav-links">
            <a href="../index.php">← Beranda</a>
        </nav>
    </header>

    <div class="auth-container">
        <div class="auth-card">
            
            <div class="toggle-container">
                <button class="toggle-btn active" id="btn-masuk" onclick="switchForm('masuk')">Masuk</button>
                <button class="toggle-btn" id="btn-daftar" onclick="switchForm('daftar')">Daftar</button>
            </div>

            <!-- menampilkan Notifikasi Error/Success -->
            <?php if($error != ''): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            <?php if($success != ''): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <!-- FORM LOGIN -->
            <div id="login-form" class="form-section active">
                <div class="form-header">
                    <h2>Selamat Datang</h2>
                    <p>Masukkan NIM Anda untuk mengakses sistem</p>
                </div>
                <!-- Action kosong agar mengirim data ke halaman ini sendiri, tambah method POST -->
                <form method="POST" action="">
                    <div class="input-group">
                        <label>NIM MAHASISWA</label>
                        <input type="text" name="nim" inputmode="numeric" pattern="[0-9]*" placeholder="Contoh: 13182420123" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Masukkan password Anda" required>
                     </div>
                    <button type="submit" name="login" class="btn-submit">Masuk Sekarang</button>
                </form>
            </div>

            <!-- FORM REGISTER -->
            <div id="register-form" class="form-section">
                <div class="form-header">
                    <h2>Mulai Tebengan Baru</h2>
                    <p>Gabung komunitas transportasi terpercaya UNIMUS</p>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #033D36; margin-bottom: 8px;">DAFTAR SEBAGAI:</label>
                    <div class="role-selector">
                        <label class="role-option selected" id="role-penumpang" onclick="selectRole('penumpang')">
                            <input type="radio" name="role" value="penumpang" checked>
                            👤 Penumpang
                        </label>
                        <label class="role-option" id="role-driver" onclick="selectRole('driver')">
                            <input type="radio" name="role" value="driver">
                            🛵 Driver
                        </label>
                    </div>

                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" placeholder="Contoh: Anan Maulana Rafi" required>
                    </div>
                    
                    <div class="input-group">
                        <label>NIM / Nomor Induk Mahasiswa</label>
                        <input type="text" name="nim" inputmode="numeric" pattern="[0-9]*" placeholder="Contoh: 13182420123" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Email Pribadi / Kampus</label>
                        <input type="email" name="email" placeholder="Contoh: nama@gmail.com" required>
                    </div>
                    <div class="input-group">
                        <label>Buat Password</label>
                        <input type="password" name="password" id="password" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
            
                    <div class="input-group">
                        <label>Ulangi Password</label>
                        <input type="password" name="konfirmasi_password" id="konfirmasi_password" placeholder="Masukkan ulang password Anda" required>
                    </div>

                    <!-- Field Khusus Driver (Disembunyikan secara default saat Penumpang dipilih) -->
                    <div id="driver-fields" style="display: none; background: #f0fdfa; padding: 15px; border-radius: 12px; border: 1px dashed #14c7c0; margin-bottom: 20px;">
                        <p style="font-size: 0.75rem; color: #0d9488; font-weight: 700; margin-bottom: 15px; text-align: center;">-- FORM TAMBAHAN KHUSUS DRIVER --</p>
                        
                        <div class="input-group">
                            <label>Tipe / Merek Kendaraan</label>
                            <input type="text" name="motor" id="input-motor" placeholder="Contoh: Honda Vario 110">
                        </div>
                        <div class="input-group">
                            <label>Nomor Pelat Kendaraan</label>
                            <input type="text" name="plat" id="input-plat" placeholder="Contoh: H 5522 ABG">
                        </div>
                        <div class="input-group">
                            <label>Upload Foto Diri / KTM</label>
                            <input type="file" name="foto" id="input-foto" accept="image/jpeg, image/png" style="padding: 10px; background: white;">
                        </div>
                    </div>
                    
                    <button type="submit" name="register" class="btn-submit">Daftar Sekarang</button>
                </form>
            </div>

        </div>
    </div>
    <script>
        // Membuka form yang benar jika ada error setelah submit
        <?php if(isset($_POST['register'])): ?>
            switchForm('daftar');
        <?php endif; ?>

        function switchForm(tipe) {
            const btnMasuk = document.getElementById('btn-masuk');
            const btnDaftar = document.getElementById('btn-daftar');
            const formMasuk = document.getElementById('login-form');
            const formDaftar = document.getElementById('register-form');

            if (tipe === 'masuk') {
                btnMasuk.classList.add('active');
                btnDaftar.classList.remove('active');
                formMasuk.classList.add('active');
                formDaftar.classList.remove('active');
            } else {
                btnDaftar.classList.add('active');
                btnMasuk.classList.remove('active');
                formDaftar.classList.add('active');
                formMasuk.classList.remove('active');
            }
        }

        function selectRole(role) {
            document.getElementById('role-penumpang').classList.remove('selected');
            document.getElementById('role-driver').classList.remove('selected');
            
            const driverFields = document.getElementById('driver-fields');
            const motorInput = document.getElementById('input-motor');
            const platInput = document.getElementById('input-plat');
            const fotoInput = document.getElementById('input-foto'); // Tangkap input foto

            if (role === 'penumpang') {
                document.getElementById('role-penumpang').classList.add('selected');
                driverFields.style.display = 'none'; 
                motorInput.removeAttribute('required');
                platInput.removeAttribute('required');
                fotoInput.removeAttribute('required');
            } else {
                document.getElementById('role-driver').classList.add('selected');
                driverFields.style.display = 'block'; 
                motorInput.setAttribute('required', 'true');
                platInput.setAttribute('required', 'true');
                fotoInput.setAttribute('required', 'true');
            }
        }
        // menambahkan pengecekan sebelum form register dikirim
        document.querySelector('#form-register form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const konfirmasi = document.getElementById('konfirmasi_password').value;
            
            if (password !== konfirmasi) {
                e.preventDefault(); // membatalkan pengiriman form
                alert("Password dan Ulangi Password harus sama!");
            }
        });
    </script>
</body>
</html>