<?php
$activePage = 'home';
require_once "config/db.php";

/* SỐ ĐỘI BÓNG */
$rsDoi = mysqli_query($conn, "SELECT COUNT(*) AS TongDoi FROM doi_bong");
$tongDoi = mysqli_fetch_assoc($rsDoi)['TongDoi'];

/* TỔNG CẦU THỦ */
$rsCT = mysqli_query($conn, "SELECT COUNT(*) AS TongCT FROM cau_thu");
$tongCT = mysqli_fetch_assoc($rsCT)['TongCT'];

/* TRẬN ĐÃ ĐẤU */
$rsTran = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS TranDaDau 
     FROM tran_dau 
     WHERE TrangThai = 'Kết Thúc'"
);
$tranDaDau = mysqli_fetch_assoc($rsTran)['TranDaDau'];

/* TỔNG BÀN THẮNG */
$rsBan = mysqli_query($conn, "SELECT COUNT(*) AS TongBan FROM ban_thang");
$tongBan = mysqli_fetch_assoc($rsBan)['TongBan'];

$sqlUpcoming = "
SELECT
    td.ThoiGian,
    dn.TenDoi AS DoiNha,
    dk.TenDoi AS DoiKhach,
    s.TenSan
FROM tran_dau td
JOIN tham_gia tgn ON td.MaTran = tgn.MaTran AND tgn.VaiTro = 'Nha'
JOIN doi_bong dn ON tgn.MaDoi = dn.MaDoi
JOIN tham_gia tgk ON td.MaTran = tgk.MaTran AND tgk.VaiTro = 'Khach'
JOIN doi_bong dk ON tgk.MaDoi = dk.MaDoi
LEFT JOIN san_dau s ON dn.MaSan = s.MaSan
WHERE td.TrangThai = 'Chưa diễn ra'
ORDER BY td.ThoiGian
LIMIT 5
";

$rsUpcoming = mysqli_query($conn, $sqlUpcoming);


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premier League Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h1>Tổng Quan Hệ Thống</h1>
        <p style="color: #666; margin-bottom: 40px; font-size: 1.1rem;">
            Chào mừng quản trị viên quay trở lại hệ thống quản lý giải đấu <b>Mùa giải 2026-2027</b>.
        </p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">⚽</div>
                <div class="stat-title">Số Đội Bóng</div>
                <div class="stat-number"><?= $tongDoi ?></div>

            </div>

            <div class="stat-card" style="--primary-bg: #00ff85;">
                <div class="stat-icon">👕</div>
                <div class="stat-title">Tổng Cầu Thủ</div>
                <div class="stat-number"><?= $tongCT ?></div>

            </div>

            <div class="stat-card" style="--primary-bg: #3498db;">
                <div class="stat-icon">📅</div>
                <div class="stat-title">Trận Đã Đấu</div>
                <div class="stat-number">
                    <?= $tranDaDau ?>
                    <span style="font-size:1.2rem;color:#ccc;font-weight:normal;"></span>
                </div>
            </div>

            <div class="stat-card" style="--primary-bg: #e90052;">
                <div class="stat-icon">🥅</div>
                <div class="stat-title">Tổng Bàn Thắng</div>
                <div class="stat-number"><?= $tongBan ?></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3 style="margin-top:0; color: var(--primary-bg);">📌 Trận đấu sắp tới</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Cặp đấu</th>
                            <th>Sân</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($rsUpcoming)): ?>
                        <tr>
                            <td><?= date('d/m', strtotime($r['ThoiGian'])) ?></td>
                            <td><b><?= $r['DoiNha'] ?></b> vs <?= $r['DoiKhach'] ?></td>
                            <td><?= $r['TenSan'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
                <div style="margin-top: 15px; text-align: right;">
                    <a href="lich-thi-dau.php" class="btn btn-primary">Xem tất cả</a>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0; color: var(--primary-bg);">⚡ Thao tác nhanh</h3>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="them-tran-dau.php" class="btn btn-primary" style="background: #e90052; color: white;">+
                        Thêm trận đấu</a>
                    <a href="quan-ly-cau-thu.php" class="btn btn-secondary">Đăng ký cầu thủ</a>
                    <a href="thong-ke.php" class="btn btn-secondary">Xuất báo cáo</a>
                </div>
                <p class="note" style="margin-top: 20px;">
                    Hệ thống đang hoạt động ổn định. Lần sao lưu cuối: 08:00 AM hôm nay.
                </p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 50px; opacity: 0.05; pointer-events: none;">
            <h1 style="font-size: 6rem; border: none; margin: 0; color: #38003c;">PREMIER LEAGUE</h1>
        </div>

    </div>
</body>

</html>