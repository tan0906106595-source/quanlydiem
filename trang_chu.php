<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Hệ thống Quản lý điểm SV</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f4f6fa;
            color: #334155;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 240px;
            background-color: #1d2d5b;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar .brand {
            padding: 24px 20px;
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }

        .sidebar .brand i {
            font-size: 1.3rem;
            color: #e2e8f0;
        }

        .sidebar .menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 12px;
        }

        .sidebar .menu a {
            padding: 12px 16px;
            color: #94a3b8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .sidebar .menu a:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sidebar .menu a.active {
            background-color: #4c77f2;
            color: #ffffff;
            font-weight: 500;
        }

        /* Main Content Styling */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .top-header {
            background-color: #ffffff;
            height: 56px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 28px;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #334155;
            font-weight: 500;
        }

        .content-body {
            padding: 28px;
        }

        /* Welcome Banner */
        .welcome-banner {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 8px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .welcome-banner h2 {
            font-size: 1.25rem;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .welcome-banner p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Grid Thống kê */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #ffffff;
        }

        .bg-blue { background-color: #3b82f6; }
        .bg-green { background-color: #10b981; }
        .bg-orange { background-color: #f59e0b; }
        .bg-purple { background-color: #8b5cf6; }

        .stat-info h3 {
            font-size: 1.35rem;
            color: #0f172a;
        }

        .stat-info p {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Dashboard Row */
        .dashboard-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .card {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .card h3 {
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 18px;
        }

        .card h3 i {
            font-weight: normal;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #334155;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .action-btn i {
            font-size: 1.4rem;
            color: #4c77f2;
        }

        .action-btn:hover {
            border-color: #4c77f2;
            background-color: #f1f5f9;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .activity-list li {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .activity-list li i {
            margin-top: 3px;
            font-size: 1rem;
        }

        .activity-list strong {
            display: block;
            font-size: 0.88rem;
            color: #1e293b;
        }

        .activity-list small {
            display: block;
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 2px;
        }

        .text-blue { color: #3b82f6; }
        .text-green { color: #10b981; }
        .text-orange { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Menu bên trái -->
        <aside class="sidebar">
            <div class="brand">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>QUẢN LÝ ĐIỂM SV</span>
            </div>
            <nav class="menu">
                <a href="index.html" class="active"><i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="demo_gd.nhapdiem.html"><i class="fa-solid fa-pen-to-square"></i> Nhập điểm</a>
                <a href="tinh_dtb.html"><i class="fa-solid fa-calculator"></i> Tính điểm trung bình</a>
                <a href="xep_loai.html"><i class="fa-solid fa-star"></i> Xếp loại</a>
                <a href="tra_cuu.html"><i class="fa-solid fa-magnifying-glass"></i> Tra cứu kết quả</a>
                <a href="thong_ke.html"><i class="fa-solid fa-chart-column"></i> Thống kê</a>
            </nav>
        </aside>

        <!-- Main Content bên phải -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-title">Trang chủ quản lý</div>
                <div class="user-profile">
                    <i class="fa-solid fa-user"></i>
                    <span>Admin</span>
                </div>
            </header>

            <!-- Content Body -->
            <div class="content-body">
                <!-- Chào mừng -->
                <div class="welcome-banner">
                    <h2>Chào mừng đến với Hệ thống Quản lý điểm Sinh viên</h2>
                    <p>Hệ thống hỗ trợ nhập điểm, tính điểm trung bình, xếp loại và tra cứu kết quả học tập nhanh chóng, chính xác.</p>
                </div>

                <!-- Thống kê nhanh (Dữ liệu mẫu) -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="stat-info">
                            <h3 id="stat-students">1,250</h3>
                            <p>Sinh viên <i>(Dữ liệu mẫu)</i></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green"><i class="fa-solid fa-book-open"></i></div>
                        <div class="stat-info">
                            <h3 id="stat-subjects">48</h3>
                            <p>Môn học <i>(Dữ liệu mẫu)</i></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-orange"><i class="fa-solid fa-layer-group"></i></div>
                        <div class="stat-info">
                            <h3 id="stat-classes">16</h3>
                            <p>Lớp học phần <i>(Dữ liệu mẫu)</i></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-purple"><i class="fa-solid fa-check-double"></i></div>
                        <div class="stat-info">
                            <h3>98.5%</h3>
                            <p>Đã nhập điểm <i>(Dữ liệu mẫu)</i></p>
                        </div>
                    </div>
                </div>

                <!-- Lối truy cập nhanh & Thông báo -->
                <div class="dashboard-row">
                    <!-- Phím tắt chức năng chính -->
                    <div class="card quick-actions-card">
                        <h3>Chức năng chính</h3>
                        <div class="actions-grid">
                            <a href="demo_gd.nhapdiem.html" class="action-btn">
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span>Nhập điểm sinh viên</span>
                            </a>
                            <a href="tinh_dtb.html" class="action-btn">
                                <i class="fa-solid fa-calculator"></i>
                                <span>Tính điểm TB & GPA</span>
                            </a>
                            <a href="xep_loai.html" class="action-btn">
                                <i class="fa-solid fa-star"></i>
                                <span>Xếp loại học lực</span>
                            </a>
                            <a href="tra_cuu.html" class="action-btn">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span>Tra cứu kết quả</span>
                            </a>
                        </div>
                    </div>

                    <!-- Bảng thông báo / Hoạt động gần đây (Dữ liệu mẫu) -->
                    <div class="card activity-card">
                        <h3>Thông báo & Cập nhật <i>(Dữ liệu mẫu)</i></h3>
                        <ul class="activity-list">
                            <li>
                                <i class="fa-solid fa-circle-info text-blue"></i>
                                <div>
                                    <strong>Mở hệ thống nhập điểm Học kỳ 1</strong>
                                    <small>20/10/2026 - Thời hạn đến hết ngày 30/11/2026</small>
                                </div>
                            </li>
                            <li>
                                <i class="fa-solid fa-circle-check text-green"></i>
                                <div>
                                    <strong>Hoàn tất cập nhật môn Lập trình Web</strong>
                                    <small>19/10/2026 - Lớp CNTT01 đã nhập đủ điểm</small>
                                </div>
                            </li>
                            <li>
                                <i class="fa-solid fa-triangle-exclamation text-orange"></i>
                                <div>
                                    <strong>Nhắc nhở: Kiểm tra điểm quá trình CSDL</strong>
                                    <small>18/10/2026 - Vẫn còn 5 sinh viên chưa có điểm</small>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JavaScript xử lý dữ liệu và tương tác giao diện -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Trang chủ đã tải hoàn tất.');
            // Khi kết nối PHP sau này, có thể dùng JS fetch() dữ liệu thực tế để thay thế số liệu mẫu ở đây
        });
    </script>
</body>
</html>