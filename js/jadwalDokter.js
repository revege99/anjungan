const jadwalBody = document.getElementById('jadwalBody');
const hariText = document.getElementById('hariText');
const dayFilter = document.getElementById('dayFilter');

const scheduleDays = ['SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD'];
let activeDay = mapTodayToDayName();

renderDayButtons();
loadSchedule(activeDay);

function scheduleEscape(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function mapTodayToDayName() {
    const map = ['AKHAD', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'];
    return map[new Date().getDay()] || 'SENIN';
}

function renderDayButtons() {
    dayFilter.innerHTML = scheduleDays
        .map((day) => `
            <button type="button" data-day="${day}" class="${day === activeDay ? 'active' : ''}">
                ${scheduleEscape(day)}
            </button>
        `)
        .join('');

    dayFilter.querySelectorAll('button').forEach((button) => {
        button.addEventListener('click', () => {
            activeDay = button.dataset.day || 'SENIN';
            renderDayButtons();
            loadSchedule(activeDay);
        });
    });
}

function renderScheduleRows(items) {
    if (!items.length) {
        jadwalBody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-state">Tidak ada jadwal dokter untuk hari ini.</td>
            </tr>
        `;
        return;
    }

    jadwalBody.innerHTML = items.map((item) => {
        let quotaClass = 'good';
        let quotaLabel = 'Tersedia';

        if (item.kuota <= 0) {
            quotaClass = 'full';
            quotaLabel = 'Penuh';
        } else if (item.kuota <= 5) {
            quotaClass = 'low';
            quotaLabel = 'Terbatas';
        }

        return `
            <tr>
                <td>
                    <div class="fw-semibold">${scheduleEscape(item.jam_mulai)} - ${scheduleEscape(item.jam_selesai)}</div>
                </td>
                <td>${scheduleEscape(item.poli)}</td>
                <td>${scheduleEscape(item.dokter)}</td>
                <td>
                    <span class="quota-badge ${quotaClass}">
                        ${scheduleEscape(String(item.kuota))} | ${scheduleEscape(quotaLabel)}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

async function loadSchedule(day) {
    hariText.textContent = `Memuat ${day}...`;
    jadwalBody.innerHTML = `
        <tr>
            <td colspan="4" class="empty-state">Memuat jadwal dokter...</td>
        </tr>
    `;

    try {
        const response = await fetch(`/anjungan/ajax/getJadwalDokter.php?hari=${encodeURIComponent(day)}`);
        const data = await response.json();

        if (!data.status) {
            throw new Error(data.message || 'Gagal memuat jadwal');
        }

        hariText.textContent = data.hari;
        renderScheduleRows(data.data || []);
    } catch (error) {
        hariText.textContent = 'Jadwal tidak tersedia';
        jadwalBody.innerHTML = `
            <tr>
                <td colspan="4" class="empty-state">Gagal memuat jadwal dokter. Silakan coba lagi.</td>
            </tr>
        `;
    }
}
