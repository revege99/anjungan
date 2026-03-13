document.addEventListener('DOMContentLoaded', () => {

    const BASE_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    const prov = document.getElementById('kd_prop');
    const kab  = document.getElementById('kd_kab');
    const kec  = document.getElementById('kd_kec');
    const kel  = document.getElementById('kd_kel');

    const nmProp = document.getElementById('nm_prop');
    const nmKab  = document.getElementById('nm_kab');
    const nmKec  = document.getElementById('nm_kec');
    const nmKel  = document.getElementById('nm_kel');

    const dbProp = document.getElementById('db_kd_prop');
    const dbKab  = document.getElementById('db_kd_kab');
    const dbKec  = document.getElementById('db_kd_kec');
    const dbKel  = document.getElementById('db_kd_kel');

    const db = { kd_prop:0, kd_kab:0, kd_kec:0, kd_kel:0 };

    function reset(el, text, disable=true){
        el.innerHTML = `<option value="">${text}</option>`;
        el.disabled = disable;
    }

    async function lookupKD(level, nama) {

    console.log('HIT lookupKD()', level, nama);

    const res = await fetch('../../ajax/lookup_wilayah.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ level, nama })
    });

    console.log('HTTP STATUS:', res.status);

    const text = await res.text();
    console.log('RAW RESPONSE:', text);

    let json;
    try {
        json = JSON.parse(text);
    } catch (e) {
        console.error('JSON PARSE ERROR', e);
        return 0;
    }

    console.log('JSON:', json);

    return json.kd ?? 0;
}

    // LOAD PROVINSI
    // =============================
// LOAD PROVINSI
// =============================
fetch(`${BASE_URL}/provinces.json`)
.then(r=>r.json())
.then(data=>{
    prov.innerHTML = '<option value="">-- Pilih Provinsi --</option>';
    data.forEach(p=>{
        prov.innerHTML += `<option value="${p.id}">${p.name}</option>`;
    });
});

// =============================
// PROVINSI
// =============================
prov.addEventListener('change', async function () {

    console.log('EVENT CHANGE PROVINSI FIRED');

    if (!this.value) {
        console.log('VALUE EMPTY → RETURN');
        return;
    }

    const nama = this.options[this.selectedIndex].text;

    // simpan nama
    nmProp.value = nama;

    // lookup kd di database
    db.kd_prop = await lookupKD('propinsi', nama);

    // simpan ke hidden input
    dbProp.value = db.kd_prop;

    console.log('AUTO INSERT PROVINSI');
    console.log('Nama  :', nama);
    console.log('KD DB :', db.kd_prop);

    // reset child
    reset(kab,'-- Loading Kabupaten --');
    reset(kec,'-- Pilih Kecamatan --');
    reset(kel,'-- Pilih Kelurahan --');

    dbKab.value = '';
    dbKec.value = '';
    dbKel.value = '';

    nmKab.value = '';
    nmKec.value = '';
    nmKel.value = '';

    // load kabupaten dari API
    const r = await fetch(`${BASE_URL}/regencies/${this.value}.json`);
    const d = await r.json();

    kab.innerHTML = '<option value="">-- Pilih Kabupaten --</option>';

    d.forEach(x=>{
        kab.innerHTML += `<option value="${x.id}">${x.name}</option>`;
    });

    kab.disabled = false;
});

// =============================
// KABUPATEN
// =============================
kab.addEventListener('change', async function () {

    console.log('EVENT CHANGE KABUPATEN FIRED');

    if (!this.value) return;

    const nama = this.options[this.selectedIndex].text;

    nmKab.value = nama;

    db.kd_kab = await lookupKD('kabupaten', nama);

    dbKab.value = db.kd_kab;

    console.log('KABUPATEN');
    console.log('Nama  :', nama);
    console.log('KD DB :', db.kd_kab);

    reset(kec,'-- Loading Kecamatan --');
    reset(kel,'-- Pilih Kelurahan --');

    dbKec.value = '';
    dbKel.value = '';

    nmKec.value = '';
    nmKel.value = '';

    const r = await fetch(`${BASE_URL}/districts/${this.value}.json`);
    const d = await r.json();

    kec.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';

    d.forEach(x=>{
        kec.innerHTML += `<option value="${x.id}">${x.name}</option>`;
    });

    kec.disabled = false;
});

// =============================
// KECAMATAN
// =============================
kec.addEventListener('change', async function () {

    console.log('EVENT CHANGE KECAMATAN FIRED');

    if (!this.value) return;

    const nama = this.options[this.selectedIndex].text;

    nmKec.value = nama;

    db.kd_kec = await lookupKD('kecamatan', nama);

    dbKec.value = db.kd_kec;

    console.log('KECAMATAN');
    console.log('Nama  :', nama);
    console.log('KD DB :', db.kd_kec);

    reset(kel,'-- Loading Kelurahan --');

    dbKel.value = '';
    nmKel.value = '';

    const r = await fetch(`${BASE_URL}/villages/${this.value}.json`);
    const d = await r.json();

    kel.innerHTML = '<option value="">-- Pilih Kelurahan --</option>';

    d.forEach(x=>{
        kel.innerHTML += `<option value="${x.id}">${x.name}</option>`;
    });

    kel.disabled = false;
});

// =============================
// KELURAHAN
// =============================
kel.addEventListener('change', async function () {

    console.log('EVENT CHANGE KELURAHAN FIRED');

    if (!this.value) return;

    const nama = this.options[this.selectedIndex].text;

    nmKel.value = nama;

    db.kd_kel = await lookupKD('kelurahan', nama);

    dbKel.value = db.kd_kel;

    console.log('KELURAHAN');
    console.log('Nama  :', nama);
    console.log('KD DB :', db.kd_kel);
});

// =============================
// FORM
// =============================
const formRM = document.getElementById('formRM');

formRM.addEventListener('submit', function(e) {

    e.preventDefault();

    const noHp = this.no_hp.value.trim();

    if (!/^08[0-9]{8,13}$/.test(noHp)) {
        Swal.fire({
            icon: 'error',
            title: 'Format Salah',
            text: 'Nomor HP tidak valid'
        });
        return;
    }

    const formData = new FormData(this);

    for (let pair of formData.entries()) {
    console.log(pair[0], pair[1]);
}

    fetch('../../ajax/prosesSimpanRM.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === "success") {

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Rekam medis berhasil dibuat',
                confirmButtonText: 'Lanjut Ambil Antrian'
            }).then(() => {

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../cekNoka/ambilAntrian.php';

                for (const key in data) {
                    if (key !== "status") {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = data[key];
                        form.appendChild(input);
                    }
                }

                document.body.appendChild(form);
                form.submit();
            });

        } else {

            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: data.message
            });

        }

    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Terjadi kesalahan sistem'
        });
        console.error(error);
    });

});

});


