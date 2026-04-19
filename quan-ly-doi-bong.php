<?php
$activePage = 'team';
$conn = mysqli_connect("localhost","root","","league_football");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn,"utf8");
$dsSan = mysqli_query($conn, "SELECT MaSan, TenSan FROM san_dau");


if (isset($_POST['action']) && $_POST['action'] == 'save') {
    $maMoi = $_POST['MaDoi'];
$maCu  = $_POST['OldMaDoi'] ?? '';

$ten = $_POST['TenDoi'];
$san = $_POST['MaSan'];

$mauNha   = $_POST['MauNha'];
$mauKhach = $_POST['MauKhach'];

if ($maCu == '') {
    // =====================
    // 👉 THÊM MỚI ĐỘI BÓNG
    // =====================
    mysqli_query($conn,"
        INSERT INTO doi_bong(MaDoi,TenDoi,MaSan)
        VALUES('$maMoi','$ten','$san')
    ");

    mysqli_query($conn,"
        INSERT INTO mau_ao_doi(MaDoi,MauAo)
        VALUES ('$maMoi','$mauNha'),('$maMoi','$mauKhach')
    ");

} else {
    // =====================
    // 👉 SỬA (CÓ ĐỔI MÃ ĐỘI)
    // =====================

    mysqli_begin_transaction($conn);

    // 1️⃣ KIỂM TRA MÃ MỚI CÓ TRÙNG KHÔNG (nếu đổi mã)
    if ($maMoi != $maCu) {
        $check = mysqli_query($conn,"
            SELECT MaDoi FROM doi_bong WHERE MaDoi='$maMoi'
        ");
        if (mysqli_num_rows($check) > 0) {
            mysqli_rollback($conn);
            echo "MA_TON_TAI";
            exit;
        }
    }

    // 2️⃣ UPDATE bảng đội bóng
    mysqli_query($conn,"
        UPDATE doi_bong 
        SET MaDoi='$maMoi', TenDoi='$ten', MaSan='$san'
        WHERE MaDoi='$maCu'
    ");

    // 3️⃣ UPDATE bảng liên quan
    mysqli_query($conn,"
        UPDATE mau_ao_doi
        SET MaDoi='$maMoi'
        WHERE MaDoi='$maCu'
    ");

    // 4️⃣ XÓA + THÊM MÀU ÁO
    mysqli_query($conn,"
        DELETE FROM mau_ao_doi WHERE MaDoi='$maMoi'
    ");

    mysqli_query($conn,"
        INSERT INTO mau_ao_doi(MaDoi,MauAo)
        VALUES ('$maMoi','$mauNha'),('$maMoi','$mauKhach')
    ");

    mysqli_commit($conn);
}
    echo "OK";
    exit;
}


if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $ma = $_POST['MaDoi'];
    mysqli_query($conn,"DELETE FROM mau_ao_doi WHERE MaDoi='$ma'");
    mysqli_query($conn,"DELETE FROM doi_bong WHERE MaDoi='$ma'");
    echo "OK";
    exit;
}


$data = mysqli_query($conn,"
    SELECT d.MaDoi, d.TenDoi, d.MaSan, s.TenSan,
           GROUP_CONCAT(m.MauAo) AS MauAo
    FROM doi_bong d
    LEFT JOIN san_dau s ON d.MaSan = s.MaSan
    LEFT JOIN mau_ao_doi m ON d.MaDoi = m.MaDoi
    GROUP BY d.MaDoi
");

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý CLB - Premier League</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h1>⚽ Quản Lý Câu Lạc Bộ</h1>

        <div class="card">
            <h3 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">➕ Thêm / Sửa CLB</h3>
            <form id="teamForm">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Mã Đội (Viết tắt 3 chữ):</label>
                        <input type="text" id="ma-doi" placeholder="VD: MUN, ARS, LIV...">
                    </div>

                    <div class="form-group">
                        <label>Tên Đội Bóng:</label>
                        <input type="text" id="ten-doi" placeholder="VD: Manchester United">
                    </div>
                </div>

                <div class="form-group">
                    <label>Sân Nhà (Stadium):</label>
                    <select id="san-nha">
                        <option value="">-- Chọn Sân Vận Động --</option>
                        <?php while($s = mysqli_fetch_assoc($dsSan)) { ?>
                        <option value="<?= $s['MaSan'] ?>">
                            <?= $s['MaSan'] ?> - <?= $s['TenSan'] ?>
                        </option>
                        <?php } ?>
                    </select>

                    <p class="note">Định dạng: [Mã Sân] - [Tên Sân]</p>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Màu áo Sân nhà:</label>
                        <input type="color" id="mau-nha" value="#DA291C" style="height: 45px; cursor: pointer;">
                    </div>

                    <div class="form-group">
                        <label>Màu áo Sân khách:</label>
                        <input type="color" id="mau-khach" value="#000000" style="height: 45px; cursor: pointer;">
                    </div>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">Làm mới</button>
                    <button type="button" onclick="saveTeam()" class="btn btn-save">
                        💾 Lưu Đội Bóng
                    </button>

                </div>
                <input type="hidden" id="old-ma-doi">
            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin: 0;">Danh Sách 20 CLB</h3>
                    <p class="note">Mùa giải 2026/2027</p>
                </div>

                <input type="text" id="searchInput" onkeyup="searchTable()" class="search-box"
                    placeholder="🔍 Tìm tên đội, sân...">
            </div>

            <table id="teamTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Mã</th>
                        <th>Tên CLB</th>
                        <th>Sân Nhà (Mã - Tên)</th>
                        <th>Màu Áo (Nhà/Khách)</th>
                        <th style="text-align: right;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while($r = mysqli_fetch_assoc($data)) { 
    $mau = explode(',', $r['MauAo']);
?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><b><?= $r['MaDoi'] ?></b></td>
                        <td style="font-weight:bold;"><?= $r['TenDoi'] ?></td>
                        <td><?= $r['MaSan'] ?> - <?= $r['TenSan'] ?></td>
                        <td>
                            <div style="display:flex; gap:5px;">
                                <div style="width:20px;height:20px;background:<?= $mau[0] ?>"></div>
                                <div style="width:20px;height:20px;background:<?= $mau[1] ?>"></div>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <button class="btn btn-edit" onclick="editTeam(
                '<?= $r['MaDoi'] ?>',
                '<?= addslashes($r['TenDoi']) ?>',
                '<?= $r['MaSan'] ?>',
                '<?= $mau[0] ?>',
                '<?= $mau[1] ?>'
            )">
                                Sửa
                            </button>
                            <button class="btn btn-delete" onclick="deleteTeam('<?= $r['MaDoi'] ?>')">
                                Xóa
                            </button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

    <script>
    function editTeam(ma, ten, san, mauNha, mauKhach) {
        document.getElementById('ma-doi').value = ma;
        document.getElementById('old-ma-doi').value = ma;
        document.getElementById('ten-doi').value = ten;
        document.getElementById('san-nha').value = san;
        document.getElementById('mau-nha').value = mauNha;
        document.getElementById('mau-khach').value = mauKhach;

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function resetForm() {
        document.getElementById('teamForm').reset();
    }

    function searchTable() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("teamTable");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var found = false;
            var td = tr[i].getElementsByTagName("td");
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    var txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? "" : "none";
        }
    }

    function saveTeam() {
        let maMoi = document.getElementById('ma-doi').value.trim();
        let maCu = document.getElementById('old-ma-doi').value;
        let ten = document.getElementById('ten-doi').value.trim();
        let san = document.getElementById('san-nha').value;
        let mauNha = document.getElementById('mau-nha').value;
        let mauKhach = document.getElementById('mau-khach').value;

        if (!maMoi || !ten || !san) {
            alert("Vui lòng nhập đầy đủ thông tin!");
            return;
        }

        fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=save&MaDoi=${maMoi}&OldMaDoi=${maCu}&TenDoi=${ten}&MaSan=${san}&MauNha=${mauNha}&MauKhach=${mauKhach}`
            })
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "OK") {
                    alert("Lưu thành công!");
                    location.reload();
                } else if (res.trim() === "MA_TON_TAI") {
                    alert("❌ Mã đội đã tồn tại, vui lòng chọn mã khác!");
                } else {
                    alert("❌ Có lỗi xảy ra, kiểm tra lại!");
                }
            });

    }

    function deleteTeam(ma) {
        if (!confirm("Xóa đội bóng này?")) return;

        fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=delete&MaDoi=${ma}`
            })
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "OK") {
                    alert("Đã xóa!");
                    location.reload();
                }
            });
    }
    </script>
</body>

</html>