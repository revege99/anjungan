<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cek Peserta BPJS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

body{
    background:linear-gradient(135deg,#4e73df,#6f86d6,#8ea6f3);
    min-height:100vh;
    font-family:'Poppins',sans-serif;
}

/* HEADER */

.title{
    font-size:38px;
    font-weight:700;
    color:white;
}

.subtitle{
    color:rgba(255,255,255,0.9);
    font-size:18px;
}

/* CARD */

.card-box{
    border-radius:24px;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(10px);
    border:1px solid rgba(255,255,255,0.25);
    color:white;
}

/* INPUT */

.form-control-lg,
.form-select-lg{
    height:65px;
    font-size:20px;
    border-radius:14px;
}

/* BUTTON */

.btn-lg{
    height:65px;
    font-size:20px;
    border-radius:14px;
}

/* RESULT */

.result-box{
    border-radius:18px;
    font-size:18px;
    min-height:80px;
}

/* STATUS */

.status-badge{
    font-size:18px;
    padding:10px 20px;
    border-radius:20px;
}

/* RESPONSIVE */

@media(max-width:768px){

.title{
    font-size:30px;
}

.form-control-lg,
.form-select-lg,
.btn-lg{
    height:58px;
    font-size:18px;
}

}

</style>
</head>

<body>

<div class="container d-flex flex-column justify-content-center align-items-center vh-100">

<!-- HEADER -->

<div class="text-center mb-4">
<div class="title">CEK DATA PESERTA BPJS</div>
<div class="subtitle">Pilih jenis kartu dan masukkan nomor peserta</div>
</div>

<!-- CARD -->

<div class="card-box shadow p-4 p-md-5 col-lg-6 col-md-8">

<div class="mb-4">

<select id="jenis" class="form-select form-select-lg">

<option value="">-- Pilih Jenis Pencarian --</option>
<option value="nik">Cek Berdasarkan NIK</option>
<option value="noka">Cek Berdasarkan No Kartu BPJS</option>

</select>

</div>

<div class="mb-4">

<input 
type="number"
id="nomor"
class="form-control form-control-lg"
placeholder="Masukkan Nomor Peserta">

</div>

<div class="d-grid gap-3 mb-4">

<button onclick="cekPeserta()" class="btn btn-success btn-lg">

<i class="bi bi-search"></i>
CEK DATA

</button>

<a href="/anjungan/" class="btn btn-outline-light btn-lg">

<i class="bi bi-arrow-left"></i>
KEMBALI

</a>

</div>

<!-- RESULT -->

<div id="hasil" class="result-box bg-light text-dark p-4 text-center">

<i class="bi bi-info-circle"></i>
Hasil pengecekan akan muncul di sini

</div>

</div>

</div>

<script src="../../js/cekNoka.js"></script>

</body>
</html>