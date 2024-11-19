<?php
// Kết nối với cơ sở dữ liệu
include('db.php');

session_start();

if (!isset($_SESSION['fullname'])) {
    header("Location: index.php");
    exit;
}

$fullname = $_SESSION['fullname'];

$start_date_alert = $_POST['start_date_alert'] ?? '';
$end_date_alert = $_POST['end_date_alert'] ?? '';

// Khởi tạo mảng kết quả
$results = ['alert' => []];

// if ($start_date_alert && $end_date_alert) {
//     // Chỉ lấy các trường gas_level và alert_time
//     $query_alert = "SELECT gas_level, alert_time FROM gas_alert_history WHERE alert_time BETWEEN '$start_date_alert' AND '$end_date_alert' ORDER BY alert_time DESC LIMIT 10";
//     $alert_result = $conn->query($query_alert);
//     while ($row = $alert_result->fetch_assoc()) {
//         $results['alert'][] = $row;
//     }
// }
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Trang hiện tại, mặc định là trang 1
$results_per_page = 10; // Số kết quả mỗi trang
$offset = ($page - 1) * $results_per_page;

// if ($start_date_alert && $end_date_alert) {
//     $query_alert = "SELECT threshold, timestamp
//                     FROM warning_history 
//                     WHERE timestamp BETWEEN '$start_date_alert' AND '$end_date_alert' 
//                     ORDER BY timestamp DESC 
//                     LIMIT $results_per_page OFFSET $offset";
//     $alert_result = $conn->query($query_alert);

//     while ($row = $alert_result->fetch_assoc()) {
//         $results['alert'][] = $row;
//     }

//     // Lấy tổng số bản ghi để tính tổng số trang
//     $query_count = "SELECT COUNT(*) as total 
//                     FROM warning_history
//                     WHERE timestamp BETWEEN '$start_date_alert' AND '$end_date_alert'";
//     $count_result = $conn->query($query_count);
//     $total_records = $count_result->fetch_assoc()['total'];
//     $results['total_pages'] = ceil($total_records / $results_per_page);
// }

// $conn->close();
if ($start_date_alert && $end_date_alert) {
    // Truy vấn dữ liệu kết hợp với bảng users để lấy fullname
    $stmt_alert = $conn->prepare("
        SELECT wh.threshold, wh.timestamp, u.fullname 
        FROM warning_history wh 
        JOIN users u ON wh.user_id = u.id 
        WHERE wh.timestamp BETWEEN ? AND ? 
        ORDER BY wh.timestamp DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt_alert->bind_param('ssii', $start_date_alert, $end_date_alert, $results_per_page, $offset);
    $stmt_alert->execute();
    $alert_result = $stmt_alert->get_result();

    // Lưu kết quả vào mảng
    while ($row = $alert_result->fetch_assoc()) {
        $results['alert'][] = $row;
    }

    // Đóng statement sau khi sử dụng
    $stmt_alert->close();

    // Truy vấn tổng số bản ghi
    $stmt_count = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM warning_history wh 
        JOIN users u ON wh.user_id = u.id
        WHERE wh.timestamp BETWEEN ? AND ?
    ");
    $stmt_count->bind_param('ss', $start_date_alert, $end_date_alert);
    $stmt_count->execute();
    $count_result = $stmt_count->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
    $results['total_pages'] = ceil($total_records / $results_per_page);

    // Đóng statement sau khi sử dụng
    $stmt_count->close();
}

// Đóng kết nối
$conn->close();

// Encode kết quả để gửi lại cho JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo json_encode($results);
    exit;
}
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
            margin-top: 60px; /* Đảm bảo nội dung không bị che bởi navbar */
            margin-left: 300px; /* Khoảng cách tương ứng với sidebar */
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
        #body-card{
            border-color: #00796b;
        }
        .card-search{
            margin: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        input {
            margin: 10px 5px;
            padding: 5px;
        }

        button {
            /* padding: 10px 10px; */
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 90px;
            height: 40px;
            margin: auto;
        }

        button:hover {
            background-color: 
            #004d40;
        }

        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
            
        }

        th, td {
            padding: 10px;
            text-align: left;
           
        }

        th {
            background-color: #00796b;
            color: #ffffff;
        }
        #pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        #pagination button {
            margin: 5px;
            padding: 8px 12px;
            border: none;
            background-color: #00796b;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        #pagination button:hover {
            background-color: #004d40;
        }

        #pagination button[disabled] {
            background-color: #ccc;
            cursor: not-allowed;
        }

        #pagination .page-info {
            font-size: 14px;
            color: #333;
            font-weight: bold;
        }

        .notification-icon {
            position: relative;
            display: inline-block;
        }

        .notification-icon .badge {
            position: absolute;
            top: -10px; 
            right: -10px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 4px 7px;
            font-size: 12px;
            font-weight: bold;
        }

        #alertHistoryContainer {
            position: fixed;
            top: 10%;
            right: 20px;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            color: #00796b;
        }

        #alertHistoryList {
            list-style-type: none;
            padding: 0;
        }

        .notification-icon {
            position: relative;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg py-3 fixed-top">
        <div class="container-fluid">
            <h1 class="ms-4" id="logo">NHÓM 1</h1>
            <div>
                <a href="#" class="me-4 notification-icon" id="notification-icon" onclick="toggleAlertHistory()">
                    <i class="fas fa-bell fa-2x"></i>
                    <span class="badge" id="notice"></span> <!-- Số lượng cảnh báo chưa xem -->
                </a>

                <!-- Modal hoặc phần tử để hiển thị lịch sử cảnh báo -->
                <div id="alertHistoryContainer" style="display: none;">
                    <h3>Lịch sử cảnh báo</h3>
                    <ul id="alertHistoryList"></ul>
                </div>
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
                <li><a href="./home.php" id="item-sidebar"><i class="fas fa-history fa-2x"></i> Lịch sử</a></li>
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
                        <div class="card text-center"style="width: 58rem;">
                            <!-- Card Header -->
                            <div class="card-header" id="header">
                                LỊCH SỬ THAY ĐỔI NGƯỠNG
                            </div>
                            <!-- Card Body -->
                            <div class="card-body" id="body-card">
                                <div class="card-search" style="font-size: 20px;font-weight:700">
                                    <label for="start_date_alert">Ngày bắt đầu:</label>
                                    <input type="date" id="start_date_alert" name="start_date_alert">
                                    <label for="end_date_alert">Ngày kết thúc:</label>
                                    <input type="date" id="end_date_alert" name="end_date_alert">
                                </div>
                            </div>
                            <!-- Nút tìm kiếm -->
                            <button style="font-weight: 700;" id="search_button" onclick="searchHistory()">Tìm kiếm</button>

                             <!-- Bảng hiển thị kết quả -->
                             <table id="result_table">
                                <thead>
                                    <tr>
                                        <th>Threshold</th>
                                        <th>Thời gian</th>
                                        <th>Người thực hiện</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Kết quả truy vấn sẽ hiển thị ở đây -->
                                </tbody>
                            </table>
                            <div id="pagination" class="text-center mt-4">
                                <!-- Nút <<, >> và số trang sẽ hiển thị tại đây -->
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
    <script>
        let currentPage = 1; // Trang hiện tại

        function searchHistory(page = 1) {
            currentPage = page;

            // Lấy giá trị từ các ô chọn ngày
            const start_date_alert = document.getElementById('start_date_alert').value;
            const end_date_alert = document.getElementById('end_date_alert').value;

            // Gửi yêu cầu AJAX đến server
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const results = JSON.parse(xhr.responseText);

                    // Hiển thị kết quả trong bảng
                    const resultTable = document.getElementById('result_table').getElementsByTagName('tbody')[0];
                    resultTable.innerHTML = ''; // Clear previous results
                    displayResults(results.alert, resultTable);

                    // Hiển thị thanh phân trang
                    displayPagination(results.total_pages);
                }
            };

            xhr.send(`start_date_alert=${start_date_alert}&end_date_alert=${end_date_alert}&page=${page}`);
        }

        function displayResults(data, tableBody) {
            data.forEach(row => {
                const tr = document.createElement('tr');
                Object.values(row).forEach(value => {
                    const td = document.createElement('td');
                    td.textContent = value;
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            });
        }

        function displayPagination(totalPages) {
            const paginationDiv = document.getElementById('pagination');
            paginationDiv.innerHTML = ''; // Xóa các nút phân trang trước đó

            // Nút << (Trang trước)
            const prevButton = document.createElement('button');
            prevButton.textContent = '<<';
            prevButton.className = 'pagination-btn';
            prevButton.onclick = () => {
                if (currentPage > 1) searchHistory(currentPage - 1);
            };
            prevButton.disabled = currentPage === 1; // Vô hiệu hóa nếu đang ở trang đầu tiên
            paginationDiv.appendChild(prevButton);

            // Thông tin số thứ tự trang hiện tại / tổng số trang
            const pageInfo = document.createElement('span');
            pageInfo.textContent = ` ${currentPage} / ${totalPages} `;
            pageInfo.className = 'page-info';
            paginationDiv.appendChild(pageInfo);

            // Nút >> (Trang sau)
            const nextButton = document.createElement('button');
            nextButton.textContent = '>>';
            nextButton.className = 'pagination-btn';
            nextButton.onclick = () => {
                if (currentPage < totalPages) searchHistory(currentPage + 1);
            };
            nextButton.disabled = currentPage === totalPages; // Vô hiệu hóa nếu đang ở trang cuối cùng
            paginationDiv.appendChild(nextButton);
        }

        // CẬP NHẬT THÔNG BÁO
     // Hàm để hiển thị/ẩn lịch sử cảnh báo khi nhấp vào chuông
     function toggleAlertHistory() {
        const historyContainer = document.getElementById('alertHistoryContainer');
        historyContainer.style.display = (historyContainer.style.display === 'none') ? 'block' : 'none';
        clearNotificationCount();
        updateAlertHistoryUI();
    }

    // Cập nhật giao diện lịch sử cảnh báo
    function updateAlertHistoryUI() {
        const historyList = document.getElementById('alertHistoryList');
        historyList.innerHTML = ''; // Xóa các phần tử cũ trong danh sách

        alertHistory.forEach((alert) => {
            const listItem = document.createElement('li');
            listItem.textContent = `${alert.message}`;
            historyList.appendChild(listItem);
        });

        // // Cập nhật số lượng cảnh báo chưa xem
        // const notificationCount = document.getElementById('notice');
        // notificationCount.textContent = alertHistory.length; // Hiển thị số lượng cảnh báo
    }

    function clearNotificationCount() {
    const notificationCount = document.getElementById('notice');
    notificationCount.textContent = ''; // Xóa số lượng thông báo
}
    </script>
</body>
</html>
