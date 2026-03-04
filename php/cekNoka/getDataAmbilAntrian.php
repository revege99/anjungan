<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Anjungan BPJS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background: linear-gradient(135deg, #4e73df, #6f86d6, #8ea6f3);
        height: 100vh;
        font-family: 'Segoe UI', sans-serif;
    }

    .glass {
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        color: #fff;
    }

    h1 {
        font-weight: 800;
        letter-spacing: 1px;
    }

    .subtitle {
        opacity: .9;
        font-size: 1.1rem;
    }

    .form-control {
        height: 75px;
        font-size: 1.4rem;
        border-radius: 18px;
        text-align: center;
    }

    .btn-main {
        height: 75px;
        font-size: 1.5rem;
        font-weight: 700;
        border-radius: 20px;
        transition: .3s;
    }

    .btn-main:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(0,0,0,.3);
    }
    .btn-back{
    font-weight:600;
    border-radius:50px;
    transition:.3s;
}

.btn-back:hover{
    background:#0d6efd;
    color:#fff;
    transform:translateY(-3px);
}

    .result-box {
        animation: fadeUp .4s ease;
    }

    @keyframes fadeUp {
        from {opacity: 0; transform: translateY(20px);}
        to {opacity: 1; transform: translateY(0);}
    }
</style>
</head>

<body>

<div class="container d-flex align-items-center justify-content-center vh-100">
    <div style="width:100%; max-width:520px">

        <!-- HEADER -->
        <div class="text-center mb-4 text-white">
            <i class="fa-solid fa-hospital-user fa-3x mb-3"></i>
            <h1>CEK DATA BPJS</h1>
            <div class="subtitle">
                Masukkan <b>NIK</b> atau <b>No Kartu BPJS</b>
            </div>
        </div>

        <!-- FORM -->
        <div class="glass p-4">

            <input type="text"
                   id="nomor"
                   class="form-control mb-4"
                   placeholder="Masukkan NIK / No Kartu BPJS">

            <button class="btn btn-success btn-main w-100" onclick="cekData()">
                <i class="fa-solid fa-magnifying-glass me-2"></i>
                CEK DATA
            </button>
            <div class="text-center mt-4">
                <a href="/anjungan/" class="btn btn-light btn-lg btn-back w-100">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    KEMBALI KE BERANDA
                </a>
            </div>

        </div>

        <!-- RESULT -->
        <div id="resultBox" class="mt-4 d-none result-box">
            <div class="glass p-4 text-center" id="resultContent"></div>
        </div>

    </div>
</div>

<script>
function cekData(){

    const nomor = document.getElementById('nomor').value.trim();
    const box   = document.getElementById('resultBox');
    const html  = document.getElementById('resultContent');

    if(!nomor){
        box.classList.remove('d-none');
        html.innerHTML = `
            <h4 class="text-warning fw-bold">
                <i class="fa-solid fa-triangle-exclamation"></i><br>
                Nomor belum diisi
            </h4>
        `;
        return;
    }

    box.classList.remove('d-none');
    html.innerHTML = `
        <div class="text-info">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i><br><br>
            Memeriksa data...
        </div>
    `;

    fetch('../../ajax/ambilAntrian.php?mode=getPasien', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'nomor=' + encodeURIComponent(nomor)
    })
    .then(res => res.json())
    .then(res => {

        if(res.status){
            const p = res.data;

            html.innerHTML = `
                <h3 class="text-success fw-bold mb-3">
                    <i class="fa-solid fa-circle-check"></i><br>
                    DATA DITEMUKAN
                </h3>
                <hr class="border-light">

                <p><b>No RM:</b> ${p.no_rkm_medis}</p>
                <p><b>Nama:</b> ${p.nm_pasien}</p>
                <p><b>NIK:</b> ${p.no_ktp}</p>
                <p><b>No BPJS:</b> ${p.no_peserta}</p>

                <button class="btn btn-success btn-main w-100 mt-3"
                    onclick="kirimPost(
                        '${p.no_ktp}',
                        '${p.no_peserta}',
                        '${p.nm_pasien}',
                        '${p.tgl_lahir}',
                        '${p.jk}',
                        '${p.alamat}'
                    )">
                AMBIL ANTRIAN
            </button>
            `;
        } else {
            html.innerHTML = `
                <h3 class="text-danger fw-bold mb-3">
                    <i class="fa-solid fa-circle-xmark"></i><br>
                    DATA TIDAK DITEMUKAN
                </h3>
                <hr class="border-light">

                <p class="mb-3">
                    Pasien belum terdaftar
                </p>

                <button class="btn btn-danger btn-main w-100 mt-3">
                    LENGKAPI REKAM MEDIK
                </button>
            `;
        }
    })
    .catch(err => {
        html.innerHTML = `
            <h4 class="text-danger">
                Terjadi kesalahan sistem
            </h4>
        `;
        console.error(err);
    });
}

function ambilAntrian(noRM){
    alert("Ambil antrian untuk RM: " + noRM);
    // lanjut ke mode=ambilAntrian
}


function kirimPost(nik, noka, nama, tgl_lahir, jk, alamat) {

    const form = document.createElement("form");
    form.method = "POST";
    form.action = "http://localhost/anjungan/php/cekNoka/ambilAntrian.php";

    const data = {
        nik: nik,
        noka: noka,
        nama: nama,
        tgl_lahir: tgl_lahir,
        jk: jk,
        alamat: alamat
    };

    for (let key in data) {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = data[key];
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
}
</script>



</body>
</html>