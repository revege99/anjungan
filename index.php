<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Anjungan Klinik</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0a2538">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/anjungan/css/anjungan-theme.css" rel="stylesheet">
</head>

<body class="app-shell">

<div class="container shell-container">
    <div class="page-header page-narrow">
        <div>
            <div class="eyebrow">
                <span class="dot"></span>
                Anjungan Mandiri Klinik
            </div>
            <h1 class="page-title">Pilih Layanan</h1>
            <p class="page-subtitle">Sentuh menu yang dibutuhkan.</p>
        </div>
    </div>

    <div class="service-grid">
        <a class="service-tile" href="/anjungan/php/cekNoka/cekNoka.php">
            <div class="service-icon"><i class="bi bi-credit-card-2-front"></i></div>
            <div class="service-kicker">BPJS</div>
            <div class="service-title">Cek Data Peserta</div>
            <div class="service-copy">Cek status dan identitas peserta.</div>
            <div class="service-cta">Buka <i class="bi bi-arrow-right-short"></i></div>
        </a>

        <a class="service-tile" href="/anjungan/php/cekNoka/getDataAmbilAntrian.php">
            <div class="service-icon"><i class="bi bi-ticket-perforated"></i></div>
            <div class="service-kicker">Antrean</div>
            <div class="service-title">Ambil Antrean Poli</div>
            <div class="service-copy">Cari pasien lalu pilih poli dan dokter.</div>
            <div class="service-cta">Buka <i class="bi bi-arrow-right-short"></i></div>
        </a>

        <a class="service-tile" href="/anjungan/php/cekNoka/jadwalDokter.php">
            <div class="service-icon"><i class="bi bi-calendar2-week"></i></div>
            <div class="service-kicker">Jadwal</div>
            <div class="service-title">Lihat Jadwal Dokter</div>
            <div class="service-copy">Lihat jadwal dan kuota hari ini.</div>
            <div class="service-cta">Buka <i class="bi bi-arrow-right-short"></i></div>
        </a>
    </div>

    <div class="footer-note">
        &copy; <?= date('Y') ?> Klinik Santa Lucia Lintong
    </div>
</div>

</body>
</html>
