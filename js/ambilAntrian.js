document.getElementById('btnSimpan').addEventListener('click', function () {

    const kd_poli   = document.getElementById('kd_poli').value;
    const kd_dokter = document.getElementById('kd_dokter').value;

    fetch('../../ajax/cariPasien.php?mode=simpan_registrasi', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            no_rkm_medis: window.no_rkm_medis, // dari hasil cari pasien
            kd_poli: kd_poli,
            kd_dokter: kd_dokter,
            kd_pj: 'BPJ'
        })
    })
    .then(res => res.json())
    .then(res => {
        if (res.status) {
            alert(`✅ ${res.message}\nNo Rawat: ${res.no_rawat}`);
            // redirect / print / lanjut SEP
        } else {
            alert(`❌ ${res.message}`);
        }
    })
    .catch(err => {
        alert('❌ Gagal koneksi server');
        console.error(err);
    });
});