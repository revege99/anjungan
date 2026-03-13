<?php
require_once '../function/configDB.php';

header('Content-Type: application/json');

$mode = $_GET['mode'] ?? '';

switch ($mode) {

    case 'cari_pasien':

        $nik  = trim($_POST['nik'] ?? '');
        $noka = trim($_POST['noka'] ?? '');
        // var_dump($nik);


        if ($nik === '' && $noka === '') {
            echo json_encode([
                'status' => false,
                'message' => 'NIK atau No Kartu kosong'
            ]);
            exit;
        }

        $sql = "
            SELECT 
                no_rkm_medis,
                nm_pasien,
                tgl_lahir,
                jk,
                no_peserta,
                no_ktp
            FROM pasien
            WHERE no_ktp = ?
               
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $nik);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            echo json_encode([
                'status' => false,
                'not_registered' => true,
                'message' => 'Pasien belum memiliki Rekam Medis'
            ]);
            exit;
        }

        echo json_encode([
            'status' => true,
            'data' => $res->fetch_assoc()
        ]);
        exit;

        case 'load_poli':

        $sql = "
            SELECT 
                p.kd_poli,
                p.nm_poli
            FROM poliklinik p
            WHERE p.status = '1'
            ORDER BY p.nm_poli
        ";

        $res = $conn->query($sql);

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            'status' => true,
            'data' => $data
        ]);
        exit;




        // ===============================
// LOAD DOKTER BY POLI
// ===============================
case 'load_dokter':

    $kd_poli = $_GET['kd_poli'] ?? '';

    if ($kd_poli == '') {
        echo json_encode([
            'status' => false,
            'message' => 'Kode poli kosong'
        ]);
        exit;
    }

    // ambil hari sekarang (SENIN, SELASA, dst)
    $hari = strtoupper(date('l'));
    $mapHari = [
        'MONDAY'    => 'SENIN',
        'TUESDAY'   => 'SELASA',
        'WEDNESDAY' => 'RABU',
        'THURSDAY'  => 'KAMIS',
        'FRIDAY'    => 'JUMAT',
        'SATURDAY'  => 'SABTU',
        'SUNDAY'    => 'AKHAD'
    ];
    $hari_ini = $mapHari[$hari] ?? 'SENIN';

    $sql = "
        SELECT 
            j.kd_dokter,
            d.nm_dokter,
            j.jam_mulai,
            j.jam_selesai,
            j.kuota
        FROM jadwal j
        JOIN dokter d ON d.kd_dokter = j.kd_dokter
        WHERE j.kd_poli = ?
          AND j.hari_kerja = ?
        ORDER BY j.jam_mulai
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $kd_poli, $hari_ini);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'status' => true,
        'data'   => $data,
        'hari'   => $hari_ini
    ]);
    exit;



    // ===============================
// SIMPAN REGISTRASI / ANTRIAN
// ===============================
case 'simpan_registrasi':

$no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
$kd_poli      = $_POST['kd_poli'] ?? '';
$kd_dokter    = $_POST['kd_dokter'] ?? '';
$kd_pj        = $_POST['kd_pj'] ?? 'BPJ';
$tgl_lahir        = $_POST['tgl_lahir'] ?? '';
$tgl = date('Y-m-d');     // untuk DB (DATE)
$jam = date('H:i:s');     // untuk DB (TIME)

// echo json_encode([
//     'debug_post' => $_POST
// ]);
// exit;

$umurdaftar = 0;
$sttsumur   = 'Th';

if (!empty($tgl_lahir)) {

    $lahir = new DateTime($tgl_lahir);
    $daftar = new DateTime($tgl);

    $diff = $lahir->diff($daftar);

    if ($diff->y > 0) {
        $umurdaftar = $diff->y;
        $sttsumur   = 'Th';
    } elseif ($diff->m > 0) {
        $umurdaftar = $diff->m;
        $sttsumur   = 'Bl';
    } else {
        $umurdaftar = $diff->d;
        $sttsumur   = 'Hr';
    }
}



$kd_pj = "BPJ";
$p_jawab = "-";
$almt_pj = "-";
$hubunganpj = "-";
$biaya_reg = 0;

$stts          = "Belum";
$stts_daftar   = "Lama";
$status_lanjut = "Ralan";

$status_bayar  = "Belum Bayar";
$status_poli   = "Baru";

if ($no_rkm_medis === '' || $kd_poli === '' || $kd_dokter === '') {
    echo json_encode([
        'status'  => false,
        'message' => 'Data belum lengkap'
    ]);
    exit;
}
    // ==============================
// GENERATE NO_REG
// ==============================
$sqlNoReg = "
    SELECT rp.no_reg, md.kode_prefix
    FROM reg_periksa rp
    INNER JOIN maping_dokter_kode md ON rp.kd_dokter = md.kd_dokter
    WHERE rp.tgl_registrasi = ?
      AND rp.kd_dokter = ?
    ORDER BY rp.no_reg DESC
    LIMIT 1
";

$stmt = $conn->prepare($sqlNoReg);
$stmt->bind_param("ss", $tgl, $kd_dokter);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $lastNumber = intval(substr($row['no_reg'], -3));
    $nextNumber = $lastNumber + 1;
    $kodePrefix = $row['kode_prefix'];
} else {
    // belum ada pasien hari ini
    $sqlPrefix = "
    SELECT kode_prefix
    FROM maping_dokter_kode
    WHERE kd_dokter = ?
    LIMIT 1
";

$stmtPrefix = $conn->prepare($sqlPrefix);
$stmtPrefix->bind_param("s", $kd_dokter);
$stmtPrefix->execute();
$resPrefix = $stmtPrefix->get_result();

if ($rowPrefix = $resPrefix->fetch_assoc()) {
    $kodePrefix = $rowPrefix['kode_prefix'];
} else {
    // fallback aman
    $kodePrefix = 'RG';
}
    $nextNumber = 1;
}

$no_reg = $kodePrefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);



// ==============================
// CEK SUDAH PERNAH DAFTAR & SUDAH DIPROSES
// ==============================
$sqlCek = "
    SELECT stts
    FROM reg_periksa
    WHERE no_rkm_medis = ?
      AND tgl_registrasi = ?
      AND stts = 'Belum'
    LIMIT 1
";

$stmtCek = $conn->prepare($sqlCek);
$stmtCek->bind_param("ss", $no_rkm_medis, $tgl);
$stmtCek->execute();
$resCek = $stmtCek->get_result();

if ($resCek->num_rows > 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Pasien sudah terdaftar dan belum dilayani hari ini'
    ]);
    exit;
}



// ==============================
// GENERATE NO_RAWAT
// ==============================
$tglFormat = date('Y/m/d', strtotime($tgl));

$sqlRawat = "
    SELECT no_rawat
    FROM reg_periksa
    WHERE tgl_registrasi = ?
    ORDER BY no_rawat DESC
    LIMIT 1
";

$stmt = $conn->prepare($sqlRawat);
$stmt->bind_param("s", $tgl);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $lastNumber = intval(substr($row['no_rawat'], -6));
    $nextNumber = $lastNumber + 1;
} else {
    $nextNumber = 1;
}

$no_rawat = $tglFormat . '/' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
// var_dump($no_rkm_medis);
// exit();

// var_dump($no_rkm_medis);
// exit();
// ==============================
// SIMPAN REGISTRASI
// ==============================
$sql = "
INSERT INTO reg_periksa (
    no_reg,
    no_rawat,
    tgl_registrasi,
    jam_reg,
    kd_dokter,
    no_rkm_medis,
    kd_poli,
    p_jawab,
    almt_pj,
    hubunganpj,
    biaya_reg,
    stts,
    stts_daftar,
    status_lanjut,
    kd_pj,
    umurdaftar,
    sttsumur,
    status_bayar,
    status_poli
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";




$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssssssssssssss",
    $no_reg,
    $no_rawat,
    $tgl,
    $jam,
    $kd_dokter,
    $no_rkm_medis,
    $kd_poli,
    $p_jawab,
    $almt_pj,
    $hubunganpj,
    $biaya_reg,
    $stts,
    $stts_daftar,
    $status_lanjut,
    $kd_pj,
    $umurdaftar,
    $sttsumur,
    $status_bayar,
    $status_poli
);

if ($stmt->execute()) {


$sqlgetData = "
SELECT  
    rp.no_reg,
    rp.no_rawat,
    rp.tgl_registrasi,
    p.nm_pasien,
    p.tgl_lahir,
    p.no_tlp,
    p.jk,
    p.no_rkm_medis,
    d.nm_dokter,
    pl.nm_poli
FROM reg_periksa rp
INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
INNER JOIN poliklinik pl ON rp.kd_poli = pl.kd_poli
WHERE rp.no_rawat = ?
";

$stmtCekgetData = $conn->prepare($sqlgetData);
$stmtCekgetData->bind_param("s", $no_rawat);
$stmtCekgetData->execute();
$resCek_data = $stmtCekgetData->get_result();

$row = $resCek_data->fetch_assoc();

if ($row) {

    $no_reg        = $row['no_reg'];
    $no_rawat      = $row['no_rawat'];
    $tgl_daftar    = date('d-m-Y', strtotime($row['tgl_registrasi']));
    $nm_pasien     = $row['nm_pasien'];
    $tgl_lahir     = $row['tgl_lahir'];
    $no_tlp        = $row['no_tlp'];
    $jk            = $row['jk'] == 'L' ? 'Laki - Laki' : 'Perempuan';
    $no_rkm_medis  = $row['no_rkm_medis'];
    $nm_dokter     = $row['nm_dokter'];
    $nm_poli       = $row['nm_poli'];

} else {

    echo json_encode([
        'status' => 'error',
        'message' => 'Data tidak ditemukan'
    ]);
    exit;

}
    echo json_encode([
        'status'   => 'success',
        'tanggal'   => $tgl_daftar,
        'no_reg'   => $no_reg,
        'no_rawat' => $no_rawat,
        'nm_poli' => $nm_poli,
        'nm_dokter' => $nm_dokter,
        'nm_pasien' => $nm_pasien,
        'tgl_lahir' => $tgl_lahir,
        'no_rkm_medis' => $no_rkm_medis,
        'jk' => $jk ,
        'no_tlp' => $no_tlp,


        

    ]);
    exit;
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => $stmt->error
    ]);
    exit;
}

    default:
        echo json_encode([
            'status' => false,
            'message' => 'Mode tidak valid'
        ]);
        exit;
}