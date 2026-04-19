<?php
require_once "config/db.php";

$maTran = $_POST['maTran'] ?? '';
$giaVe  = $_POST['giaVe'] ?? '';
$events = $_POST['events'] ?? '[]';
$khanGia = $_POST['khanGia'] ?? null;

if ($maTran === '' || $giaVe === '' || $khanGia === null) {
    die("Thiếu dữ liệu!");
}


$events = json_decode($events, true);

mysqli_begin_transaction($conn);

try {
    /* 1️⃣ CẬP NHẬT GIÁ VÉ + TRẠNG THÁI */
    $sql = "
        UPDATE tran_dau
SET GiaVe = ?, KhanGia = ?, TrangThai = 'Kết thúc'
WHERE MaTran = ?

    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "dis", $giaVe, $khanGia, $maTran);
    mysqli_stmt_execute($stmt);

    /* 2️⃣ XÓA DỮ LIỆU CŨ (nếu sửa lại trận) */
    mysqli_query($conn, "DELETE FROM ban_thang WHERE MaTran='$maTran'");
    mysqli_query($conn, "DELETE FROM the_phat WHERE MaTran='$maTran'");

    /* 3️⃣ INSERT NHẬT KÝ TRẬN ĐẤU */
$thuTuBan = 1;
$thuTuThe = 1;
$yellowCount = []; // [MaCT => số thẻ vàng]
$redGiven = []; 

foreach ($events as $e) {
    $maCT = $e['maCT'];
    $phut = (int)$e['minute'];
    $type = $e['type'];

    /* ⚽ BÀN THẮNG */
    if ($type === 'goal') {
        $sql = "
            INSERT INTO ban_thang (MaCT, MaTran, SoThuTuBan, ThoiGian)
            VALUES (?, ?, ?, ?)
        ";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ssis",
            $maCT,
            $maTran,
            $thuTuBan,
            $phut
        );
        mysqli_stmt_execute($stmt);

        $thuTuBan++;
    }

    /* 🟨🟥 THẺ PHẠT */
    elseif ($type === 'yellow' || $type === 'red') {

    // Nếu cầu thủ đã có thẻ đỏ → bỏ qua
    if (isset($redGiven[$maCT])) {
        continue;
    }

    /* 🟥 THẺ ĐỎ TRỰC TIẾP */
    if ($type === 'red') {
        $loai = 'Đỏ';
        $redGiven[$maCT] = true;

        insertThe($conn, $maCT, $maTran, $thuTuThe, $phut, $loai);
        $thuTuThe++;
        continue;
    }

    /* 🟨 THẺ VÀNG */
    $yellowCount[$maCT] = ($yellowCount[$maCT] ?? 0) + 1;

    // 🟨🟨 → 🟥
    if ($yellowCount[$maCT] === 2) {
        $loai = 'Đỏ';
        $redGiven[$maCT] = true;

        insertThe($conn, $maCT, $maTran, $thuTuThe, $phut, $loai);
        $thuTuThe++;
    }
    // 🟨 lần 1
    elseif ($yellowCount[$maCT] === 1) {
        $loai = 'Vàng';

        insertThe($conn, $maCT, $maTran, $thuTuThe, $phut, $loai);
        $thuTuThe++;
    }
}

}



    /* 4️⃣ ĐĂNG KÝ THI ĐẤU (ĐỘI HÌNH RA SÂN) */
$homeAll     = json_decode($_POST['homeAll'] ?? '[]', true);
$awayAll     = json_decode($_POST['awayAll'] ?? '[]', true);
$homeChecked = json_decode($_POST['homeChecked'] ?? '[]', true);
$awayChecked = json_decode($_POST['awayChecked'] ?? '[]', true);

/* XÓA ĐĂNG KÝ CŨ */
$sql = "DELETE FROM dang_ky_thi_dau WHERE MaTran = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $maTran);
mysqli_stmt_execute($stmt);

/* INSERT MỚI */
$sql = "
    INSERT INTO dang_ky_thi_dau (MaCT, MaTran, TrangThai)
    VALUES (?, ?, ?)
";
$stmt = mysqli_prepare($conn, $sql);

/* ĐỘI NHÀ */
foreach ($homeAll as $maCT) {
    $trangThai = in_array($maCT, $homeChecked) ? 'Ra sân' : 'Dự bị';
    mysqli_stmt_bind_param($stmt, "sss", $maCT, $maTran, $trangThai);
    mysqli_stmt_execute($stmt);
}

/* ĐỘI KHÁCH */
foreach ($awayAll as $maCT) {
    $trangThai = in_array($maCT, $awayChecked) ? 'Ra sân' : 'Dự bị';
    mysqli_stmt_bind_param($stmt, "sss", $maCT, $maTran, $trangThai);
    mysqli_stmt_execute($stmt);
}


    function insertThe($conn, $maCT, $maTran, $thuTuThe, $phut, $loai) {
    $sql = "
        INSERT INTO the_phat (MaCT, MaTran, SoThuTuThe, ThoiGian, LoaiThe)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssiss",
        $maCT,
        $maTran,
        $thuTuThe,
        $phut,
        $loai
    );
    mysqli_stmt_execute($stmt);
}

    mysqli_commit($conn);
    echo "success";

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "error: " . $e->getMessage();
}