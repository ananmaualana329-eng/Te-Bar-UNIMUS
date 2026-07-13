<?php
// index.php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['nama'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te-Bar - Tebengan Bareng UNIMUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* --- RESET & DASAR --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        html { scroll-behavior: smooth; scroll-padding-top: 100px; }
        body { background: linear-gradient(135deg, #00FFFF 0%, #00BFFF 100%); color: #333; overflow-x: hidden; line-height: 1.6; }

        @keyframes fadeUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Animasi Mengambang Super Halus & Profesional untuk Gambar */
        @keyframes floatElegant { 
            0%, 100% { transform: translateY(0px) scale(1); box-shadow: 0 20px 40px rgba(0,0,0,0.1); } 
            50% { transform: translateY(-12px) scale(1.01); box-shadow: 0 30px 50px rgba(0,0,0,0.15); } 
        }

        /* --- STICKY NAVBAR --- */
        header { display: flex; justify-content: space-between; align-items: center; padding: 20px 80px; background-color: rgba(255, 255, 255, 0.3); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.4); position: sticky; top: 0; z-index: 1000; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); }
        .logo { font-size: 2.2rem; font-weight: 900; color: #033D36; text-decoration: none; letter-spacing: -1.5px; }
        .nav-links { display: flex; gap: 40px; }
        .nav-links a { text-decoration: none; color: #033D36; font-weight: 700; position: relative; padding-bottom: 5px; transition: 0.3s; }
        .nav-links a::after { content: ''; position: absolute; width: 0; height: 3px; bottom: 0; left: 0; background-color: #033D36; transition: width 0.3s ease; }
        .nav-links a.active::after, .nav-links a:hover::after { width: 100%; }

        .btn { padding: 14px 28px; border-radius: 12px; font-weight: 700; text-decoration: none; transition: all 0.3s ease; cursor: pointer; border: none; display: inline-block; text-align: center; }
        .btn-primary { background-color: #033D36; color: white; box-shadow: 0 8px 15px rgba(3, 61, 54, 0.2); }
        .btn-primary:hover { background-color: #022622; transform: translateY(-3px); box-shadow: 0 12px 20px rgba(3, 61, 54, 0.3); }
        .btn-outline { background-color: rgba(255, 255, 255, 0.4); color: #033D36; border: 2px solid #033D36; }
        .btn-outline:hover { background-color: #033D36; color: white; transform: translateY(-3px); }

        section { padding: 80px 10%; display: flex; flex-direction: column; align-items: center; }
        .section-badge { display: inline-block; background: rgba(3, 61, 54, 0.1); color: #033D36; padding: 8px 18px; border-radius: 20px; font-weight: 800; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 1.5px; }
        .section-title { color: #033D36; font-size: 2.8rem; font-weight: 900; margin-bottom: 50px; text-align: center; }

        /* --- 1. HERO SECTION --- */
        #beranda { display: flex; flex-direction: row; justify-content: space-between; align-items: center; text-align: left; min-height: calc(100vh - 90px); }
        .hero-text { flex: 1; max-width: 650px; animation: fadeUp 1s ease-out; }
        .hero-text h1 { font-size: 4rem; font-weight: 900; color: #033D36; line-height: 1.1; margin-bottom: 25px; }
        
        /* PERBAIKAN TEKS PUTIH AGAR TIDAK BERTABRAKAN DAN TERLIHAT ELEGAN */
        .hero-text h1 span { 
            color: white; 
            display: block; 
            /* Soft shadow profesional (tidak pakai stroke/garis tepi atau blok norak) */
            text-shadow: 0px 8px 25px rgba(3, 61, 54, 0.4), 0px 2px 5px rgba(0, 0, 0, 0.2); 
            letter-spacing: -1px;
            padding-bottom: 10px;
        }
        
        .hero-subtitle { font-size: 1.15rem; color: #022622; font-weight: 500; margin-bottom: 40px; }
        .cta-group { display: flex; gap: 20px; }
        .hero-image { flex: 1; display: flex; justify-content: flex-end; }
        
        /* KEMBALI MENGGUNAKAN GAMBAR DENGAN FLOAT ELEGAN */
        .image-wrapper { 
            width: 100%; 
            max-width: 500px; 
            height: 480px; 
            background: rgba(255, 255, 255, 0.4); 
            border: 4px solid rgba(255, 255, 255, 0.8); 
            border-radius: 30px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden; 
            animation: floatElegant 6s ease-in-out infinite, fadeUp 1.2s ease-out; 
        }
        .image-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        /* --- 2. LAYANAN & TENTANG KAMI --- */
        #tentang { padding-top: 100px; }
        .about-card { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 60px; border-radius: 30px; box-shadow: 0 15px 40px rgba(0,0,0,0.05); width: 100%; max-width: 1000px; margin: 0 auto; }
        .about-card h2 { color: #033D36; font-size: 2.4rem; font-weight: 900; margin-bottom: 25px; }
        .about-card p { font-size: 1.1rem; color: #333; margin-bottom: 20px; font-weight: 500; }

        .benefits-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; width: 100%; max-width: 1200px; margin: 0 auto; }
        .benefit-card { background: white; padding: 40px 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
        .benefit-card:hover { transform: translateY(-12px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .benefit-icon { font-size: 2.5rem; margin-bottom: 20px; display: inline-block; background: rgba(3, 61, 54, 0.05); width: 70px; height: 70px; line-height: 70px; border-radius: 50%; text-align: center; }
        .benefit-card h3 { font-size: 1.2rem; color: #033D36; font-weight: 800; margin-bottom: 12px; }
        .benefit-card p { font-size: 0.95rem; color: #555; }

        /* --- 3. ULASAN (DIKEMBALIKAN SESUAI PERMINTAAN) --- */
        #ulasan { padding-bottom: 120px; }
        .feedback-container { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 50px; border-radius: 30px; width: 100%; max-width: 1100px; display: flex; gap: 50px; box-shadow: 0 15px 40px rgba(0,0,0,0.08); }
        .feedback-text { flex: 1; }
        .feedback-text h2 { color: #033D36; font-size: 2.2rem; font-weight: 900; margin-bottom: 20px; }
        .feedback-text p { font-size: 1.05rem; color: #444; margin-bottom: 30px; }
        .stars-display { display: flex; align-items: center; gap: 10px; font-size: 1.8rem; color: #fbbf24; }
        .stars-display span { font-size: 1rem; color: #666; font-weight: 700; margin-left: 15px; }
        
        .feedback-form { flex: 1; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .feedback-form h3 { font-size: 1.3rem; color: #033D36; margin-bottom: 25px; font-weight: 800; }
        .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .input-group { flex: 1; }
        .input-group label { display: block; font-size: 0.8rem; font-weight: 800; color: #333; margin-bottom: 10px; text-transform: uppercase; }
        .input-group input, .input-group textarea { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; font-size: 0.95rem; outline: none; font-weight: 600; }
        .input-group input:focus, .input-group textarea:focus { border-color: #14c7c0; }
        .input-group textarea { resize: vertical; min-height: 120px; }
        .star-rating { display: flex; gap: 8px; color: #fbbf24; font-size: 1.8rem; margin-bottom: 25px; cursor: pointer; }
        
        .btn-submit-dark { width: 100%; background-color: #033D36; color: white; padding: 16px; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 800; cursor: pointer; transition: 0.3s; }
        .btn-submit-dark:hover { background-color: #022622; }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) { .benefits-grid { grid-template-columns: repeat(2, 1fr); } header, section { padding: 40px 5%; } .hero-text h1 { font-size: 3.5rem; } }
        @media (max-width: 768px) { header { flex-direction: column; gap: 20px; padding: 20px; } .nav-links { flex-wrap: wrap; justify-content: center; gap: 20px; } #beranda { flex-direction: column; text-align: center; } .hero-text { margin-bottom: 50px; } .cta-group { justify-content: center; flex-direction: column; } .image-wrapper { height: 350px; } .benefits-grid { grid-template-columns: 1fr; } .section-title { font-size: 2.2rem; } .feedback-container { flex-direction: column; gap: 30px; } .form-row { flex-direction: column; gap: 20px; } }
    </style>
</head>
<body>

    <header>
        <a href="#beranda" class="logo">Te-Bar.</a>
        <nav class="nav-links">
            <a href="#beranda" class="nav-link active">Beranda</a>
            <a href="#layanan" class="nav-link">Layanan</a>
            <a href="#tentang" class="nav-link">Tentang Kami</a>
            <a href="#ulasan" class="nav-link">Ulasan</a>
        </nav>
        
        <?php if ($is_logged_in): ?>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-weight: 700; color: #033D36;">Halo, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>!</span>
                <a href="pages/map.php" class="btn btn-primary">Buka Aplikasi</a>
            </div>
        <?php else: ?>
            <a href="pages/login.php" class="btn btn-primary">Masuk / Daftar</a>
        <?php endif; ?>
    </header>

    <section id="beranda">
        <div class="hero-text">
            <div class="section-badge">🛵 Khusus Mahasiswa UNIMUS</div>
            <h1>Aplikasi Transportasi<br><span>Tebengan Bareng.</span></h1>
            <p class="hero-subtitle">Solusi mobilitas cerdas mahasiswa UNIMUS. Kurangi macet, hemat ongkos harian kos-kampus, dan tambah teman baru satu almamater.</p>
            <div class="cta-group">
                <a href="<?= $is_logged_in ? 'pages/map.php' : 'pages/login.php' ?>" class="btn btn-primary">Cari Tebengan</a>
                <a href="<?= $is_logged_in ? 'pages/driver_dashboard.php' : 'pages/login.php' ?>" class="btn btn-outline">Beri Tebengan</a>
            </div>
        </div>
        
        <div class="hero-image">
            <div class="image-wrapper">
                <img src="assets/images/tebar.jpg" alt="Te-Bar Ilustrasi" onerror="this.innerHTML='<p style=\'color: #033D36; font-weight: 800; font-size: 1.2rem;\'>Gambar Ilustrasi</p>'">
            </div>
        </div>
    </section>

    <section id="layanan">
        <span class="section-badge">Layanan Kami</span>
        <h2 class="section-title">Solusi Mobilitas Kampus</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">🛡️</div>
                <h3>Aman & Terpercaya</h3>
                <p>Sistem verifikasi internal menggunakan NIM & email institusi UNIMUS.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">💳</div>
                <h3>Tarif Super Hemat</h3>
                <p>Cukup membayar bensin sukarela dengan perhitungan rute otomatis yang transparan.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🤝</div>
                <h3>Tambah Relasi</h3>
                <p>Berkendara searah bersama teman dari fakultas berbeda, memperluas jaringan perkawanan Anda.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">📍</div>
                <h3>Live Tracking</h3>
                <p>Lacak posisi pengemudi secara real-time langsung melalui peta interaktif terintegrasi.</p>
            </div>
        </div>
    </section>

    <section id="tentang">
        <div class="about-card">
            <span class="section-badge">Tentang Te-Bar</span>
            <h2>Lebih Dari Sekadar Aplikasi</h2>
            <p>Te-Bar (Tebengan Bareng) dirancang oleh mahasiswa untuk mahasiswa. Kami percaya bahwa setiap kursi kosong di kendaraan yang menuju kampus adalah peluang untuk berbagi, menghemat biaya, dan membangun komunitas UNIMUS yang lebih solid.</p>
            <p>Melalui ekosistem transportasi berbasis gotong royong ini, kami juga berupaya mengurangi kepadatan area parkir dan jejak karbon di lingkungan kampus tercinta.</p>
        </div>
    </section>

    <section id="ulasan">
        <div class="feedback-container">
            <div class="feedback-text">
                <span class="section-badge">Ulasan</span>
                <h2>Suara Anda Sangat Berarti!</h2>
                <p>Punya kritik, saran, atau masukan untuk pengemudi Te-Bar? Laporkan kepuasan Anda langsung di sini. Seluruh masukan Anda membantu kami menyaring pengemudi terbaik demi keamanan bersama.</p>
                <div class="stars-display">
                    ⭐⭐⭐⭐⭐ <span>Rata-rata 4.9/5 Kepuasan Pengguna</span>
                </div>
            </div>
            
            <div class="feedback-form">
                <h3>Kirim Ulasan Layanan</h3>
                <form onsubmit="event.preventDefault(); alert('Ulasan berhasil dikirim. Terima kasih atas masukan Anda!');">
                    <div class="form-row">
                        <div class="input-group">
                            <label>Nama Anda (Opsional)</label>
                            <input type="text" placeholder="Masukkan Nama Anda">
                        </div>
                        <div class="input-group">
                            <label>Nama Driver *</label>
                            <input type="text" placeholder="Contoh: Yeza / Aldi" required>
                        </div>
                    </div>
                    
                    <div class="input-group" style="margin-bottom: 20px;">
                        <label>Pilihan Bintang (Skala 1 - 5) *</label>
                        <div class="star-rating">
                            <span>⭐</span><span>⭐</span><span>⭐</span><span>⭐</span><span>⭐</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Ulasan / Masukan Anda *</label>
                        <textarea placeholder="Tulis pengalaman perjalanan Anda bersama pengemudi" required></textarea>
                    </div>

                    <button type="submit" class="btn-submit-dark">Kirim Ulasan Sekarang</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let currentId = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= (sectionTop - 200)) {
                    currentId = section.getAttribute('id');
                }
            });
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (currentId && link.getAttribute('href') === `#${currentId}`) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>