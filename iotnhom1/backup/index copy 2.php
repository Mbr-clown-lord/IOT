<?php
session_start();

// Nếu người dùng đã đăng nhập, chuyển hướng đến trang home.php
if (isset($_SESSION['fullname'])) {
    header("Location: home.php");
    exit;
}

// Kết nối đến cơ sở dữ liệu sử dụng mysqli (sử dụng kết nối đã có trong db.php)
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Truy vấn chỉ lấy thông tin người dùng dựa trên username
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    
    // Liên kết tham số vào câu truy vấn
    $stmt->bind_param("s", $username); // "s" nghĩa là tham số là chuỗi

    // Thực thi câu lệnh
    $stmt->execute();

    // Lấy kết quả
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Kiểm tra nếu người dùng tồn tại và mật khẩu có khớp
    if ($user && password_verify($password, $user['password'])) {
        // Đăng nhập thành công
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['user_id'] = $user['id'];  // Lưu id người dùng vào session

        header("Location: home.php");
        exit;
    } else {
        // Đăng nhập thất bại
        $error = "Tên đăng nhập hoặc mật khẩu không chính xác!";
    }

    // Đóng câu lệnh chuẩn bị
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" />
    <title>Đăng nhập</title>
    <style>
        /* Thiết lập chung cho trang */
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to right, #5F9EA0, #8FBC8F); /* Màu nền gradient */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Container chính */
        .login-container {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        /* Hộp đăng nhập */
        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }

        /* Tiêu đề */
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        /* Nhóm nhập liệu */
        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            font-size: 14px;
            color: #333;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Nút đăng nhập */
        .submit-btn {
            width: 100%;
            padding: 10px;
            background-color: #5F9EA0;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #4682B4;
        }

        /* Thông báo lỗi */
        .error-message {
            color: red;
            font-size: 14px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Đăng nhập</h2>
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="input-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="input-group">
                    <label for="password">Mật khẩu</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="submit-btn">Đăng nhập</button>
            </form>
        </div>
    </div>
</body>
</html>
