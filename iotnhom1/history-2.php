<?php
    session_start();
    $fullname = $_SESSION['fullname'];
    $user_id = $_SESSION['user_id'];
    $email = $_SESSION['email'];
    $tel = $_SESSION['tel'];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống phát hiện rò rỉ khí gas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
        }

        .navbar {
            background-color: #00796b;
            color: white;
            padding: 15px 20px;
            position: fixed; /* Cố định navbar */
            top: 0;
            left: 0;
            width: 100%; /* Đảm bảo navbar chiếm toàn bộ chiều ngang */
            z-index: 1040; /* Đặt z-index lớn hơn để navbar luôn nằm trên cùng */
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .navbar a:hover {
            color: #b2dfdb;
        }

        .sidebar {
            background-color: #004d40;
            color: white;
            padding: 20px;
            height: 100vh; /* Đảm bảo sidebar luôn chiếm toàn bộ chiều cao */
            width: 300px;
            position: fixed; /* Cố định sidebar */
            top: 60px; /* Đảm bảo sidebar bắt đầu dưới navbar */
            left: 0;
            z-index: 1030; /* Đảm bảo sidebar không che navbar */
            transition: width 0.3s ease-in-out;
            overflow: hidden;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            display: block;
            /* align-items: center; */
            padding: 10px;
            border-radius: 5px;
            white-space: nowrap;
            overflow: hidden;
            transition: background-color 0.3s ease;
        }
        .sidebar a i {
            margin-right: 10px; /* Khoảng cách giữa icon và văn bản */
        }

        .sidebar a:hover {
            background-color: #00796b;
        }

        .toggle-btn {
            display: inline-block;
            color: white;
            font-size: 30px;
            cursor: pointer;
            margin-bottom: 20px;
            text-align: center;
            margin-left: 5%;
        }

        .toggle-btn:hover {
            color: #b2dfdb;
        }

        .content {
            padding: 20px;
            margin-top: 60px; /* Đảm bảo nội dung không bị che bởi navbar */
            margin-left: 240px; /* Khoảng cách tương ứng với sidebar */
            background-color: #ffffff;
            border-left: 1px solid #b2dfdb;
            transition: margin-left 0.3s ease-in-out;
        }

        .content h2 {
            color: #00796b;
            margin-bottom: 20px;
        }

        .content p {
            font-size: 16px;
            line-height: 1.6;
        }

        .sidebar.collapsed a {
            visibility: hidden;
        }

        .sidebar.collapsed a::before {
            content: attr(data-tooltip);
            visibility: visible;
            display: block;
            text-align: center;
            font-size: 14px;
        }
        .sidebar.collapsed + .content {
            margin-left: 60px; 
        }
        body {
            padding-top: 60px; 
        }
       
        @media (min-width: 768px) {
            body {
                padding-top: 70px; 
            }
        }
       #logo{
        font-size: 30px;
        font-weight: 550;
       }
       #item-sidebar{
        font-size: 18px;
        font-weight: 550;
       }
       #header{
            background-color: 
            #00796b;
            color: #ffffff;
            font-weight: 550;
        }
        button {
            /* padding: 10px 10px; */
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 200px;
            height: 40px;
            margin: auto;
        }

        button:hover {
            background-color: 
            #004d40;
        }
        .user-info {
            margin: 5px auto;
            padding: 5px;
            border-radius: 12px;
            max-width: 300px;
            background: #00796b;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            color: #fff;
            font-family: Arial, sans-serif;
        }
        .user-info label {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
            display: block;
        }
        .user-info span {
            display: block;
            font-size: 16px;
            margin-bottom: 15px;
        }
        .user-info hr {
            border: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.6);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg py-3 fixed-top">
        <div class="container-fluid">
            <h1 class="ms-4" id="logo">NHÓM 1</h1>
            <div>
                <a href="./account.php" class="me-4"><i class="fas fa-user fa-2x"></i></a>
                <a href="./logout.php" class="me-4"><i class="fas fa-sign-out-alt fa-2x"></i></a> 
            </div>
        </div>
    </nav>    

    <!-- Container -->
    <div class="d-flex">
         <!-- Sidebar -->
         <div class="sidebar" id="sidebar">
            <div class="toggle-btn text-center mb-4" id="toggle-btn">
                ☰
            </div>
            <ul class="list-unstyled">
                <li><a href="./home.php" id="item-sidebar"><i class="fas fa-tachometer-alt fa-2x"></i> Tổng quan</a></li>
                <li><a href="./history.php" id="item-sidebar"><i class="fas fa-history fa-2x"></i> Lịch sử</a></li>
                    <ul class="list-unstyled ms-3" id="historySubMenu">
                        <li><a href="./history.php" id="item-sidebar">Lịch sử cảnh báo</a></li>
                        <li><a href="./system_history.php" id="item-sidebar">Lịch sử bật/tắt hệ thống</a></li>
                        <li><a href="./warning_history.php" id="item-sidebar">Lịch sử thay đổi ngưỡng</a></li>
                    </ul>
                <li><a href="./account.php" id="item-sidebar"><i class="fas fa-user-cog fa-2x"></i> Tài khoản</a></li>
            </ul>
        </div>

        <!-- Content -->
            <div class="content flex-grow-1 p-4" style="margin-top: 0%;">
                <div class="row">
                    <div class="col-12">
                        <div class="col-4">
                            <div class="card text-center"style="width: 30rem;">
                                <!-- Card Header -->
                                <div class="card-header" id="header">
                                   THÔNG TIN TÀI KHOẢN
                                </div>
                                <!-- Card Body -->
                                <div class="card-body" id="body-card">
                                <div class="user-info">
                                <label>Họ và tên:</label>
                                <span><?php echo htmlspecialchars($fullname); ?></span>
                                
                                <hr>
                                
                                <label>Số điện thoại:</label>
                                <span><?php echo htmlspecialchars($tel); ?></span>
                                
                                <hr>
                                
                                <label>Email:</label>
                                <span><?php echo htmlspecialchars($email); ?></span>
                            </div>
                                </div>
                                <!-- Nút tìm kiếm -->
                                <button style="font-weight: 700;" id="search_button" onclick="searchHistory()">Sửa thông tin tài khoản</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggle-btn');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
   
</body>
</html>
