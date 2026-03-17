// ================================
// SIMPAN REGISTRASI
// ================================
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
                    window.location.href = '/anjungan/';
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



// ================================
// LOAD DATA SAAT HALAMAN DIBUKA
// ================================
document.addEventListener('DOMContentLoaded', () => {

    const nik  = document.getElementById('nik').value;
    const noka = document.getElementById('noka').value;

    if (!nik && !noka) {

        document.getElementById('infoBox').className='alert alert-danger info-box';
        document.getElementById('infoBox').innerText='Data identitas pasien tidak ditemukan';
        return;

    }

    cariPasien(nik,noka);

});



// ================================
// CARI PASIEN
// ================================
function cariPasien(nik,noka){

fetch('../../ajax/cariPasien.php?mode=cari_pasien',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:new URLSearchParams({nik:nik,noka:noka})
})
.then(res=>res.json())
.then(res=>{

console.log("RESPON SERVER:", res);

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

console.log("BPJS DATA ambil antrian:", bpjsData);

document.getElementById('btnRegistrasiRM').addEventListener('click',function(){

if(!bpjsData){
alert('Data BPJS tidak tersedia');
return;
}

const form = document.createElement('form');
form.method = 'POST';
form.action = 'registrasiRM.php';

const fields = {
    nik: bpjsData.noKTP || '',
    noka: bpjsData.noKartu || '',
    nama: bpjsData.nama || '',
    tgl_lahir: bpjsData.tglLahir || '',
    jk: bpjsData.jenisKelamin || '',
    alamat: bpjsData.alamat || '',
    no_hp: bpjsData.noHP || ''
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

console.log("DATA PASIEN:", p);
console.log("NO RM:", p.no_rkm_medis);

info.className='alert alert-success info-box';
info.innerText='Pasien ditemukan';

document.getElementById('no_rkm_medis').value=p.no_rkm_medis || '';
document.getElementById('nm_pasien').value=p.nm_pasien || '';
document.getElementById('tgl_lahir').value=p.tgl_lahir || '';
document.getElementById('jk').value=p.jk==='L'?'Laki-laki':'Perempuan';
document.getElementById('no_peserta').value=p.no_peserta || '';

document.getElementById('sectionPasien').classList.remove('d-none');
document.getElementById('sectionPoli').classList.remove('d-none');

loadPoli();

})
.catch(err=>{

console.error("ERROR FETCH PASIEN:",err);

const info=document.getElementById('infoBox');
info.className='alert alert-danger info-box';
info.innerText='Gagal menghubungi server';

});

}



// ================================
// LOAD POLI
// ================================
function loadPoli(){

const poli=document.getElementById('kd_poli');

poli.innerHTML=`<option>Loading Poli...</option>`;

fetch(`../../ajax/cariPasien.php?mode=load_poli`)
.then(res=>res.json())
.then(res=>{

console.log("DATA POLI:",res);

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

})
.catch(err=>{
console.error("ERROR LOAD POLI:",err);
});

poli.onchange=function(){
if(this.value){
loadDokter(this.value);
}
};

}



// ================================
// LOAD DOKTER
// ================================
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

console.log("DATA DOKTER:",res);

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
console.error("ERROR LOAD DOKTER:",err);
dokter.innerHTML=`<option>Error Load Dokter</option>`;
});

dokter.onchange=function(){
if(this.value){
btnSimpan.classList.remove('d-none');
}
};

}



// ================================
// DATA BPJS
// ================================
