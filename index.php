<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Anjungan Klinik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #6f86d6, #8ea6f3);
            height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
        }

        .kiosk-container {
            height: 100vh;
        }

        .title {
            font-size: 48px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 2px;
        }

        .subtitle {
            font-size: 20px;
            color: rgba(255,255,255,0.85);
        }

        .menu-card {
            height: 190px;
            border-radius: 24px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.25);
            transition: all 0.35s ease;
            cursor: pointer;
            color: white;
        }

        .menu-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 25px 50px rgba(0,0,0,0.35);
            background: rgba(255,255,255,0.25);
        }

        .menu-icon {
            font-size: 56px;
            margin-bottom: 10px;
        }

        .menu-text {
            font-size: 22px;
            font-weight: 600;
        }

        .footer-text {
            position: absolute;
            bottom: 12px;
            width: 100%;
            text-align: center;
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            letter-spacing: 1px;
        }

        /* Touch screen friendly */
        .menu-card:active {
            transform: scale(0.97);
        }
    </style>
</head>
<body>

<div class="container-fluid kiosk-container d-flex flex-column justify-content-center align-items-center">

    <!-- Title -->
    <div class="text-center mb-5">
        <div class="title">ANJUNGAN KLINIK</div>
        <div class="subtitle">Silakan pilih layanan yang tersedia</div>
    </div>

    <!-- Menu -->
    <div class="container">
        <div class="row g-4 justify-content-center">

            <div class="col-md-6 col-lg-5">
                <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                     onclick="location.href='/anjungan/php/cekNoka/cekNoka.php'">
                    <div class="menu-icon">🪪</div>
                    <div class="menu-text">Cek No. Kartu BPJS</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-5">
                <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                     onclick="location.href='daftar-baru.html'">
                    <div class="menu-icon">👤</div>
                    <div class="menu-text">Daftar Pasien Baru</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-5">
                <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                     onclick="location.href='/anjungan/php/cekNoka/getDataAmbilAntrian.php'">
                    <div class="menu-icon">🏥</div>
                    <div class="menu-text">Ambil Antrian Poli</div>
                </div>
            </div>

            <div class="col-md-6 col-lg-5">
                <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                     onclick="location.href='/anjungan/php/cekNoka/jadwalDokter.php'">
                    <div class="menu-icon">📅</div>
                    <div class="menu-text">Informasi Jadwal Dokter</div>
                </div>
            </div>

        </div>
    </div>

    <div class="footer-text">
        © 2026 Klinik Santa Lucia Lintong • Sistem Anjungan Mandiri
    </div>

</div>

</body>
</html>