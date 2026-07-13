<?php
// pages/map.php
session_start();
require_once '../config/database.php';

// Cek login & role (Cegah driver masuk)
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
$stmt_check = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
$stmt_check->execute([$user_id]);
if ($stmt_check->rowCount() > 0) { header("Location: driver_dashboard.php"); exit; }

$user_name = $_SESSION['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Penumpang Te-Bar</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Montserrat', sans-serif; }
        body { display: flex; height: 100vh; overflow: hidden; background: #f8fafc; color: #333; }

        /* --- LAYOUT SPLIT SCREEN --- */
        .sidebar { width: 420px; background: white; height: 100vh; overflow-y: auto; padding: 25px; box-shadow: 2px 0 15px rgba(0,0,0,0.05); z-index: 10; display: flex; flex-direction: column; }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .map-container { flex: 1; height: 100vh; position: relative; z-index: 1; }
        #map { width: 100%; height: 100%; }

        /* --- SIDEBAR COMPONENTS --- */
        .nav-top { display: flex; justify-content: flex-end; margin-bottom: 25px; }
        /* Menu Riwayat dihilangkan sesuai permintaan */
        .nav-top a { text-decoration: none; color: #64748b; font-weight: 700; font-size: 0.85rem; padding: 8px 15px; background: #f1f5f9; border-radius: 20px; transition: 0.3s; }
        .nav-top a:hover { background: #14c7c0; color: white; }

        .input-section { margin-bottom: 25px; }
        .input-label { font-size: 0.75rem; font-weight: 800; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Input diperbarui menjadi Dropdown yang terlihat seperti kotak teks */
        .custom-select { width: 100%; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-weight: 600; color: #333; margin-bottom: 15px; background: white; transition: 0.3s; appearance: none; outline: none; cursor: pointer; }
        .custom-select:focus { border-color: #14c7c0; background: #f0fdfa; }

        /* --- PROMO BOX --- */
        .promo-box { border: 2px solid #f1f5f9; border-radius: 16px; padding: 20px; margin-bottom: 25px; }
        .promo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .promo-header h4 { font-size: 0.85rem; color: #033D36; font-weight: 800; }
        .badge-menarik { background: #fef08a; color: #854d0e; padding: 4px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 800; }
        .promo-list { max-height: 150px; overflow-y: auto; padding-right: 5px; margin-bottom: 15px; }
        .promo-item { border: 1px solid #e2e8f0; padding: 12px; border-radius: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: flex-start; }
        .promo-item h5 { font-size: 0.85rem; color: #033D36; margin-bottom: 3px; font-weight: 800;}
        .promo-item p { font-size: 0.7rem; color: #64748b; font-weight: 500; }
        .promo-code { color: #14c7c0; font-weight: 800; font-size: 0.75rem; background: #f0fdfa; padding: 3px 8px; border-radius: 6px; }

        /* --- DRIVER DI SEKITAR --- */
        .driver-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .driver-header h4 { font-size: 0.8rem; color: #033D36; font-weight: 800; }
        .driver-count { color: #64748b; font-size: 0.75rem; font-weight: 700; }
        .driver-card { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; }
        .d-info { display: flex; align-items: center; gap: 12px; }
        .d-avatar { width: 40px; height: 40px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #033D36; border: 2px solid #14c7c0;}
        .d-text h5 { color: #033D36; font-weight: 800; font-size: 0.9rem; }
        .d-text p { color: #64748b; font-size: 0.75rem; font-weight: 600; }
        .d-rating { text-align: right; }
        .d-rating span { display: block; font-size: 0.85rem; font-weight: 800; color: #fbbf24; margin-bottom: 2px; }
        .d-rating small { color: #64748b; font-weight: 700; font-size: 0.7rem; }

        /* --- ORDER ACTION --- */
        .checkout-box { margin-top: auto; padding-top: 20px; border-top: 2px dashed #e2e8f0; display: none; }
        .price-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .price-row h3 { color: #033D36; font-size: 1.5rem; font-weight: 900; }
        .btn-pesan { width: 100%; padding: 16px; background: #033D36; color: white; border: none; border-radius: 12px; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: 0.3s; }
        .btn-pesan:hover { background: #14c7c0; }

        /* --- MAP CONTROLS --- */
        .map-controls { position: absolute; top: 20px; right: 20px; z-index: 1000; display: flex; flex-direction: column; gap: 10px; align-items: flex-end; }
        .control-btn { width: 45px; height: 45px; background: white; border: none; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.1); color: #033D36; transition: 0.3s; }
        .control-btn:hover { background: #f0fdfa; color: #14c7c0; }
        .style-switcher { background: white; padding: 15px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 150px; text-align: center; }
        .style-switcher h5 { font-size: 0.7rem; color: #64748b; margin-bottom: 10px; text-transform: uppercase; font-weight: 800; }
        .style-btn { display: block; width: 100%; padding: 8px; margin-bottom: 5px; border-radius: 8px; border: none; background: transparent; font-weight: 700; color: #64748b; cursor: pointer; font-size: 0.85rem; }
        .style-btn.active { background: #14c7c0; color: white; }
        .leaflet-routing-container { display: none !important; }

        /* RESPONSIVE MOBILE */
        @media (max-width: 768px) {
            body { flex-direction: column-reverse; }
            .sidebar { width: 100%; height: 55vh; border-top-left-radius: 24px; border-top-right-radius: 24px; box-shadow: 0 -5px 20px rgba(0,0,0,0.1); padding: 20px; }
            .map-container { height: 45vh; }
            .promo-box { display: none; } 
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="nav-top">
            <a href="profil.php">👤 Profil</a>
            </div>

        <div class="input-group">
                <label>📍 Titik Jemput</label>
                <select id="pickup" class="custom-select" onchange="handleDropdownChange('pickup')">
                    <option value="">🔵 Pilih Lokasi atau Klik Peta...</option>
                    <option value="gps" style="font-weight: 800; color: #14c7c0;">🎯 Gunakan Lokasi Saya Saat Ini</option>
                    
                    <option value="-7.02178,110.46191">Gedung NRC UNIMUS</option>
                    <option value="-7.02220,110.46250">Gedung GKB 1</option>
                    <option value="-7.02260,110.46280">Gedung Rektorat UNIMUS</option>
                    <option value="-7.02100,110.46150">Masjid At-Taqwa UNIMUS</option>
                    <option value="-7.05150,110.43690">Area Kos Tembalang (Undip)</option>
                    
                    <option value="custom" id="custom-pickup" style="display:none; color: #033D36; font-weight: bold;">Titik dari Peta (Kustom)</option>
                </select>
            </div>

            <div class="input-group">
                <label>🚩 Tujuan</label>
                <select id="dropoff" class="custom-select" onchange="handleDropdownChange('dropoff')">
                    <option value="">🔴 Pilih Lokasi atau Klik Peta...</option>
                    
                    <option value="-7.02178,110.46191">Gedung NRC UNIMUS</option>
                    <option value="-7.02220,110.46250">Gedung GKB 1</option>
                    <option value="-7.02260,110.46280">Gedung Rektorat UNIMUS</option>
                    <option value="-7.02100,110.46150">Masjid At-Taqwa UNIMUS</option>
                    <option value="-7.05150,110.43690">Area Kos Tembalang (Undip)</option>
                    
                    <option value="custom" id="custom-dropoff" style="display:none; color: #033D36; font-weight: bold;">Titik dari Peta (Kustom)</option>
                </select>
            </div>

        <div class="promo-box">
            <div class="promo-header">
                <h4>✨ PROMO & VOUCHER MAHASISWA</h4>
                <span class="badge-menarik">MENARIK</span>
            </div>
            <div class="promo-list">
                <div class="promo-item">
                    <div>
                        <h5>Diskon Teknik Rp3.000</h5>
                        <p>Potongan instan khusus rute menuju lab FT</p>
                    </div>
                    <span class="promo-code">TEBARTEKNO</span>
                </div>
            </div>
        </div>

        <div class="driver-section">
            <div class="driver-header">
                <h4>🚲 DRIVER TE-BAR DI SEKITAR</h4>
                <span class="driver-count">Akan menjemputmu</span>
            </div>
            
            <div class="driver-card">
                <div class="d-info">
                    <div class="d-avatar">R</div>
                    <div class="d-text">
                        <h5>Rian Hidayat</h5>
                        <p>Honda Vario 150 Hitam</p>
                    </div>
                </div>
                <div class="d-rating">
                    <span>⭐ 4.85</span>
                    <small>H 4123 BG</small>
                </div>
            </div>
        </div>

        <div class="checkout-box" id="checkout-box">
            <div class="price-row">
                <div>
                    <p style="font-size: 0.8rem; color: #64748b; font-weight: 700;">Tarif Estimasi</p>
                    <p style="font-size: 0.85rem; color: #14c7c0; font-weight: 800;" id="teks-jarak">Jarak: - KM</p>
                </div>
                <h3 id="teks-harga">Rp 0</h3>
            </div>
            <button class="btn-pesan" id="btn-pesan" onclick="prosesPesanan()">Pesan Sekarang</button>
        </div>
    </div>

    <div class="map-container">
        <div class="map-controls">
            <button class="control-btn" title="Lokasi Saya" onclick="lokasiGPS()">🎯</button>
            <button class="control-btn" title="Reset Peta" onclick="resetPeta()">🔄</button>
            <div class="style-switcher">
                <h5>Gaya Peta</h5>
                <button class="style-btn active" onclick="gantiPeta('voyager', this)">Voyager</button>
                <button class="style-btn" onclick="gantiPeta('classic', this)">Classic</button>
                <button class="style-btn" onclick="gantiPeta('satelit', this)">Satelit</button>
            </div>
        </div>
        <div id="map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script>
        // --- INISIALISASI PETA ---
       // Peta difokuskan ke koordinat pusat UNIMUS Kedungmundu dengan zoom lebih dekat (16)
        const map = L.map('map', { zoomControl: false }).setView([-7.02178, 110.46191], 16);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        const tiles = {
            voyager: L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'),
            classic: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'),
            satelit: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}')
        };
        tiles.voyager.addTo(map);

        function gantiPeta(jenis, element) {
            map.eachLayer((layer) => { if (layer instanceof L.TileLayer) map.removeLayer(layer); });
            tiles[jenis].addTo(map);
            document.querySelectorAll('.style-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
        }

        const iconJemput = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png', iconSize: [25, 41], iconAnchor: [12, 41] });
        const iconTujuan = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', iconSize: [25, 41], iconAnchor: [12, 41] });

        let markerJemput = null, markerTujuan = null, routingControl = null;
        let finalPickupLat, finalPickupLng, finalDropLat, finalDropLng, finalJarak, finalHarga;

        // --- FUNGSI JIKA MEMILIH DARI DROPDOWN ---
        function handleDropdownChange(type) {
            const selectEl = document.getElementById(type);
            const val = selectEl.value;

            if (val === 'gps' && type === 'pickup') {
                lokasiGPS();
                return;
            }

            if (val && val !== 'custom') {
                const [lat, lng] = val.split(',').map(Number);
                const latlng = new L.LatLng(lat, lng);

                if (type === 'pickup') {
                    if (markerJemput) markerJemput.setLatLng(latlng);
                    else markerJemput = L.marker(latlng, {icon: iconJemput}).addTo(map).bindPopup("Lokasi Jemput").openPopup();
                    finalPickupLat = lat; finalPickupLng = lng;
                    map.setView(latlng, 15);
                } else {
                    if (markerTujuan) markerTujuan.setLatLng(latlng);
                    else markerTujuan = L.marker(latlng, {icon: iconTujuan}).addTo(map).bindPopup("Titik Tujuan").openPopup();
                    finalDropLat = lat; finalDropLng = lng;
                }
                cekDanHitungRute();
            }
        }

        // --- FUNGSI JIKA KLIK MANUAL DARI PETA ---
        map.on('click', function(e) {
            if (!markerJemput) {
                markerJemput = L.marker(e.latlng, {icon: iconJemput}).addTo(map).bindPopup("Lokasi Jemput").openPopup();
                finalPickupLat = e.latlng.lat; finalPickupLng = e.latlng.lng;
                
                // Ubah tampilan dropdown jadi Custom
                const optJemput = document.getElementById('custom-pickup');
                optJemput.style.display = 'block';
                optJemput.value = `${finalPickupLat},${finalPickupLng}`;
                document.getElementById('pickup').value = optJemput.value;
            } 
            else if (!markerTujuan) {
                markerTujuan = L.marker(e.latlng, {icon: iconTujuan}).addTo(map).bindPopup("Titik Tujuan").openPopup();
                finalDropLat = e.latlng.lat; finalDropLng = e.latlng.lng;
                
                // Ubah tampilan dropdown jadi Custom
                const optTujuan = document.getElementById('custom-dropoff');
                optTujuan.style.display = 'block';
                optTujuan.value = `${finalDropLat},${finalDropLng}`;
                document.getElementById('dropoff').value = optTujuan.value;
                
                cekDanHitungRute();
            }
        });

        // --- FUNGSI RESET PETA ---
        function resetPeta() {
            if(markerJemput) map.removeLayer(markerJemput);
            if(markerTujuan) map.removeLayer(markerTujuan);
            if(routingControl) map.removeControl(routingControl);
            markerJemput = null; markerTujuan = null;
            finalPickupLat = null; finalPickupLng = null; finalDropLat = null; finalDropLng = null;
            
            document.getElementById('pickup').value = "";
            document.getElementById('dropoff').value = "";
            document.getElementById('custom-pickup').style.display = "none";
            document.getElementById('custom-dropoff').style.display = "none";
            document.getElementById('checkout-box').style.display = 'none';
        }

        function lokasiGPS() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const latlng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    map.setView(latlng, 16);
                    
                    if (markerJemput) markerJemput.setLatLng(latlng);
                    else markerJemput = L.marker(latlng, {icon: iconJemput}).addTo(map);
                    
                    finalPickupLat = latlng.lat; finalPickupLng = latlng.lng;
                    
                    const optJemput = document.getElementById('custom-pickup');
                    optJemput.style.display = 'block';
                    optJemput.text = '📍 Lokasi GPS Anda';
                    optJemput.value = `${finalPickupLat},${finalPickupLng}`;
                    document.getElementById('pickup').value = optJemput.value;
                    
                    cekDanHitungRute();
                });
            } else {
                alert("Browser tidak mendukung GPS.");
                document.getElementById('pickup').value = "";
            }
        }

        // --- KALKULASI RUTE DAN HARGA ---
        function cekDanHitungRute() {
            if (finalPickupLat && finalDropLat) {
                if(routingControl) map.removeControl(routingControl);
                
                routingControl = L.Routing.control({
                    waypoints: [ L.latLng(finalPickupLat, finalPickupLng), L.latLng(finalDropLat, finalDropLng) ],
                    routeWhileDragging: false, createMarker: function() { return null; },
                    lineOptions: { styles: [{color: '#14c7c0', opacity: 0.8, weight: 6}] }
                }).addTo(map);

                routingControl.on('routesfound', function(e) {
                    const summary = e.routes[0].summary;
                    finalJarak = (summary.totalDistance / 1000).toFixed(1);
                    
                    finalHarga = 3000 + (Math.ceil(finalJarak) * 1500);
                    if (finalHarga < 5000) finalHarga = 5000;

                    document.getElementById('checkout-box').style.display = 'block';
                    document.getElementById('teks-jarak').innerText = `Jarak: ${finalJarak} KM`;
                    document.getElementById('teks-harga').innerText = `Rp ${finalHarga.toLocaleString('id-ID')}`;
                    
                    const sidebar = document.querySelector('.sidebar');
                    sidebar.scrollTop = sidebar.scrollHeight;
                });
            }
        }

        // --- PROSES PEMESANAN ---
        function prosesPesanan() {
            if (!finalPickupLat || !finalDropLat) return;
            
            const btn = document.getElementById('btn-pesan');
            btn.innerText = "Mencari Driver... ⌛"; btn.disabled = true;

            const payload = { 
                pickup_lat: finalPickupLat, pickup_lng: finalPickupLng, 
                dropoff_lat: finalDropLat, dropoff_lng: finalDropLng, 
                jarak_km: finalJarak, harga: finalHarga 
            };

            fetch('../api/order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') { window.location.href = 'tracking.php?id=' + data.order_id; } 
                else { alert('Gagal: ' + data.message); btn.innerText = "Pesan Sekarang"; btn.disabled = false; }
            });
        }
    </script>
</body>
</html>