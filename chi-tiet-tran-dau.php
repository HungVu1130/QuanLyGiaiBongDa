<?php
require_once "config/db.php";

if (!isset($_GET['maTran'])) {
    die("Thiếu mã trận!");
}

$maTran = $_GET['maTran'];

/* ================= TRẬN ĐẤU ================= */
$sqlMatch = "
SELECT
    td.MaTran,
    td.ThoiGian,
    td.GiaVe,
    td.TrangThai,
    vd.TenVong,
    dn.MaDoi AS MaDoiNha,
    dn.TenDoi AS DoiNha,
    dk.MaDoi AS MaDoiKhach,
    dk.TenDoi AS DoiKhach,
    td.KhanGia,
    s.TenSan
FROM tran_dau td
JOIN vong_dau vd ON td.MaVong = vd.MaVong
JOIN tham_gia tgn ON td.MaTran = tgn.MaTran AND tgn.VaiTro='Nha'
JOIN doi_bong dn ON tgn.MaDoi = dn.MaDoi
JOIN tham_gia tgk ON td.MaTran = tgk.MaTran AND tgk.VaiTro='Khach'
JOIN doi_bong dk ON tgk.MaDoi = dk.MaDoi
LEFT JOIN san_dau s ON dn.MaSan = s.MaSan
WHERE td.MaTran = ?
";

$stmt = mysqli_prepare($conn, $sqlMatch);
mysqli_stmt_bind_param($stmt, "s", $maTran);
mysqli_stmt_execute($stmt);
$match = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$match) die("Không tìm thấy trận đấu!");

$sqlScore = "
SELECT hd.MaDoiChuQuan AS MaDoi, COUNT(*) AS SoBan
FROM ban_thang bt
JOIN hop_dong hd ON bt.MaCT = hd.MaCT
WHERE bt.MaTran = ?
GROUP BY hd.MaDoiChuQuan
";


$stmt = mysqli_prepare($conn, $sqlScore);
mysqli_stmt_bind_param($stmt, "s", $maTran);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

$score = [
    $match['MaDoiNha'] => 0,
    $match['MaDoiKhach'] => 0
];

while ($r = mysqli_fetch_assoc($rs)) {
    $score[$r['MaDoi']] = $r['SoBan'];
}
$sqlEvents = "
SELECT * FROM (
    SELECT 
        bt.ThoiGian,
        'goal' AS Loai,
        ct.TenCT,
        hd.MaDoiChuQuan AS MaDoi
    FROM ban_thang bt
    JOIN cau_thu ct ON bt.MaCT = ct.MaCT
    JOIN hop_dong hd ON ct.MaCT = hd.MaCT
    WHERE bt.MaTran = ?

    UNION ALL

    SELECT
        tp.ThoiGian,
        tp.LoaiThe AS Loai,
        ct.TenCT,
        hd.MaDoiChuQuan AS MaDoi
    FROM the_phat tp
    JOIN cau_thu ct ON tp.MaCT = ct.MaCT
    JOIN hop_dong hd ON ct.MaCT = hd.MaCT
    WHERE tp.MaTran = ?
) e
ORDER BY ThoiGian ASC
";


$stmt = mysqli_prepare($conn, $sqlEvents);
mysqli_stmt_bind_param($stmt, "ss", $maTran, $maTran);
mysqli_stmt_execute($stmt);
$events = mysqli_stmt_get_result($stmt);
$sqlRef = "
SELECT dh.VaiTro, tt.TenTT
FROM dieu_hanh dh
JOIN trong_tai tt ON dh.MaTT = tt.MaTT
WHERE dh.MaTran = ?
";

$stmt = mysqli_prepare($conn, $sqlRef);
mysqli_stmt_bind_param($stmt, "s", $maTran);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);

$refs = [
    'Chinh' => null,
    'Ban'   => null,
    'Bien'  => []
];

while ($r = mysqli_fetch_assoc($rs)) {
    if ($r['VaiTro'] === 'Bien') {
        $refs['Bien'][] = $r['TenTT'];
    } else {
        $refs[$r['VaiTro']] = $r['TenTT'];
    }
}


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Báo Cáo Trận Đấu</title>
    <link rel="stylesheet" href="style.css">
    <style>
    .final-score-board {
        background: linear-gradient(135deg, var(--primary-bg), #2a002e);
        color: white;
        padding: 40px;
        border-radius: 15px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 50px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .final-score-board::before {
        content: "🦁";
        position: absolute;
        font-size: 15rem;
        opacity: 0.05;
        top: -50px;
        left: 50%;
        transform: translateX(-50%);
    }

    .score-big {
        font-size: 5rem;
        font-weight: 800;
        line-height: 1;
    }

    .team-block {
        text-align: center;
        z-index: 1;
    }

    .team-block h2 {
        margin: 10px 0 0 0;
        font-size: 2rem;
    }

    .match-meta {
        text-align: center;
        color: #aaa;
        margin-bottom: 5px;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding: 10px 0;
        border-bottom: 1px dashed #eee;
        align-items: center;
    }

    .info-box {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        height: 100%;
    }

    .info-box h4 {
        margin-top: 0;
        color: var(--primary-bg);
        border-bottom: 2px solid var(--accent-green);
        display: inline-block;
        padding-bottom: 5px;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-list li {
        margin-bottom: 12px;
        font-size: 0.95rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
    }

    .info-list li strong {
        display: inline-block;
        width: 140px;
        color: #555;
    }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>PREMIER LEAGUE</h2>
        <a href="lich-thi-dau.php" class="active">📅 Lịch thi đấu</a>
    </div>

    <div class="content">
        <a href="lich-thi-dau.php" class="btn btn-secondary" style="margin-bottom: 20px;">← Quay lại danh sách</a>

        <div class="match-meta">
            <?= $match['TenVong'] ?> • <?= $match['TenSan'] ?>
        </div>

        <div class="final-score-board">
            <div class="team-block">
                <h2><?= strtoupper($match['DoiNha']) ?></h2>
            </div>

            <div class="score-big">
                <?= $score[$match['MaDoiNha']] ?> - <?= $score[$match['MaDoiKhach']] ?>
            </div>

            <div class="team-block">
                <h2><?= strtoupper($match['DoiKhach']) ?></h2>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3>⚽ Diễn biến chính</h3>

                <?php while ($e = mysqli_fetch_assoc($events)): ?>
                <div class="stat-row">
                    <span>
                        <b><?= $e['ThoiGian'] ?>'</b>
                        <?php if ($e['Loai'] === 'goal'): ?>
                        ⚽ Ghi bàn: <?= $e['TenCT'] ?>
                        <?php elseif ($e['Loai'] === 'Vàng'): ?>
                        🟨 Thẻ vàng: <?= $e['TenCT'] ?>
                        <?php else: ?>
                        🟥 Thẻ đỏ: <?= $e['TenCT'] ?>
                        <?php endif; ?>
                    </span>

                    <span class="badge">
                        <?= $e['MaDoi'] == $match['MaDoiNha'] ? $match['DoiNha'] : $match['DoiKhach'] ?>
                    </span>
                </div>
                <?php endwhile; ?>

                <div class="stat-row" style="justify-content:center;font-weight:bold;">
                    ⏰ HẾT GIỜ
                </div>
            </div>


            <div class="card" style="padding: 0;">
                <div class="info-box">
                    <h4>🎫 Thông Tin Tổ Chức</h4>
                    <ul class="info-list">
                        <li>
                            <strong>Sân vận động:</strong>
                            <?= $match['TenSan'] ?? 'Chưa cập nhật' ?>
                        </li>

                        <li>
                            <strong>Số lượng khán giả:</strong>
                            <span style="font-weight: bold; font-size: 1.1rem;">
                                <?= number_format($match['KhanGia'] ?? 0) ?>
                            </span>
                        </li>

                    </ul>

                    <br>

                    <h4>👮 Tổ Trọng Tài</h4>
                    <ul class="info-list">
                        <li><strong>Trọng tài chính:</strong> <?= $refs['Chinh'] ?? '—' ?></li>
                        <li><strong>Trợ lý 1:</strong> <?= $refs['Bien'][0] ?? '—' ?></li>
                        <li><strong>Trợ lý 2:</strong> <?= $refs['Bien'][1] ?? '—' ?></li>

                        <li><strong>Trọng tài bàn:</strong> <?= $refs['Ban'] ?? '—' ?></li>
                    </ul>

                </div>
            </div>
        </div>

    </div>
</body>

</html>