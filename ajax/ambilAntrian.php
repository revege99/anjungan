<?php
header('Content-Type: application/json');
require_once '../function/configDB.php';

$mode = $_GET['mode'] ?? '';

switch ($mode) {

    case 'getPasien':
        $nomor = $_POST['nomor'] ?? '';

        if ($nomor == '') {
            echo json_encode(['status'=>false,'message'=>'Nomor kosong']);
            exit;
        }

        $sql = "SELECT no_rkm_medis, nm_pasien, no_ktp, no_peserta
                FROM pasien
                WHERE no_ktp=? OR no_peserta=?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nomor, $nomor);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            echo json_encode([
                'status' => true,
                'data'   => $res->fetch_assoc()
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message'=> 'Tidak ditemukan'
            ]);
        }
        break;

    case 'ambilAntrian':
        // nanti kita isi insert ke tabel antrian
        break;

    default:
        echo json_encode(['status'=>false,'message'=>'Mode tidak valid']);
}