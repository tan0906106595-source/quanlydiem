<?php
$host = 'localhost';
$username = 'root';
$password = '';         // Trên XAMPP mặc định để trống
$dbname = 'ql_diem_sv'; // Database bạn đã tạo trong phpMyAdmin

// Kết nối MySQL bằng mysqli
$conn = mysqli_connect($host, $username, $password, $dbname);

// Kiểm tra kết nối
if (!$conn) {
    die("Lỗi kết nối cơ sở dữ liệu: " . mysqli_connect_error());
}

// Thiết lập bảng mã tiếng Việt
mysqli_set_charset($conn, "utf8mb4");
?>