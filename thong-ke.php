<?php
$activePage = 'stat';
require_once "config/db.php";
$sqlTopScorer = "
SELECT 
    ct.TenCT,
    db.TenDoi,
    COUNT(bt.SoThuTuBan) AS SoBan
FROM ban_thang bt
JOIN cau_thu ct ON bt.MaCT = ct.MaCT
JOIN hop_dong hd ON ct.MaCT = hd.MaCT
JOIN doi_bong db ON hd.MaDoiChuQuan = db.MaDoi
GROUP BY ct.MaCT, ct.TenCT, db.TenDoi
ORDER BY SoBan DESC
LIMIT 10

";

$rsTop = mysqli_query($conn, $sqlTopScorer);

$thang = $_GET['thang'] ?? date('Y-m');

$sqlLuongTT = "
SELECT 
    tt.TenTT,

    SUM(CASE WHEN dh.VaiTro = 'Chinh' THEN 1 ELSE 0 END) AS SoChinh,
    SUM(CASE WHEN dh.VaiTro = 'Bien'  THEN 1 ELSE 0 END) AS SoBien,
    SUM(CASE WHEN dh.VaiTro = 'Ban'   THEN 1 ELSE 0 END) AS SoBan,

    SUM(dh.LuongTT) AS TongLuong

FROM dieu_hanh dh
JOIN trong_tai tt ON dh.MaTT = tt.MaTT
JOIN tran_dau td ON dh.MaTran = td.MaTran

WHERE DATE_FORMAT(td.ThoiGian, '%Y-%m') = ?

GROUP BY tt.MaTT, tt.TenTT
ORDER BY TongLuong DESC;
";


$stmt = mysqli_prepare($conn, $sqlLuongTT);
mysqli_stmt_bind_param($stmt, "s", $thang);
mysqli_stmt_execute($stmt);
$rsLuong = mysqli_stmt_get_result($stmt);

$sqlDoanhThu = "
SELECT
    td.MaTran,
    td.ThoiGian,
    td.KhanGia,
    td.GiaVe,
    (td.KhanGia * td.GiaVe) AS DoanhThu,
    dn.TenDoi AS DoiNha,
    dk.TenDoi AS DoiKhach,
    s.TenSan
FROM tran_dau td
JOIN tham_gia tgn ON td.MaTran = tgn.MaTran AND tgn.VaiTro = 'Nha'
JOIN doi_bong dn ON tgn.MaDoi = dn.MaDoi
JOIN tham_gia tgk ON td.MaTran = tgk.MaTran AND tgk.VaiTro = 'Khach'
JOIN doi_bong dk ON tgk.MaDoi = dk.MaDoi
LEFT JOIN san_dau s ON dn.MaSan = s.MaSan
WHERE DATE_FORMAT(td.ThoiGian, '%Y-%m') = ?
ORDER BY td.ThoiGian
";

$stmtDT = mysqli_prepare($conn, $sqlDoanhThu);
mysqli_stmt_bind_param($stmtDT, "s", $thang);
mysqli_stmt_execute($stmtDT);
$rsDoanhThu = mysqli_stmt_get_result($stmtDT);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thống Kê - Premier League</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* CSS bổ sung cho form chọn tháng */
    .filter-bar {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        gap: 15px;
        align-items: flex-end;
        border: 1px solid #eee;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h1>📊 Thống Kê & Báo Cáo</h1>

        <div class="card">
            <h3
                style="color: var(--primary-bg); border-bottom: 2px solid var(--accent-green); display: inline-block; padding-bottom: 5px;">
                👟 Vua Phá Lưới (Golden Boot)
            </h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">Hạng</th>
                        <th>Cầu Thủ</th>
                        <th>CLB</th>
                        <th style="text-align: center;">Số Bàn Thắng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
$rank = 1;
while ($row = mysqli_fetch_assoc($rsTop)): 
    $medal = match($rank) {
        1 => '🥇',
        2 => '🥈',
        3 => '🥉',
        default => $rank
    };
?>
                    <tr>
                        <td style="text-align: center;"><?= $medal ?></td>
                        <td><b><?= $row['TenCT'] ?></b></td>
                        <td><?= $row['TenDoi'] ?></td>
                        <td style="font-weight: 800; color: var(--primary-bg); text-align: center;">
                            <?= $row['SoBan'] ?>
                        </td>
                    </tr>
                    <?php 
$rank++;
endwhile; 
?>
                </tbody>

            </table>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3 style="color: #2980b9; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    💰 Bảng Lương Trọng Tài
                </h3>

                <form method="get" class="filter-bar">
                    <div style="flex:1;">
                        <label>Chọn tháng báo cáo:</label>
                        <input type="month" name="thang" value="<?= $thang ?>" class="form-control">
                    </div>
                    <button class="btn btn-primary">Xem Báo Cáo</button>
                </form>


                <p class="note">Đơn giá: Bắt chính (3tr) | Bắt biên (2tr) | Trọng tài bàn (1tr) / trận.</p>

                <table>
                    <thead>
                        <tr>
                            <th>Trọng Tài</th>
                            <th>Nhiệm vụ</th>
                            <th style="text-align: right;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($rsLuong)): ?>
                        <tr>
                            <td><b><?= $r['TenTT'] ?></b></td>
                            <td>
                                <div><?= $r['SoChinh'] ?? 0 ?> Chính </div>
                                <div><?= $r['SoBien'] ?? 0 ?> Biên </div>
                                <div><?= $r['SoBan'] ?? 0 ?> Bàn </div>

                            </td>
                            <td style="font-weight:bold; color:#27ae60; text-align:right;">
                                <?= number_format($r['TongLuong']) ?> đ
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>

            <div class="card">
                <h3 style="color: var(--accent-pink); border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    💵 Doanh Thu Trận Đấu
                </h3>

                <table>
                    <thead>
                        <tr>
                            <th>Trận Đấu</th>
                            <th>Khán Giả</th>
                            <th>Giá Vé TB</th>
                            <th style="text-align: right;">Tổng Thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
$tong = 0;
while ($r = mysqli_fetch_assoc($rsDoanhThu)):
    $tong += $r['DoanhThu'];
?>
                        <tr>
                            <td>
                                <b><?= $r['DoiNha'] ?> vs <?= $r['DoiKhach'] ?></b>
                                <div style="font-size:12px;color:#888;">
                                    <?= date('d/m/Y H:i', strtotime($r['ThoiGian'])) ?>
                                    – <?= $r['TenSan'] ?>
                                </div>
                            </td>
                            <td><?= number_format($r['KhanGia']) ?></td>
                            <td><?= number_format($r['GiaVe']) ?> đ</td>
                            <td style="font-weight:bold; text-align:right;">
                                <?= number_format($r['DoanhThu']) ?> đ
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <tr style="background:#f9f9f9; border-top:2px solid #ccc;">
                            <td colspan="3" style="text-align:right; font-weight:bold;">TỔNG CỘNG:</td>
                            <td style="font-weight:800; color:var(--accent-pink); text-align:right;">
                                <?= number_format($tong) ?> đ
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</body>

</html>