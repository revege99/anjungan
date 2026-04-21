const lookupForm = document.getElementById('lookupForm');
const nomorLookup = document.getElementById('nomor');
const lookupResultBox = document.getElementById('resultBox');
const lookupResultContent = document.getElementById('resultContent');

if (lookupForm) {
    lookupForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await cariPasienTersimpan();
    });
}

function lookupEscape(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function lookupSafe(value, fallback = '-') {
    const text = String(value ?? '').trim();
    return text !== '' ? text : fallback;
}

function lookupFormatDate(value) {
    const text = String(value ?? '').trim();

    if (!text) {
        return '-';
    }

    const parts = text.split('-');

    if (parts.length === 3) {
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    return text;
}

function showLookupBox(html) {
    lookupResultBox.hidden = false;
    lookupResultContent.innerHTML = html;
}

function showLookupLoading() {
    showLookupBox(`
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-border text-info" role="status" aria-hidden="true"></div>
            <div>
                <div class="fw-semibold fs-5">Mencari pasien</div>
                <div class="text-muted">Mohon tunggu.</div>
            </div>
        </div>
    `);
}

function submitLookupForm(fields) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/anjungan/php/cekNoka/ambilAntrian.php';

    Object.entries(fields).forEach(([key, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value ?? '';
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

function renderStoredPatient(patient) {
    showLookupBox(`
        <div class="d-flex flex-column gap-4">
            <div>
                <span class="status-chip success">Data Pasien Ditemukan</span>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="section-title">Data Identitas</div>
                    <div class="list-clean">
                        <div class="list-row">
                            <span>No. Rekam Medis</span>
                            <span>${lookupEscape(lookupSafe(patient.no_rkm_medis))}</span>
                        </div>
                        <div class="list-row">
                            <span>Nama</span>
                            <span>${lookupEscape(lookupSafe(patient.nm_pasien))}</span>
                        </div>
                        <div class="list-row">
                            <span>NIK</span>
                            <span>${lookupEscape(lookupSafe(patient.no_ktp))}</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="section-title">Data Tambahan</div>
                    <div class="list-clean">
                        <div class="list-row">
                            <span>No. BPJS</span>
                            <span>${lookupEscape(lookupSafe(patient.no_peserta))}</span>
                        </div>
                        <div class="list-row">
                            <span>Tanggal Lahir</span>
                            <span>${lookupEscape(lookupFormatDate(patient.tgl_lahir))}</span>
                        </div>
                        <div class="list-row">
                            <span>Jenis Kelamin</span>
                            <span>${lookupEscape(patient.jk === 'L' ? 'Laki-laki' : 'Perempuan')}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <button id="btnLanjutAntrean" type="button" class="btn btn-anj-primary">
                    <i class="bi bi-arrow-right-circle me-2"></i>
                    Lanjut Pilih Poli dan Dokter
                </button>
            </div>
        </div>
    `);

    document.getElementById('btnLanjutAntrean')?.addEventListener('click', () => {
        submitLookupForm({
            nik: patient.no_ktp,
            noka: patient.no_peserta,
            nama: patient.nm_pasien,
            tgl_lahir: patient.tgl_lahir,
            jk: patient.jk,
            alamat: patient.alamat,
            no_hp: patient.no_tlp,
        });
    });
}

function renderMissingPatient(message) {
    showLookupBox(`
        <div class="d-flex flex-column gap-4">
            <div>
                <span class="status-chip danger">Data Belum Ditemukan</span>
            </div>
            <div class="fs-5 fw-semibold">${lookupEscape(message)}</div>
            <div>
                <a href="/anjungan/php/cekNoka/cekNoka.php" class="btn btn-anj-secondary">
                    <i class="bi bi-arrow-right-circle me-2"></i>
                    Cek BPJS
                </a>
            </div>
        </div>
    `);
}

async function cariPasienTersimpan() {
    const nomor = nomorLookup.value.trim();

    if (!nomor) {
        renderMissingPatient('Nomor identitas belum diisi.');
        return;
    }

    showLookupLoading();

    try {
        const response = await fetch('/anjungan/ajax/ambilAntrian.php?mode=getPasien', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ nomor }),
        });

        const data = await response.json();

        if (!data.status) {
            renderMissingPatient(data.message || 'Data pasien tidak ditemukan.');
            return;
        }

        renderStoredPatient(data.data);
    } catch (error) {
        renderMissingPatient('Gagal memeriksa data pasien.');
    }
}
