<?php
require_once '../../function/configDB.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nik = $_POST['nik'] ?? '';
    $noka = $_POST['noka'] ?? '';
    $nama = $_POST['nama'] ?? '';
    $tgl_lahir = $_POST['tgl_lahir'] ?? '';
    $jk = $_POST['jk'] ?? '';
    $alamat = $_POST['alamat'] ?? '';

} else {
    echo "Akses tidak valid";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Ambil Antrian Pasien</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

<style>

body{
    font-family:'Poppins',sans-serif;
    background:linear-gradient(135deg,#2c6bed,#4e8df5);
    min-height:100vh;
}

/* ===== CARD ===== */

.main-card{
    background:rgba(255,255,255,0.12);
    backdrop-filter:blur(16px);
    border-radius:24px;
    border:1px solid rgba(255,255,255,0.2);
    box-shadow:0 25px 60px rgba(0,0,0,.35);
    color:white;
}

/* ===== TITLE ===== */

.page-title{
    font-size:34px;
    font-weight:700;
    letter-spacing:1px;
}

/* ===== SECTION ===== */

.section-box{
    background:rgba(255,255,255,0.15);
    border-radius:18px;
    padding:18px;
}

/* ===== FORM ===== */

.form-control,
.form-select{
    height:58px;
    border-radius:14px;
    font-size:16px;
}

/* ===== BUTTON ===== */

.btn-main{
    height:64px;
    font-size:20px;
    font-weight:700;
    border-radius:18px;
    transition:.25s;
}

.btn-main:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 25px rgba(0,0,0,.3);
}

/* ===== ALERT BOX ===== */

.info-box{
    border-radius:14px;
    padding:14px;
    font-weight:500;
}

/* ===== RESPONSIVE ===== */

@media(max-width:768px){

.page-title{
font-size:26px;
}

.btn-main{
font-size:18px;
}

}

</style>
</head>
<body>


<div class="container py-5">

<div class="row justify-content-center">

<div class="col-xl-8 col-lg-10">

<div class="main-card p-4 p-lg-5">

<div class="text-center mb-4">
<div class="page-title">FORM AMBIL ANTRIAN</div>
<div class="opacity-75">Silakan pilih poli dan dokter yang tersedia</div>
</div>

<!-- HIDDEN -->
<input type="hidden" id="nik" value="<?= htmlspecialchars($nik) ?>">
<input type="hidden" id="noka" value="<?= htmlspecialchars($noka) ?>">

<!-- INFO BOX -->
<div id="infoBox" class="alert alert-info info-box text-center mb-4">
Memeriksa data pasien...
</div>

<!-- ================= PASIEN ================= -->

<div id="sectionPasien" class="section-box mb-4 d-none">

<h5 class="mb-3 fw-semibold">Data Pasien</h5>

<div class="row g-3">

<div class="col-md-4">
<label class="form-label">No RM</label>
<input type="text" id="no_rkm_medis" class="form-control" readonly>
</div>

<div class="col-md-8">
<label class="form-label">Nama Pasien</label>
<input type="text" id="nm_pasien" class="form-control" readonly>
</div>

<div class="col-md-4">
<label class="form-label">Tanggal Lahir</label>
<input type="text" id="tgl_lahir" class="form-control" readonly>
</div>

<div class="col-md-4">
<label class="form-label">Jenis Kelamin</label>
<input type="text" id="jk" class="form-control" readonly>
</div>

<div class="col-md-4">
<label class="form-label">No BPJS</label>
<input type="text" id="no_peserta" class="form-control" readonly>
</div>

</div>
</div>

<!-- ================= POLI ================= -->

<div id="sectionPoli" class="section-box mb-4 d-none">

<h5 class="mb-3 fw-semibold">Pilih Poliklinik</h5>

<select id="kd_poli" class="form-select">
<option value="">-- Pilih Poli --</option>
</select>

</div>

<!-- ================= DOKTER ================= -->

<div id="sectionDokter" class="section-box mb-4 d-none">

<h5 class="mb-3 fw-semibold">Pilih Dokter</h5>

<select id="kd_dokter" class="form-select">
<option value="">-- Pilih Dokter --</option>
</select>

</div>

<!-- ================= BUTTON ================= -->

<div class="d-grid">

<button id="btnSimpan" class="btn btn-success btn-main d-none">
Ambil Antrian
</button>

</div>

</div>

</div>

</div>

</div>
<script>
let bpjsData = {
    noKTP: "<?= htmlspecialchars($nik) ?>",
    noKartu: "<?= htmlspecialchars($noka) ?>",
    nama: "<?= htmlspecialchars($nama) ?>",
    tglLahir: "<?= htmlspecialchars($tgl_lahir) ?>",
    jenisKelamin: "<?= htmlspecialchars($jk) ?>",
    alamat: "<?= htmlspecialchars($alamat) ?>",
    noHP: "<?= htmlspecialchars($no_hp ?? '') ?>"
};
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../js/ambilAntrian.js"></script>
</body>
</html>