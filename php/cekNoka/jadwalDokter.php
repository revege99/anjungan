<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Jadwal Dokter</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: linear-gradient(135deg, #4e73df, #6f86d6, #8ea6f3);
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
}

/* HEADER */
.header{
    text-align:center;
    margin-bottom:40px;
    color:#fff;
}

.header h1{
    font-size:38px;
    font-weight:800;
    letter-spacing:1px;
}

.header p{
    font-size:18px;
    opacity:.9;
}

/* TABLE WRAPPER */
/* ===============================
   GLASS MODERN TABLE STYLE
=================================*/

/* TABLE WRAPPER */
.table-wrapper{
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-radius: 28px;
    padding: 35px;
    border: 1px solid rgba(255,255,255,0.35);
    box-shadow: 0 20px 45px rgba(0,0,0,.15);
}

/* HEADER */
.table thead th{
    background: rgba(255,255,255,0.35);
    color:#1f2d3d;
    font-weight:700;
    border:none;
}

/* BODY ROW */
.table tbody tr{
    background: rgba(255,255,255,0.75);
    border-radius: 16px;
    transition: .3s ease;
}

.table tbody tr:hover{
    background: rgba(255,255,255,0.95);
    transform: scale(1.01);
}

/* TEXT BODY */
.table td{
    border:none;
    padding:16px;
    vertical-align: middle;
    color:#1f2d3d; /* DARK TEXT */
}

/* TEXT STYLE */
.jam{
    font-weight:700;
    color:#3b5bdb;
}

.poli{
    text-transform:uppercase;
    font-weight:600;
    color:#495057;
}

.dokter{
    font-weight:600;
    font-size:16px;
    color:#212529;
}

/* BADGE GLASS */
.badge-kuota{
    padding:6px 16px;
    border-radius:30px;
    font-size:13px;
    backdrop-filter: blur(6px);
}

.badge-aman{
    background: rgba(40,167,69,.35);
    border:1px solid rgba(40,167,69,.6);
}

.badge-sedikit{
    background: rgba(255,193,7,.35);
    border:1px solid rgba(255,193,7,.7);
    color:#000;
}

.badge-habis{
    background: rgba(220,53,69,.35);
    border:1px solid rgba(220,53,69,.6);
}

/* Responsive TV */
@media(min-width:1400px){
    .table td, .table th{
        font-size:18px;
        padding:18px;
    }
}

/* Tombol Kembali */
.kembali-btn{
    font-weight:600;
    border-radius:40px;
    transition:.3s ease;
    background:#ffffff;
    color:#4e73df;
    border:none;
}

.kembali-btn:hover{
    background:#4e73df;
    color:#fff;
    transform:translateY(-2px);
}

</style>
</head>

<body>

<div class="container-fluid py-5 px-4">

    <div class="header">
        <h1>JADWAL DOKTER</h1>
        <p id="hariText">Memuat...</p>
    </div>

    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>Jam Praktik</th>
                        <th>Poli</th>
                        <th>Dokter</th>
                        <th>Kuota</th>
                    </tr>
                </thead>
                <tbody id="jadwalBody">
                    <!-- Diisi JS -->
                </tbody>
            </table>
        </div>
    </div>
    <div class="text-center mt-4">
    <a href="/anjungan/" class="btn btn-light btn-lg px-4 shadow-sm kembali-btn">
        ← Kembali ke Beranda
    </a>
</div>

</div>

<script>
const bodyTable = document.getElementById('jadwalBody');
const hariText = document.getElementById('hariText');

fetch('../../ajax/getJadwalDokter.php')
    .then(res => res.json())
    .then(res => {

        hariText.textContent = `Hari ${res.hari}`;

        if (!res.status || res.total === 0) {
            bodyTable.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center fw-bold">
                        Tidak ada jadwal dokter hari ini
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';

        res.data.forEach(item => {

            let badgeClass = 'badge-aman';
            let label = 'Tersedia';

            if(item.kuota <= 0){
                badgeClass = 'badge-habis';
                label = 'Habis';
            }else if(item.kuota <= 5){
                badgeClass = 'badge-sedikit';
                label = 'Hampir Habis';
            }

            html += `
                <tr>
                    <td class="jam">${item.jam_mulai} - ${item.jam_selesai}</td>
                    <td class="poli">${item.poli}</td>
                    <td class="dokter">${item.dokter}</td>
                    <td>
                        <span class="badge badge-kuota ${badgeClass}">
                            ${item.kuota} (${label})
                        </span>
                    </td>
                </tr>
            `;
        });

        bodyTable.innerHTML = html;
    })
    .catch(() => {
        bodyTable.innerHTML = `
            <tr>
                <td colspan="4" class="text-center fw-bold text-danger">
                    Gagal memuat jadwal dokter
                </td>
            </tr>
        `;
        hariText.textContent = '';
    });
</script>

</body>
</html>