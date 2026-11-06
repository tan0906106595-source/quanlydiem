<?php
session_start();
require_once "ketnoi.php";

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    unset($_SESSION['user_logged']);
    header('Location: dangnhap.php');
    exit();
}

// Nếu đã đăng nhập rồi thì chuyển thẳng vào trang chủ
if (isset($_SESSION['user_logged'])) {
    header('Location: index.php');
    exit();
}

$error = "";
$success = "";
$active_tab = "login";

// XỬ LÝ ĐĂNG KÝ TÀI KHOẢN
if (isset($_POST['btn_register'])) {
    $active_tab = "register";
    $reg_username = trim($_POST['reg_username']);
    $reg_fullname = trim($_POST['reg_fullname']);
    $reg_password = $_POST['reg_password'];
    $reg_confirm  = $_POST['reg_confirm'];

    if (empty($reg_username) || empty($reg_fullname) || empty($reg_password)) {
        $error = "Vui lòng nhập đầy đủ tất cả thông tin!";
    } elseif ($reg_password !== $reg_confirm) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        $user_safe = mysqli_real_escape_string($conn, $reg_username);
        $check_sql = "SELECT id FROM tai_khoan WHERE username = '$user_safe'";
        $res_check = mysqli_query($conn, $check_sql);

        if ($res_check && mysqli_num_rows($res_check) > 0) {
            $error = "Tên tài khoản này đã tồn tại, vui lòng chọn tên khác!";
        } else {
            $fullname_safe = mysqli_real_escape_string($conn, $reg_fullname);
            $pass_safe = mysqli_real_escape_string($conn, $reg_password);
            $insert_sql = "INSERT INTO tai_khoan (username, password, fullname, role) 
                           VALUES ('$user_safe', '$pass_safe', '$fullname_safe', 'user')";
            if (mysqli_query($conn, $insert_sql)) {
                $success = "Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.";
                $active_tab = "login";
            } else {
                $error = "Lỗi tạo tài khoản: " . mysqli_error($conn);
            }
        }
    }
}

// XỬ LÝ ĐĂNG NHẬP
if (isset($_POST['btn_login'])) {
    $active_tab = "login";
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Vui lòng nhập tài khoản và mật khẩu!";
    } else {
        $user_safe = mysqli_real_escape_string($conn, $username);
        $pass_safe = mysqli_real_escape_string($conn, $password);

        $sql_login = "SELECT * FROM tai_khoan WHERE username = '$user_safe' AND password = '$pass_safe'";
        $res_login = mysqli_query($conn, $sql_login);

        if ($res_login && mysqli_num_rows($res_login) > 0) {
            $row = mysqli_fetch_assoc($res_login);
            $_SESSION['user_logged'] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'fullname' => $row['fullname'],
                'role' => $row['role']
            ];
            header('Location: index.php');
            exit();
        } else {
            $error = "Sai tên đăng nhập hoặc mật khẩu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập & Đăng ký - Hệ thống Quản lý điểm SV</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
            background: linear-gradient(135deg, #1d2d5b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-container {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .auth-header {
            background-color: #f8fafc;
            padding: 24px 20px 18px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .auth-header i { font-size: 2.2rem; color: #2563eb; margin-bottom: 8px; }
        .auth-header h2 { font-size: 1.25rem; color: #1e293b; font-weight: 700; }
        .auth-header p { font-size: 0.85rem; color: #64748b; margin-top: 4px; }

        .tabs { display: flex; border-bottom: 1px solid #e2e8f0; }
        .tab-btn {
            flex: 1;
            padding: 14px;
            background: #fff;
            border: none;
            outline: none;
            font-size: 0.92rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: 0.2s;
        }
        .tab-btn.active {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            background: #fafafa;
        }

        .auth-body { padding: 24px; }
        .alert {
            padding: 11px 14px;
            border-radius: 6px;
            font-size: 0.86rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }

        .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: #334155; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.95rem; }
        .input-group input {
            width: 100%;
            padding: 10px 14px 10px 40px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
            transition: 0.2s;
        }
        .input-group input:focus { border-color: #2563eb; }

        .btn-submit {
            width: 100%;
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 6px;
        }
        .btn-submit:hover { background: #1d4ed8; }

        .form-tab { display: none; }
        .form-tab.active { display: block; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <i class="fa-solid fa-graduation-cap"></i>
            <h2>HỆ THỐNG QUẢN LÝ ĐIỂM</h2>
            <p>Đăng nhập hoặc tạo tài khoản để sử dụng hệ thống</p>
        </div>

        <div class="tabs">
            <button type="button" class="tab-btn <?php echo ($active_tab == 'login') ? 'active' : ''; ?>" onclick="switchTab('login')">Đăng nhập</button>
            <button type="button" class="tab-btn <?php echo ($active_tab == 'register') ? 'active' : ''; ?>" onclick="switchTab('register')">Đăng ký mới</button>
        </div>

        <div class="auth-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <!-- FORM ĐĂNG NHẬP -->
            <form id="form-login" class="form-tab <?php echo ($active_tab == 'login') ? 'active' : ''; ?>" action="dangnhap.php" method="POST">
                <div class="form-group">
                    <label>Tài khoản</label>
                    <div class="input-group">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" name="username" placeholder="Nhập tên đăng nhập" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="Nhập mật khẩu" required>
                    </div>
                </div>
                <button type="submit" name="btn_login" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                </button>
            </form>

            <!-- FORM ĐĂNG KÝ -->
            <form id="form-register" class="form-tab <?php echo ($active_tab == 'register') ? 'active' : ''; ?>" action="dangnhap.php" method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-group">
                        <i class="fa-solid fa-user-plus"></i>
                        <input type="text" name="reg_username" placeholder="Chọn tên tài khoản" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Họ và tên hiển thị</label>
                    <div class="input-group">
                        <i class="fa-solid fa-id-card"></i>
                        <input type="text" name="reg_fullname" placeholder="Họ và tên của bạn" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fa-solid fa-key"></i>
                        <input type="password" name="reg_password" placeholder="Tạo mật khẩu" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <div class="input-group">
                        <i class="fa-solid fa-shield-halved"></i>
                        <input type="password" name="reg_confirm" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>
                <button type="submit" name="btn_register" class="btn-submit" style="background-color: #0ea5e9;">
                    <i class="fa-solid fa-user-check"></i> Xác nhận đăng ký
                </button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.form-tab').forEach(form => form.classList.remove('active'));

            if (tab === 'login') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('form-login').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('form-register').classList.add('active');
            }
        }
    </script>
</body>
</html>