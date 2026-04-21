<?php
declare(strict_types=1);

require_once '../../function/app.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /anjungan/php/cekNoka/getDataAmbilAntrian.php');
    exit;
}

$nik = trim((string) ($_POST['nik'] ?? ''));
$noka = trim((string) ($_POST['noka'] ?? ''));
$nama = trim((string) ($_POST['nama'] ?? ''));
$tglLahir = anjungan_normalize_date(trim((string) ($_POST['tgl_lahir'] ?? '')));
$jk = anjungan_normalize_gender(trim((string) ($_POST['jk'] ?? '')));
$alamat = trim((string) ($_POST['alamat'] ?? ''));
$noHp = trim((string) ($_POST['no_hp'] ?? ''));

$bpjsPayload = [
    'noKTP' => $nik,
    'noKartu' => $noka,
    'nama' => $nama,
    'tglLahir' => $tglLahir,
    'sex' => $jk,
    'jenisKelamin' => $jk,
    'alamat' => $alamat,
    'noHP' => $noHp,
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ambil Antrean Pasien</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/anjungan/css/anjungan-theme.css" rel="stylesheet">
</head>

<body class="app-shell">

<div class="container shell-container page-narrow">
    <div class="page-header">
        <div>
            <div class="eyebrow">
                <span class="dot"></span>
                Lanjutkan ke Antrean
            </div>
            <h1 class="page-title">Pilih Poli</h1>
            <p class="page-subtitle">Pastikan data pasien, pilih poli, lalu pilih dokter.</p>
        </div>
    </div>

    <section class="glass-panel panel-pad stack-gap">
        <input type="hidden" id="nik" value="<?= anjungan_escape($nik) ?>">
        <input type="hidden" id="noka" value="<?= anjungan_escape($noka) ?>">

        <div id="infoBox" class="state-banner info">
            Memeriksa data pasien...
        </div>

        <div id="sectionPasien" class="soft-panel panel-pad d-none">
            <div class="panel-title">Data Pasien</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="no_rkm_medis" class="form-label">No. Rekam Medis</label>
                    <input type="text" id="no_rkm_medis" class="form-control" readonly>
                </div>
                <div class="col-md-8">
                    <label for="nm_pasien" class="form-label">Nama Pasien</label>
                    <input type="text" id="nm_pasien" class="form-control" readonly>
                </div>
                <div class="col-md-4">
                    <label for="tgl_lahir" class="form-label">Tanggal Lahir</label>
                    <input type="text" id="tgl_lahir" class="form-control" readonly>
                </div>
                <div class="col-md-4">
                    <label for="jk" class="form-label">Jenis Kelamin</label>
                    <input type="text" id="jk" class="form-control" readonly>
                </div>
                <div class="col-md-4">
                    <label for="no_peserta" class="form-label">No. BPJS</label>
                    <input type="text" id="no_peserta" class="form-control" readonly>
                </div>
            </div>
        </div>

        <div class="card-grid">
            <div id="sectionPoli" class="soft-panel panel-pad d-none">
                <div class="panel-title">Poliklinik</div>
                <label for="kd_poli" class="form-label">Pilih Poli</label>
                <select id="kd_poli" class="form-select">
                    <option value="">Pilih poli</option>
                </select>
            </div>

            <div id="sectionDokter" class="soft-panel panel-pad d-none">
                <div class="panel-title">Dokter</div>
                <label for="kd_dokter" class="form-label">Pilih Dokter</label>
                <select id="kd_dokter" class="form-select">
                    <option value="">Pilih dokter</option>
                </select>
            </div>
        </div>

        <div class="d-grid">
            <button id="btnSimpan" type="button" class="btn btn-anj-primary d-none">
                <i class="bi bi-ticket-perforated me-2"></i>
                Ambil Antrean
            </button>
        </div>
    </section>
</div>

<script>
const bpjsData = <?= json_encode($bpjsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/anjungan/js/ambilAntrian.js"></script>

</body>
</html>
