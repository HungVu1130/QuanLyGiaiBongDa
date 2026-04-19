<?php
$activePage = 'referee';
include "config/db.php";

/* ===== THÊM / SỬA ===== */
if (isset($_POST['save'])) {
    $ten  = $_POST['ten'];
    $ngay = $_POST['ngay'];
    $cap  = $_POST['cap'];

    // ===== TRƯỜNG HỢP SỬA =====
    if (!empty($_POST['ma_hidden'])) {
        $ma = $_POST['ma_hidden'];
        mysqli_query($conn, "UPDATE trong_tai 
            SET TenTT='$ten', NgaySinh='$ngay', CapBac='$cap'
            WHERE MaTT='$ma'");
    } 
    // ===== TRƯỜNG HỢP THÊM =====
    else {
        // Lấy mã lớn nhất
        $rs = mysqli_query($conn, "SELECT MaTT FROM trong_tai ORDER BY MaTT DESC LIMIT 1");
        if (mysqli_num_rows($rs) > 0) {
            $row = mysqli_fetch_assoc($rs);
            $num = intval(substr($row['MaTT'], 2)) + 1;
        } else {
            $num = 1;
        }

        // Format TT01, TT02,...
        $ma = 'TT' . str_pad($num, 2, '0', STR_PAD_LEFT);

        mysqli_query($conn, "INSERT INTO trong_tai VALUES ('$ma','$ten','$ngay','$cap')");
    }

    header("Location: quan-ly-trong-tai.php");
    exit;
}


/* ===== XÓA ===== */
if (isset($_GET['delete'])) {
    mysqli_query($conn, "DELETE FROM trong_tai WHERE MaTT='{$_GET['delete']}'");
    header("Location: quan-ly-trong-tai.php");
    exit;
}

/* ===== SỬA ===== */
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM trong_tai WHERE MaTT='{$_GET['edit']}'")
    );
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Referees - Premier League</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h1>🚩 Quản Lý Trọng Tài (PGMOL)</h1>

        <div class="card">
            <h3>➕ Thêm Trọng Tài</h3>
            <form method="post">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Họ và Tên:</label>
                        <input type="text" name="ten" value="<?= $edit['TenTT'] ?? '' ?>"
                            placeholder="Ví dụ: Howard Webb">
                    </div>

                    <div class="form-group">
                        <label>Ngày sinh:</label>
                        <input type="date" name="ngay" value="<?= $edit['NgaySinh'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Cấp bậc:</label>
                        <select name="cap" class="form-control">
                            <option value="Ngoại hạng Anh" <?= ($edit && $edit['CapBac']=='Ngoại hạng Anh')?'selected':'' ?>>
                                Ngoại hạng Anh
                            </option>
                            <option value="Hạng Nhất" <?= ($edit && $edit['CapBac']=='Hạng Nhất')?'selected':'' ?>>
                                Hạng Nhất
                            </option>
                            <option value="FIFA" <?= ($edit && $edit['CapBac']=='FIFA')?'selected':'' ?>>
                                FIFA Elite
                            </option>
                        </select>
                    </div>
                </div>

                <div style="text-align: right;">
                    <button type="submit" name="save" class="btn btn-save">
                        Lưu hồ sơ
                    </button>
                </div>
                <?php if ($edit): ?>
    <input type="hidden" name="ma_hidden" value="<?= $edit['MaTT'] ?>">
<?php endif; ?>

            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                <h3>Danh sách Trọng tài</h3>
                <input type="text" class="search-box" placeholder="🔍 Tìm kiếm..." onkeyup="searchTable()"
                    id="searchInput">
            </div>

            <table id="refereeTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Họ Tên</th>
                        <th>Năm sinh</th>
                        <th>Cấp bậc</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
$rs = mysqli_query($conn, "SELECT * FROM trong_tai ORDER BY MaTT");
while($r = mysqli_fetch_assoc($rs)):
?>
                    <tr>
                        <td><b><?= $r['MaTT'] ?></b></td>
                        <td><b><?= $r['TenTT'] ?></b></td>
                        <td><?= date('Y', strtotime($r['NgaySinh'])) ?></td>
                        <td>
                            <?php if($r['CapBac']=='FIFA'): ?>
                            <span class="badge bg-green">FIFA Elite</span>
                            <?php elseif($r['CapBac']=='Ngoại hạng Anh'): ?>
                            <span class="badge" style="background:#333;color:#fff">Ngoại hạng Anh</span>
                            <?php else: ?>
                            <span class="badge" style="background:#666;color:#fff">Hạng Nhất</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a class="btn btn-edit" href="?edit=<?= $r['MaTT'] ?>"
                                style="padding:5px 10px;font-size:11px;">
                                Sửa
                            </a>

                            <a class="btn btn-delete" href="?delete=<?= $r['MaTT'] ?>"
                                onclick="return confirm('Xóa trọng tài này?')" style="padding:5px 10px;font-size:11px;">
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
    function searchTable() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("refereeTable");
        var tr = table.getElementsByTagName("tr");
        for (var i = 1; i < tr.length; i++) {
            var found = false;
            var td = tr[i].getElementsByTagName("td");
            for (var j = 0; j < td.length; j++) {
                if (td[j] && td[j].innerText.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
            tr[i].style.display = found ? "" : "none";
        }
    }
    </script>
</body>

</html>