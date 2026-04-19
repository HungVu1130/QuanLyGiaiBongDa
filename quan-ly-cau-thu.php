<?php
$activePage = 'player';
$conn = new mysqli("localhost", "root", "", "league_football");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) die("Lỗi CSDL");

/* ================== XÓA CẦU THỦ ================== */
if(isset($_GET['xoa'])){
    $maCT = $_GET['xoa'];
    // xóa hợp đồng của cầu thủ
$conn->query("DELETE FROM hop_dong WHERE MaCT='$maCT'");

// xóa cầu thủ
$conn->query("DELETE FROM cau_thu WHERE MaCT='$maCT'");


    header("Location: quan-ly-cau-thu.php");
    exit;
}


/* ================== TẠO MÃ ================== */
function taoMaCT($conn){
    $rs = $conn->query("SELECT MaCT FROM cau_thu ORDER BY MaCT DESC LIMIT 1");
    if($rs && $rs->num_rows){
        $row = $rs->fetch_assoc();
        $num = intval(substr($row['MaCT'],2)) + 1;
        return "CT".str_pad($num,2,"0",STR_PAD_LEFT);
    }
    return "CT01";
}

function taoMaHD($conn){
    $rs = $conn->query("SELECT MaHD FROM hop_dong ORDER BY MaHD DESC LIMIT 1");
    if($rs && $rs->num_rows){
        $row = $rs->fetch_assoc();
        $num = intval(substr($row['MaHD'],2)) + 1;
        return "HD".str_pad($num,3,"0",STR_PAD_LEFT);
    }
    return "HD001";
}

/* ================== LẤY ĐỘI BÓNG ================== */
$dsDoi = $conn->query("SELECT MaDoi, TenDoi FROM doi_bong");

/* ================== LƯU DỮ LIỆU ================== */
if(isset($_POST['luu'])){
    $ten = $_POST['ten'];
    $ngaySinh = $_POST['ngay_sinh'];
    $viTri = $_POST['vi_tri'][0];
    $soAo  = $_POST['so_ao'][0];
    $maDoi = $_POST['doi_bong'][0];
    $tu    = $_POST['tu_ngay'][0];
    $den   = $_POST['den_ngay'][0];
    $loai  = $_POST['loai_hd'][0];

    if(isset($_POST['maCT'])){ // UPDATE
        $maCT = $_POST['maCT'];

        $conn->query("
        UPDATE cau_thu
        SET TenCT='$ten', NgaySinh='$ngaySinh', ViTri='$viTri', SoAo='$soAo'
        WHERE MaCT='$maCT'
        ");

        $conn->query("
        UPDATE hop_dong
SET TuNgay='$tu',
    DenNgay='$den',
    LoaiHD='$loai',
    MaDoiChuQuan='$maDoi'
WHERE MaCT='$maCT'

        ");

        $conn->query("
        UPDATE so_huu
        SET MaDoiChuQuan='$maDoi'
        WHERE MaCT='$maCT'
        ");

    } else { // INSERT
        $maCT = taoMaCT($conn);
        $conn->query("
        INSERT INTO cau_thu(MaCT, TenCT, NgaySinh, ViTri, SoAo)
        VALUES ('$maCT','$ten','$ngaySinh','$viTri','$soAo')
        ");

        $maHD = taoMaHD($conn);

$conn->query("
INSERT INTO hop_dong(MaHD, MaCT, MaDoiChuQuan, TuNgay, DenNgay, LoaiHD)
VALUES ('$maHD','$maCT','$maDoi','$tu','$den','$loai')
");
    }

    header("Location: quan-ly-cau-thu.php");
    exit;
}

$edit = false;
$ctEdit = null;

if(isset($_GET['sua'])){
    $edit = true;
    $maCT = $_GET['sua'];

    $sqlEdit = "
SELECT ct.*, hd.*
FROM cau_thu ct
JOIN hop_dong hd ON ct.MaCT = hd.MaCT
WHERE ct.MaCT='$maCT'
LIMIT 1
";
$ctEdit = $conn->query($sqlEdit)->fetch_assoc();

}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản Lý Cầu Thủ - Premier League</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h1>👕 Hồ Sơ Cầu Thủ</h1>

        <form method="post">
            <?php if($edit): ?>
            <input type="hidden" name="maCT" value="<?= $ctEdit['MaCT'] ?>">
            <?php endif; ?>

            <div class="card">
                <h3>➕ Đăng Ký Cầu Thủ Mới</h3>

                <h4
                    style="color: var(--primary-bg); border-bottom: 2px solid var(--accent-green); display: inline-block; padding-bottom: 5px;">
                    1. Thông tin cá nhân</h4>

                <div class="grid-2">
                    <div class="form-group">
                        <label>Họ và Tên <span style="color:red">*</span>:</label>
                        <input type="text" name="ten" value="<?= $edit ? $ctEdit['TenCT'] : '' ?>"
                            placeholder="Ví dụ: Erling Haaland">

                    </div>
                    <div class="form-group">
                        <label>Ngày sinh <span style="color:red">*</span>:</label>
                        <input type="date" name="ngay_sinh" value="<?= $edit ? $ctEdit['NgaySinh'] : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Vị trí sở trường:</label>
                        <select name="vi_tri[]">
                            <?php
$vitri = ["Tiền đạo","Tiền vệ","Hậu vệ","Thủ môn"];
foreach($vitri as $v):
?>
                            <option value="<?= $v ?>" <?= ($edit && $ctEdit['ViTri']==$v)?'selected':'' ?>>
                                <?= $v ?>
                            </option>
                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>

                <h4
                    style="color: var(--primary-bg); border-bottom: 2px solid var(--accent-green); display: inline-block; padding-bottom: 5px; margin-top: 20px;">
                    2. Hợp đồng & Chuyển nhượng</h4>

                <button type="button" class="btn btn-add" style="font-size: 12px; margin-bottom: 10px;"
                    onclick="themDongHopDong()">+ Thêm giai đoạn</button>

                <table class="contract-table">
                    <thead>
                        <tr>
                            <th style="width: 25%">CLB Chủ Quản</th>
                            <th style="width: 10%">Số áo</th>
                            <th style="width: 20%">Loại HĐ</th>
                            <th style="width: 15%">Từ ngày</th>
                            <th style="width: 15%">Đến ngày</th>
                            <th style="width: 5%">Xóa</th>
                        </tr>
                    </thead>
                    <tbody id="dsHopDong">
                        <tr>
                            <td>
                                <select name="doi_bong[]">
                                    <?php while($d = $dsDoi->fetch_assoc()): ?>
                                    <option value="<?= $d['MaDoi'] ?>"
                                        <?= ($edit && $d['MaDoi']==$ctEdit['MaDoiChuQuan'])?'selected':'' ?>>
                                        <?= $d['TenDoi'] ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>

                            </td>
                            <td><input type="number" name="so_ao[]" value="<?= $edit ? $ctEdit['SoAo'] : '' ?>"
                                    style="width:80px;"></td>
                            <td>
                                <select name="loai_hd[]">
                                    <option <?= ($edit && $ctEdit['LoaiHD']=="Chính thức")?'selected':'' ?>>
                                        Chính thức
                                    </option>
                                    <option <?= ($edit && $ctEdit['LoaiHD']=="Cho mượn")?'selected':'' ?>>
                                        Cho mượn
                                    </option>
                                </select>

                            </td>
                            <td><input type="date" name="tu_ngay[]" value="<?= $edit ? $ctEdit['TuNgay'] : '' ?>"></td>
                            <td><input type="date" name="den_ngay[]" value="<?= $edit ? $ctEdit['DenNgay'] : '' ?>">
                            </td>
                            <td style="text-align: center;">
                                <button class="btn btn-delete" style="padding: 5px 8px;"
                                    onclick="xoaDong(this)">🗑️</button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style="text-align: right; margin-top: 25px;">
                    <button type="reset" class="btn btn-secondary">Làm mới</button>
                    <button type="submit" name="luu" class="btn btn-save">Lưu hồ sơ</button>
                </div>

            </div>
        </form>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3>Danh sách cầu thủ đăng ký</h3>
                <div style="display: flex; gap: 10px;">
                    <form method="get" style="display:flex; gap:10px;">
                        <select id="doiFilter" class="form-control" style="width: 200px;" onchange="filterByTeam()">
                            <option value="">-- Lọc theo đội --</option>
                            <?php
    $dsDoiLoc = $conn->query("SELECT MaDoi, TenDoi FROM doi_bong");
    while($d = $dsDoiLoc->fetch_assoc()):
    ?>
                            <option value="<?= $d['TenDoi'] ?>">
                                <?= $d['TenDoi'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>

                    </form>

                    <input type="text" id="searchInput" class="search-box" placeholder="🔍 Tìm tên, số áo..."
                        style="width: 200px;" onkeyup="searchTable()"
                        onkeydown="if(event.key === 'Enter') event.preventDefault();">

                </div>

            </div>

            <table id="playerTable">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã</th>
                        <th>Họ Tên</th>
                        <th>CLB Hiện tại</th>
                        <th>Số áo</th>
                        <th>Vị Trí</th>
                        <th>Trạng thái</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $where = "";
if(isset($_GET['doi']) && $_GET['doi'] != ""){
    $maDoiLoc = $_GET['doi'];
    $where = "WHERE sh.MaDoiChuQuan = '$maDoiLoc'";
}
$sql = "
SELECT ct.MaCT, ct.TenCT, ct.ViTri, ct.SoAo,
       db.TenDoi, hd.LoaiHD
FROM cau_thu ct
JOIN hop_dong hd ON ct.MaCT = hd.MaCT
JOIN doi_bong db ON hd.MaDoiChuQuan = db.MaDoi
";

$rs = $conn->query($sql);
$stt = 1;
while($r = $rs->fetch_assoc()):
?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><b><?= $r['MaCT'] ?></b></td>
                        <td><b><?= $r['TenCT'] ?></b></td>
                        <td><?= $r['TenDoi'] ?></td>

                        <td>
                            <span class="badge bg-blue" style="color:#000;font-size: 14px;">
                                #<?= $r['SoAo'] ?>
                            </span>
                        </td>

                        <td><?= $r['ViTri'] ?></td>
                        <td>
                            <span class="badge <?= $r['LoaiHD']=='Cho mượn'?'bg-orange':'bg-green' ?>">
                                <?= $r['LoaiHD'] ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a class="btn btn-edit" href="?sua=<?= $r['MaCT'] ?>">Sửa</a>
                            <a class="btn btn-delete" href="?xoa=<?= $r['MaCT'] ?>"
                                onclick="return confirm('Xóa cầu thủ này?')">
                                Xóa
                            </a>
                        </td>
                    </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

    <script>
    function themDongHopDong() {
        var tbody = document.getElementById("dsHopDong");
        var row = document.createElement("tr");
        row.innerHTML = `
                <td><select style="border: none; background: transparent; width: 100%;"><option>Manchester City</option><option>Arsenal</option></select></td>
                <td><input type="number" style="width: 60px;"></td>
                <td><select style="border: none; background: transparent; width: 100%;"><option>Chính thức</option><option>Cho mượn</option></select></td>
                <td><input type="date" style="width: 100%;"></td>
                <td><input type="date" style="width: 100%;"></td>
                <td style="text-align: center;"><button class="btn btn-delete" style="padding: 5px 8px;" onclick="xoaDong(this)">🗑️</button></td>
            `;
        tbody.appendChild(row);
    }

    function xoaDong(btn) {
        var row = btn.parentNode.parentNode;
        var tbody = document.getElementById("dsHopDong");
        if (tbody.rows.length > 1) row.parentNode.removeChild(row);
        else alert("Cần ít nhất một thông tin hợp đồng!");
    }

    function searchTable() {
        var input = document.getElementById("searchInput").value.toUpperCase();
        var doi = document.getElementById("doiFilter").value.toUpperCase();
        var tr = document.getElementById("playerTable").getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName("td");
            var matchSearch = false;

            for (var j = 0; j < td.length; j++) {
                if (td[j] && td[j].innerText.toUpperCase().includes(input)) {
                    matchSearch = true;
                    break;
                }
            }

            var matchDoi = true;
            if (doi !== "") {
                var tdDoi = td[3]; // cột CLB
                matchDoi = tdDoi && tdDoi.innerText.toUpperCase() === doi;
            }

            tr[i].style.display = (matchSearch && matchDoi) ? "" : "none";
        }
    }

    function filterByTeam() {
        var doi = document.getElementById("doiFilter").value.toUpperCase();
        var table = document.getElementById("playerTable");
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var tdDoi = tr[i].getElementsByTagName("td")[3]; // cột CLB
            if (!tdDoi) continue;

            var tenDoi = tdDoi.innerText.toUpperCase();
            tr[i].style.display =
                (doi === "" || tenDoi === doi) ? "" : "none";
        }
    }
    </script>
</body>

</html>