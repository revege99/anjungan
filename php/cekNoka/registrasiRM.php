<?php
$nik       = $_POST['nik'] ?? '';
$noka      = $_POST['noka'] ?? '';
$nama      = $_POST['nama'] ?? '';
$tgl_lahir = $_POST['tgl_lahir'] ?? '';
$jk        = $_POST['jk'] ?? '';
$alamat    = $_POST['alamat'] ?? '';
// $no_hp     = $_POST['no_hp'] ?? '';

// var_dump($_POST);
// var_dump($noka);
// exit;

if (!empty($tgl_lahir)) {
    $date = DateTime::createFromFormat('d-m-Y', $tgl_lahir);
    if ($date) {
        $tgl_lahir = $date->format('Y-m-d');
    }
}




if ($nik == '' || $nama == '') {
    die('Akses tidak valid');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Rekam Medis</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #3a7bd5, #00d2ff);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .glass-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(15px);
            border-radius: 28px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            width: 100%;
            max-width: 800px;
        }

        .glass-card h4 {
            font-weight: 700;
            letter-spacing: .5px;
        }

        .form-control, .form-select, textarea {
            border-radius: 16px;
            height: 55px;
            border: none;
            font-size: 1rem;
        }

        textarea.form-control {
            height: auto;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }

        .btn-modern {
            height: 60px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 20px;
            transition: .3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,.3);
        }

        label {
            font-weight: 500;
            margin-bottom: 6px;
        }

        .header-section {
            background: rgba(255,255,255,0.1);
            border-radius: 20px 20px 0 0;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="glass-card shadow">

    <div class="header-section">
        <h4>🩺 Registrasi Rekam Medis Pasien</h4>
        <small>Lengkapi data berikut untuk membuat Rekam Medis</small>
    </div>

    <div class="p-4">

        <form id="formRM">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>NIK</label>
                    <input type="text" name="nik" class="form-control"
                           value="<?= htmlspecialchars($nik) ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label>No Kartu BPJS</label>
                    <input type="text" name="noka" class="form-control"
                           value="<?= htmlspecialchars($noka) ?>" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control"
                       value="<?= htmlspecialchars($nama) ?>" required>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" class="form-control"
                           value="<?= htmlspecialchars($tgl_lahir) ?>" required>
                </div>

                <div class="col-md-6">
                    <label>Jenis Kelamin</label>
                    <select name="jk" class="form-select" required>
                        <option value="">-- Pilih --</option>
                        <option value="L" <?= $jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>
             <div class="">
                <label>No HP</label>
                <input type="tel" name="no_hp" class="form-control"
                       placeholder="08xxxxxxxxxx" pattern="[0-9]{10,15}" required>
            </div>

            <div class="row g-3 mb-4">

    <!-- PROVINSI -->
                <div class="col-md-6">

    <!-- Hidden Nama -->
                    <input type="hidden" name="kd_prop" id="db_kd_prop">
                    <input type="hidden" name="kd_kab"  id="db_kd_kab">
                    <input type="hidden" name="kd_kec"  id="db_kd_kec">
                    <input type="hidden" name="kd_kel"  id="db_kd_kel">

                    <input type="hidden" name="nm_prop" id="nm_prop">
                    <input type="hidden" name="nm_kab"  id="nm_kab">
                    <input type="hidden" name="nm_kec"  id="nm_kec">
                    <input type="hidden" name="nm_kel"  id="nm_kel">

                    <label>Provinsi</label>
                    <select id="kd_prop" class="form-select" required></select>
                        <option value="">Loading Provinsi...</option>
                    </select>
                </div>

                <!-- KABUPATEN -->
                <div class="col-md-6">
                    <label>Kabupaten / Kota</label>
                    <select id="kd_kab"  class="form-select" required disabled>
                        <option value="">-- Pilih Provinsi Terlebih Dahulu --</option>
                    </select>
                </div>

                <!-- KECAMATAN -->
                <div class="col-md-6">
                    <label>Kecamatan</label>
                    <select id="kd_kec"  class="form-select" required disabled>
                        <option value="">-- Pilih Kabupaten Terlebih Dahulu --</option>
                    </select>
                </div>

                <!-- KELURAHAN -->
                <div class="col-md-6">
                    <label>Kelurahan</label>
                    <select id="kd_kel"  class="form-select" required disabled>
                        <option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>
                    </select>
                </div>

                <!-- ALAMAT -->
                <div class="col-12">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="3" required></textarea>
                </div>

            </div>

           

            <div class="d-flex justify-content-between gap-3">
                <a href="javascript:history.back()" class="btn btn-light btn-modern w-50">
                    ← Kembali
                </a>

                <button type="submit" class="btn btn-success btn-modern w-50">
                    💾 Simpan Rekam Medis
                </button>
            </div>

        </form>

    </div>
</div>

</body>
</html>

<script src="../../js/registrasiRM.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>