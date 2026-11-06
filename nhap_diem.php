<?php
session_start();
require_once "ketnoi.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_logged'])) {
    header('Location: dangnhap.php');
    exit();
}

$current_user = $_SESSION['user_logged'];
$msg = "";
$msg_type = "";

// Xử lý lưu điểm (Quá trình 30% - Cuối kỳ 70%)
if (isset($_POST['btn_luu_diem'])) {
    $ma_sv    = trim($_POST['ma_sv']);
    $ma_mon   = trim($_POST['monhoc']);
    $diem_qt  = floatval($_POST['diem_qt']);
    $diem_thi = floatval($_POST['diem_thi']);

    // 1. Kiểm tra mã sinh viên có tồn tại hay không
    $sql_check_sv = "SELECT ma_sv FROM sinh_vien WHERE ma_sv = '" . mysqli_real_escape_string($conn, $ma_sv) . "'";
    $res_check_sv = mysqli_query($conn, $sql_check_sv);

    if (mysqli_num_rows($res_check_sv) == 0) {
        $msg = "Mã sinh viên '$ma_sv' không tồn tại trong hệ thống!";
        $msg_type = "error";
    } elseif ($diem_qt < 0 || $diem_qt > 10 || $diem_thi < 0 || $diem_thi > 10) {
        $msg = "Điểm phải nằm trong thang điểm từ 0 đến 10!";
        $msg_type = "error";
    } else {
        // Tính điểm tổng kết học phần
        $diem_hp = round($diem_qt * 0.3 + $diem_thi * 0.7, 2);

        // Quy đổi điểm chữ
        $diem_chu = 'F';
        if ($diem_hp >= 8.5) $diem_chu = 'A';
        elseif ($diem_hp >= 8.0) $diem_chu = 'B+';
        elseif ($diem_hp >= 7.0) $diem_chu = 'B';
        elseif ($diem_hp >= 6.5) $diem_chu = 'C+';
        elseif ($diem_hp >= 5.5) $diem_chu = 'C';
        elseif ($diem_hp >= 5.0) $diem_chu = 'D+';
        elseif ($diem_hp >= 4.0) $diem_chu = 'D';

        // 2. Kiểm tra xem môn này của SV đã có điểm chưa
        $sql_check_diem = "SELECT id FROM bang_diem WHERE ma_sv = '" . mysqli_real_escape_string($conn, $ma_sv) . "' AND ma_mon = '" . mysqli_real_escape_string($conn, $ma_mon) . "'";
        $res_check_diem = mysqli_query($conn, $sql_check_diem);

        if (mysqli_num_rows($res_check_diem) > 0) {
            // Đã có -> Cập nhật (UPDATE)
            $sql_update = "UPDATE bang_diem SET diem_qt = $diem_qt, diem_thi = $diem_thi, diem_hp = $diem_hp, diem_chu = '$diem_chu' 
                           WHERE ma_sv = '" . mysqli_real_escape_string($conn, $ma_sv) . "' AND ma_mon = '" . mysqli_real_escape_string($conn, $ma_mon) . "'";
            if (mysqli_query($conn, $sql_update)) {
                $msg = "Cập nhật điểm thành công cho SV: $ma_sv!";
                $msg_type = "success";
            } else {
                $msg = "Lỗi khi cập nhật điểm: " . mysqli_error($conn);
                $msg_type = "error";
            }
        } else {
            // Chưa có -> Thêm mới (INSERT)
            $sql_insert = "INSERT INTO bang_diem (ma_sv, ma_mon, diem_qt, diem_thi, diem_hp, diem_chu) 
                           VALUES ('" . mysqli_real_escape_string($conn, $ma_sv) . "', '" . mysqli_real_escape_string($conn, $ma_mon) . "', $diem_qt, $diem_thi, $diem_hp, '$diem_chu')";
            if (mysqli_query($conn, $sql_insert)) {
                $msg = "Lưu điểm thành công cho SV: $ma_sv!";
                $msg_type = "success";
            } else {
                $msg = "Lỗi khi lưu điểm: " . mysqli_error($conn);
                $msg_type = "error";
            }
        }
    }
}

// Lấy danh sách môn học
$ds_monhoc = [];
$res_mon = mysqli_query($conn, "SELECT * FROM mon_hoc");
if ($res_mon) {
    while ($row = mysqli_fetch_assoc($res_mon)) {
        $ds_monhoc[] = $row;
    }
}

// Lấy danh sách bảng điểm
$ds_diem = [];
$sql_bangdiem = "SELECT b.*, s.ho_ten, s.lop, m.ten_mon 
                 FROM bang_diem b
                 JOIN sinh_vien s ON b.ma_sv = s.ma_sv
                 JOIN mon_hoc m ON b.ma_mon = m.ma_mon
                 ORDER BY b.id DESC";
$res_diem = mysqli_query($conn, $sql_bangdiem);
if ($res_diem) {
    while ($row = mysqli_fetch_assoc($res_diem)) {
        $ds_diem[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập điểm sinh viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #f8fafc; color: #334155; display: flex; min-height: 100vh; }
        .app-container { display: flex; width: 100%; }

        .sidebar { width: 240px; background-color: #1d2d5b; color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar .brand { padding: 22px 20px; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar .menu { display: flex; flex-direction: column; gap: 4px; padding: 14px 10px; }
        .sidebar .menu a { padding: 11px 16px; color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 12px; font-size: 0.88rem; border-radius: 6px; transition: 0.2s; }
        .sidebar .menu a:hover { color: #fff; background-color: rgba(255,255,255,0.05); }
        .sidebar .menu a.active { background-color: #4c77f2; color: #fff; font-weight: 500; }

        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-header { background: #fff; height: 56px; display: flex; justify-content: space-between; align-items: center; padding: 0 28px; border-bottom: 1px solid #e2e8f0; }
        .header-title { font-weight: 600; font-size: 0.92rem; color: #1e293b; }
        .user-profile { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 600; color: #334155; }

        .content-body { padding: 28px; display: flex; flex-direction: column; gap: 24px; }
        .page-intro h2 { font-size: 1.25rem; color: #0f172a; margin-bottom: 4px; }
        .page-intro p { font-size: 0.85rem; color: #64748b; }

        .card { background: #ffffff; padding: 24px; border-radius: 8px; border: 1px solid #edf2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card h3 { font-size: 0.98rem; font-weight: 700; color: #1e293b; margin-bottom: 20px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 24px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.82rem; font-weight: 600; color: #475569; }
        .form-group input, .form-group select { padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #4c77f2; }

        .form-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-submit { background-color: #4c77f2; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; font-size: 0.88rem; font-weight: 600; cursor: pointer; }
        .btn-submit:hover { background-color: #3b62d9; }
        .btn-reset { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 9px 18px; border-radius: 6px; font-size: 0.88rem; text-decoration: none; }

        .alert { padding: 12px 16px; border-radius: 6px; font-size: 0.88rem; margin-bottom: 16px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }

        .table-responsive { overflow-x: auto; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
        th { background: #f8fafc; color: #475569; font-weight: 600; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 3px 7px; border-radius: 4px; font-weight: 600; font-size: 0.78rem; }
        .badge-pass { background: #dcfce7; color: #16a34a; }
        .badge-fail { background: #fee2e2; color: #dc2626; }
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
                <a href="index.php"><i class="fa-solid fa-house"></i> Trang chủ</a>
                <a href="nhap_diem.php" class="active"><i class="fa-solid fa-pen-to-square"></i> Nhập điểm</a>
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
                <div class="header-title">Nhập điểm sinh viên</div>
                <div class="user-profile">
                    <i class="fa-solid fa-user"></i>
                    <span><?php echo htmlspecialchars($current_user['fullname']); ?></span>
                </div>
            </header>

            <div class="content-body">
                <div class="page-intro">
                    <h2>Nhập điểm</h2>
                    <p>Nhập và theo dõi điểm học tập của sinh viên.</p>
                </div>

                <?php if (!empty($msg)): ?>
                    <div class="alert <?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                        <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <!-- Form Nhập điểm -->
                <div class="card">
                    <h3>Thông tin sinh viên và điểm</h3>
                    <form action="nhap_diem.php" method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Mã sinh viên</label>
                                <input type="text" name="ma_sv" placeholder="Nhập mã SV (VD: SV001)" required>
                            </div>

                            <div class="form-group">
                                <label>Môn học</label>
                                <select name="monhoc" required>
                                    <option value="">Chọn môn học</option>
                                    <?php foreach ($ds_monhoc as $mh): ?>
                                        <option value="<?php echo htmlspecialchars($mh['ma_mon']); ?>">
                                            <?php echo htmlspecialchars($mh['ten_mon']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Điểm quá trình (Hệ số 0.3)</label>
                                <input type="number" step="0.1" min="0" max="10" name="diem_qt" placeholder="Nhập điểm quá trình" required>
                            </div>

                            <div class="form-group">
                                <label>Điểm cuối kỳ (Hệ số 0.7)</label>
                                <input type="number" step="0.1" min="0" max="10" name="diem_thi" placeholder="Nhập điểm cuối kỳ" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="btn_luu_diem" class="btn-submit">Lưu điểm</button>
                            <a href="nhap_diem.php" class="btn-reset">Làm mới</a>
                        </div>
                    </form>
                </div>

                <!-- Bảng danh sách điểm -->
                <div class="card">
                    <h3>Danh sách điểm sinh viên</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã SV</th>
                                    <th>Họ và Tên</th>
                                    <th>Lớp</th>
                                    <th>Môn học</th>
                                    <th>Điểm quá trình</th>
                                    <th>Điểm cuối kỳ</th>
                                    <th>Tổng kết HP</th>
                                    <th>Điểm chữ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ds_diem)): ?>
                                    <?php $stt = 1; foreach ($ds_diem as $row): ?>
                                        <tr>
                                            <td><?php echo $stt++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['ma_sv']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['ho_ten']); ?></td>
                                            <td><?php echo htmlspecialchars($row['lop']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ten_mon']); ?></td>
                                            <td><?php echo number_format($row['diem_qt'], 1); ?></td>
                                            <td><?php echo number_format($row['diem_thi'], 1); ?></td>
                                            <td><strong><?php echo number_format($row['diem_hp'], 2); ?></strong></td>
                                            <td>
                                                <span class="badge <?php echo ($row['diem_hp'] >= 4.0) ? 'badge-pass' : 'badge-fail'; ?>">
                                                    <?php echo $row['diem_chu']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" style="text-align: center; color: #94a3b8; padding: 24px;">Chưa có dữ liệu điểm nào trong hệ thống.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>