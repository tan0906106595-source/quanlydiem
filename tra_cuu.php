<?php
session_start();
require_once "ketnoi.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_logged'])) {
    header('Location: dangnhap.php');
    exit();
}

$current_user = $_SESSION['user_logged'];

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$sinh_vien = null;
$ds_diem = [];
$tong_tin_chi = 0;
$tong_diem_he_10 = 0;
$dtb_tich_luy = 0;
$xep_loai = ['loai' => 'Chưa có điểm', 'class' => 'badge-none'];

// Hàm xếp loại học lực theo thang điểm 10
function layXepLoai($dtb) {
    if ($dtb >= 8.5) return ['loai' => 'Xuất sắc', 'class' => 'badge-xs'];
    if ($dtb >= 8.0) return ['loai' => 'Giỏi', 'class' => 'badge-gioi'];
    if ($dtb >= 6.5) return ['loai' => 'Khá', 'class' => 'badge-kha'];
    if ($dtb >= 5.0) return ['loai' => 'Trung bình', 'class' => 'badge-tb'];
    return ['loai' => 'Yếu / Kém', 'class' => 'badge-yeu'];
}

// Xử lý tìm kiếm khi có từ khóa
if (!empty($keyword)) {
    $kw_safe = mysqli_real_escape_string($conn, $keyword);

    // 1. Tìm thông tin sinh viên theo mã SV hoặc tên
    $sql_sv = "SELECT * FROM sinh_vien WHERE ma_sv = '$kw_safe' OR ho_ten LIKE '%$kw_safe%' LIMIT 1";
    $res_sv = mysqli_query($conn, $sql_sv);

    if ($res_sv && mysqli_num_rows($res_sv) > 0) {
        $sinh_vien = mysqli_fetch_assoc($res_sv);
        $ma_sv_tim_thay = $sinh_vien['ma_sv'];

        // 2. Lấy danh sách điểm các môn học của sinh viên
        $sql_diem = "SELECT b.*, m.ten_mon, m.so_tin_chi 
                     FROM bang_diem b
                     JOIN mon_hoc m ON b.ma_mon = m.ma_mon
                     WHERE b.ma_sv = '$ma_sv_tim_thay'
                     ORDER BY m.ma_mon ASC";
        $res_diem = mysqli_query($conn, $sql_diem);

        if ($res_diem) {
            while ($row = mysqli_fetch_assoc($res_diem)) {
                $tc = (int)$row['so_tin_chi'];
                $hp = (float)$row['diem_hp'];

                $tong_tin_chi += $tc;
                $tong_diem_he_10 += ($hp * $tc);
                $ds_diem[] = $row;
            }

            if ($tong_tin_chi > 0) {
                $dtb_tich_luy = round($tong_diem_he_10 / $tong_tin_chi, 2);
                $xep_loai = layXepLoai($dtb_tich_luy);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu kết quả học tập - Quản lý điểm SV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #f4f6fa; color: #334155; display: flex; min-height: 100vh; }
        .app-container { display: flex; width: 100%; }

        /* Sidebar */
        .sidebar { width: 250px; background-color: #1d2d5b; color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .brand { padding: 24px 20px; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar .menu { display: flex; flex-direction: column; gap: 4px; padding: 16px 12px; }
        .sidebar .menu a { padding: 12px 16px; color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.9rem; border-radius: 6px; transition: 0.2s; }
        .sidebar .menu a:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .sidebar .menu a.active { background-color: #4c77f2; color: #fff; font-weight: 500; }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-header { background-color: #fff; height: 60px; display: flex; justify-content: space-between; align-items: center; padding: 0 28px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        .header-title { font-weight: 600; color: #1e293b; }
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .btn-logout { background: #ef4444; color: #fff; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 500; }

        .content-body { padding: 28px; display: flex; flex-direction: column; gap: 24px; }
        h2 { font-size: 1.35rem; color: #1e293b; margin-bottom: 6px; }
        .page-desc { font-size: 0.9rem; color: #64748b; margin-bottom: 15px; }

        .card { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .search-box { display: flex; gap: 12px; margin-bottom: 24px; }
        .search-box input { flex: 1; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; }
        .search-box input:focus { border-color: #4c77f2; }
        .btn-search { background-color: #2563eb; color: #fff; border: none; padding: 0 24px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-search:hover { background-color: #1d4ed8; }

        .info-panel { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 20px; border-radius: 6px; margin-bottom: 20px; display: flex; gap: 40px; font-size: 0.9rem; }
        
        .table-container { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table-container th, .table-container td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        .table-container th { background-color: #f8fafc; font-weight: 600; color: #1e293b; }
        .table-container tr:hover { background-color: #f8fafc; }

        /* Huy hiệu kết quả */
        .badge { padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 0.8rem; display: inline-block; }
        .badge-xs { background-color: #ede9fe; color: #6d28d9; }
        .badge-gioi { background-color: #dcfce7; color: #16a34a; }
        .badge-kha { background-color: #e0f2fe; color: #0284c7; }
        .badge-tb { background-color: #fef9c3; color: #ca8a04; }
        .badge-yeu { background-color: #fee2e2; color: #dc2626; }
        .badge-none { background-color: #f1f5f9; color: #64748b; }

        .footer-summary { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #e2e8f0; font-weight: 600; }
        .empty-notify { text-align: center; color: #94a3b8; padding: 30px; font-size: 0.92rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand"><i class="fa-solid fa-graduation-cap"></i><span>QUẢN LÝ ĐIỂM SV</span></div>
            <nav class="menu">
                <a href="index.php"><i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="nhap_diem.php"><i class="fa-solid fa-pen-to-square"></i> Nhập điểm</a>
                <a href="tinh_dtb.php"><i class="fa-solid fa-calculator"></i> Tính điểm trung bình</a>
                <a href="xep_loai.php"><i class="fa-solid fa-star"></i> Xếp loại</a>
                <a href="tra_cuu.php" class="active"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu kết quả</a>
                <a href="thong_ke.php"><i class="fa-solid fa-chart-column"></i> Thống kê</a>
                <a href="dangnhap.php?logout=1" style="margin-top: 20px; color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">Tra cứu kết quả học tập</div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user" style="color: #4c77f2; font-size: 1.1rem;"></i>
                    <span>Xin chào, <strong><?php echo htmlspecialchars($current_user['fullname']); ?></strong></span>
                    <a href="dangnhap.php?logout=1" class="btn-logout" style="margin-left: 10px;">Đăng xuất</a>
                </div>
            </header>

            <div class="content-body">
                <div>
                    <h2>Tra cứu kết quả học tập</h2>
                    <p class="page-desc">Tìm kiếm kết quả học tập của sinh viên theo Mã sinh viên hoặc Họ tên.</p>
                </div>

                <div class="card">
                    <div style="font-weight: 600; margin-bottom: 8px; font-size: 0.9rem;">Tìm kiếm sinh viên</div>
                    <form action="tra_cuu.php" method="GET" class="search-box">
                        <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Nhập mã sinh viên (VD: SV001) hoặc họ tên..." required>
                        <button type="submit" class="btn-search">
                            <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
                        </button>
                    </form>

                    <?php if (!empty($keyword)): ?>
                        <?php if ($sinh_vien): ?>
                            <div style="font-weight: 600; margin-bottom: 8px; font-size: 0.9rem;">Thông tin sinh viên</div>
                            <div class="info-panel">
                                <div><strong>Mã SV:</strong> <?php echo htmlspecialchars($sinh_vien['ma_sv']); ?></div>
                                <div><strong>Họ tên:</strong> <?php echo htmlspecialchars($sinh_vien['ho_ten']); ?></div>
                                <div><strong>Lớp:</strong> <?php echo htmlspecialchars($sinh_vien['lop']); ?></div>
                            </div>

                            <div style="font-weight: 600; margin-bottom: 8px; font-size: 0.9rem;">Danh sách môn học</div>
                            <table class="table-container">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã môn</th>
                                        <th>Tên môn</th>
                                        <th>Số tín chỉ</th>
                                        <th>Điểm QT (30%)</th>
                                        <th>Điểm Thi (70%)</th>
                                        <th>Điểm HP</th>
                                        <th>Điểm chữ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ds_diem)): ?>
                                        <?php $stt = 1; foreach ($ds_diem as $d): ?>
                                            <tr>
                                                <td><?php echo $stt++; ?></td>
                                                <td><strong><?php echo htmlspecialchars($d['ma_mon']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($d['ten_mon']); ?></td>
                                                <td><?php echo $d['so_tin_chi']; ?></td>
                                                <td><?php echo number_format($d['diem_qt'], 1); ?></td>
                                                <td><?php echo number_format($d['diem_thi'], 1); ?></td>
                                                <td><strong><?php echo number_format($d['diem_hp'], 2); ?></strong></td>
                                                <td>
                                                    <?php 
                                                        $chu = $d['diem_chu'];
                                                        $badge_cls = 'badge-yeu';
                                                        if (strpos($chu, 'A') !== false) $badge_cls = 'badge-gioi';
                                                        elseif (strpos($chu, 'B') !== false) $badge_cls = 'badge-kha';
                                                        elseif (strpos($chu, 'C') !== false) $badge_cls = 'badge-tb';
                                                        elseif (strpos($chu, 'D') !== false) $badge_cls = 'badge-tb';
                                                    ?>
                                                    <span class="badge <?php echo $badge_cls; ?>"><?php echo $chu; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="empty-notify">Sinh viên này chưa có điểm học phần nào trong hệ thống.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <div class="footer-summary">
                                <div>Tổng số tín chỉ: <strong><?php echo $tong_tin_chi; ?></strong> | Điểm TB tích lũy: <strong style="color: #2563eb;"><?php echo number_format($dtb_tich_luy, 2); ?></strong></div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span>Xếp loại:</span>
                                    <span class="<?php echo $xep_loai['class']; ?>" style="font-size: 0.9rem; padding: 6px 16px;"><?php echo $xep_loai['loai']; ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="empty-notify" style="color: #ef4444;">
                                <i class="fa-solid fa-circle-exclamation" style="font-size: 1.5rem; margin-bottom: 8px; display: block;"></i>
                                Không tìm thấy sinh viên nào khớp với từ khóa "<strong><?php echo htmlspecialchars($keyword); ?></strong>".
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</body>
</html>