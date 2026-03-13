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
                    const nomorInput = nomor;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'ambilAntrian.php';

                    const fields = {
                        nik: bpjsData.noKTP && bpjsData.noKTP.trim() !== ''
                        ? bpjsData.noKTP
                        : nomorInput,
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