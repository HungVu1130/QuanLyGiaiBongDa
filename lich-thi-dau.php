<?php
require_once "config/db.php";

$keyword   = $_GET['q'] ?? '';
$maVong    = $_GET['vong'] ?? '';
$trangThai = $_GET['status'] ?? '';


$sqlLich = "
SELECT 
    td.MaTran,
    td.ThoiGian,
    td.TrangThai,

    vd.MaVong,
    vd.TenVong,

    dn.TenDoi AS DoiNha,
    dk.TenDoi AS DoiKhach,
    s.TenSan,

    (
        SELECT COUNT(*)
        FROM ban_thang bt
        JOIN hop_dong hd ON bt.MaCT = hd.MaCT
        WHERE bt.MaTran = td.MaTran
          AND hd.MaDoiChuQuan = dn.MaDoi
    ) AS BanNha,

    (
        SELECT COUNT(*)
        FROM ban_thang bt
        JOIN hop_dong hd ON bt.MaCT = hd.MaCT
        WHERE bt.MaTran = td.MaTran
          AND hd.MaDoiChuQuan = dk.MaDoi
    ) AS BanKhach

FROM tran_dau td
JOIN vong_dau vd ON td.MaVong = vd.MaVong

JOIN tham_gia tgn 
    ON td.MaTran = tgn.MaTran AND tgn.VaiTro = 'Nha'
JOIN doi_bong dn ON tgn.MaDoi = dn.MaDoi

JOIN tham_gia tgk 
    ON td.MaTran = tgk.MaTran AND tgk.VaiTro = 'Khach'
JOIN doi_bong dk ON tgk.MaDoi = dk.MaDoi

LEFT JOIN san_dau s ON dn.MaSan = s.MaSan

WHERE 1=1
";

if ($keyword !== '') {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $sqlLich .= "
        AND (
            td.MaTran LIKE '%$kw%'
            OR dn.TenDoi LIKE '%$kw%'
            OR dk.TenDoi LIKE '%$kw%'
            OR s.TenSan LIKE '%$kw%'
        )
    ";
}

if ($maVong !== '') {
    $sqlLich .= " AND vd.MaVong = '$maVong' ";
}

if ($trangThai !== '' && $trangThai !== 'all') {
    $sqlLich .= " AND td.TrangThai = '$trangThai' ";
}

$sqlLich .= " ORDER BY td.ThoiGian DESC ";



$lichThiDau = mysqli_query($conn, $sqlLich);

$createSuccess = false;
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tenVong = trim($_POST['tenVong']);
    $tuNgay  = $_POST['tuNgay'];
    $denNgay = $_POST['denNgay'];

    if ($tenVong == "" || $tuNgay == "" || $denNgay == "") {
        $errorMsg = "Thiếu dữ liệu!";
    } else {

        // 🔢 LẤY MÃ VÒNG LỚN NHẤT
        $rs = mysqli_query($conn, "SELECT MaVong FROM vong_dau ORDER BY MaVong DESC LIMIT 1");
        if (mysqli_num_rows($rs) > 0) {
            $row = mysqli_fetch_assoc($rs);
            $num = intval(substr($row['MaVong'], 1)) + 1;
        } else {
            $num = 1;
        }

        // 👉 V01, V02, V03...
        $maVong = 'V' . str_pad($num, 2, '0', STR_PAD_LEFT);

        // 👉 Tên vòng tự đồng bộ
        if ($tenVong == "") {
            $tenVong = "Vòng " . $num;
        }

        $sql = "INSERT INTO vong_dau (MaVong, TenVong, TuNgay, DenNgay)
                VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $maVong, $tenVong, $tuNgay, $denNgay);

        if (mysqli_stmt_execute($stmt)) {
            $createSuccess = true;
        } else {
            $errorMsg = "Lỗi khi thêm vòng đấu!";
        }

        mysqli_stmt_close($stmt);
    }
}

?>




<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch Thi Đấu - Premier League</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* --- CSS CƠ BẢN GIỮ NGUYÊN --- */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.3s;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 25px;
        border: 1px solid #888;
        width: 500px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: black;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .modal-footer {
        margin-top: 20px;
        text-align: right;
        border-top: 1px solid #eee;
        padding-top: 15px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0
        }

        to {
            opacity: 1
        }
    }

    /* --- CSS MỚI: THANH TÌM KIẾM & TOOLBAR --- */
    .top-search-bar {
        margin-bottom: 20px;
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
    }

    .top-search-bar input {
        width: 100%;
        border: none;
        font-size: 16px;
        outline: none;
        padding: 5px;
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }

    .filter-group {
        display: flex;
        gap: 15px;
        align-items: center;
        background: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .action-group {
        display: flex;
        gap: 10px;
    }

    .btn-create-round {
        background-color: #e9ecef;
        color: #495057;
        border: 1px solid #ced4da;
        padding: 10px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-add-match {
        background-color: #00ff85;
        color: #37003c;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 2px 5px rgba(0, 255, 133, 0.4);
    }

    select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        min-width: 150px;
    }

    /* --- CSS QUAN TRỌNG: STYLE GIỐNG HÌNH ẢNH --- */

    /* Header bảng màu tím đậm */
    table thead tr {
        background-color: #37003c !important;
        /* Màu tím đậm */
        color: white;
        text-transform: uppercase;
        font-size: 14px;
    }

    table th {
        padding: 12px 10px;
        font-weight: 600;
    }

    /* Badge Trạng Thái */
    .badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 800;
        /* Chữ đậm */
        font-size: 12px;
        text-transform: uppercase;
        /* Chữ in hoa */
        display: inline-block;
        min-width: 100px;
        text-align: center;
    }

    /* Style cho KẾT THÚC (Xanh lá nhạt, chữ xanh đậm) */
    .badge-finished {
        background-color: #e6f7e6;
        color: #008000;
    }

    /* Style cho CHƯA DIỄN RA (Vàng nhạt, chữ nâu vàng) */
    .badge-upcoming {
        background-color: #fff8e1;
        /* Vàng kem */
        color: #bfa100;
        /* Vàng đậm/Nâu */
    }

    /* Nút Thao Tác */
    .btn-action {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 700;
        font-size: 12px;
        text-decoration: none;
        text-transform: uppercase;
        /* Chữ in hoa */
        min-width: 80px;
        text-align: center;
    }

    /* Nút CHI TIẾT (Xám nhạt) */
    .btn-detail {
        background-color: #f0f0f0;
        color: #333;
        border: 1px solid #e0e0e0;
    }

    .btn-detail:hover {
        background-color: #e0e0e0;
    }

    /* Nút CẬP NHẬT (Tím đậm) */
    .btn-update {
        background-color: #37003c;
        color: white;
        border: none;
    }

    .btn-update:hover {
        background-color: #500055;
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>PREMIER LEAGUE</h2>
        <a href="trang-chu.php">🏠 Tổng quan</a>
        <a href="quan-ly-doi-bong.php">⚽ Đội bóng</a>
        <a href="quan-ly-cau-thu.php">👕 Cầu thủ</a>
        <a href="quan-ly-trong-tai.php">🚩 Trọng tài</a>
        <a href="quan-ly-san.php">🏟️ Sân vận động</a>
        <a href="lich-thi-dau.php" class="active">📅 Lịch thi đấu</a>
        <a href="bang-xep-hang.php">🏆 Bảng xếp hạng</a>
        <a href="thong-ke.php">📊 Thống kê</a>
    </div>

    <div class="content">
        <form method="get" class="top-search-bar">
            <span style="font-size: 20px; margin-right: 10px;">🔍</span>
            <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>"
                placeholder="Tìm theo mã trận, đội bóng, sân vận động...">
        </form>
        <?php if ($createSuccess): ?>
        <script>
        alert("🎉 Tạo vòng đấu thành công!");
        window.location.href = "lich-thi-dau.php";
        </script>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
        <script>
        alert("❌ <?= $errorMsg ?>");
        </script>
        <?php endif; ?>

        <h1>📅 Quản Lý Lịch Thi Đấu</h1>

        <div class="toolbar">

            <form method="get" class="filter-group">

                <div>
                    <label style="font-size: 12px; color: #777; display: block; margin-bottom: 2px;">Vòng đấu:</label>
                    <select name="vong">
                        <option value="">-- Tất cả vòng --</option>
                        <?php
                        $dsVong = mysqli_query($conn, "SELECT MaVong, TenVong FROM vong_dau ORDER BY MaVong");
                        while ($v = mysqli_fetch_assoc($dsVong)):
                        ?>
                        <option value="<?= $v['MaVong'] ?>" <?= ($maVong === $v['MaVong']) ? 'selected' : '' ?>>
                            <?= $v['TenVong'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>

                </div>

                <div style="border-left: 1px solid #eee; height: 30px; margin: 0 5px;"></div>

                <div>
                    <label style="font-size: 12px; color: #777; display: block; margin-bottom: 2px;">Trạng thái:</label>
                    <select name="status">
                        <option value="all">Tất cả</option>
                        <option value="Chưa diễn ra" <?= $trangThai==='Chưa diễn ra'?'selected':'' ?>>
                            Chưa diễn ra
                        </option>
                        <option value="Kết thúc" <?= $trangThai==='Kết thúc'?'selected':'' ?>>
                            Kết thúc
                        </option>
                    </select>

                </div>

                <button class="btn btn-primary" style="height: 35px; margin-top: 15px;">Lọc</button>
            </form>

            <div class="action-group">
                <button class="btn-create-round" onclick="openModal()">
                    <span>⚙️</span> Tạo Vòng Mới
                </button>
                <a href="them-tran-dau.php" class="btn-add-match">
                    <span>+</span> Thêm Trận Đấu
                </a>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding-left: 20px;">Mã</th>
                        <th>Thời gian</th>
                        <th style="text-align: right;">Chủ nhà</th>
                        <th style="text-align: center;">Tỷ số</th>
                        <th>Đội khách</th>
                        <th>Sân vận động</th>
                        <th style="text-align: center;">Trạng thái</th>
                        <th style="text-align: center;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($lichThiDau)) : ?>

                    <tr style="border-bottom: 1px solid #eee;">
                        <!-- Mã trận -->
                        <td style="padding: 15px 10px 15px 20px;">
                            <b><?= $row['MaTran'] ?></b>
                        </td>

                        <!-- Thời gian -->
                        <td>
                            <?= date("d/m/Y H:i", strtotime($row['ThoiGian'])) ?>
                        </td>

                        <!-- Đội nhà -->
                        <td style="text-align: right; font-weight: bold;">
                            <?= $row['DoiNha'] ?>
                        </td>

                        <!-- Tỷ số -->
                        <td style="text-align: center;">
                            <?php if ($row['TrangThai'] === 'Chưa diễn ra'): ?>
                            <span style="color:#888;">vs</span>
                            <?php else: ?>
                            <span style="font-weight:800;background:#f0f2f5;padding:4px 8px;border-radius:4px;">
                                <?= $row['BanNha'] ?> - <?= $row['BanKhach'] ?>
                            </span>
                            <?php endif; ?>

                        </td>

                        <!-- Đội khách -->
                        <td style="font-weight: bold;">
                            <?= $row['DoiKhach'] ?>
                        </td>

                        <!-- Sân -->
                        <td>
                            <?= $row['TenSan'] ?? 'Chưa xác định' ?>
                        </td>

                        <!-- Trạng thái -->
                        <td style="text-align:center;">
                            <?php if ($row['TrangThai'] === 'Chưa diễn ra'): ?>
                            <span class="badge badge-upcoming">CHƯA DIỄN RA</span>
                            <?php else: ?>
                            <span class="badge badge-finished">KẾT THÚC</span>
                            <?php endif; ?>
                        </td>

                        <!-- Thao tác -->
                        <td style="text-align:center;">
                            <?php if ($row['TrangThai'] === 'Chưa diễn ra'): ?>
                            <a href="cap-nhat-ket-qua.php?maTran=<?= $row['MaTran'] ?>"
                                class="btn-action btn-update">CẬP NHẬT</a>
                            <?php else: ?>
                            <a href="chi-tiet-tran-dau.php?maTran=<?= $row['MaTran'] ?>"
                                class="btn-action btn-detail">CHI TIẾT</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>

    <div id="modalCreateRound" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="color: #37003c; margin-top: 0;">Tạo Vòng Đấu Mới</h2>
            <p style="color: #666; font-size: 14px;">Nhập thông tin để khởi tạo vòng đấu mới.</p>
            <form id="formCreateRound" method="POST">
                <div class="form-group">
                    <label>Tên vòng đấu (VD: Vòng 39):</label>
                    <input type="text" name="tenVong" placeholder="Nhập tên vòng..." required>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Từ ngày:</label>
                        <input type="date" name="tuNgay" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Đến ngày:</label>
                        <input type="date" name="denNgay" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary"
                        style="padding: 10px 20px; margin-right: 10px; cursor: pointer;">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary"
                        style="padding: 10px 20px; background: #37003c; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Lưu Vòng Đấu
                    </button>

                </div>
            </form>
        </div>
    </div>

    <script>
    const modal = document.getElementById("modalCreateRound");

    function openModal() {
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
</body>

</html>