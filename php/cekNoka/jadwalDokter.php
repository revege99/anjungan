<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Jadwal Dokter</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/anjungan/css/anjungan-theme.css" rel="stylesheet">
</head>

<body class="app-shell">

<div class="container shell-container">
    <div class="page-header">
        <div>
            <div class="eyebrow">
                <span class="dot"></span>
                Jadwal Dokter
            </div>
            <h1 class="page-title">Jadwal Dokter</h1>
            <p class="page-subtitle">Pilih hari untuk melihat jadwal.</p>
        </div>
    </div>

    <section class="glass-panel panel-pad mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="panel-title">Hari Aktif: <span id="hariText">Memuat...</span></div>
            </div>
            <div id="dayFilter" class="day-filter"></div>
        </div>
    </section>

    <section class="glass-panel panel-pad table-shell">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Jam Praktik</th>
                        <th>Poliklinik</th>
                        <th>Dokter</th>
                        <th>Kuota</th>
                    </tr>
                </thead>
                <tbody id="jadwalBody">
                    <tr>
                        <td colspan="4" class="empty-state">Memuat jadwal dokter...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <div class="d-grid d-md-flex justify-content-md-end mt-4">
        <a href="/anjungan/" class="btn btn-anj-secondary px-4">
            <i class="bi bi-arrow-left me-2"></i>
            Kembali
        </a>
    </div>
</div>

<script src="/anjungan/js/jadwalDokter.js"></script>

</body>
</html>
