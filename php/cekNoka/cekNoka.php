<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cek Peserta BPJS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #6f86d6, #8ea6f3);
            min-height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }

        .title {
            font-size: 40px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }

        .subtitle {
            color: rgba(255,255,255,0.85);
        }

        .card-box {
            border-radius: 28px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
        }

        .form-control-lg,
        .form-select-lg {
            height: 65px;
            font-size: 20px;
            border-radius: 16px;
        }

        .btn-lg {
            height: 65px;
            font-size: 20px;
            border-radius: 16px;
        }

        .result-box {
            border-radius: 20px;
            font-size: 18px;
        }

        .section-title {
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding-bottom: 6px;
        }

        .label {
            opacity: 0.85;
        }

        .value {
            font-weight: 600;
        }

        .status-badge {
            font-size: 18px;
            padding: 10px 20px;
            border-radius: 20px;
        }
    </style>
</head>
<body>

<div class="container-fluid d-flex flex-column justify-content-center align-items-center vh-100">

    <!-- HEADER -->
    <div class="text-center mb-4">
        <div class="title">CEK DATA PESERTA BPJS</div>
        <div class="subtitle">Pilih jenis kartu dan masukkan nomor</div>
    </div>

    <!-- FORM -->
    <div class="card shadow p-5 card-box col-lg-6 col-md-8">

        <div class="mb-4">
            <select id="jenis" class="form-select form-select-lg">
                <option value="">-- Pilih Jenis Kartu --</option>
                <option value="nik">Cek Berdasarkan NIK</option>
                <option value="noka">Cek Berdasarkan No Kartu</option>
            </select>
        </div>

        <div class="mb-4">
            <input type="number" id="nomor" class="form-control form-control-lg" placeholder="Masukkan Nomor">
        </div>

        <div class="d-grid gap-3 mb-4">
            <button onclick="cekPeserta()" class="btn btn-success btn-lg">
                🔍 CEK DATA
            </button>

            <a href="/anjungan/" class="btn btn-outline-light btn-lg">
                ⬅ KEMBALI
            </a>
        </div>

        <!-- HASIL -->
        <div id="hasil" class="p-4 result-box text-center bg-light text-dark">
            Hasil pengecekan akan muncul di sini
        </div>

    </div>
</div>

<script>
    let bpjsData = null;
function cekPeserta() {

    const jenis = document.getElementById('jenis').value;
    const nomor = document.getElementById('nomor').value;
    const hasil = document.getElementById('hasil');

    if (!jenis || !nomor) {
        hasil.className = "p-4 result-box bg-danger text-white";
        hasil.innerHTML = "Jenis kartu dan nomor wajib diisi";
        return;
    }

    hasil.className = "p-4 result-box bg-info text-white";
    hasil.innerHTML = "⏳ Memproses data...";

    fetch(`bpjs_peserta.php?jenis=${jenis}&nomor=${nomor}`)
        .then(res => res.json())
        .then(data => {

            if (!data.status) {
                hasil.className = "p-4 result-box bg-warning text-dark";
                hasil.innerHTML = data.message ?? "Terjadi kesalahan";
                return;
            }

            bpjsData = data.data; // <-- SIMPAN GLOBAL

            const p = bpjsData;

            const val = v => (!v || v === "") ? "-" : v;
            const bool = v => v ? "YA" : "TIDAK";

            const aktif = p.aktif;

            hasil.className = `p-4 result-box ${aktif ? 'bg-success' : 'bg-danger'} text-white`;

            hasil.innerHTML = `
                <div class="text-center mb-3">
                    <span class="status-badge ${aktif ? 'bg-light text-success' : 'bg-light text-danger'}">
                        ${aktif ? 'PESERTA AKTIF' : 'PESERTA TIDAK AKTIF'}
                    </span>
                </div>

                <div class="section-title">DATA UTAMA</div>
                <div class="row">
                    <div class="col-md-6"><span class="label">Nama</span><br><span class="value">${val(p.nama)}</span></div>
                    <div class="col-md-6"><span class="label">No Kartu</span><br><span class="value">${val(p.noKartu)}</span></div>
                    <div class="col-md-6 mt-2"><span class="label">NIK</span><br><span class="value">${val(p.noKTP)}</span></div>
                    <div class="col-md-6 mt-2"><span class="label">Tgl Lahir</span><br><span class="value">${val(p.tglLahir)}</span></div>
                </div>

                <div class="section-title">KEPESERTAAN</div>
                <div class="row">
                    <div class="col-md-6">Aktif: <strong>${bool(p.aktif)}</strong></div>
                    <div class="col-md-6">Tunggakan: <strong>${val(p.tunggakan)}</strong></div>
                </div>

                <div class="section-title">FASILITAS KESEHATAN</div>
                <div>
                    FKTP: <strong>${val(p.kdProviderPst?.nmProvider)}</strong>
                </div>

                <div class="text-center mt-4">
                    <button id="btnAntrian"
                        class="btn btn-lg ${aktif ? 'btn-light text-success' : 'btn-secondary'}"
                        ${aktif ? '' : 'disabled'}>
                        🏥 LANJUT AMBIL ANTRIAN
                    </button>
                </div>
            `;

            if (aktif) {
                document.getElementById('btnAntrian').addEventListener('click', function(e) {

                    e.preventDefault();

                    if (!bpjsData) {
                        alert('Data BPJS tidak tersedia');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'ambilAntrian.php';

                    const fields = {
                        nik: bpjsData.noKTP ?? '',
                        noka: bpjsData.noKartu ?? '',
                        nama: bpjsData.nama ?? '',
                        tgl_lahir: bpjsData.tglLahir ?? '',
                        jk: bpjsData.sex ?? '',
                        alamat: bpjsData.alamat ?? ''
                    };

                    Object.keys(fields).forEach(key => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                });
            }
        })
        .catch(() => {
            hasil.className = "p-4 result-box bg-danger text-white";
            hasil.innerHTML = "❌ Gagal menghubungi server";
        });
}
</script>

</body>
</html>