document.addEventListener('DOMContentLoaded', () => {
    const BASE_API_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    const provinsiSelect = document.getElementById('kd_prop');
    const kabupatenSelect = document.getElementById('kd_kab');
    const kecamatanSelect = document.getElementById('kd_kec');
    const kelurahanSelect = document.getElementById('kd_kel');

    const hiddenNames = {
        propinsi: document.getElementById('nm_prop'),
        kabupaten: document.getElementById('nm_kab'),
        kecamatan: document.getElementById('nm_kec'),
        kelurahan: document.getElementById('nm_kel'),
    };

    const hiddenCodes = {
        propinsi: document.getElementById('db_kd_prop'),
        kabupaten: document.getElementById('db_kd_kab'),
        kecamatan: document.getElementById('db_kd_kec'),
        kelurahan: document.getElementById('db_kd_kel'),
    };

    const form = document.getElementById('formRM');

    initializeProvinceOptions();

    provinsiSelect.addEventListener('change', async () => {
        await handleRegionChange({
            select: provinsiSelect,
            level: 'propinsi',
            childReset: [
                { select: kabupatenSelect, text: 'Memuat kabupaten...' },
                { select: kecamatanSelect, text: 'Pilih kabupaten terlebih dahulu', disabled: true },
                { select: kelurahanSelect, text: 'Pilih kecamatan terlebih dahulu', disabled: true },
            ],
            targetUrl: `${BASE_API_URL}/regencies/${provinsiSelect.value}.json`,
            targetSelect: kabupatenSelect,
            placeholder: 'Pilih kabupaten / kota',
        });
    });

    kabupatenSelect.addEventListener('change', async () => {
        await handleRegionChange({
            select: kabupatenSelect,
            level: 'kabupaten',
            childReset: [
                { select: kecamatanSelect, text: 'Memuat kecamatan...' },
                { select: kelurahanSelect, text: 'Pilih kecamatan terlebih dahulu', disabled: true },
            ],
            targetUrl: `${BASE_API_URL}/districts/${kabupatenSelect.value}.json`,
            targetSelect: kecamatanSelect,
            placeholder: 'Pilih kecamatan',
        });
    });

    kecamatanSelect.addEventListener('change', async () => {
        await handleRegionChange({
            select: kecamatanSelect,
            level: 'kecamatan',
            childReset: [
                { select: kelurahanSelect, text: 'Memuat kelurahan...' },
            ],
            targetUrl: `${BASE_API_URL}/villages/${kecamatanSelect.value}.json`,
            targetSelect: kelurahanSelect,
            placeholder: 'Pilih kelurahan',
        });
    });

    kelurahanSelect.addEventListener('change', async () => {
        const optionText = kelurahanSelect.options[kelurahanSelect.selectedIndex]?.text || '';

        hiddenNames.kelurahan.value = optionText;
        hiddenCodes.kelurahan.value = await lookupRegionCode('kelurahan', optionText);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        await submitMedicalRecord(form);
    });

    function resetSelect(select, text, disabled = true) {
        select.innerHTML = `<option value="">${text}</option>`;
        select.disabled = disabled;
    }

    function populateSelect(select, items, placeholder) {
        select.innerHTML = `<option value="">${placeholder}</option>`;

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            select.appendChild(option);
        });

        select.disabled = false;
    }

    function clearChildRegionState(fromLevel) {
        if (fromLevel === 'propinsi') {
            hiddenCodes.kabupaten.value = '';
            hiddenCodes.kecamatan.value = '';
            hiddenCodes.kelurahan.value = '';
            hiddenNames.kabupaten.value = '';
            hiddenNames.kecamatan.value = '';
            hiddenNames.kelurahan.value = '';
        }

        if (fromLevel === 'kabupaten') {
            hiddenCodes.kecamatan.value = '';
            hiddenCodes.kelurahan.value = '';
            hiddenNames.kecamatan.value = '';
            hiddenNames.kelurahan.value = '';
        }

        if (fromLevel === 'kecamatan') {
            hiddenCodes.kelurahan.value = '';
            hiddenNames.kelurahan.value = '';
        }
    }

    async function initializeProvinceOptions() {
        try {
            const response = await fetch(`${BASE_API_URL}/provinces.json`);
            const items = await response.json();
            populateSelect(provinsiSelect, items, 'Pilih provinsi');
        } catch (error) {
            resetSelect(provinsiSelect, 'Provinsi tidak dapat dimuat');
        }
    }

    async function handleRegionChange({ select, level, childReset, targetUrl, targetSelect, placeholder }) {
        if (!select.value) {
            hiddenNames[level].value = '';
            hiddenCodes[level].value = '';
            clearChildRegionState(level);
            return;
        }

        const optionText = select.options[select.selectedIndex]?.text || '';

        hiddenNames[level].value = optionText;
        hiddenCodes[level].value = await lookupRegionCode(level, optionText);
        clearChildRegionState(level);

        childReset.forEach((item) => {
            resetSelect(item.select, item.text, item.disabled ?? false);
        });

        try {
            const response = await fetch(targetUrl);
            const items = await response.json();
            populateSelect(targetSelect, items, placeholder);
        } catch (error) {
            resetSelect(targetSelect, 'Data wilayah tidak dapat dimuat');
        }
    }

    async function lookupRegionCode(level, nama) {
        if (!nama) {
            return 0;
        }

        try {
            const response = await fetch('/anjungan/ajax/lookup_wilayah.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ level, nama }),
            });
            const data = await response.json();
            return data.kd || 0;
        } catch (error) {
            return 0;
        }
    }

    async function submitMedicalRecord(currentForm) {
        const phone = currentForm.no_hp.value.trim();

        if (!/^08[0-9]{8,13}$/.test(phone)) {
            Swal.fire({
                icon: 'error',
                title: 'Nomor HP tidak valid',
                text: 'Gunakan format 08xxxxxxxxxx.',
                confirmButtonColor: '#d85d4f',
            });
            return;
        }

        if (!hiddenCodes.propinsi.value || !hiddenCodes.kabupaten.value || !hiddenCodes.kecamatan.value || !hiddenCodes.kelurahan.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Wilayah belum lengkap',
                text: 'Silakan pilih provinsi sampai kelurahan terlebih dahulu.',
                confirmButtonColor: '#22c7b8',
            });
            return;
        }

        Swal.fire({
            title: 'Menyimpan rekam medis',
            text: 'Sistem sedang membuat nomor rekam medis pasien.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            },
        });

        try {
            const response = await fetch('/anjungan/ajax/prosesSimpanRM.php', {
                method: 'POST',
                body: new FormData(currentForm),
            });
            const data = await response.json();
            Swal.close();

            if (data.status !== 'success') {
                Swal.fire({
                    icon: 'error',
                    title: 'Rekam medis gagal dibuat',
                    text: data.message || 'Terjadi kesalahan sistem.',
                    confirmButtonColor: '#d85d4f',
                });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Rekam medis berhasil dibuat',
                text: 'Pasien akan diarahkan kembali ke halaman pengambilan antrean.',
                confirmButtonColor: '#22c7b8',
            }).then(() => {
                const formForward = document.createElement('form');
                formForward.method = 'POST';
                formForward.action = '/anjungan/php/cekNoka/ambilAntrian.php';

                Object.entries(data).forEach(([key, value]) => {
                    if (key === 'status') {
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value ?? '';
                    formForward.appendChild(input);
                });

                document.body.appendChild(formForward);
                formForward.submit();
            });
        } catch (error) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Server tidak merespons',
                text: 'Silakan coba kembali beberapa saat lagi.',
                confirmButtonColor: '#d85d4f',
            });
        }
    }
});
