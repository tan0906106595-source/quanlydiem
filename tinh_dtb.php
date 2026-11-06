<?php
session_start();
require_once "ketnoi.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_logged'])) {
    header('Location: dangnhap.php');
    exit();
}

$current_user = $_SESSION['user_logged'];

// Lấy danh sách sinh viên cho dropdown
$ds_sinhvien = [];
$res_sv = mysqli_query($conn, "SELECT ma_sv, ho_ten, lop FROM sinh_vien ORDER BY lop, ho_ten");
if ($res_sv) {
    while ($row = mysqli_fetch_assoc($res_sv)) {
        $ds_sinhvien[] = $row;
    }
}

// Xử lý tính điểm trung bình khi chọn sinh viên
$selected_sv = isset($_GET['ma_sv']) ? trim($_GET['ma_sv']) : '';
$info_sv = null;
$ds_diem_mon = [];
$tong_tin_chi = 0;
$tong_diem_tich_luy_10 = 0;
$tong_diem_tich_luy_4 = 0;
$dtb_he_10 = 0;
$gpa_he_4 = 0;

// Hàm quy đổi điểm hệ 10 sang hệ 4 theo từng học phần
function quyDoiHe4($diem) {
    if ($diem >= 8.5) return 4.0;
    if ($diem >= 8.0) return 3.5;
    if ($diem >= 7.0) return 3.0;
    if ($diem >= 6.5) return 2.5;
    if ($diem >= 5.5) return 2.0;
    if ($diem >= 5.0) return 1.5;
    if ($diem >= 4.0) return 1.0;
    return 0.0;
}

if (!empty($selected_sv)) {
    // 1. Lấy thông tin sinh viên
    $sql_info = "SELECT * FROM sinh_vien WHERE ma_sv = '" . mysqli_real_escape_string($conn, $selected_sv) . "'";
    $res_info = mysqli_query($conn, $sql_info);
    $info_sv = mysqli_fetch_assoc($res_info);

    // 2. Lấy điểm chi tiết các môn học của SV
    $sql_diem = "SELECT b.*, m.ten_mon, m.so_tin_chi 
                 FROM bang_diem b
                 JOIN mon_hoc m ON b.ma_mon = m.ma_mon
                 WHERE b.ma_sv = '" . mysqli_real_escape_string($conn, $selected_sv) . "'";
    $res_diem = mysqli_query($conn, $sql_diem);

    if ($res_diem) {
        while ($row = mysqli_fetch_assoc($res_diem)) {
            $tc = (int)$row['so_tin_chi'];
            $diem_hp = (float)$row['diem_hp'];
            $he_4 = quyDoiHe4($diem_hp);

            $row['diem_he_4'] = $he_4;
            $ds_diem_mon[] = $row;

            $tong_tin_chi += $tc;
            $tong_diem_tich_luy_10 += ($diem_hp * $tc);
            $tong_diem_tich_luy_4 += ($he_4 * $tc);
        }

        if ($tong_tin_chi > 0) {
            $dtb_he_10 = round($tong_diem_tich_luy_10 / $tong_tin_chi, 2);
            $gpa_he_4 = round($tong_diem_tich_luy_4 / $tong_tin_chi, 2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tính điểm trung bình - Quản lý điểm SV</title>
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
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.9rem; font-weight: 600; color: #334155; }
        .form-group select { padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; background: #fff; }
        .form-group select:focus { border-color: #4c77f2; }

        .btn-primary { background-color: #2563eb; color: #fff; border: none; padding: 11px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-primary:hover { background-color: #1d4ed8; }

        /* Kết quả điểm trung bình */
        .result-box { margin-top: 25px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .result-header { background: #f8fafc; padding: 14px 18px; font-weight: 600; color: #1e293b; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
        .result-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-bottom: 1px solid #f1f5f9; font-size: 0.92rem; }
        .result-row:last-child { border-bottom: none; }
        .score-val { font-weight: 700; color: #2563eb; font-size: 1.2rem; }

        /* Bảng chi tiết môn học */
        .table-responsive { overflow-x: auto; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
        th { background: #f8fafc; color: #475569; font-weight: 600; padding: 12px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.8rem; }
        .badge-a { background: #dcfce7; color: #16a34a; }
        .badge-b { background: #e0e7ff; color: #4338ca; }
        .badge-c { background: #fef9c3; color: #ca8a04; }
        .badge-d { background: #ffedd5; color: #c2410c; }
        .badge-f { background: #fee2e2; color: #dc2626; }
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
                <a href="tinh_dtb.php" class="active"><i class="fa-solid fa-calculator"></i> Tính điểm trung bình</a>
                <a href="xep_loai.php"><i class="fa-solid fa-star"></i> Xếp loại</a>
                <a href="tra_cuu.php"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu kết quả</a>
                <a href="thong_ke.php"><i class="fa-solid fa-chart-column"></i> Thống kê</a>
                <a href="dangnhap.php?logout=1" style="margin-top: 20px; color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">Tính điểm trung bình học tập</div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user" style="color: #4c77f2; font-size: 1.1rem;"></i>
                    <span>Xin chào, <strong><?php echo htmlspecialchars($current_user['fullname']); ?></strong></span>
                    <a href="dangnhap.php?logout=1" class="btn-logout" style="margin-left: 10px;">Đăng xuất</a>
                </div>
            </header>

            <div class="content-body">
                <div>
                    <h2>Tính điểm trung bình tích lũy</h2>
                    <p class="page-desc">Chọn sinh viên từ hệ thống để tính GPA hệ 10, GPA hệ 4 theo trọng số tín chỉ.</p>
                </div>

                <div class="card" style="max-width: 800px;">
                    <form action="tinh_dtb.php" method="GET">
                        <div class="form-group">
                            <label>Chọn sinh viên cần tính điểm</label>
                            <select name="ma_sv" required>
                                <option value="">-- Chọn sinh viên --</option>
                                <?php foreach ($ds_sinhvien as $sv): ?>
                                    <option value="<?php echo htmlspecialchars($sv['ma_sv']); ?>" <?php echo ($selected_sv == $sv['ma_sv']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sv['ma_sv'] . " - " . $sv['ho_ten'] . " (Lớp: " . $sv['lop'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-calculator"></i> Tính điểm ngay
                        </button>
                    </form>

                    <?php if (!empty($selected_sv)): ?>
                        <?php if ($info_sv): ?>
                            <div class="result-box">
                                <div class="result-header">
                                    <span>Kết quả tổng hợp</span>
                                    <span>Sinh viên: <strong><?php echo htmlspecialchars($info_sv['ho_ten']); ?></strong> (<?php echo htmlspecialchars($info_sv['lop']); ?>)</span>
                                </div>
                                <div class="result-row">
                                    <span>Tổng số tín chỉ đã học</span>
                                    <span class="score-val" style="color: #0f172a; font-size: 1rem;"><?php echo $tong_tin_chi; ?> tín chỉ</span>
                                </div>
                                <div class="result-row">
                                    <span>Điểm trung bình hệ 10</span>
                                    <span class="score-val"><?php echo number_format($dtb_he_10, 2); ?></span>
                                </div>
                                <div class="result-row">
                                    <span>Điểm trung bình GPA (Hệ 4)</span>
                                    <span class="score-val" style="color: #10b981;"><?php echo number_format($gpa_he_4, 2); ?> / 4.0</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 20px; color: #ef4444;">Không tìm thấy thông tin sinh viên này!</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Bảng điểm chi tiết từng môn -->
                <?php if (!empty($selected_sv) && $info_sv): ?>
                    <div class="card">
                        <h3 style="font-size: 1rem; color: #1e293b; margin-bottom: 12px;">Chi tiết các môn học đã tích lũy</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mã môn</th>
                                        <th>Tên môn học</th>
                                        <th>Số tín chỉ</th>
                                        <th>Điểm QT (30%)</th>
                                        <th>Điểm Thi (70%)</th>
                                        <th>Tổng kết HP</th>
                                        <th>Điểm chữ</th>
                                        <th>Quy đổi hệ 4</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($ds_diem_mon)): ?>
                                        <?php foreach ($ds_diem_mon as $d): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($d['ma_mon']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($d['ten_mon']); ?></td>
                                                <td><?php echo $d['so_tin_chi']; ?></td>
                                                <td><?php echo number_format($d['diem_qt'], 1); ?></td>
                                                <td><?php echo number_format($d['diem_thi'], 1); ?></td>
                                                <td><strong><?php echo number_format($d['diem_hp'], 2); ?></strong></td>
                                                <td>
                                                    <?php 
                                                        $chu = $d['diem_chu'];
                                                        $class = 'badge-f';
                                                        if (strpos($chu, 'A') !== false) $class = 'badge-a';
                                                        elseif (strpos($chu, 'B') !== false) $class = 'badge-b';
                                                        elseif (strpos($chu, 'C') !== false) $class = 'badge-c';
                                                        elseif (strpos($chu, 'D') !== false) $class = 'badge-d';
                                                    ?>
                                                    <span class="badge <?php echo $class; ?>"><?php echo $chu; ?></span>
                                                </td>
                                                <td><strong><?php echo number_format($d['diem_he_4'], 1); ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">Sinh viên này chưa có dữ liệu điểm môn nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>