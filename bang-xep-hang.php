<?php
$activePage = 'rank';
require_once "config/db.php";
$sql = "SELECT *
FROM (
    SELECT
        d.MaDoi,
        d.TenDoi,

        COUNT(DISTINCT td.MaTran) AS SoTran,

        COALESCE(SUM(
            CASE
                WHEN kq.BanDoi > kq.BanDoiThu THEN 3
                WHEN kq.BanDoi = kq.BanDoiThu THEN 1
                ELSE 0
            END
        ), 0) AS Diem,

        COALESCE(SUM(kq.BanDoi), 0) AS BanThang,
        COALESCE(SUM(kq.BanDoiThu), 0) AS BanThua,

        COALESCE(SUM(
            CASE
                WHEN tg.VaiTro = 'Khach'
                     AND kq.BanDoi > kq.BanDoiThu
                THEN 1 ELSE 0
            END
        ), 0) AS ThangKhach,

        COALESCE(tp_sum.TheVang, 0) AS TheVang,
        COALESCE(tp_sum.TheDo, 0) AS TheDo

    FROM doi_bong d

    LEFT JOIN tham_gia tg 
        ON d.MaDoi = tg.MaDoi

    LEFT JOIN tran_dau td 
        ON tg.MaTran = td.MaTran
       AND td.TrangThai = 'Kết thúc'

    /* ===== BÀN THẮNG / BÀN THUA ===== */
    LEFT JOIN (
        SELECT
            bt.MaTran,
            hd.MaDoiChuQuan AS MaDoi,
            COUNT(*) AS BanDoi,
            (
                SELECT COUNT(*)
                FROM ban_thang bt2
                JOIN hop_dong hd2 ON bt2.MaCT = hd2.MaCT
                WHERE bt2.MaTran = bt.MaTran
                  AND hd2.MaDoiChuQuan <> hd.MaDoiChuQuan
            ) AS BanDoiThu
        FROM ban_thang bt
        JOIN hop_dong hd ON bt.MaCT = hd.MaCT
        GROUP BY bt.MaTran, hd.MaDoiChuQuan
    ) kq 
        ON kq.MaTran = td.MaTran
       AND kq.MaDoi = d.MaDoi

    /* ===== THẺ PHẠT ===== */
    LEFT JOIN (
        SELECT
            hd.MaDoiChuQuan AS MaDoi,
            SUM(tp.LoaiThe = 'Vàng') AS TheVang,
            SUM(tp.LoaiThe = 'Đỏ') AS TheDo
        FROM the_phat tp
        JOIN hop_dong hd ON tp.MaCT = hd.MaCT
        GROUP BY hd.MaDoiChuQuan
    ) tp_sum 
        ON tp_sum.MaDoi = d.MaDoi

    GROUP BY d.MaDoi, d.TenDoi
) bxh
ORDER BY
    bxh.Diem DESC,
    (bxh.BanThang - bxh.BanThua) DESC,
    bxh.BanThang DESC;


";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("Lỗi SQL: " . mysqli_error($conn));
}

$hang = 1;
$tong_doi = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Bảng Xếp Hạng - Premier League</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* Highlight vùng đặc biệt */
    .ucl-zone {
        border-left: 4px solid #00ff85;
        background-color: rgba(0, 255, 133, 0.05);
    }

    /* Dự C1 */
    .rel-zone {
        border-left: 4px solid #e90052;
        background-color: rgba(233, 0, 82, 0.05);
    }

    /* Xuống hạng */

    /* Căn chỉnh cột số liệu cho dễ nhìn */
    td.num-cell {
        text-align: center;
    }

    th.num-cell {
        text-align: center;
    }

    /* Tô đậm cột Điểm */
    .pts-cell {
        font-weight: 800;
        color: var(--primary-bg);
        font-size: 1.1rem;
        background-color: rgba(0, 0, 0, 0.02);
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h1>🏆 Bảng Xếp Hạng Mùa Giải</h1>

        <div class="card">
            <div style="margin-bottom: 15px; display: flex; gap: 20px; font-size: 13px;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="width: 12px; height: 12px; background: #00ff85; border-radius: 2px;"></span> Dự
                    Champions League
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <span style="width: 12px; height: 12px; background: #e90052; border-radius: 2px;"></span> Xuống hạng
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="num-cell" style="width: 50px;">Hạng</th>
                        <th>Đội Bóng</th>
                        <th class="num-cell" title="Số trận đã đấu">Trận</th>
                        <th class="num-cell pts-cell">Điểm</th>
                        <th class="num-cell" title="Hiệu số bàn thắng bại">Hiệu Số</th>
                        <th class="num-cell" title="Tổng bàn thắng ghi được">Tổng BT</th>
                        <th class="num-cell" title="Tổng số bàn thua">Bàn Thua</th>
                        <th class="num-cell" title="Số trận thắng trên sân khách">Thắng Khách</th>
                        <th class="num-cell" title="Tổng thẻ vàng" style="color: #f1c40f;">🟨 Thẻ Vàng</th>
                        <th class="num-cell" title="Tổng thẻ đỏ" style="color: #e90052;">🟥 Thẻ Đỏ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="
    <?= $hang <= 4 ? 'ucl-zone' : ($hang > $tong_doi - 3 ? 'rel-zone' : '') ?>
">
                        <td class="num-cell"><?= $hang ?></td>
                        <td style="font-weight:bold;"><?= $row['TenDoi'] ?></td>
                        <td class="num-cell"><?= $row['SoTran'] ?></td>
                        <td class="num-cell pts-cell"><?= $row['Diem'] ?></td>
                        <td class="num-cell"><?= $row['BanThang'] - $row['BanThua'] ?></td>
                        <td class="num-cell"><?= $row['BanThang'] ?></td>
                        <td class="num-cell"><?= $row['BanThua'] ?></td>
                        <td class="num-cell"><?= $row['ThangKhach'] ?></td>
                        <td class="num-cell"><?= $row['TheVang'] ?></td>
                        <td class="num-cell"><?= $row['TheDo'] ?></td>
                    </tr>
                    <?php $hang++; endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>
</body>

</html>