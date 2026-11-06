<?php
session_start();
require_once "ketnoi.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_logged'])) {
    header('Location: dangnhap.php');
    exit();
}

$current_user = $_SESSION['user_logged'];

// Xác định tab đang chọn: 'lop' hoặc 'hocphan'
$type = isset($_GET['type']) && $_GET['type'] === 'hocphan' ? 'hocphan' : 'lop';

// 1. Lấy danh sách lớp học thực tế
$ds_lop = [];
$res_lop = mysqli_query($conn, "SELECT DISTINCT lop FROM sinh_vien ORDER BY lop ASC");
if ($res_lop) {
    while ($r = mysqli_fetch_assoc($res_lop)) {
        $ds_lop[] = $r['lop'];
    }
}

// 2. Lấy danh sách môn học thực tế
$ds_mon = [];
$res_mon = mysqli_query($conn, "SELECT ma_mon, ten_mon FROM mon_hoc ORDER BY ten_mon ASC");
if ($res_mon) {
    while ($r = mysqli_fetch_assoc($res_mon)) {
        $ds_mon[] = $r;
    }
}

// ==========================================
// XỬ LÝ THỐNG KÊ THEO LỚP
// ==========================================
$selected_lop = isset($_GET['lop']) ? trim($_GET['lop']) : ($ds_lop[0] ?? '');
$thong_ke_lop = [
    'XuatSac'   => 0,
    'Gioi'      => 0,
    'Kha'       => 0,
    'TrungBinh' => 0,
    'Yeu'       => 0
];
$tong_sv_co_diem = 0;

if ($type === 'lop' && !empty($selected_lop)) {
    $lop_safe = mysqli_real_escape_string($conn, $selected_lop);
    
    // Lấy điểm và số tín chỉ của từng SV trong lớp để tính ĐTB tích lũy
    $sql_lop = "SELECT s.ma_sv, b.diem_hp, m.so_tin_chi 
                FROM sinh_vien s
                JOIN bang_diem b ON s.ma_sv = b.ma_sv
                JOIN mon_hoc m ON b.ma_mon = m.ma_mon
                WHERE s.lop = '$lop_safe'";
    $res_diem_lop = mysqli_query($conn, $sql_lop);

    $sv_scores = [];
    if ($res_diem_lop) {
        while ($row = mysqli_fetch_assoc($res_diem_lop)) {
            $msv = $row['ma_sv'];
            $tc  = (int)$row['so_tin_chi'];
            $d   = (float)$row['diem_hp'];

            if (!isset($sv_scores[$msv])) {
                $sv_scores[$msv] = ['tong_diem' => 0, 'tong_tc' => 0];
            }
            $sv_scores[$msv]['tong_diem'] += ($d * $tc);
            $sv_scores[$msv]['tong_tc']   += $tc;
        }
    }

    // Phân loại học lực
    foreach ($sv_scores as $msv => $data) {
        if ($data['tong_tc'] > 0) {
            $dtb = round($data['tong_diem'] / $data['tong_tc'], 2);
            $tong_sv_co_diem++;
            if ($dtb >= 8.5) {
                $thong_ke_lop['XuatSac']++;
            } elseif ($dtb >= 8.0) {
                $thong_ke_lop['Gioi']++;
            } elseif ($dtb >= 6.5) {
                $thong_ke_lop['Kha']++;
            } elseif ($dtb >= 5.0) {
                $thong_ke_lop['TrungBinh']++;
            } else {
                $thong_ke_lop['Yeu']++;
            }
        }
    }
}

// ==========================================
// XỬ LÝ THỐNG KÊ THEO HỌC PHẦN (MÔN HỌC)
// ==========================================
$selected_mon = isset($_GET['ma_mon']) ? trim($_GET['ma_mon']) : ($ds_mon[0]['ma_mon'] ?? '');
$thong_ke_mon = [
    'A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0,
    'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0
];
$tong_sv_thi = 0;
$so_sv_qua_mon = 0;
$diem_cao_nhat = 0;
$diem_thap_nhat = 10;
$tong_diem_mon = 0;

if ($type === 'hocphan' && !empty($selected_mon)) {
    $mon_safe = mysqli_real_escape_string($conn, $selected_mon);
    $sql_mon = "SELECT diem_hp, diem_chu FROM bang_diem WHERE ma_mon = '$mon_safe'";
    $res_mon_data = mysqli_query($conn, $sql_mon);

    if ($res_mon_data && mysqli_num_rows($res_mon_data) > 0) {
        while ($row = mysqli_fetch_assoc($res_mon_data)) {
            $d = (float)$row['diem_hp'];
            $chu = trim($row['diem_chu']);

            $tong_sv_thi++;
            $tong_diem_mon += $d;

            if ($d > $diem_cao_nhat) $diem_cao_nhat = $d;
            if ($d < $diem_thap_nhat) $diem_thap_nhat = $d;

            if ($d >= 4.0) {
                $so_sv_qua_mon++;
            }

            if (isset($thong_ke_mon[$chu])) {
                $thong_ke_mon[$chu]++;
            }
        }
    } else {
        $diem_thap_nhat = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê kết quả học tập - Quản lý điểm SV</title>
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

        .content-body { padding: 28px; display: flex; flex-direction: column; gap: 20px; }
        h2 { font-size: 1.35rem; color: #1e293b; margin-bottom: 4px; }
        .page-desc { font-size: 0.9rem; color: #64748b; margin-bottom: 10px; }

        .card { background: #fff; padding: 26px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1px solid #edf2f7; }
        .tab-filter { display: flex; gap: 10px; margin-bottom: 22px; }
        .tab-btn { padding: 10px 22px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: 1px solid #cbd5e1; background: #fff; color: #64748b; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .tab-btn.active { background-color: #2563eb; color: #fff; border-color: #2563eb; box-shadow: 0 2px 6px rgba(37,99,235,0.25); }

        .filter-row { display: flex; gap: 15px; align-items: center; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-row select { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; min-width: 260px; outline: none; background: #fff; }
        .btn-filter { background-color: #2563eb; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-filter:hover { background-color: #1d4ed8; }

        .stats-layout { display: grid; grid-template-columns: 1.3fr 1fr; gap: 28px; align-items: stretch; }
        @media (max-width: 1000px) { .stats-layout { grid-template-columns: 1fr; } }

        .table-container { width: 100%; border-collapse: collapse; }
        .table-container th, .table-container td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        .table-container th { background-color: #f8fafc; font-weight: 600; color: #1e293b; }
        .table-container tr:hover { background-color: #f8fafc; }

        /* KHỐI BIỂU ĐỒ TRÒN MỚI */
        .chart-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        /* Biểu đồ Donut Chart lớn */
        .donut-container {
            position: relative;
            width: 230px;
            height: 230px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }

        .pie-chart {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        .pie-chart:hover {
            transform: scale(1.03);
        }

        /* Lỗ tròn trung tâm tạo hiệu ứng Donut */
        .donut-hole {
            position: absolute;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background-color: #ffffff;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .donut-hole .hole-val { font-size: 1.45rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .donut-hole .hole-sub { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-top: 3px; }

        /* Chú thích Legend trực quan */
        .legend { width: 100%; display: flex; flex-direction: column; gap: 10px; margin-top: 4px; }
        .legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #edf2f7;
            font-size: 0.88rem;
        }
        .legend-label { display: flex; align-items: center; gap: 10px; font-weight: 500; color: #334155; }
        .dot { width: 12px; height: 12px; border-radius: 4px; display: inline-block; }

        /* Khối thống kê nhanh của học phần */
        .summary-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .s-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 8px; }
        .s-card h4 { font-size: 0.78rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
        .s-card p { font-size: 1.35rem; font-weight: 700; color: #1e293b; }
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
                <a href="tra_cuu.php"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu kết quả</a>
                <a href="thong_ke.php" class="active"><i class="fa-solid fa-chart-column"></i> Thống kê</a>
                <a href="dangnhap.php?logout=1" style="margin-top: 20px; color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">Thống kê & Báo cáo kết quả học tập</div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user" style="color: #4c77f2; font-size: 1.1rem;"></i>
                    <span>Xin chào, <strong><?php echo htmlspecialchars($current_user['fullname']); ?></strong></span>
                    <a href="dangnhap.php?logout=1" class="btn-logout" style="margin-left: 10px;">Đăng xuất</a>
                </div>
            </header>

            <div class="content-body">
                <div>
                    <h2>Thống kê kết quả học tập</h2>
                    <p class="page-desc">Phân tích tỷ lệ xếp loại theo lớp hoặc đánh giá phổ điểm theo từng môn học.</p>
                </div>

                <div class="card">
                    <!-- Tab chọn loại thống kê -->
                    <div class="tab-filter">
                        <a href="thong_ke.php?type=lop" class="tab-btn <?php echo ($type === 'lop') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-users"></i> Thống kê theo lớp
                        </a>
                        <a href="thong_ke.php?type=hocphan" class="tab-btn <?php echo ($type === 'hocphan') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-book"></i> Thống kê theo học phần
                        </a>
                    </div>

                    <?php if ($type === 'lop'): ?>
                        <!-- ================= BỘ LỌC THEO LỚP ================= -->
                        <form action="thong_ke.php" method="GET" class="filter-row">
                            <input type="hidden" name="type" value="lop">
                            <span style="font-size: 0.9rem; font-weight: 600;">Chọn lớp học:</span>
                            <select name="lop">
                                <?php foreach ($ds_lop as $lop_item): ?>
                                    <option value="<?php echo htmlspecialchars($lop_item); ?>" <?php echo ($selected_lop == $lop_item) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lop_item); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> Xem thống kê</button>
                        </form>

                        <?php
                            $p_xs   = $tong_sv_co_diem > 0 ? round(($thong_ke_lop['XuatSac'] / $tong_sv_co_diem) * 100, 1) : 0;
                            $p_gioi = $tong_sv_co_diem > 0 ? round(($thong_ke_lop['Gioi'] / $tong_sv_co_diem) * 100, 1) : 0;
                            $p_kha  = $tong_sv_co_diem > 0 ? round(($thong_ke_lop['Kha'] / $tong_sv_co_diem) * 100, 1) : 0;
                            $p_tb   = $tong_sv_co_diem > 0 ? round(($thong_ke_lop['TrungBinh'] / $tong_sv_co_diem) * 100, 1) : 0;
                            $p_yeu  = $tong_sv_co_diem > 0 ? round(($thong_ke_lop['Yeu'] / $tong_sv_co_diem) * 100, 1) : 0;

                            // Tính góc độ gradient tròn
                            $d1 = round(($p_xs / 100) * 360);
                            $d2 = $d1 + round(($p_gioi / 100) * 360);
                            $d3 = $d2 + round(($p_kha / 100) * 360);
                            $d4 = $d3 + round(($p_tb / 100) * 360);
                        ?>

                        <div style="font-weight: 600; margin-bottom: 14px; font-size: 0.95rem;">
                            Kết quả xếp loại học lực lớp: <span style="color: #2563eb;"><?php echo htmlspecialchars($selected_lop); ?></span> (Tổng: <?php echo $tong_sv_co_diem; ?> SV có điểm)
                        </div>

                        <div class="stats-layout">
                            <table class="table-container">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Xếp loại</th>
                                        <th>Số lượng</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>1</td><td><strong style="color: #8b5cf6;">Xuất sắc</strong></td><td><?php echo $thong_ke_lop['XuatSac']; ?> SV</td><td><strong><?php echo $p_xs; ?>%</strong></td></tr>
                                    <tr><td>2</td><td><strong style="color: #10b981;">Giỏi</strong></td><td><?php echo $thong_ke_lop['Gioi']; ?> SV</td><td><strong><?php echo $p_gioi; ?>%</strong></td></tr>
                                    <tr><td>3</td><td><strong style="color: #3b82f6;">Khá</strong></td><td><?php echo $thong_ke_lop['Kha']; ?> SV</td><td><strong><?php echo $p_kha; ?>%</strong></td></tr>
                                    <tr><td>4</td><td><strong style="color: #f59e0b;">Trung bình</strong></td><td><?php echo $thong_ke_lop['TrungBinh']; ?> SV</td><td><strong><?php echo $p_tb; ?>%</strong></td></tr>
                                    <tr><td>5</td><td><strong style="color: #ef4444;">Yếu / Kém</strong></td><td><?php echo $thong_ke_lop['Yeu']; ?> SV</td><td><strong><?php echo $p_yeu; ?>%</strong></td></tr>
                                </tbody>
                            </table>

                            <!-- KHỐI BIỂU ĐỒ DONUT LỚN ĐẸP -->
                            <div class="chart-box">
                                <?php if ($tong_sv_co_diem > 0): ?>
                                    <div class="donut-container">
                                        <div class="pie-chart" style="background: conic-gradient(
                                            #8b5cf6 0deg <?php echo $d1; ?>deg,
                                            #10b981 <?php echo $d1; ?>deg <?php echo $d2; ?>deg,
                                            #3b82f6 <?php echo $d2; ?>deg <?php echo $d3; ?>deg,
                                            #f59e0b <?php echo $d3; ?>deg <?php echo $d4; ?>deg,
                                            #ef4444 <?php echo $d4; ?>deg 360deg
                                        );"></div>
                                        <div class="donut-hole">
                                            <div class="hole-val"><?php echo $tong_sv_co_diem; ?></div>
                                            <div class="hole-sub">Sinh viên</div>
                                        </div>
                                    </div>
                                    <div class="legend">
                                        <div class="legend-item"><div class="legend-label"><span class="dot" style="background:#8b5cf6"></span> Xuất sắc</div> <strong><?php echo $thong_ke_lop['XuatSac']; ?> SV (<?php echo $p_xs; ?>%)</strong></div>
                                        <div class="legend-item"><div class="legend-label"><span class="dot" style="background:#10b981"></span> Giỏi</div> <strong><?php echo $thong_ke_lop['Gioi']; ?> SV (<?php echo $p_gioi; ?>%)</strong></div>
                                        <div class="legend-item"><div class="legend-label"><span class="dot" style="background:#3b82f6"></span> Khá</div> <strong><?php echo $thong_ke_lop['Kha']; ?> SV (<?php echo $p_kha; ?>%)</strong></div>
                                        <div class="legend-item"><div class="legend-label"><span class="dot" style="background:#f59e0b"></span> Trung bình</div> <strong><?php echo $thong_ke_lop['TrungBinh']; ?> SV (<?php echo $p_tb; ?>%)</strong></div>
                                        <div class="legend-item"><div class="legend-label"><span class="dot" style="background:#ef4444"></span> Yếu / Kém</div> <strong><?php echo $thong_ke_lop['Yeu']; ?> SV (<?php echo $p_yeu; ?>%)</strong></div>
                                    </div>
                                <?php else: ?>
                                    <p style="color: #94a3b8; font-size: 0.95rem;">Chưa có dữ liệu điểm của lớp này.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- ================= BỘ LỌC THEO HỌC PHẦN ================= -->
                        <form action="thong_ke.php" method="GET" class="filter-row">
                            <input type="hidden" name="type" value="hocphan">
                            <span style="font-size: 0.9rem; font-weight: 600;">Chọn học phần:</span>
                            <select name="ma_mon">
                                <?php foreach ($ds_mon as $m): ?>
                                    <option value="<?php echo htmlspecialchars($m['ma_mon']); ?>" <?php echo ($selected_mon == $m['ma_mon']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['ma_mon'] . " - " . $m['ten_mon']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-filter"><i class="fa-solid fa-chart-pie"></i> Xem thống kê</button>
                        </form>

                        <?php 
                            $dtb_mon = ($tong_sv_thi > 0) ? round($tong_diem_mon / $tong_sv_thi, 2) : 0;
                            $ty_le_dat = ($tong_sv_thi > 0) ? round(($so_sv_qua_mon / $tong_sv_thi) * 100, 1) : 0;
                        ?>

                        <!-- Thống kê nhanh chỉ số môn học -->
                        <div class="summary-cards">
                            <div class="s-card">
                                <h4>Sinh viên đã học</h4>
                                <p><?php echo $tong_sv_thi; ?></p>
                            </div>
                            <div class="s-card">
                                <h4>Tỷ lệ đạt môn</h4>
                                <p style="color: #10b981;"><?php echo $ty_le_dat; ?>%</p>
                            </div>
                            <div class="s-card">
                                <h4>Điểm TB môn</h4>
                                <p style="color: #2563eb;"><?php echo number_format($dtb_mon, 2); ?></p>
                            </div>
                            <div class="s-card">
                                <h4>Cao nhất / Thấp nhất</h4>
                                <p style="font-size: 1.15rem;"><?php echo $diem_cao_nhat; ?> / <?php echo $diem_thap_nhat; ?></p>
                            </div>
                        </div>

                        <div class="stats-layout">
                            <!-- Bảng phổ điểm chữ -->
                            <table class="table-container">
                                <thead>
                                    <tr>
                                        <th>Điểm chữ</th>
                                        <th>Thang điểm tương ứng</th>
                                        <th>Số lượng</th>
                                        <th>Tỷ lệ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $diem_mo_ta = [
                                            'A'  => '8.5 - 10.0',
                                            'B+' => '8.0 - 8.4',
                                            'B'  => '7.0 - 7.9',
                                            'C+' => '6.5 - 6.9',
                                            'C'  => '5.5 - 6.4',
                                            'D+' => '5.0 - 5.4',
                                            'D'  => '4.0 - 4.9',
                                            'F'  => 'Dưới 4.0 (Trượt môn)'
                                        ];
                                        foreach ($thong_ke_mon as $chu => $sl):
                                            $ti_le = ($tong_sv_thi > 0) ? round(($sl / $tong_sv_thi) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><strong><?php echo $chu; ?></strong></td>
                                            <td style="color: #64748b;"><?php echo $diem_mo_ta[$chu]; ?></td>
                                            <td><?php echo $sl; ?></td>
                                            <td><?php echo $ti_le; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Biểu đồ tỷ lệ Đạt / Trượt môn -->
                            <div class="chart-box">
                                <?php if ($tong_sv_thi > 0): 
                                    $goc_dat = round(($ty_le_dat / 100) * 360);
                                ?>
                                    <div class="donut-container">
                                        <div class="pie-chart" style="background: conic-gradient(
                                            #10b981 0deg <?php echo $goc_dat; ?>deg,
                                            #ef4444 <?php echo $goc_dat; ?>deg 360deg
                                        );"></div>
                                        <div class="donut-hole">
                                            <div class="hole-val" style="color: #10b981;"><?php echo $ty_le_dat; ?>%</div>
                                            <div class="hole-sub">Đạt môn</div>
                                        </div>
                                    </div>
                                    <div class="legend">
                                        <div class="legend-item">
                                            <div class="legend-label"><span class="dot" style="background:#10b981"></span> Đạt môn (≥ 4.0)</div> 
                                            <strong><?php echo $so_sv_qua_mon; ?> SV (<?php echo $ty_le_dat; ?>%)</strong>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-label"><span class="dot" style="background:#ef4444"></span> Trượt môn (< 4.0)</div> 
                                            <strong><?php echo ($tong_sv_thi - $so_sv_qua_mon); ?> SV (<?php echo round(100 - $ty_le_dat, 1); ?>%)</strong>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p style="color: #94a3b8; font-size: 0.95rem;">Học phần này chưa có dữ liệu điểm thi.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>
</body>
</html>