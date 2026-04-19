<?php
// biến $activePage sẽ được truyền từ trang cha
?>

<div class="sidebar">
    <h2>PREMIER LEAGUE</h2>

    <a href="trang-chu.php" class="<?= ($activePage=='home')?'active':'' ?>">🏠 Tổng quan</a>
    <a href="quan-ly-doi-bong.php" class="<?= ($activePage=='team')?'active':'' ?>">⚽ Đội bóng</a>
    <a href="quan-ly-cau-thu.php" class="<?= ($activePage=='player')?'active':'' ?>">👕 Cầu thủ</a>
    <a href="quan-ly-trong-tai.php" class="<?= ($activePage=='referee')?'active':'' ?>">🚩 Trọng tài</a>
    <a href="quan-ly-san.php" class="<?= ($activePage=='stadium')?'active':'' ?>">🏟️ Sân vận động</a>
    <a href="lich-thi-dau.php" class="<?= ($activePage=='schedule')?'active':'' ?>">📅 Lịch thi đấu</a>
    <a href="bang-xep-hang.php" class="<?= ($activePage=='rank')?'active':'' ?>">🏆 Bảng xếp hạng</a>
    <a href="thong-ke.php" class="<?= ($activePage=='stat')?'active':'' ?>">📊 Thống kê</a>
</div>
