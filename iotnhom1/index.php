<?php
session_start();
ob_start(); // Bật bộ đệm đầu ra

// Nếu người dùng đã đăng nhập, chuyển hướng đến trang devices.php
if (isset($_SESSION['fullname'])) {
    header("Location: devices.php");
    exit;
}

// Kết nối đến cơ sở dữ liệu sử dụng mysqli (sử dụng kết nối đã có trong db.php)
require 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['tel'] = $user['tel'];
                $_SESSION['email'] = $user['email'];

                header("Location: devices.php");
                exit;
            } else {
                $error = "Tên đăng nhập hoặc mật khẩu không chính xác!";
            }

            $stmt->close();
        } else {
            $error = "Lỗi truy vấn cơ sở dữ liệu!";
        }
    } elseif (isset($_POST['register'])) {
        $username = $_POST['reg_username'];
        $password = $_POST['reg_password'];
        $email = $_POST['email'];
        $fullname = $_POST['fullname'];
        $tel = $_POST['tel'];

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, fullname, tel) VALUES (?, ?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $fullname, $tel);

            if ($stmt->execute()) {
                header("Location: index.php?success=1");
                exit;
            } else {
                $error = "Lỗi khi đăng ký người dùng!";
            }

            $stmt->close();
        } else {
            $error = "Lỗi truy vấn cơ sở dữ liệu!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Đăng nhập</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #00796b;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .login-box {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            width: 360px;
            text-align: center;
        }

        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #00796b;
        }

        .input-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .input-group label {
            font-size: 14px;
            font-weight: bold;
            color: #00796b;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #b2dfdb;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s ease-in-out;
        }

        .input-group input:focus {
            border-color: #00796b;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 121, 107, 0.5);
        }

        .submit-btn {
            width: 100%;
            padding: 10px;
            background: #00796b;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .submit-btn:hover {
            background: #004d40;
        }

        .error-message {
            color: #d32f2f;
            font-size: 14px;
            margin-bottom: 10px;
        }

        footer {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            color: #757575;
        }

        .register-btn {
            margin-top: 10px;
            display: block;
            font-size: 14px;
            color: #00796b;
            text-decoration: none;
        }

        .register-btn:hover {
            color: #004d40;
        }

        /* Dialog Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            text-align: center;
        }

        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
        }

        .modal-content button {
            width: 48%;
            padding: 10px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            background-color: #00796b;
            color: #fff;
        }

        .modal-content .cancel-btn {
            background-color: #d32f2f;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h2>HỆ THỐNG PHÁT HIỆN <br> RÒ RỈ KHÍ GAS</h2>
            <i class="fas fa-fire fa-5x" style="margin-right:auto;color:#45aed7"></i> <br>
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

                <button type="submit" name="login" class="submit-btn">Đăng nhập</button>
            </form>

            <a href="#" class="register-btn" id="registerBtn">Đăng ký tài khoản</a>
        </div>
    </div>

    <!-- Modal Dialog for Registration -->
    <div class="modal" id="registerModal">
        <div class="modal-content">
            <h3>Đăng ký tài khoản</h3>
            <form action="" method="POST">
                <input type="text" name="reg_username" placeholder="Tên đăng nhập" required>
                <input type="password" name="reg_password" placeholder="Mật khẩu" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="fullname" placeholder="Họ và tên" required>
                <input type="tel" name="tel" placeholder="Số điện thoại" required>
                <button type="submit" name="register">Xác nhận</button>
                <button type="button" class="cancel-btn" id="cancelRegister">Hủy</button>
            </form>
        </div>
    </div>

    <script>
        const registerBtn = document.getElementById('registerBtn');
        const registerModal = document.getElementById('registerModal');
        const cancelRegister = document.getElementById('cancelRegister');

        registerBtn.addEventListener('click', function() {
            registerModal.style.display = 'flex';
        });

        cancelRegister.addEventListener('click', function() {
            registerModal.style.display = 'none';
        });
    </script>

    <script>
        // Kiểm tra nếu URL chứa tham số "success"
        document.addEventListener("DOMContentLoaded", function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('success')) {
                alert("Đăng ký thành công! Vui lòng đăng nhập.");
                // Xóa tham số "success" khỏi URL để tránh hiển thị lại
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>

</html>