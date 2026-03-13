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


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

document.addEventListener('DOMContentLoaded', () => {

const nik = document.getElementById('nik').value;
const noka = document.getElementById('noka').value;

if (!nik && !noka) {

document.getElementById('infoBox').className='alert alert-danger info-box';
document.getElementById('infoBox').innerText='Data identitas pasien tidak ditemukan';
return;

}

cariPasien(nik,noka);

});


function cariPasien(nik,noka){

fetch('../../ajax/cariPasien.php?mode=cari_pasien',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:new URLSearchParams({nik:nik,noka:noka})
})
.then(res=>res.json())
.then(res=>{

const info=document.getElementById('infoBox');

if(!res.status){

if(res.not_registered){

info.className='alert alert-warning info-box';

info.innerHTML=`
Pasien belum memiliki Rekam Medis.<br><br>
<button type="button" id="btnRegistrasiRM" class="btn btn-primary">
Registrasi Rekam Medis
</button>
`;

document.getElementById('btnRegistrasiRM').addEventListener('click',function(){

if(!bpjsData){
alert('Data BPJS tidak tersedia');
return;
}

const form=document.createElement('form');
form.method='POST';
form.action='registrasiRM.php';

const fields={
nik:bpjsData.noKTP ?? '',
noka:bpjsData.noKartu ?? '',
nama:bpjsData.nama ?? '',
tgl_lahir:bpjsData.tglLahir ?? '',
jk:bpjsData.jenisKelamin ?? '',
alamat:bpjsData.alamat ?? ''
};

for(let key in fields){
const input=document.createElement('input');
input.type='hidden';
input.name=key;
input.value=fields[key];
form.appendChild(input);
}

document.body.appendChild(form);
form.submit();

});

return;

}

info.className='alert alert-danger info-box';
info.innerText=res.message || 'Terjadi kesalahan';
return;

}

const p=res.data;

info.className='alert alert-success info-box';
info.innerText='Pasien ditemukan';

document.getElementById('no_rkm_medis').value=p.no_rkm_medis;
document.getElementById('nm_pasien').value=p.nm_pasien;
document.getElementById('tgl_lahir').value=p.tgl_lahir;
document.getElementById('jk').value=p.jk==='L'?'Laki-laki':'Perempuan';
document.getElementById('no_peserta').value=p.no_peserta;

document.getElementById('sectionPasien').classList.remove('d-none');
document.getElementById('sectionPoli').classList.remove('d-none');

loadPoli();

})
.catch(err=>{
console.error(err);

const info=document.getElementById('infoBox');
info.className='alert alert-danger info-box';
info.innerText='Gagal menghubungi server';

});

}


function loadPoli(){

const poli=document.getElementById('kd_poli');

poli.innerHTML=`<option>Loading Poli...</option>`;

fetch(`../../ajax/cariPasien.php?mode=load_poli`)
.then(res=>res.json())
.then(res=>{

if(!res.status){
poli.innerHTML=`<option>Gagal Load Poli</option>`;
return;
}

poli.innerHTML=`<option value="">-- Pilih Poli --</option>`;

res.data.forEach(p=>{
const opt=document.createElement('option');
opt.value=p.kd_poli;
opt.textContent=p.nm_poli;
poli.appendChild(opt);
});

});

poli.addEventListener('change',function(){
if(this.value){
loadDokter(this.value);
}
});

}


function loadDokter(kd_poli){

const dokter=document.getElementById('kd_dokter');
const sectionDokter=document.getElementById('sectionDokter');
const btnSimpan=document.getElementById('btnSimpan');

sectionDokter.classList.remove('d-none');
btnSimpan.classList.add('d-none');

dokter.innerHTML=`<option>Loading Dokter...</option>`;

fetch(`../../ajax/cariPasien.php?mode=load_dokter&kd_poli=${kd_poli}`)
.then(res=>res.json())
.then(res=>{

if(!res.status || res.data.length===0){
dokter.innerHTML=`<option>Dokter Tidak Tersedia</option>`;
return;
}

dokter.innerHTML=`<option value="">-- Pilih Dokter --</option>`;

res.data.forEach(d=>{

const opt=document.createElement('option');
opt.value=d.kd_dokter;
opt.textContent=`${d.nm_dokter} (${d.jam_mulai} - ${d.jam_selesai})`;
dokter.appendChild(opt);

});

})
.catch(err=>{
console.error(err);
dokter.innerHTML=`<option>Error Load Dokter</option>`;
});

dokter.addEventListener('change',function(){
if(this.value){
btnSimpan.classList.remove('d-none');
}
});

}


</script>

<script>

let bpjsData = {
noKTP:"<?= htmlspecialchars($nik) ?>",
noKartu:"<?= htmlspecialchars($noka) ?>",
nama:"<?= htmlspecialchars($nama) ?>",
tglLahir:"<?= htmlspecialchars($tgl_lahir) ?>",
jenisKelamin:"<?= htmlspecialchars($jk) ?>",
alamat:"<?= htmlspecialchars($alamat) ?>"
};

</script>

</body>
</html>