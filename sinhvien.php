
<?php
require_once "ketnoi.php";

$sql = "SELECT * FROM sinh_vien";
$result = $conn->query($sql);

if (!$result) {
    die("Loi truy van: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sinh viên</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: #f3f6fb;
        }

        h2 {
            color: #123b70;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #123b70;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f6fc;
        }
    </style>
</head>
<body>

<h2>Danh sách sinh viên</h2>

<table>
    <tr>
        <th>Mã sinh viên</th>
        <th>Họ tên</th>
        <th>Ngày sinh</th>
        <th>Giới tính</th>
        <th>Lớp</th>
        <th>Email</th>
        <th>Số điện thoại</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= htmlspecialchars($row["MaSV"]) ?></td>
            <td><?= htmlspecialchars($row["HoTen"]) ?></td>
            <td><?= htmlspecialchars($row["NgaySinh"] ?? "") ?></td>
            <td><?= htmlspecialchars($row["GioiTinh"] ?? "") ?></td>
            <td><?= htmlspecialchars($row["Lop"] ?? "") ?></td>
            <td><?= htmlspecialchars($row["Email"] ?? "") ?></td>
            <td><?= htmlspecialchars($row["SDT"] ?? "") ?></td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
