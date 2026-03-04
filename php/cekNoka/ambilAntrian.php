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
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }
        /* ===== GLASS STYLE ===== */
.glass-card {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(15px);
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
    color: #fff;
    border: none;
}

/* ===== TITLE ===== */
.glass-card h3,
.glass-card h5 {
    font-weight: 800;
    letter-spacing: .5px;
}

/* ===== INPUT ===== */
.glass-card .form-control,
.glass-card .form-select {
    height: 60px;
    border-radius: 16px;
    font-size: 1.05rem;
    background: rgba(255,255,255,0.85);
    border: none;
}

/* ===== SECTION BOX ===== */
.glass-section {
    background: rgba(255,255,255,0.12);
    border-radius: 18px;
    padding: 16px;
}

/* ===== ALERT ===== */
.glass-alert {
    background: rgba(13,110,253,0.25);
    color: #fff;
    border: none;
    border-radius: 16px;
}

/* ===== BUTTON ===== */
.btn-main {
    height: 65px;
    font-size: 1.3rem;
    font-weight: 700;
    border-radius: 20px;
    transition: .3s;
}

.btn-main:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(0,0,0,.3);
}
    </style>
</head>
<body class="bg-light">



<div class="container my-5">

    <div class="glass-card p-4">
        <div class="card-body p-4">

            <h3 class="text-center mb-4">FORM AMBIL ANTRIAN</h3>

            <!-- ================= HIDDEN PARAM ================= -->
            <input type="hidden" id="nik"  value="<?= htmlspecialchars($nik) ?>">
            <input type="hidden" id="noka" value="<?= htmlspecialchars($noka) ?>">

            <!-- ================= INFO ================= -->
            <div id="infoBox" class="glass-alert mb-4">
                Memeriksa data pasien...
            </div>

            <!-- ================= DATA PASIEN ================= -->
            <div id="sectionPasien" class="glass-section mb-4 d-none">
                <h5 class="mb-3">Data Pasien</h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label>No RM</label>
                        <input type="text" id="no_rkm_medis" class="form-control" readonly>
                    </div>

                    <div class="col-md-8">
                        <label>Nama Pasien</label>
                        <input type="text" id="nm_pasien" class="form-control" readonly>
                    </div>

                    <div class="col-md-4">
                        <label>Tanggal Lahir</label>
                        <input type="text" id="tgl_lahir" class="form-control" readonly>
                    </div>

                    <div class="col-md-4">
                        <label>Jenis Kelamin</label>
                        <input type="text" id="jk" class="form-control" readonly>
                    </div>

                    <div class="col-md-4">
                        <label>No BPJS</label>
                        <input type="text" id="no_peserta" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <!-- ================= PILIH POLI ================= -->
            <div id="sectionPoli" class="border rounded-3 p-3 mb-4 d-none">
                <h5 class="mb-3">Pilih Poli</h5>

                <select id="kd_poli" class="form-select">
                    <option value="">-- Pilih Poli --</option>
                </select>
            </div>

            <!-- ================= PILIH DOKTER ================= -->
            <div id="sectionDokter" class="border rounded-3 p-3 mb-4 d-none">
                <h5 class="mb-3">Pilih Dokter</h5>

                <select id="kd_dokter" class="form-select">
                    <option value="">-- Pilih Dokter --</option>
                </select>
            </div>

            <!-- ================= SUBMIT ================= -->
            <div class="d-grid">
                <button id="btnSimpan" class="btn btn-success btn-main d-none">
                    Ambil Antrian
                </button>
            </div>

        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const nik  = document.getElementById('nik').value;
    const noka = document.getElementById('noka').value;


    console.log('NIK hidden:', nik);
console.log('NOKA hidden:', noka);

    if (!nik && !noka) {
        document.getElementById('infoBox').className = 'alert alert-danger';
        document.getElementById('infoBox').innerText = 'Data identitas pasien tidak ditemukan';
        return;
    }

    // =====================
    // AUTO CARI PASIEN
    // =====================
    cariPasien(nik, noka);
});


function cariPasien(nik, noka) {

    fetch('../../ajax/cariPasien.php?mode=cari_pasien', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            nik: nik,
            noka: noka
        })
    })
    .then(res => res.json())
    .then(res => {

        const info = document.getElementById('infoBox');

        // ===============================
        // ❌ JIKA TIDAK DITEMUKAN
        // ===============================
        if (!res.status) {

            if (res.not_registered) {

                info.className = 'alert alert-warning';
                info.innerHTML = `
                    Pasien belum memiliki Rekam Medis.<br><br>
                    <button type="button" id="btnRegistrasiRM" class="btn btn-primary">
                        Registrasi Rekam Medis
                    </button>
                `;

                document
                    .getElementById('btnRegistrasiRM')
                    .addEventListener('click', function () {

                        if (!bpjsData) {
                            alert('Data BPJS tidak tersedia');
                            return;
                        }

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'registrasiRM.php';

                        const fields = {
                            nik: bpjsData.noKTP ?? '',
                            noka: bpjsData.noKartu ?? '',
                            nama: bpjsData.nama ?? '',
                            tgl_lahir: bpjsData.tglLahir ?? '',
                            jk: bpjsData.jenisKelamin ?? '',
                            alamat: bpjsData.alamat ?? ''
                        };

                        for (let key in fields) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = fields[key];
                            form.appendChild(input);
                        }

                        document.body.appendChild(form);
                        form.submit();
                    });

                return; // 🔥 hentikan eksekusi
            }

            info.className = 'alert alert-danger';
            info.innerText = res.message || 'Terjadi kesalahan';
            return;
        }

        // ===============================
        // ✅ JIKA PASIEN DITEMUKAN
        // ===============================
        const p = res.data;

        info.className = 'alert alert-success';
        info.innerText = 'Pasien ditemukan';

        document.getElementById('no_rkm_medis').value = p.no_rkm_medis;
        document.getElementById('nm_pasien').value   = p.nm_pasien;
        document.getElementById('tgl_lahir').value   = p.tgl_lahir;
        document.getElementById('jk').value          = p.jk === 'L' ? 'Laki-laki' : 'Perempuan';
        document.getElementById('no_peserta').value  = p.no_peserta;

        document.getElementById('sectionPasien').classList.remove('d-none');
        document.getElementById('sectionPoli').classList.remove('d-none');

        loadPoli();
    })
    .catch((err) => {
        console.error(err);
        const info = document.getElementById('infoBox');
        info.className = 'alert alert-danger';
        info.innerText = 'Gagal menghubungi server';
    });
}

function loadPoli() {
    const poli = document.getElementById('kd_poli');

    poli.innerHTML = `<option value="">-- Loading Poli --</option>`;

    fetch(`../../ajax/cariPasien.php?mode=load_poli`)
        .then(res => res.json())
        .then(res => {

            if (!res.status) {
                poli.innerHTML = `<option value="">-- Gagal Load Poli --</option>`;
                return;
            }

            poli.innerHTML = `<option value="">-- Pilih Poli --</option>`;

            res.data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.kd_poli;
                opt.textContent = p.nm_poli;
                poli.appendChild(opt);
            });

        })
        .catch(err => {
            console.error(err);
            poli.innerHTML = `<option value="">-- Error Load Poli --</option>`;
        });

    poli.addEventListener('change', function () {
        if (this.value) {
            loadDokter(this.value); // nanti kita sambung ke case dokter
        }
    });
}

function loadDokter(kd_poli) {
    const dokter = document.getElementById('kd_dokter');
    const sectionDokter = document.getElementById('sectionDokter');
    const btnSimpan = document.getElementById('btnSimpan');

    sectionDokter.classList.remove('d-none');
    btnSimpan.classList.add('d-none');

    dokter.innerHTML = `<option value="">-- Loading Dokter --</option>`;

    fetch(`../../ajax/cariPasien.php?mode=load_dokter&kd_poli=${kd_poli}`)
        .then(res => res.json())
        .then(res => {

            if (!res.status || res.data.length === 0) {
                dokter.innerHTML = `<option value="">-- Dokter Tidak Tersedia --</option>`;
                return;
            }

            dokter.innerHTML = `<option value="">-- Pilih Dokter --</option>`;

            res.data.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.kd_dokter;
                opt.textContent = `${d.nm_dokter} (${d.jam_mulai} - ${d.jam_selesai})`;
                opt.dataset.kuota = d.kuota;
                dokter.appendChild(opt);
            });
        })
        .catch(err => {
            console.error(err);
            dokter.innerHTML = `<option value="">-- Error Load Dokter --</option>`;
        });

    dokter.addEventListener('change', function () {
        if (this.value) {
            btnSimpan.classList.remove('d-none');
        }
    });
}




function tampilFormRegistrasi() {

    document.getElementById('sectionPasien').classList.remove('d-none');

    document.getElementById('no_rkm_medis').value = '';
    document.getElementById('no_rkm_medis').removeAttribute('readonly');

    document.getElementById('btnSimpanRM').classList.remove('d-none');
}








document.addEventListener('click', function (e) {
    if (e.target && e.target.id === 'btnSimpan') {

        const kd_poli       = document.getElementById('kd_poli').value;
        const no_rkm_medis  = document.getElementById('no_rkm_medis').value;
        const kd_dokter     = document.getElementById('kd_dokter').value;
        const tgl_lahir     = document.getElementById('tgl_lahir').value;

        

        // ===============================
        // VALIDASI
        // ===============================
        if (!kd_poli || !kd_dokter) {
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap',
                text: 'Silakan pilih Poli dan Dokter terlebih dahulu',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        // ===============================
        // LOADING
        // ===============================
        Swal.fire({
            title: 'Memproses Antrian...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // ===============================
        // FETCH SIMPAN REGISTRASI
        // ===============================
        fetch('../../ajax/cariPasien.php?mode=simpan_registrasi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                no_rkm_medis: no_rkm_medis,
                kd_poli: kd_poli,
                kd_dokter: kd_dokter,
                kd_pj: 'BPJ',
                tgl_lahir :tgl_lahir
            })
        })
        .then(res => res.json())
        .then(res => {

            Swal.close();

            // ===============================
            // SUKSES
            // ===============================
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Antrian Berhasil',
                    html: `
                        <div class="text-center">

                            <h5 class="mb-0 fw-bold">KLINIK SANTA LUCIA</h5>
                            <small>Bukti Registrasi ${res.nm_poli}</small><br>
                            <small>Tanggal ${res.tanggal}</small><br>
                            <small> ${res.nm_dokter}</small>
                            <hr>
                            <b>Nomor Antrian Poliklinik</b>
                        </div>

                        <div class="text-start">
                            <div class="text-center my-2">
                                <span style="font-size:60px; font-weight:bold;">
                                    ${res.no_reg}
                                </span>
                            </div>
                            <div class="text-center my-0">
                                <small style="font-size:25px; font-weight:bold;"">${res.nm_pasien}</small><br><br>
                            </div>
                            <div class="text-start" style="font-size:14px;">

                                <div class="d-flex">
                                    <div style="width:160px;">Tanggal Lahir</div>
                                    <div>: ${res.tgl_lahir}</div>
                                </div>

                                <div class="d-flex">
                                    <div style="width:160px;">No. Rekam Medis</div>
                                    <div>: ${res.no_rkm_medis}</div>
                                </div>

                                <div class="d-flex">
                                    <div style="width:160px;">Jenis Kelamin</div>
                                    <div>: ${res.jk}</div>
                                </div>

                                <div class="d-flex">
                                    <div style="width:160px;">Jenis Bayar</div>
                                    <div>: BPJS</div>
                                </div>

                            </div>

                        </div>

                        <hr>

                        <div class="text-center">
                            <small>
                                Terimakasih atas kepercayaan Anda.<br>
                                Silahkan menunggu di ruang poli.
                            </small>
                        </div>
                    `,
                    confirmButtonText: 'Selesai',
                    confirmButtonColor: '#198754',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    window.location.href = 'http://localhost/anjungan/';
                });
            } else {
                // ===============================
                // GAGAL LOGIC
                // ===============================
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengambil Antrian',
                    text: res.message || 'Terjadi kesalahan sistem',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(err => {

            Swal.close();
            console.error(err);

            // ===============================
            // ERROR SERVER
            // ===============================
            Swal.fire({
                icon: 'error',
                title: 'Server Tidak Merespons',
                text: 'Silakan coba kembali beberapa saat lagi',
                confirmButtonColor: '#dc3545'
            });
        });
    }
});
</script>

<script>
let bpjsData = {
    noKTP: "<?= htmlspecialchars($nik) ?>",
    noKartu: "<?= htmlspecialchars($noka) ?>",
    nama: "<?= htmlspecialchars($nama) ?>",
    tglLahir: "<?= htmlspecialchars($tgl_lahir) ?>",
    jenisKelamin: "<?= htmlspecialchars($jk) ?>",
    alamat: "<?= htmlspecialchars($alamat) ?>"
};
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>