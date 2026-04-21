const infoBox = document.getElementById('infoBox');
const nikField = document.getElementById('nik');
const nokaField = document.getElementById('noka');
const patientSection = document.getElementById('sectionPasien');
const poliSection = document.getElementById('sectionPoli');
const doctorSection = document.getElementById('sectionDokter');
const noRmField = document.getElementById('no_rkm_medis');
const patientNameField = document.getElementById('nm_pasien');
const birthDateField = document.getElementById('tgl_lahir');
const genderField = document.getElementById('jk');
const participantField = document.getElementById('no_peserta');
const poliSelect = document.getElementById('kd_poli');
const doctorSelect = document.getElementById('kd_dokter');
const saveButton = document.getElementById('btnSimpan');

const BPJS_SKRINING_URL = 'https://webskrining.bpjs-kesehatan.go.id/skrining';
const POLLING_INTERVAL_MS = 5000;

const screeningState = {
    overlayElement: null,
    infoElement: null,
    hintElement: null,
    pollTimer: null,
    active: false,
    overlayOpen: false,
    overlayDismissed: false,
    popupWindow: null,
    popupAutoAttempted: false,
};

document.addEventListener('DOMContentLoaded', () => {
    createScreeningOverlay();
    initializeQueuePage();
});

saveButton?.addEventListener('click', async () => {
    await saveRegistration();
});

poliSelect?.addEventListener('change', async () => {
    const kdPoli = poliSelect.value;
    saveButton.classList.add('d-none');

    if (!kdPoli) {
        doctorSection.classList.add('d-none');
        doctorSelect.innerHTML = '<option value="">Pilih dokter setelah memilih poli</option>';
        return;
    }

    await loadDoctors(kdPoli);
});

doctorSelect?.addEventListener('change', () => {
    if (doctorSelect.value) {
        saveButton.classList.remove('d-none');
    } else {
        saveButton.classList.add('d-none');
    }
});

function queueEscape(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function queueSafe(value, fallback = '-') {
    const text = String(value ?? '').trim();
    return text !== '' ? text : fallback;
}

function queueFormatDate(value) {
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

function queueGenderLabel(value) {
    const normalized = String(value ?? '').trim().toUpperCase();

    if (normalized === 'L') {
        return 'Laki-laki';
    }

    if (normalized === 'P') {
        return 'Perempuan';
    }

    return '-';
}

function setInfoState(type, html) {
    infoBox.className = `state-banner ${type} mb-4`;
    infoBox.innerHTML = html;
}

function submitQueueForm(action, fields) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;

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

function buildBpjsPayload() {
    return {
        nik: bpjsData?.noKTP || '',
        noka: bpjsData?.noKartu || '',
        nama: bpjsData?.nama || '',
        tgl_lahir: bpjsData?.tglLahir || '',
        jk: bpjsData?.jenisKelamin || bpjsData?.sex || '',
        alamat: bpjsData?.alamat || '',
        no_hp: bpjsData?.noHP || '',
    };
}

function createScreeningOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'screening-overlay';
    overlay.innerHTML = `
        <div class="screening-shell">
            <div class="screening-sidebar">
                <div>
                    <div class="eyebrow mb-3">
                        <span class="dot"></span>
                        Skrining BPJS
                    </div>
                    <h3 class="panel-title mb-2">Lengkapi skrining sebelum antrean ditampilkan.</h3>
                    <p class="panel-subtitle mb-0">
                        Situs skrining BPJS tidak mengizinkan ditampilkan di dalam frame aplikasi. Karena itu skrining harus dibuka
                        lewat jendela terpisah, sementara status tetap dipantau otomatis dari halaman ini.
                    </p>
                </div>

                <div class="screening-status-note" id="screeningInfo">
                    <span class="screening-pulse"></span>
                    Menunggu hasil pemeriksaan skrining dari service BPJS.
                </div>

                <div class="screening-copy-group">
                    <div class="screening-copy-card">
                        <div>
                            <strong>NIK</strong>
                            <span id="screeningNik">-</span>
                        </div>
                        <button type="button" class="btn btn-anj-secondary btn-sm" data-copy-target="nik">Salin</button>
                    </div>
                    <div class="screening-copy-card">
                        <div>
                            <strong>No. BPJS</strong>
                            <span id="screeningNoka">-</span>
                        </div>
                        <button type="button" class="btn btn-anj-secondary btn-sm" data-copy-target="noka">Salin</button>
                    </div>
                    <div class="screening-copy-card">
                        <div>
                            <strong>Tanggal Lahir</strong>
                            <span id="screeningDob">-</span>
                        </div>
                        <button type="button" class="btn btn-anj-secondary btn-sm" data-copy-target="dob">Salin</button>
                    </div>
                </div>

                <div class="screening-toolbar">
                    <button type="button" class="btn btn-anj-primary flex-fill" id="screeningOpenBtn">
                        Buka Jendela Skrining
                    </button>
                    <button type="button" class="btn btn-anj-secondary flex-fill" id="screeningCopyNikBtn">
                        Salin NIK
                    </button>
                    <button type="button" class="btn btn-anj-secondary flex-fill" id="screeningCloseBtn">
                        Tutup Overlay
                    </button>
                </div>
            </div>

            <div class="screening-guide">
                <div class="screening-guide-card">
                    <div class="service-kicker">Alur Skrining</div>
                    <h4 class="panel-title mb-2">Selesaikan skrining di jendela BPJS, lalu sistem akan cek ulang otomatis.</h4>
                    <div class="panel-subtitle mb-0">
                        Langkah yang disarankan:
                        <br>1. Tekan tombol <strong>Buka Jendela Skrining</strong>.
                        <br>2. Tempel NIK atau nomor BPJS bila diperlukan.
                        <br>3. Selesaikan captcha dan isi skrining sampai selesai.
                        <br>4. Tidak perlu kembali menekan apa pun di halaman ini, karena status akan dipantau otomatis.
                    </div>
                </div>

                <div class="screening-guide-card">
                    <div class="service-kicker">Status Browser</div>
                    <div class="screening-browser-note" id="screeningBrowserHint">
                        Sistem akan mencoba membuka jendela skrining secara otomatis. Jika browser memblokir popup, gunakan tombol buka manual.
                    </div>
                </div>

                <div class="screening-guide-card">
                    <div class="service-kicker">Catatan Teknis</div>
                    <div class="panel-subtitle mb-0">
                        BPJS mengirim header keamanan seperti <code>X-Frame-Options: sameorigin</code> dan kebijakan cookie lintas situs,
                        jadi pemuatan di dalam iframe aplikasi memang ditolak oleh browser.
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    screeningState.overlayElement = overlay;
    screeningState.infoElement = overlay.querySelector('#screeningInfo');
    screeningState.hintElement = overlay.querySelector('#screeningBrowserHint');

    overlay.querySelector('#screeningCloseBtn')?.addEventListener('click', () => {
        hideScreeningOverlay(true);
        setInfoState('warning', 'Skrining BPJS masih menunggu selesai. Sistem tetap mengecek status secara otomatis di background.');
    });

    overlay.querySelector('#screeningOpenBtn')?.addEventListener('click', () => {
        openScreeningPopup(true);
    });

    overlay.querySelector('#screeningCopyNikBtn')?.addEventListener('click', async (event) => {
        await copyScreeningValue(
            overlay.querySelector('#screeningNik')?.textContent || '',
            event.currentTarget
        );
    });

    overlay.querySelectorAll('[data-copy-target]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = button.getAttribute('data-copy-target');
            const lookup = {
                nik: overlay.querySelector('#screeningNik')?.textContent || '',
                noka: overlay.querySelector('#screeningNoka')?.textContent || '',
                dob: overlay.querySelector('#screeningDob')?.textContent || '',
            };

            const value = lookup[target] || '';
            await copyScreeningValue(value, button);
        });
    });
}

function showScreeningOverlay(queueData) {
    if (!screeningState.overlayElement) {
        return;
    }

    screeningState.overlayElement.querySelector('#screeningNik').textContent = queueSafe(queueData.no_ktp);
    screeningState.overlayElement.querySelector('#screeningNoka').textContent = queueSafe(queueData.no_peserta);
    screeningState.overlayElement.querySelector('#screeningDob').textContent = queueSafe(queueData.tgl_lahir);

    screeningState.overlayElement.classList.add('is-visible');
    screeningState.overlayOpen = true;
    screeningState.overlayDismissed = false;
}

function hideScreeningOverlay(manualClose = false) {
    screeningState.overlayElement?.classList.remove('is-visible');
    screeningState.overlayOpen = false;
    screeningState.overlayDismissed = manualClose ? true : screeningState.overlayDismissed;
}

function updateScreeningBrowserHint(message) {
    if (!screeningState.hintElement) {
        return;
    }

    screeningState.hintElement.textContent = message;
}

function popupFeatures() {
    const width = 1180;
    const height = 860;
    const left = Math.max(0, Math.round((window.screen.width - width) / 2));
    const top = Math.max(0, Math.round((window.screen.height - height) / 2));

    return `popup=yes,width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`;
}

function openScreeningPopup(fromUserAction = false) {
    try {
        if (screeningState.popupWindow && !screeningState.popupWindow.closed) {
            screeningState.popupWindow.focus();
            updateScreeningBrowserHint('Jendela skrining sudah terbuka. Silakan lanjutkan proses di jendela tersebut.');
            return true;
        }

        const popup = window.open(BPJS_SKRINING_URL, 'bpjs_skrining_popup', popupFeatures());

        if (!popup) {
            updateScreeningBrowserHint(
                fromUserAction
                    ? 'Browser masih memblokir popup. Izinkan popup untuk situs ini lalu coba lagi.'
                    : 'Browser memblokir pembukaan otomatis. Tekan tombol Buka Jendela Skrining.'
            );
            return false;
        }

        screeningState.popupWindow = popup;
        popup.focus();
        updateScreeningBrowserHint('Jendela skrining berhasil dibuka. Setelah selesai, sistem akan mendeteksi status Ok secara otomatis.');
        return true;
    } catch (error) {
        updateScreeningBrowserHint('Jendela skrining tidak dapat dibuka dari browser ini. Coba tekan tombol buka manual.');
        return false;
    }
}

async function copyScreeningValue(value, button) {
    if (!value || value === '-') {
        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        const originalText = button.textContent;
        button.textContent = 'Tersalin';

        setTimeout(() => {
            button.textContent = originalText;
        }, 1400);
    } catch (error) {
        Swal.fire({
            icon: 'warning',
            title: 'Gagal menyalin',
            text: 'Browser tidak mengizinkan akses clipboard di perangkat ini.',
            confirmButtonColor: '#22c7b8',
        });
    }
}

function showScreeningProgress(title, text) {
    Swal.fire({
        title,
        html: `
            <div class="d-flex flex-column align-items-center gap-3 py-2">
                <div class="spinner-border text-info" style="width:3rem;height:3rem;" role="status" aria-hidden="true"></div>
                <div class="text-center">
                    <div class="fw-semibold">${queueEscape(text)}</div>
                    <div class="text-muted small mt-2">Service BPJS dicek setiap beberapa detik.</div>
                </div>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
    });
}

function updateScreeningOverlayMessage(message) {
    if (!screeningState.infoElement) {
        return;
    }

    screeningState.infoElement.innerHTML = `
        <span class="screening-pulse"></span>
        ${queueEscape(message)}
    `;
}

function stopScreeningPolling() {
    if (screeningState.pollTimer) {
        clearTimeout(screeningState.pollTimer);
    }

    screeningState.pollTimer = null;
    screeningState.active = false;
}

function scheduleScreeningPoll(queueData) {
    screeningState.pollTimer = window.setTimeout(() => {
        void pollBpjsScreening(queueData);
    }, POLLING_INTERVAL_MS);
}

async function pollBpjsScreening(queueData) {
    if (!screeningState.active) {
        return;
    }

    try {
        const response = await fetch(
            `/anjungan/ajax/cariPasien.php?mode=cek_status_antrean_bpjs&no_rawat=${encodeURIComponent(queueData.no_rawat)}`
        );
        const data = await response.json();

        if (!data.status) {
            throw new Error(data.message || 'Status skrining tidak dapat dibaca');
        }

        if (data.state === 'waiting') {
            showScreeningProgress('Pengecekan skrining BPJS', data.message || 'Menunggu service BPJS memproses antrean');
            updateScreeningOverlayMessage(data.message || 'Menunggu service BPJS memproses antrean');
            scheduleScreeningPoll(queueData);
            return;
        }

        if (data.state === 'screening_required') {
            Swal.close();
            if (!screeningState.overlayDismissed) {
                showScreeningOverlay(queueData);
                updateScreeningOverlayMessage(
                    'Peserta belum skrining. Buka jendela skrining BPJS, selesaikan prosesnya, lalu sistem akan mengecek ulang otomatis.'
                );

                if (!screeningState.popupAutoAttempted) {
                    screeningState.popupAutoAttempted = true;
                    openScreeningPopup(false);
                }
            } else {
                setInfoState('warning', 'Peserta belum skrining. Selesaikan skrining BPJS pada tab yang dibuka, lalu sistem akan mengecek ulang otomatis.');
            }
            scheduleScreeningPoll(queueData);
            return;
        }

        if (data.state === 'completed') {
            stopScreeningPolling();
            hideScreeningOverlay();
            Swal.close();
            showSuccessTicket(queueData);
            return;
        }

        stopScreeningPolling();
        hideScreeningOverlay();
        Swal.close();

        Swal.fire({
            icon: 'error',
            title: 'Sinkron BPJS belum berhasil',
            html: `
                <div class="text-start">
                    <div class="mb-3">Registrasi poli sudah tersimpan, tetapi status antrean BPJS belum berhasil.</div>
                    <div class="list-clean text-start">
                        <div class="list-row">
                            <span>No. Rawat</span>
                            <span>${queueEscape(queueData.no_rawat)}</span>
                        </div>
                        <div class="list-row">
                            <span>Pesan</span>
                            <span>${queueEscape(data.message || 'Tidak ada pesan')}</span>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Kembali',
            confirmButtonColor: '#d85d4f',
            allowOutsideClick: false,
        }).then(() => {
            window.location.href = '/anjungan/';
        });
    } catch (error) {
        scheduleScreeningPoll(queueData);
    }
}

function showSuccessTicket(data) {
    if (screeningState.popupWindow && !screeningState.popupWindow.closed) {
        screeningState.popupWindow.close();
    }

    Swal.fire({
        icon: 'success',
        title: 'Antrean berhasil dibuat',
        html: `
            <div class="text-start">
                <div class="text-center mb-4">
                    <div class="fw-bold fs-4">${queueEscape(data.no_reg)}</div>
                    <div>${queueEscape(data.nm_poli)}</div>
                    <div>${queueEscape(data.nm_dokter)}</div>
                </div>
                <div class="list-clean text-start">
                    <div class="list-row">
                        <span>Tanggal</span>
                        <span>${queueEscape(data.tanggal)}</span>
                    </div>
                    <div class="list-row">
                        <span>Pasien</span>
                        <span>${queueEscape(data.nm_pasien)}</span>
                    </div>
                    <div class="list-row">
                        <span>No. Rekam Medis</span>
                        <span>${queueEscape(data.no_rkm_medis)}</span>
                    </div>
                    <div class="list-row">
                        <span>Tanggal Lahir</span>
                        <span>${queueEscape(data.tgl_lahir)}</span>
                    </div>
                    <div class="list-row">
                        <span>Jenis Kelamin</span>
                        <span>${queueEscape(data.jk)}</span>
                    </div>
                </div>
            </div>
        `,
        confirmButtonText: 'Selesai',
        confirmButtonColor: '#22c7b8',
        allowOutsideClick: false,
    }).then(() => {
        window.location.href = '/anjungan/';
    });
}

async function startScreeningFlow(queueData) {
    screeningState.active = true;
    screeningState.overlayDismissed = false;
    screeningState.popupAutoAttempted = false;
    hideScreeningOverlay(false);
    showScreeningProgress('Pengecekan skrining BPJS', 'Menunggu service BPJS membaca antrean yang baru dibuat');
    await pollBpjsScreening(queueData);
}

async function initializeQueuePage() {
    const nik = nikField?.value.trim() || '';
    const noka = nokaField?.value.trim() || '';

    if (!nik && !noka) {
        setInfoState('danger', 'Data identitas pasien tidak tersedia.');
        return;
    }

    setInfoState('info', 'Memeriksa data pasien...');
    await searchPatient(nik, noka);
}

async function searchPatient(nik, noka) {
    try {
        const response = await fetch('/anjungan/ajax/cariPasien.php?mode=cari_pasien', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ nik, noka }),
        });

        const data = await response.json();

        if (!data.status) {
            if (data.not_registered) {
                renderRegistrationPrompt(data.message || 'Pasien belum memiliki rekam medis.');
                return;
            }

            setInfoState('danger', queueEscape(data.message || 'Data pasien tidak dapat diproses.'));
            return;
        }

        populatePatient(data.data);
        setInfoState('success', 'Data pasien ditemukan. Pilih poli dan dokter.');
        await loadPoli();
    } catch (error) {
        setInfoState('danger', 'Gagal menghubungi server.');
    }
}

function renderRegistrationPrompt(message) {
    patientSection.classList.add('d-none');
    poliSection.classList.add('d-none');
    doctorSection.classList.add('d-none');
    saveButton.classList.add('d-none');

    setInfoState(
        'warning',
        `
            <div class="d-flex flex-column gap-3">
                <div class="fw-semibold fs-5">${queueEscape(message)}</div>
                <div>Lengkapi rekam medis terlebih dahulu.</div>
                <div>
                    <button id="btnRegistrasiRM" type="button" class="btn btn-anj-secondary">
                        <i class="bi bi-file-earmark-plus me-2"></i>
                        Lengkapi Rekam Medis
                    </button>
                </div>
            </div>
        `
    );

    document.getElementById('btnRegistrasiRM')?.addEventListener('click', () => {
        submitQueueForm('/anjungan/php/cekNoka/registrasiRM.php', buildBpjsPayload());
    });
}

function populatePatient(patient) {
    patientSection.classList.remove('d-none');
    poliSection.classList.remove('d-none');

    noRmField.value = queueSafe(patient.no_rkm_medis, '');
    patientNameField.value = queueSafe(patient.nm_pasien, '');
    birthDateField.value = queueFormatDate(patient.tgl_lahir);
    genderField.value = queueGenderLabel(patient.jk);
    participantField.value = queueSafe(patient.no_peserta, '');
}

async function loadPoli() {
    poliSelect.innerHTML = '<option value="">Memuat data poli...</option>';

    try {
        const response = await fetch('/anjungan/ajax/cariPasien.php?mode=load_poli');
        const data = await response.json();

        if (!data.status) {
            throw new Error(data.message || 'Gagal memuat poli');
        }

        poliSelect.innerHTML = '<option value="">Pilih poliklinik</option>';

        (data.data || []).forEach((item) => {
            const option = document.createElement('option');
            option.value = item.kd_poli;
            option.textContent = item.nm_poli;
            poliSelect.appendChild(option);
        });
    } catch (error) {
        poliSelect.innerHTML = '<option value="">Poli tidak dapat dimuat</option>';
        setInfoState('danger', 'Daftar poli tidak dapat dimuat.');
    }
}

async function loadDoctors(kdPoli) {
    doctorSection.classList.remove('d-none');
    doctorSelect.innerHTML = '<option value="">Memuat dokter...</option>';

    try {
        const response = await fetch(`/anjungan/ajax/cariPasien.php?mode=load_dokter&kd_poli=${encodeURIComponent(kdPoli)}`);
        const data = await response.json();

        if (!data.status) {
            throw new Error(data.message || 'Gagal memuat dokter');
        }

        const doctors = data.data || [];

        if (!doctors.length) {
            doctorSelect.innerHTML = '<option value="">Tidak ada dokter tersedia hari ini</option>';
            setInfoState('warning', 'Belum ada dokter untuk poli ini.');
            return;
        }

        doctorSelect.innerHTML = '<option value="">Pilih dokter</option>';

        doctors.forEach((doctor) => {
            const option = document.createElement('option');
            option.value = doctor.kd_dokter;
            option.textContent = `${doctor.nm_dokter} | ${doctor.jam_mulai} - ${doctor.jam_selesai} | Kuota ${doctor.kuota}`;
            doctorSelect.appendChild(option);
        });

        setInfoState('info', `Dokter dimuat. Hari: ${queueEscape(data.hari || '-')}.`);
    } catch (error) {
        doctorSelect.innerHTML = '<option value="">Dokter tidak dapat dimuat</option>';
        setInfoState('danger', 'Daftar dokter tidak dapat dimuat.');
    }
}

async function saveRegistration() {
    const kdPoli = poliSelect.value;
    const kdDokter = doctorSelect.value;
    const noRm = noRmField.value.trim();
    const tglLahir = birthDateField.value.trim();

    if (!kdPoli || !kdDokter) {
        Swal.fire({
            icon: 'warning',
            title: 'Data belum lengkap',
            text: 'Pilih poli dan dokter terlebih dahulu.',
            confirmButtonColor: '#22c7b8',
        });
        return;
    }

    Swal.fire({
        title: 'Menerbitkan antrean',
        text: 'Menyimpan registrasi poli.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    try {
        const response = await fetch('/anjungan/ajax/cariPasien.php?mode=simpan_registrasi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                no_rkm_medis: noRm,
                kd_poli: kdPoli,
                kd_dokter: kdDokter,
                tgl_lahir: tglLahir,
            }),
        });

        const data = await response.json();

        if (data.status !== 'success') {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Gagal mengambil antrean',
                text: data.message || 'Registrasi poli gagal.',
                confirmButtonColor: '#d85d4f',
            });
            return;
        }

        await startScreeningFlow(data);
    } catch (error) {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Server tidak merespons',
            text: 'Silakan coba lagi.',
            confirmButtonColor: '#d85d4f',
        });
    }
}
