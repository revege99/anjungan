const bpjsForm = document.getElementById('bpjsForm');
const jenisField = document.getElementById('jenis');
const nomorField = document.getElementById('nomor');
const hasilBox = document.getElementById('hasil');

const bpjsState = {
    participant: null,
};

if (bpjsForm) {
    bpjsForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        await cekPeserta();
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function safeText(value, fallback = '-') {
    const text = String(value ?? '').trim();
    return text !== '' ? text : fallback;
}

function formatDate(value) {
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

function providerName(participant) {
    return (
        participant?.kdProviderPst?.nmProvider ||
        participant?.provUmum?.kdProviderPst?.nmProvider ||
        participant?.faskes?.nama ||
        ''
    );
}

function normalizeParticipant(rawParticipant) {
    const participant = rawParticipant || {};
    const gender = String(participant.jenisKelamin || participant.sex || '').trim().toUpperCase();
    const active = typeof participant.aktif === 'boolean'
        ? participant.aktif
        : ['true', '1', 'ya', 'aktif'].includes(String(participant.aktif).trim().toLowerCase());

    return {
        noKTP: safeText(participant.noKTP, ''),
        noKartu: safeText(participant.noKartu, ''),
        nama: safeText(participant.nama, ''),
        tglLahir: safeText(participant.tglLahir, ''),
        jenisKelamin: gender === 'L' || gender === 'P' ? gender : '',
        aktif: active,
        tunggakan: safeText(participant.tunggakan),
        fktp: safeText(providerName(participant)),
        alamat: safeText(participant.alamat, ''),
        noHP: safeText(participant.noHP, ''),
    };
}

function renderNotice(type, title, description) {
    hasilBox.hidden = false;

    const chipClass = type === 'success' ? 'success' : 'danger';

    hasilBox.innerHTML = `
        <div class="d-flex flex-column gap-3">
            <div>
                <span class="status-chip ${chipClass}">${escapeHtml(title)}</span>
            </div>
            <div class="fs-5 fw-semibold">${escapeHtml(description)}</div>
        </div>
    `;
}

function renderLoading() {
    hasilBox.hidden = false;
    hasilBox.innerHTML = `
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-border text-info" role="status" aria-hidden="true"></div>
            <div>
                <div class="fw-semibold fs-5">Memeriksa data peserta</div>
                <div class="text-muted">Sistem sedang menghubungi layanan BPJS.</div>
            </div>
        </div>
    `;
}

function renderParticipant(participant) {
    hasilBox.hidden = false;

    const statusClass = participant.aktif ? 'success' : 'danger';
    const statusText = participant.aktif ? 'Peserta Aktif' : 'Peserta Tidak Aktif';
    const actionButton = participant.aktif
        ? `
            <button id="btnAntrian" type="button" class="btn btn-anj-primary mt-4">
                <i class="bi bi-arrow-right-circle me-2"></i>
                Lanjut Ambil Antrean
            </button>
        `
        : `
            <button type="button" class="btn btn-anj-ghost mt-4" disabled>
                Antrean hanya tersedia untuk peserta aktif
            </button>
        `;

    hasilBox.innerHTML = `
        <div class="d-flex flex-column gap-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <span class="status-chip ${statusClass}">${escapeHtml(statusText)}</span>
                </div>
                <div class="foot-note">Pastikan data sesuai.</div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="section-title">Identitas Peserta</div>
                    <div class="list-clean">
                        <div class="list-row">
                            <span>Nama</span>
                            <span>${escapeHtml(safeText(participant.nama))}</span>
                        </div>
                        <div class="list-row">
                            <span>No. Kartu BPJS</span>
                            <span>${escapeHtml(safeText(participant.noKartu))}</span>
                        </div>
                        <div class="list-row">
                            <span>NIK</span>
                            <span>${escapeHtml(safeText(participant.noKTP))}</span>
                        </div>
                        <div class="list-row">
                            <span>Tanggal Lahir</span>
                            <span>${escapeHtml(formatDate(participant.tglLahir))}</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="section-title">Informasi Kepesertaan</div>
                    <div class="list-clean">
                        <div class="list-row">
                            <span>Status</span>
                            <span>${escapeHtml(statusText)}</span>
                        </div>
                        <div class="list-row">
                            <span>Tunggakan</span>
                            <span>${escapeHtml(participant.tunggakan)}</span>
                        </div>
                        <div class="list-row">
                            <span>FKTP</span>
                            <span>${escapeHtml(participant.fktp)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>${actionButton}</div>
        </div>
    `;

    if (participant.aktif) {
        document.getElementById('btnAntrian')?.addEventListener('click', () => {
            submitHiddenForm('/anjungan/php/cekNoka/ambilAntrian.php', {
                nik: participant.noKTP,
                noka: participant.noKartu,
                nama: participant.nama,
                tgl_lahir: participant.tglLahir,
                jk: participant.jenisKelamin,
                alamat: participant.alamat,
                no_hp: participant.noHP,
            });
        });
    }
}

function submitHiddenForm(action, fields) {
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

async function cekPeserta() {
    const jenis = jenisField.value.trim();
    const nomor = nomorField.value.trim();

    if (!jenis || !nomor) {
        renderNotice('danger', 'Data belum lengkap', 'Pilih jenis pencarian dan isi nomor.');
        return;
    }

    renderLoading();

    try {
        const response = await fetch(
            `/anjungan/php/cekNoka/bpjs_peserta.php?jenis=${encodeURIComponent(jenis)}&nomor=${encodeURIComponent(nomor)}`
        );
        const data = await response.json();

        if (!data.status) {
            renderNotice('danger', 'Pemeriksaan gagal', data.message || 'Data tidak ditemukan.');
            return;
        }

        bpjsState.participant = normalizeParticipant(data.data);
        renderParticipant(bpjsState.participant);
    } catch (error) {
        renderNotice('danger', 'Server tidak merespons', 'Silakan coba lagi.');
    }
}
