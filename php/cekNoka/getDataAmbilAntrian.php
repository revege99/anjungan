<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ambil Antrean Poli</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/anjungan/css/anjungan-theme.css" rel="stylesheet">
</head>

<body class="app-shell">

<div class="container shell-container page-narrow-sm">
    <div class="page-header">
        <div>
            <div class="eyebrow">
                <span class="dot"></span>
                Ambil Antrean Poli
            </div>
            <h1 class="page-title">Cari Pasien</h1>
            <p class="page-subtitle">Isi NIK atau nomor kartu BPJS.</p>
        </div>
    </div>

    <section class="glass-panel panel-pad">
        <form id="lookupForm" class="row g-3">
            <div class="col-12">
                <label for="nomor" class="form-label">NIK / Nomor Kartu BPJS</label>
                <input
                    type="text"
                    id="nomor"
                    class="form-control"
                    inputmode="numeric"
                    placeholder="Masukkan nomor">
            </div>

            <div class="col-12 d-grid gap-2">
                <button type="submit" class="btn btn-anj-primary">
                    <i class="bi bi-search me-2"></i>
                    Cari
                </button>
                <a href="/anjungan/" class="btn btn-anj-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali
                </a>
            </div>
        </form>
    </section>

    <section id="resultBox" class="mt-4" hidden>
        <div class="result-surface p-4" id="resultContent">
            <div class="text-center text-muted">Hasil pencarian pasien akan muncul di sini.</div>
        </div>
    </section>
</div>

<script src="/anjungan/js/getDataAmbilAntrian.js"></script>

</body>
</html>
