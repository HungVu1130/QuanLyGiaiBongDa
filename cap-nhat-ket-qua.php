<?php
require_once "config/db.php";

if (!isset($_GET['maTran'])) {
    die("Thiếu mã trận!");
}

$maTran = $_GET['maTran'];

$sql = "
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
        s.TenSan
    FROM tran_dau td

    JOIN vong_dau vd ON td.MaVong = vd.MaVong

    JOIN tham_gia tgn 
        ON td.MaTran = tgn.MaTran AND tgn.VaiTro = 'Nha'
    JOIN doi_bong dn 
        ON tgn.MaDoi = dn.MaDoi

    JOIN tham_gia tgk 
        ON td.MaTran = tgk.MaTran AND tgk.VaiTro = 'Khach'
    JOIN doi_bong dk 
        ON tgk.MaDoi = dk.MaDoi

    LEFT JOIN san_dau s 
        ON dn.MaSan = s.MaSan

    WHERE td.MaTran = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $maTran);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$match = mysqli_fetch_assoc($result);

if (!$match) {
    die("Không tìm thấy trận đấu!");
}

$sqlTT = "
    SELECT dh.VaiTro, tt.MaTT, tt.TenTT
    FROM dieu_hanh dh
    JOIN trong_tai tt ON dh.MaTT = tt.MaTT
    WHERE dh.MaTran = ?
";

$stmtTT = mysqli_prepare($conn, $sqlTT);
mysqli_stmt_bind_param($stmtTT, "s", $maTran);
mysqli_stmt_execute($stmtTT);
$rsTT = mysqli_stmt_get_result($stmtTT);

$referees = [
    'Chinh' => null,
    'Bien'  => [],
    'Ban'   => null
];

while ($r = mysqli_fetch_assoc($rsTT)) {
    if ($r['VaiTro'] === 'Bien') {
        $referees['Bien'][] = $r;
    } else {
        $referees[$r['VaiTro']] = $r;
    }
}


$sqlHomePlayers = "
    SELECT
        ct.MaCT,
        ct.TenCT,
        ct.SoAo,
        ct.ViTri,
        dk.MaCT AS DaDangKy
    FROM cau_thu ct

    JOIN hop_dong hd
        ON ct.MaCT = hd.MaCT
        AND hd.MaDoiChuQuan = ?

    LEFT JOIN dang_ky_thi_dau dk
        ON ct.MaCT = dk.MaCT
        AND dk.MaTran = ?

    ORDER BY ct.SoAo
";


$stmtHome = mysqli_prepare($conn, $sqlHomePlayers);
if (!$stmtHome) {
    die("SQL lỗi (home): " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtHome,
    "ss",
    $match['MaDoiNha'], // 👈 đội nhà
    $maTran              // 👈 mã trận
);

mysqli_stmt_execute($stmtHome);
$homePlayers = mysqli_stmt_get_result($stmtHome);


$stmtAway = mysqli_prepare($conn, $sqlHomePlayers);
if (!$stmtAway) {
    die("SQL lỗi (away): " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmtAway,
    "ss",
    $match['MaDoiKhach'],
    $maTran
);

mysqli_stmt_execute($stmtAway);
$awayPlayers = mysqli_stmt_get_result($stmtAway);




?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cập Nhật Kết Quả - Premier League</title>
    <link rel="stylesheet" href="style.css">
    <style>
    /* CSS riêng cho bảng tỉ số TV */
    .tv-scoreboard {
        background: var(--primary-bg);
        color: white;
        padding: 30px;
        border-radius: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 40px;
        box-shadow: 0 10px 30px rgba(56, 0, 60, 0.3);
        margin-bottom: 30px;
    }

    .score-display {
        font-size: 4rem;
        font-weight: 800;
        background: white;
        color: var(--primary-bg);
        padding: 5px 40px;
        border-radius: 8px;
        font-family: 'Segoe UI', sans-serif;
        min-width: 150px;
        text-align: center;
    }

    .team-name {
        font-size: 1.8rem;
        font-weight: 700;
        text-transform: uppercase;
        width: 250px;
        text-align: center;
    }

    .squad-box {
        height: 300px;
        overflow-y: auto;
        border: 1px solid #eee;
        padding: 15px;
        background: #fafafa;
        border-radius: 8px;
    }

    .squad-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .squad-item:last-child {
        border: none;
    }

    /* Style cho phần thông tin tổ chức */
    .info-section-title {
        color: var(--primary-bg);
        border-bottom: 2px solid var(--accent-green);
        display: inline-block;
        padding-bottom: 5px;
        margin-bottom: 15px;
    }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>PREMIER LEAGUE</h2>
        <a href="lich-thi-dau.php" class="active">📅 Lịch thi đấu</a>
    </div>

    <div class="content">
        <a href="lich-thi-dau.php" class="btn btn-secondary">← Quay lại danh sách</a>

        <div style="text-align: center; margin: 20px 0;">
            <h2 style="margin:0; color: #888;">
                <?= strtoupper($match['TenVong']) ?>
            </h2>
            <p class="note">
                <?= $match['TenSan'] ?>
            </p>
        </div>

        <div class="tv-scoreboard">
            <div class="team-name"><?= strtoupper($match['DoiNha']) ?></div>
            <div class="score-display" id="matchScore">0 - 0</div>
            <div class="team-name"><?= strtoupper($match['DoiKhach']) ?></div>
        </div>


        <div class="card">
            <h3 class="info-section-title">ℹ️ Thông Tin Trận Đấu & Trọng Tài</h3>

            <div class="grid-2">
                <div style="border-right: 1px dashed #ddd; padding-right: 20px;">
                    <h4 style="margin-top: 0;">🎫 Vé & Khán Giả</h4>
                    <div class="form-group">
                        <label>Số lượng khán giả thực tế:</label>
                        <input type="number" id="spectators" class="form-control" placeholder="Ví dụ: 60000">
                    </div>
                    <div class="form-group">
                        <label>Giá vé trung bình (VNĐ):</label>
                        <input type="number" id="ticketPrice" class="form-control" placeholder="Ví dụ: 1500000"
                            required>
                        <p class="note">* Giá vé sẽ được lưu vào hệ thống</p>
                    </div>

                </div>

                <div style="padding-left: 10px;">
                    <h4 style="margin-top: 0;">👮 Tổ Trọng Tài Điều Khiển</h4>
                    <div class="form-group">
                        <label>Trọng tài chính (Main):</label>
                        <select id="refMain" class="form-control" disabled>
                            <?php if ($referees['Chinh']): ?>
                            <option value="<?= $referees['Chinh']['MaTT'] ?>">
                                <?= $referees['Chinh']['TenTT'] ?>
                            </option>
                            <?php else: ?>
                            <option>Chưa phân công</option>
                            <?php endif; ?>
                        </select>

                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Trợ lý 1 (Biên):</label>
                            <select disabled class="form-control">
                                <?php if (!empty($referees['Bien'][0])): ?>
                                <option><?= $referees['Bien'][0]['TenTT'] ?></option>
                                <?php else: ?>
                                <option>Chưa phân công</option>
                                <?php endif; ?>
                            </select>


                        </div>
                        <div class="form-group">
                            <label>Trợ lý 2 (Biên):</label>
                            <select disabled class="form-control">
                                <?php if (!empty($referees['Bien'][1])): ?>
                                <option><?= $referees['Bien'][1]['TenTT'] ?></option>
                                <?php else: ?>
                                <option>Chưa phân công</option>
                                <?php endif; ?>
                            </select>


                        </div>
                    </div>

                    <div class="form-group">
                        <label>Trọng tài bàn (4th Official):</label>
                        <select id="ref4th" class="form-control" disabled>
                            <?php if ($referees['Ban']): ?>
                            <option><?= $referees['Ban']['TenTT'] ?></option>
                            <?php else: ?>
                            <option>Chưa phân công</option>
                            <?php endif; ?>
                        </select>

                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3 style="color:#EF0107;">🔴 Đội hình <?= strtoupper($match['DoiNha']) ?></h3>

                <div class="squad-box">
                    <?php while ($p = mysqli_fetch_assoc($homePlayers)) : ?>
                    <div class="squad-item">
                        <label>
                            <input type="checkbox" class="chk-home" value="<?= $p['MaCT'] ?>"
                                <?= $p['DaDangKy'] ? 'checked' : '' ?>>
                            #<?= $p['SoAo'] ?> <?= $p['TenCT'] ?>
                            <?= $p['ViTri'] ? "({$p['ViTri']})" : '' ?>
                        </label>
                    </div>
                    <?php endwhile; ?>
                </div>

                <p class="note" id="countHome"></p>
            </div>


            <div class="card">
                <h3 style="color:#003399;">🔵 Đội hình <?= strtoupper($match['DoiKhach']) ?></h3>

                <div class="squad-box">
                    <?php while ($p = mysqli_fetch_assoc($awayPlayers)) : ?>
                    <div class="squad-item">
                        <label>
                            <input type="checkbox" class="chk-away" value="<?= $p['MaCT'] ?>"
                                <?= $p['DaDangKy'] ? 'checked' : '' ?>>
                            #<?= $p['SoAo'] ?> <?= $p['TenCT'] ?>
                            <?= $p['ViTri'] ? "({$p['ViTri']})" : '' ?>
                        </label>
                    </div>
                    <?php endwhile; ?>
                </div>

                <p class="note" id="countAway"></p>
            </div>

        </div>

        <div class="card">
            <h3>📝 Nhật Ký Trận Đấu</h3>
            <div class="grid-4" style="background: #f0f2f5; padding: 20px; border-radius: 8px; align-items: end;">
                <div class="form-group" style="margin:0;">
                    <label>Phút</label>
                    <input type="number" id="evMin" placeholder="Phút..." class="form-control">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Cầu thủ</label>
                    <select id="evPlayer" class="form-control">
                        <optgroup label="<?= strtoupper($match['DoiNha']) ?>">
                            <?php mysqli_data_seek($homePlayers, 0); ?>
                            <?php while ($p = mysqli_fetch_assoc($homePlayers)): ?>
                            <option value="<?= $p['MaCT'] ?>">
                                #<?= $p['SoAo'] ?> <?= $p['TenCT'] ?>
                            </option>
                            <?php endwhile; ?>
                        </optgroup>

                        <optgroup label="<?= strtoupper($match['DoiKhach']) ?>">
                            <?php mysqli_data_seek($awayPlayers, 0); ?>
                            <?php while ($p = mysqli_fetch_assoc($awayPlayers)): ?>
                            <option value="<?= $p['MaCT'] ?>">
                                #<?= $p['SoAo'] ?> <?= $p['TenCT'] ?>
                            </option>
                            <?php endwhile; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group" style="margin:0;">
                    <label>Sự kiện</label>
                    <select id="evType" class="form-control">
                        <option value="goal">⚽ Ghi bàn (+1)</option>
                        <option value="yellow">🟨 Thẻ vàng</option>
                        <option value="red">🟥 Thẻ đỏ</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <button class="btn btn-primary" onclick="addEvent()" style="width: 100%;">Thêm sự kiện</button>
                </div>
            </div>

            <div id="eventLog" style="margin-top: 20px;"></div>
        </div>

        <div style="text-align: center; margin-bottom: 50px;">
            <button class="btn btn-save" style="padding: 15px 50px; font-size: 1.2rem;" onclick="saveMatchResult()">XÁC
                NHẬN KẾT QUẢ</button>
        </div>
    </div>

    <script>
    let matchEvents = [];
    let scoreHome = 0,
        scoreAway = 0;
    let yellowCards = {};
    let redCards = new Set();

    function addEvent() {
        const min = document.getElementById('evMin').value;
        const maCT = document.getElementById('evPlayer').value;
        const type = document.getElementById('evType').value;

        if (!min || !maCT) {
            alert("Thiếu phút hoặc cầu thủ!");
            return;
        }

        // ❌ Nếu đã bị thẻ đỏ → không cho thêm sự kiện
        if (redCards.has(maCT)) {
            alert("❌ Cầu thủ này đã bị thẻ đỏ!");
            return;
        }

        // ===== XỬ LÝ THẺ =====
        if (type === 'yellow') {
            yellowCards[maCT] = (yellowCards[maCT] || 0) + 1;

            // 🟨🟨 → 🟥
            if (yellowCards[maCT] === 2) {
                redCards.add(maCT);

                matchEvents.push({
                    minute: min,
                    maCT: maCT,
                    type: 'red'
                });

                logEvent(min, maCT, '🟥 Thẻ đỏ (2 vàng)');
                return;
            }
        }

        if (type === 'red') {
            redCards.add(maCT);
        }

        // ===== BÀN THẮNG =====
        if (type === 'goal') {
            const isHome = document.querySelector(`.chk-home[value="${maCT}"]`);
            if (isHome) scoreHome++;
            else scoreAway++;

            document.getElementById('matchScore').innerText =
                `${scoreHome} - ${scoreAway}`;
        }

        // ===== LƯU SỰ KIỆN =====
        matchEvents.push({
            minute: min,
            maCT: maCT,
            type: type
        });

        logEvent(min, maCT, type);

        document.getElementById('evMin').value = '';
    }



    function saveMatchResult() {
        const ticketPrice = document.getElementById('ticketPrice').value;
        if (!ticketPrice) {
            alert("Chưa nhập giá vé!");
            return;
        }

        const spectators = document.getElementById('spectators').value;

        const homeAll = [...document.querySelectorAll('.chk-home')]
            .map(cb => cb.value);

        const awayAll = [...document.querySelectorAll('.chk-away')]
            .map(cb => cb.value);

        const homeChecked = [...document.querySelectorAll('.chk-home:checked')]
            .map(cb => cb.value);

        const awayChecked = [...document.querySelectorAll('.chk-away:checked')]
            .map(cb => cb.value);

        // ✅ KHAI BÁO TRƯỚC – KHÔNG ĐƯỢC ĐỂ SAU
        const formData = new FormData();

        formData.append('maTran', '<?= $maTran ?>');
        formData.append('giaVe', ticketPrice);
        formData.append('khanGia', spectators);
        formData.append('events', JSON.stringify(matchEvents));

        formData.append('homeAll', JSON.stringify(homeAll));
        formData.append('awayAll', JSON.stringify(awayAll));
        formData.append('homeChecked', JSON.stringify(homeChecked));
        formData.append('awayChecked', JSON.stringify(awayChecked));

        fetch('xu-ly-cap-nhat-ket-qua.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === 'success') {
                    alert("✅ Đã lưu toàn bộ trận đấu!");
                    window.location.href = 'lich-thi-dau.php';
                } else {
                    alert(data);
                }
            });
    }





    function updateCount(cls, id) {
        const checked = document.querySelectorAll('.' + cls + ':checked').length;
        document.getElementById(id).innerText = `Đã chọn: ${checked}/11`;
    }

    document.querySelectorAll('.chk-home').forEach(cb =>
        cb.addEventListener('change', () => updateCount('chk-home', 'countHome'))
    );

    document.querySelectorAll('.chk-away').forEach(cb =>
        cb.addEventListener('change', () => updateCount('chk-away', 'countAway'))
    );

    updateCount('chk-home', 'countHome');
    updateCount('chk-away', 'countAway');
    </script>
</body>

</html>