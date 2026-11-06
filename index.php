<?php
session_start();
require_once "ketnoi.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_logged'])) {
    header('Location: dangnhap.php');
    exit();
}

$current_user = $_SESSION['user_logged'];

// 1. Đếm tổng số sinh viên thực tế
$res_sv = mysqli_query($conn, "SELECT COUNT(*) AS total FROM sinh_vien");
$row_sv = mysqli_fetch_assoc($res_sv);
$tong_sinh_vien = $row_sv['total'] ?? 0;

// 2. Đếm tổng số môn học thực tế
$res_mh = mysqli_query($conn, "SELECT COUNT(*) AS total FROM mon_hoc");
$row_mh = mysqli_fetch_assoc($res_mh);
$tong_mon_hoc = $row_mh['total'] ?? 0;

// 3. Đếm số lớp học thực tế từ bảng sinh_vien
$res_lop = mysqli_query($conn, "SELECT COUNT(DISTINCT lop) AS total FROM sinh_vien");
$row_lop = mysqli_fetch_assoc($res_lop);
$tong_lop = $row_lop['total'] ?? 0;

// 4. Tính tỷ lệ sinh viên đã được nhập điểm thực tế
$res_sv_nhap = mysqli_query($conn, "SELECT COUNT(DISTINCT ma_sv) AS total FROM bang_diem");
$row_sv_nhap = mysqli_fetch_assoc($res_sv_nhap);
$sv_co_diem = $row_sv_nhap['total'] ?? 0;

$ty_le_nhap = ($tong_sinh_vien > 0) ? round(($sv_co_diem / $tong_sinh_vien) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Hệ thống Quản lý điểm SV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #f8fafc; color: #334155; display: flex; min-height: 100vh; }
        .app-container { display: flex; width: 100%; }

        /* Sidebar Menu */
        .sidebar { width: 240px; background-color: #1d2d5b; color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .brand { padding: 22px 20px; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar .menu { display: flex; flex-direction: column; gap: 4px; padding: 14px 10px; }
        .sidebar .menu a { padding: 11px 16px; color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.88rem; border-radius: 6px; transition: 0.2s; }
        .sidebar .menu a:hover { color: #fff; background-color: rgba(255,255,255,0.05); }
        .sidebar .menu a.active { background-color: #4c77f2; color: #fff; font-weight: 500; }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-header { background: #fff; height: 56px; display: flex; justify-content: space-between; align-items: center; padding: 0 28px; border-bottom: 1px solid #e2e8f0; }
        .header-title { font-weight: 600; font-size: 0.92rem; color: #1e293b; }
        .user-profile { display: flex; align-items: center; gap: 12px; font-size: 0.88rem; color: #334155; }
        .btn-logout { background: #dc2626; color: #fff; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 0.82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }

        .content-body { padding: 28px; display: flex; flex-direction: column; gap: 24px; }
        
        /* Banner chào mừng */
        .welcome-card { background: #fff; padding: 24px 28px; border-radius: 8px; border: 1px solid #edf2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .welcome-card h2 { font-size: 1.25rem; color: #0f172a; margin-bottom: 6px; font-weight: 700; }
        .welcome-card p { font-size: 0.88rem; color: #64748b; }

        /* Khối thống kê số liệu thực tế */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #edf2f7; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .stat-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; }
        .stat-info h3 { font-size: 1.4rem; font-weight: 700; color: #0f172a; }
        .stat-info p { font-size: 0.82rem; color: #64748b; margin-top: 2px; }

        .icon-blue { background-color: #3b82f6; }
        .icon-green { background-color: #10b981; }
        .icon-amber { background-color: #f59e0b; }
        .icon-purple { background-color: #8b5cf6; }

        /* Danh mục chức năng chính */
        .section-title { font-size: 0.98rem; font-weight: 700; color: #1e293b; margin-top: 4px; }
        .features-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; }
        .feature-card { background: #fff; padding: 24px 16px; border-radius: 8px; border: 1px solid #edf2f7; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #1e293b; gap: 12px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .feature-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .feature-card i { font-size: 1.5rem; color: #3b82f6; }
        .feature-card span { font-size: 0.85rem; font-weight: 600; text-align: center; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>QUẢN LÝ ĐIỂM SV</span>
            </div>
            <nav class="menu">
                <a href="index.php" class="active"><i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="nhap_diem.php"><i class="fa-solid fa-pen-to-square"></i> Nhập điểm</a>
                <a href="tinh_dtb.php"><i class="fa-solid fa-calculator"></i> Tính điểm trung bình</a>
                <a href="xep_loai.php"><i class="fa-solid fa-star"></i> Xếp loại</a>
                <a href="tra_cuu.php"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu kết quả</a>
                <a href="thong_ke.php"><i class="fa-solid fa-chart-column"></i> Thống kê</a>
                <a href="dangnhap.php?logout=1" style="margin-top: 20px; color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-title">Trang chủ hệ thống</div>
                <div class="user-profile">
                    <i class="fa-solid fa-circle-user" style="font-size: 1.1rem; color: #3b82f6;"></i>
                    <span>Xin chào, <strong><?php echo htmlspecialchars($current_user['fullname']); ?></strong></span>
                    <a href="dangnhap.php?logout=1" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
                </div>
            </header>

            <div class="content-body">
                <!-- Card Chào mừng -->
                <div class="welcome-card">
                    <h2>Chào mừng đến với Hệ thống Quản lý điểm Sinh viên</h2>
                    <p>Hệ thống hỗ trợ đầy đủ các chức năng: Nhập điểm, Tính điểm trung bình, Xếp loại, Tra cứu và Thống kê kết quả học tập.</p>
                </div>

                <!-- 4 Khối thống kê lấy số liệu thực từ MySQL -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-blue"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($tong_sinh_vien); ?></h3>
                            <p>Sinh viên</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-green"><i class="fa-solid fa-book-open"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($tong_mon_hoc); ?></h3>
                            <p>Môn học</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-amber"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="stat-info">
                            <h3><?php echo number_format($tong_lop); ?></h3>
                            <p>Lớp học</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon icon-purple"><i class="fa-solid fa-square-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $ty_le_nhap; ?>%</h3>
                            <p>Đã nhập điểm</p>
                        </div>
                    </div>
                </div>

                <!-- Danh mục chức năng chính -->
                <div class="section-title">Danh mục chức năng chính</div>
                <div class="features-grid">
                    <a href="nhap_diem.php" class="feature-card">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Nhập điểm sinh viên</span>
                    </a>
                    <a href="tinh_dtb.php" class="feature-card">
                        <i class="fa-solid fa-calculator"></i>
                        <span>Tính điểm TB & GPA</span>
                    </a>
                    <a href="xep_loai.php" class="feature-card">
                        <i class="fa-solid fa-star"></i>
                        <span>Xếp loại học lực</span>
                    </a>
                    <a href="tra_cuu.php" class="feature-card">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Tra cứu kết quả</span>
                    </a>
                    <a href="thong_ke.php" class="feature-card">
                        <i class="fa-solid fa-chart-column"></i>
                        <span>Thống kê điểm</span>
                    </a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>