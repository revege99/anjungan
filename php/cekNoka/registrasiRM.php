<?php
declare(strict_types=1);

require_once '../../function/app.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /anjungan/php/cekNoka/cekNoka.php');
    exit;
}

$nik = trim((string) ($_POST['nik'] ?? ''));
$noka = trim((string) ($_POST['noka'] ?? ''));
$nama = trim((string) ($_POST['nama'] ?? ''));
$tglLahir = anjungan_normalize_date(trim((string) ($_POST['tgl_lahir'] ?? '')));
$jk = anjungan_normalize_gender(trim((string) ($_POST['jk'] ?? '')));
$alamat = trim((string) ($_POST['alamat'] ?? ''));
$noHp = trim((string) ($_POST['no_hp'] ?? ''));

if ($nik === '' || $nama === '') {
    header('Location: /anjungan/php/cekNoka/cekNoka.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Registrasi Rekam Medis</title>
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
                Rekam Medis Baru
            </div>
            <h1 class="page-title">Lengkapi Rekam Medis</h1>
            <p class="page-subtitle">Cek data, isi wilayah, lalu simpan.</p>
        </div>
    </div>

    <section class="glass-panel panel-pad">
        <form id="formRM" class="stack-gap">
            <div class="soft-panel panel-pad">
                <div class="panel-title">Identitas</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NIK</label>
                        <input type="text" name="nik" class="form-control" value="<?= anjungan_escape($nik) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. Kartu BPJS</label>
                        <input type="text" name="noka" class="form-control" value="<?= anjungan_escape($noka) ?>" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" value="<?= anjungan_escape($nama) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" class="form-control" value="<?= anjungan_escape($tglLahir) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jk" class="form-select" required>
                            <option value="">Pilih jenis kelamin</option>
                            <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. HP</label>
                        <input type="tel" name="no_hp" class="form-control" value="<?= anjungan_escape($noHp) ?>" placeholder="08xxxxxxxxxx" inputmode="numeric" required>
                    </div>
                </div>
            </div>

            <div class="soft-panel panel-pad">
                <div class="panel-title">Alamat</div>

                <input type="hidden" name="kd_prop" id="db_kd_prop">
                <input type="hidden" name="kd_kab" id="db_kd_kab">
                <input type="hidden" name="kd_kec" id="db_kd_kec">
                <input type="hidden" name="kd_kel" id="db_kd_kel">

                <input type="hidden" name="nm_prop" id="nm_prop">
                <input type="hidden" name="nm_kab" id="nm_kab">
                <input type="hidden" name="nm_kec" id="nm_kec">
                <input type="hidden" name="nm_kel" id="nm_kel">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Provinsi</label>
                        <select id="kd_prop" class="form-select" required>
                            <option value="">Memuat daftar provinsi...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kabupaten / Kota</label>
                        <select id="kd_kab" class="form-select" required disabled>
                            <option value="">Pilih provinsi</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kecamatan</label>
                        <select id="kd_kec" class="form-select" required disabled>
                            <option value="">Pilih kabupaten</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kelurahan</label>
                        <select id="kd_kel" class="form-select" required disabled>
                            <option value="">Pilih kecamatan</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat" class="form-control" required><?= anjungan_escape($alamat) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="d-grid d-lg-flex gap-2">
                <a href="javascript:history.back()" class="btn btn-anj-secondary flex-fill">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali
                </a>
                <button type="submit" class="btn btn-anj-primary flex-fill">
                    <i class="bi bi-save me-2"></i>
                    Simpan Rekam Medis
                </button>
            </div>
        </form>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/anjungan/js/registrasiRM.js"></script>

</body>
</html>
