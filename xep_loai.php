<?php
session_start();
require_once "ketnoi.php";

// Kiểm tra phiên đăng nhập
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

// Hàm xếp loại học lực theo thang điểm 10
function tinhXepLoai($dtb) {
    if ($dtb >= 8.5) return ['loai' => 'Xuất sắc', 'class' => 'badge-xs'];
    if ($dtb >= 8.0) return ['loai' => 'Giỏi', 'class' => 'badge-gioi'];
    if ($dtb >= 6.5) return ['loai' => 'Khá', 'class' => 'badge-kha'];
    if ($dtb >= 5.0) return ['loai' => 'Trung bình', 'class' => 'badge-tb'];
    return ['loai' => 'Yếu / Kém', 'class' => 'badge-yeu'];
}

// Xử lý khi chọn sinh viên
$selected_sv = isset($_GET['ma_sv']) ? trim($_GET['ma_sv']) : '';
$info_sv = null;
$dtb_sv = 0;
$tong_tc = 0;
$xep_loai = null;

if (!empty($selected_sv)) {
    // 1. Lấy thông tin sinh viên
    $sql_sv = "SELECT * FROM sinh_vien WHERE ma_sv = '" . mysqli_real_escape_string($conn, $selected_sv) . "'";
    $res_info = mysqli_query($conn, $sql_sv);
    $info_sv = mysqli_fetch_assoc($res_info);

    // 2. Tính điểm trung bình tích lũy theo tín chỉ
    $sql_diem = "SELECT b.diem_hp, m.so_tin_chi 
                 FROM bang_diem b
                 JOIN mon_hoc m ON b.ma_mon = m.ma_mon
                 WHERE b.ma_sv = '" . mysqli_real_escape_string($conn, $selected_sv) . "'";
    $res_diem = mysqli_query($conn, $sql_diem);

    $tong_diem = 0;
    if ($res_diem) {
        while ($r = mysqli_fetch_assoc($res_diem)) {
            $tc = (int)$r['so_tin_chi'];
            $hp = (float)$r['diem_hp'];
            $tong_diem += ($hp * $tc);
            $tong_tc += $tc;
        }
        if ($tong_tc > 0) {
            $dtb_sv = round($tong_diem / $tong_tc, 2);
            $xep_loai = tinhXepLoai($dtb_sv);
        } else {
            $xep_loai = ['loai' => 'Chưa có điểm', 'class' => 'badge-none'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xếp loại học tập - Quản lý điểm SV</title>
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

        .card { background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); max-width: 800px; }
        .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.9rem; font-weight: 600; color: #334155; }
        .form-group select { padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none; background: #fff; }
        .form-group select:focus { border-color: #4c77f2; }

        .btn-primary { background-color: #2563eb; color: #fff; border: none; padding: 11px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-primary:hover { background-color: #1d4ed8; }

        /* Kết quả xếp loại */
        .result-box { margin-top: 25px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .result-header { background: #f8fafc; padding: 14px 18px; font-weight: 600; color: #1e293b; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
        .result-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 18px; border-bottom: 1px solid #f1f5f9; font-size: 0.92rem; }
        .result-row:last-child { border-bottom: none; }
        .score-val { font-weight: 700; color: #0f172a; font-size: 1.15rem; }

        /* Huy hiệu xếp loại */
        .badge-xs { background-color: #ede9fe; color: #6d28d9; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .badge-gioi { background-color: #dcfce7; color: #16a34a; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .badge-kha { background-color: #e0f2fe; color: #0284c7; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .badge-tb { background-color: #fef9c3; color: #ca8a04; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .badge-yeu { background-color: #fee2e2; color: #dc2626; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.9rem; }
        .badge-none { background-color: #f1f5f9; color: #64748b; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; }
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
                <a href="xep_loai.php" class="active"><i class="fa-solid fa-star"></i> Xếp loại</a>
                <a href="tra_cuu.php"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu kết quả</a>
                <a href="thong_ke.php"><i class="fa-solid fa-chart-column"></i> Thống kê</a>
                <a href="dangnhap.php?logout=1" style="margin-top: 20px; color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">Xếp loại kết quả học tập</div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user" style="color: #4c77f2; font-size: 1.1rem;"></i>
                    <span>Xin chào, <strong><?php echo htmlspecialchars($current_user['fullname']); ?></strong></span>
                    <a href="dangnhap.php?logout=1" class="btn-logout" style="margin-left: 10px;">Đăng xuất</a>
                </div>
            </header>

            <div class="content-body">
                <div>
                    <h2>Xếp loại học lực sinh viên</h2>
                    <p class="page-desc">Hệ thống đánh giá và xếp loại học lực dựa trên Điểm trung bình tích lũy thang 10.</p>
                </div>

                <div class="card">
                    <form action="xep_loai.php" method="GET">
                        <div class="form-group">
                            <label>Chọn sinh viên cần xếp loại</label>
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
                            <i class="fa-solid fa-award"></i> Xem kết quả xếp loại
                        </button>
                    </form>

                    <?php if (!empty($selected_sv)): ?>
                        <?php if ($info_sv): ?>
                            <div class="result-box">
                                <div class="result-header">
                                    <span>Thông tin sinh viên</span>
                                    <span><strong><?php echo htmlspecialchars($info_sv['ho_ten']); ?></strong> (<?php echo htmlspecialchars($info_sv['ma_sv']); ?> - <?php echo htmlspecialchars($info_sv['lop']); ?>)</span>
                                </div>
                                <div class="result-row">
                                    <span>Số tín chỉ hoàn thành</span>
                                    <span class="score-val"><?php echo $tong_tc; ?> TC</span>
                                </div>
                                <div class="result-row">
                                    <span>Điểm trung bình tích lũy (Hệ 10)</span>
                                    <span class="score-val" style="color: #2563eb;"><?php echo number_format($dtb_sv, 2); ?></span>
                                </div>
                                <div class="result-row">
                                    <span>Xếp loại học lực</span>
                                    <span class="<?php echo $xep_loai['class']; ?>"><?php echo $xep_loai['loai']; ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 20px; color: #ef4444;">Không tìm thấy thông tin sinh viên!</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>
</body>
</html>