<?php
require_once "config/db.php";

$success = false;
$error = "";

/* ===== LOAD VÒNG ĐẤU ===== */
$vongList = mysqli_query($conn, "SELECT MaVong, TenVong FROM vong_dau");

/* ===== LOAD ĐỘI BÓNG + SÂN ===== */
$doiSql = "
    SELECT 
        d.MaDoi, 
        d.TenDoi, 
        s.TenSan,
        GROUP_CONCAT(m.MauAo) AS MauAos
    FROM doi_bong d
    LEFT JOIN san_dau s ON d.MaSan = s.MaSan
    LEFT JOIN mau_ao_doi m ON d.MaDoi = m.MaDoi
    GROUP BY d.MaDoi
";
$doiList = mysqli_query($conn, $doiSql);

/* ===== LOAD TRỌNG TÀI ===== */
$trongTaiList = mysqli_query(
    $conn,
    "SELECT MaTT, TenTT, CapBac FROM trong_tai"
);

/* ===== XỬ LÝ SUBMIT ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rs = mysqli_query($conn, "
    SELECT MaTran 
    FROM tran_dau 
    ORDER BY MaTran DESC 
    LIMIT 1
");

if (mysqli_num_rows($rs) > 0) {
    $row = mysqli_fetch_assoc($rs);
    // T07 → 7
    $num = intval(substr($row['MaTran'], 1)) + 1;
} else {
    $num = 1;
}

$maTran = 'T' . str_pad($num, 2, '0', STR_PAD_LEFT);

    $maVong   = $_POST['maVong'];
    $thoiGian = $_POST['thoiGian'];
    $doiNha   = $_POST['doiNha'];
    $doiKhach = $_POST['doiKhach'];
    $mauAoNha   = $_POST['mauAoNha'] ?? null;
$mauAoKhach = $_POST['mauAoKhach'] ?? null;

    $ttChinh = $_POST['ttChinh'] ?? null;
    $ttBien1 = $_POST['ttBien1'] ?? null;
    $ttBien2 = $_POST['ttBien2'] ?? null;
    $ttBan   = $_POST['ttBan']   ?? null;

    if ($doiNha == $doiKhach) {
        $error = "Đội nhà và đội khách không được trùng nhau!";
    } else {

        mysqli_begin_transaction($conn);

        try {
            // 1️⃣ Thêm trận đấu
            $sqlTran = "INSERT INTO tran_dau (MaTran, ThoiGian, MaVong, TrangThai)
                        VALUES (?, ?, ?, 'Chưa diễn ra')";
            $stmt1 = mysqli_prepare($conn, $sqlTran);
            mysqli_stmt_bind_param($stmt1, "sss", $maTran, $thoiGian, $maVong);
            mysqli_stmt_execute($stmt1);

            // 2️⃣ Đội nhà
            $sqlTG = "INSERT INTO tham_gia (MaTran, MaDoi, VaiTro, MauAoRaSan)
          VALUES (?, ?, ?, ?)";


            $stmt2 = mysqli_prepare($conn, $sqlTG);

            $vaiTroNha = "Nha";
mysqli_stmt_bind_param(
    $stmt2,
    "ssss",
    $maTran,
    $doiNha,
    $vaiTroNha,
    $mauAoNha
);
mysqli_stmt_execute($stmt2);


            // 3️⃣ Đội khách
            $vaiTroKhach = "Khach";
mysqli_stmt_bind_param(
    $stmt2,
    "ssss",
    $maTran,
    $doiKhach,
    $vaiTroKhach,
    $mauAoKhach
);
mysqli_stmt_execute($stmt2);


            // 3️⃣ PHÂN CÔNG TRỌNG TÀI → dieu_hanh
    $sqlDH = "INSERT INTO dieu_hanh (MaTran, MaTT, VaiTro, LuongTT)
          VALUES (?, ?, ?, ?)";
$stmt3 = mysqli_prepare($conn, $sqlDH);

if ($ttChinh) {
    $role = "Chinh";
    $luong = 3000000;
    mysqli_stmt_bind_param($stmt3, "sssd", $maTran, $ttChinh, $role, $luong);
    mysqli_stmt_execute($stmt3);
}

if ($ttBien1) {
    $role = "Bien";
    $luong = 2000000;
    mysqli_stmt_bind_param($stmt3, "sssd", $maTran, $ttBien1, $role, $luong);
    mysqli_stmt_execute($stmt3);
}

if ($ttBien2) {
    $role = "Bien";
    $luong = 2000000;
    mysqli_stmt_bind_param($stmt3, "sssd", $maTran, $ttBien2, $role, $luong);
    mysqli_stmt_execute($stmt3);
}

if ($ttBan) {
    $role = "Ban";
    $luong = 1000000;
    mysqli_stmt_bind_param($stmt3, "sssd", $maTran, $ttBan, $role, $luong);
    mysqli_stmt_execute($stmt3);
}


            mysqli_commit($conn);
            $success = true;
            

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Lỗi khi tạo trận đấu!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm Trận Đấu Mới</title>
    <link rel="stylesheet" href="style.css">

    <!-- CSS cho màu áo -->
    <style>
    .kit-picker {
        display: flex;
        gap: 12px;
        margin-top: 10px;
    }

    .kit {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: #ccc;
        cursor: pointer;
        border: 2px solid #ddd;
    }

    .kit.active {
        border: 3px solid #000;
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

        <div class="card" style="max-width: 900px; margin: 0 auto;">
            <h2 style="color: var(--primary-bg); text-align: center; border-bottom: none;">Thêm Trận Đấu Mới</h2>

            <form id="createMatchForm" method="POST">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Vòng đấu</label>
                        <select name="maVong" required>
                            <option value="">-- Chọn vòng --</option>
                            <?php while ($v = mysqli_fetch_assoc($vongList)) : ?>
                            <option value="<?= $v['MaVong'] ?>">
                                <?= $v['TenVong'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>


                <div class="form-group">
                    <label>Ngày & Giờ thi đấu (*)</label>
                    <input type="datetime-local" name="thoiGian" required>
                </div>


                <hr style="border: 0; border-top: 1px dashed #ddd; margin: 20px 0;">

                <div class="grid-2">

                    <!-- ĐỘI NHÀ -->
                    <div class="form-group">
                        <label>Đội nhà</label>
                        <select name="doiNha" id="homeTeam" onchange="onChangeHomeTeam(this)" required>
                            <option value="">-- Chọn đội --</option>
                            <?php mysqli_data_seek($doiList, 0); ?>
                            <?php while ($d = mysqli_fetch_assoc($doiList)) : ?>
                            <option value="<?= $d['MaDoi'] ?>" data-san="<?= $d['TenSan'] ?>"
                                data-colors="<?= $d['MauAos'] ?>">
                                <?= $d['TenDoi'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <!-- 👕 ÁO ĐỘI NHÀ (UI MỚI) -->
                        <div id="homeKits" class="kit-picker"></div>
                        <input type="hidden" name="mauAoNha" id="mauAoNha">
                    </div>


                    <!-- ĐỘI KHÁCH -->
                    <div class="form-group">
                        <label>Đội khách</label>
                        <select name="doiKhach" id="awayTeam" onchange="onChangeAwayTeam(this)" required>
                            <option value="">-- Chọn đội --</option>
                            <?php mysqli_data_seek($doiList, 0); ?>
                            <?php while ($d = mysqli_fetch_assoc($doiList)) : ?>
                            <option value="<?= $d['MaDoi'] ?>" data-colors="<?= $d['MauAos'] ?>">
                                <?= $d['TenDoi'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                        <!-- 👕 ÁO ĐỘI KHÁCH (UI MỚI) -->
                        <div id="awayKits" class="kit-picker"></div>
                        <input type="hidden" name="mauAoKhach" id="mauAoKhach">
                    </div>

                </div>


                <div class="form-group">
                    <label>Sân thi đấu</label>
                    <input type="text" id="stadium" readonly>
                </div>


                <hr style="border: 0; border-top: 1px dashed #ddd; margin: 20px 0;">

                <h4 style="margin-bottom: 15px; color: var(--primary-bg);">🚩 Phân Công Tổ Trọng Tài</h4>

                <div class="form-group">
                    <label>Trọng tài chính (Main Referee):</label>
                    <select name="ttChinh" class="form-control">

                        <option value="">-- Chọn trọng tài chính --</option>
                        <?php while ($tt = mysqli_fetch_assoc($trongTaiList)) : ?>
                        <option value="<?= $tt['MaTT'] ?>">
                            <?= $tt['TenTT'] ?> (<?= $tt['CapBac'] ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>


                <div class="grid-2">
                    <div class="form-group">
                        <label>Trợ lý trọng tài 1 (Biên 1):</label>
                        <select name="ttBien1" class="form-control">
                            <option value="">-- Chọn trợ lý 1 --</option>
                            <?php mysqli_data_seek($trongTaiList, 0); ?>
                            <?php while ($tt = mysqli_fetch_assoc($trongTaiList)) : ?>
                            <option value="<?= $tt['MaTT'] ?>">
                                <?= $tt['TenTT'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Trợ lý trọng tài 2 (Biên 2):</label>
                        <select name="ttBien2" class="form-control">
                            <option value="">-- Chọn trợ lý 2 --</option>
                            <?php mysqli_data_seek($trongTaiList, 0); ?>
                            <?php while ($tt = mysqli_fetch_assoc($trongTaiList)) : ?>
                            <option value="<?= $tt['MaTT'] ?>">
                                <?= $tt['TenTT'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                </div>

                <div class="form-group">
                    <label>Trọng tài bàn (Fourth Official):</label>
                    <select name="ttBan" class="form-control">
                        <option value="">-- Chọn trọng tài bàn --</option>
                        <?php mysqli_data_seek($trongTaiList, 0); ?>
                        <?php while ($tt = mysqli_fetch_assoc($trongTaiList)) : ?>
                        <option value="<?= $tt['MaTT'] ?>">
                            <?= $tt['TenTT'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div style="text-align: right; margin-top: 30px;">
                    <button type="submit" class="btn btn-save" style="padding: 12px 30px;">
                        Lên Lịch Ngay
                    </button>

                </div>
            </form>
        </div>
    </div>

    <script>
    function setStadium() {
        const select = document.getElementById("homeTeam");
        const option = select.options[select.selectedIndex];
        const san = option.getAttribute("data-san");
        document.getElementById("stadium").value = san || "";
    }
    </script>



    </script>
    <?php if ($success): ?>
    <script>
    alert("🎉 Tạo trận đấu thành công!");
    window.location.href = "lich-thi-dau.php";
    </script>
    <?php endif; ?>

    <?php if ($error): ?>
    <script>
    alert("❌ <?= $error ?>");
    </script>
    <?php endif; ?>

    <script>
    function renderKits(selectEl, containerId, inputId) {
        const option = selectEl.options[selectEl.selectedIndex];
        const colors = option.getAttribute("data-colors");
        const box = document.getElementById(containerId);

        box.innerHTML = "";
        if (!colors) return;

        colors.split(",").forEach(color => {
            const kit = document.createElement("div");
            kit.className = "kit";
            kit.style.backgroundColor = color;

            kit.onclick = () => {
                box.querySelectorAll(".kit").forEach(k => k.classList.remove("active"));
                kit.classList.add("active");
                document.getElementById(inputId).value = color;
            };

            box.appendChild(kit);
        });
    }

    function onChangeHomeTeam(select) {
        setStadium();
        renderKits(select, "homeKits", "mauAoNha");
    }

    function onChangeAwayTeam(select) {
        renderKits(select, "awayKits", "mauAoKhach");
    }
    </script>



</body>

</html>