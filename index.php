<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Anjungan Klinik</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

body{
    font-family:'Poppins',sans-serif;
    min-height:100vh;
    background:linear-gradient(135deg,#4e73df,#6f86d6,#8ea6f3);
    display:flex;
    flex-direction:column;
}

/* Header */

.header-title{
    font-size:44px;
    font-weight:700;
    color:#fff;
    letter-spacing:2px;
}

.header-subtitle{
    color:rgba(255,255,255,0.9);
    font-size:18px;
}

/* Card Menu */

.menu-card{
    border-radius:22px;
    height:190px;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(10px);
    border:1px solid rgba(255,255,255,0.25);
    transition:all .35s ease;
    cursor:pointer;
    color:#fff;
}

.menu-card:hover{
    transform:translateY(-8px) scale(1.03);
    background:rgba(255,255,255,0.25);
    box-shadow:0 20px 40px rgba(0,0,0,0.3);
}

.menu-card:active{
    transform:scale(.97);
}

.menu-icon{
    font-size:56px;
    margin-bottom:10px;
}

.menu-text{
    font-size:20px;
    font-weight:600;
}

/* Footer */

.footer{
    margin-top:auto;
    text-align:center;
    padding:12px;
    color:rgba(255,255,255,0.7);
    font-size:14px;
}

/* Responsive */

@media (max-width:768px){

.header-title{
    font-size:34px;
}

.menu-card{
    height:160px;
}

.menu-icon{
    font-size:44px;
}

.menu-text{
    font-size:18px;
}

}

@media (max-width:576px){

.header-title{
    font-size:28px;
}

.header-subtitle{
    font-size:16px;
}

}

</style>
</head>

<body>

<div class="container text-center py-5">

    <!-- HEADER -->
    <div class="mb-5">
        <div class="header-title">ANJUNGAN KLINIK</div>
        <div class="header-subtitle">Silakan pilih layanan yang tersedia</div>
    </div>

    <!-- MENU -->
    <div class="row g-4 justify-content-center">

        <div class="col-lg-4 col-md-6">
            <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                 onclick="location.href='/anjungan/php/cekNoka/cekNoka.php'">
                <div class="menu-icon">
                    <i class="bi bi-credit-card"></i>
                </div>
                <div class="menu-text">Cek No. Kartu BPJS</div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                 onclick="location.href='/anjungan/php/cekNoka/getDataAmbilAntrian.php'">
                <div class="menu-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
                <div class="menu-text">Ambil Antrian Poli</div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="menu-card d-flex flex-column justify-content-center align-items-center"
                 onclick="location.href='/anjungan/php/cekNoka/jadwalDokter.php'">
                <div class="menu-icon">
                    <i class="bi bi-calendar2-week"></i>
                </div>
                <div class="menu-text">Informasi Jadwal Dokter</div>
            </div>
        </div>

    </div>

</div>

<!-- FOOTER -->

<div class="footer">
© 2026 Klinik St Lucia Lintong — Sistem Anjungan Mandiri
</div>

</body>
</html>
