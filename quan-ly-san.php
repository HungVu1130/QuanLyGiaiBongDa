<?php
$activePage = 'stadium';
$conn = mysqli_connect("localhost","root","","league_football");
mysqli_set_charset($conn,"utf8");

// THÊM SÂN
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $ten = $_POST['TenSan'];
    $dc  = $_POST['DiaChi'];

    $kq = mysqli_query($conn,"SELECT MAX(MaSan) AS maxMa FROM san_dau");
    $row = mysqli_fetch_assoc($kq);

    $so = 1;
    if ($row['maxMa']) {
        $so = intval(substr($row['maxMa'], 3)) + 1;
    }

    $ma = "SVD" . str_pad($so, 2, "0", STR_PAD_LEFT);

    mysqli_query($conn,"INSERT INTO san_dau VALUES ('$ma','$ten','$dc')");
    echo "OK";
    exit;
}

// SỬA SÂN
if (isset($_POST['action']) && $_POST['action'] == 'update') {
    $ma  = $_POST['MaSan'];
    $ten = $_POST['TenSan'];
    $dc  = $_POST['DiaChi'];

    mysqli_query($conn,"
        UPDATE san_dau 
        SET TenSan='$ten', DiaChi='$dc'
        WHERE MaSan='$ma'
    ");

    echo "OK";
    exit;
}



// XÓA SÂN
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $ma = $_POST['MaSan'];
    mysqli_query($conn,"DELETE FROM san_dau WHERE MaSan='$ma'");
    echo "OK";
    exit;
}

// LOAD DANH SÁCH
$data = mysqli_query($conn,"SELECT * FROM san_dau");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản Lý Sân Vận Động - Premier League</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h1>🏟️ Danh Sách Sân Vận Động</h1>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <button class="btn btn-add" onclick="openModal()">+ Thêm Sân Mới</button>
                <input type="text" id="searchInput" onkeyup="searchTable()" class="search-box"
                    placeholder="🔍 Tìm tên sân, địa chỉ...">
            </div>

            <table id="stadiumTable">
                <thead>
                    <tr>
                        <th style="width: 100px;">Mã Sân</th>
                        <th>Tên Sân</th>
                        <th>Địa Chỉ</th>
                        <th style="text-align: right;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($data)) { ?>
                    <tr>
                        <td><b><?php echo $row['MaSan']; ?></b></td>
                        <td style="font-weight: bold; color: var(--primary-bg);">
                            <?php echo $row['TenSan']; ?>
                        </td>
                        <td><?php echo $row['DiaChi']; ?></td>
                        <td style="text-align: right;">
                            <button class="btn btn-edit" onclick="editStadium(this)"
                                data-masan="<?php echo $row['MaSan']; ?>">
                                Sửa
                            </button>

                            <button class="btn btn-delete" onclick="deleteStadium(this)"
                                data-masan="<?php echo $row['MaSan']; ?>">
                                Xóa
                            </button>

                        </td>
                    </tr>
                    <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="text-align: center; color: var(--primary-bg);">Thêm / Sửa Sân</h2>
            <form onsubmit="event.preventDefault(); saveStadium();">
                <div class="form-group">
                    <label>Tên Sân:</label>
                    <input type="text" id="sanName" required placeholder="Nhập tên sân...">
                </div>

                <div class="form-group">
                    <label>Địa Chỉ:</label>
                    <input type="text" id="sanAddress" required placeholder="Thành phố...">
                </div>

                <input type="hidden" id="maSan">

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-save">Lưu Thông Tin</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function searchTable() {
        var input = document.getElementById("searchInput");
        var filter = input.value.toUpperCase();
        var table = document.getElementById("stadiumTable");
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

    function openModal() {
        document.getElementById("maSan").value = "";
        document.getElementById("sanName").value = "";
        document.getElementById("sanAddress").value = "";
        document.getElementById("addModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("addModal").style.display = "none";
    }

    window.onclick = function(event) {
        var modal = document.getElementById("addModal");
        if (event.target == modal) modal.style.display = "none";
    }

    function saveStadium() {
        let ma = document.getElementById("maSan").value;
        let ten = document.getElementById("sanName").value;
        let dc = document.getElementById("sanAddress").value;

        let action = ma ? "update" : "add";

        fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=${action}&MaSan=${ma}&TenSan=${ten}&DiaChi=${dc}`
            })
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "OK") {
                    alert("Lưu thành công!");
                    location.reload(); // reload để load DB lại
                }
            });
    }

    function deleteStadium(btn) {
        let ma = btn.dataset.masan;
        if (!confirm("Xóa sân này?")) return;

        fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=delete&MaSan=${ma}`
            })
            .then(res => res.text())
            .then(res => {
                if (res.trim() === "OK") {
                    alert("Đã xóa!");
                    location.reload();
                }
            });
    }

    function editStadium(btn) {
        let row = btn.parentNode.parentNode;

        document.getElementById("maSan").value = btn.dataset.masan;
        document.getElementById("sanName").value = row.cells[1].innerText;
        document.getElementById("sanAddress").value = row.cells[2].innerText;

        document.getElementById("addModal").style.display = "block";
    }
    </script>
</body>

</html>