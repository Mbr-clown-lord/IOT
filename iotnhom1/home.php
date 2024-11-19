<?php

session_start();

if (!isset($_SESSION['fullname'])) {
    header("Location: index.php");
    exit;
}
// Kết nối với cơ sở dữ liệu
include('db.php');

$fullname = $_SESSION['fullname'];
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Xử lý khi có yêu cầu từ WebSocket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['chedo'])) {
        $mode = $_POST['chedo'];
        // Lưu lịch sử chế độ vào cơ sở dữ liệu
        $sql = "INSERT INTO system_history (mode,timestamp,user_id) VALUES ('$mode', NOW(),'$user_id')";
        if ($conn->query($sql) === TRUE) {
            echo "Lưu chế độ thành công!";
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
    if (isset($_POST['mucCanhbao'])) {
        $threshold = $_POST['mucCanhbao'];
        // Lưu mức cảnh báo vào cơ sở dữ liệu
        $sql = "INSERT INTO warning_history (threshold,timestamp, user_id) VALUES ('$threshold', NOW(),'$user_id')";
        if ($conn->query($sql) === TRUE) {
            echo "Lưu mức cảnh báo thành công!";
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
    if (isset($_POST['gasLevel']) && isset($_POST['alertTriggered']) && $_POST['alertTriggered'] == 1) {
        // Lưu lịch sử cảnh báo khí gas vượt ngưỡng
        $gasLevel = $_POST['gasLevel'];
        $sql = "INSERT INTO gas_alert_history (gas_level, alert_time) VALUES ('$gasLevel', NOW())";
        if ($conn->query($sql) === TRUE) {
            echo "Lưu cảnh báo khí gas thành công!";
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
        }

        .navbar {
            background-color: #00796b;
            color: white;
            padding: 15px 20px;
            position: fixed;
            /* Cố định navbar */
            top: 0;
            left: 0;
            width: 100%;
            /* Đảm bảo navbar chiếm toàn bộ chiều ngang */
            z-index: 1040;
            /* Đặt z-index lớn hơn để navbar luôn nằm trên cùng */
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
            height: 100vh;
            /* Đảm bảo sidebar luôn chiếm toàn bộ chiều cao */
            width: 240px;
            position: fixed;
            /* Cố định sidebar */
            top: 60px;
            /* Đảm bảo sidebar bắt đầu dưới navbar */
            left: 0;
            z-index: 1030;
            /* Đảm bảo sidebar không che navbar */
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
            margin-right: 10px;
            /* Khoảng cách giữa icon và văn bản */
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
            margin-top: 60px;
            /* Đảm bảo nội dung không bị che bởi navbar */
            margin-left: 240px;
            /* Khoảng cách tương ứng với sidebar */
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

        .sidebar.collapsed+.content {
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

        #logo {
            font-size: 30px;
            font-weight: 550;
        }

        #item-sidebar {
            font-size: 18px;
            font-weight: 550;
        }

        .status {
            margin-top: 10px;
        }

        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-top: 10px;
            display: inline-block;
        }

        #header {
            background-color:
                #00796b;
            color: #ffffff;
            font-weight: 550;
        }

        #body-card {
            border-color: #00796b;
        }

        #gasChart {
            max-width: 600px;
            margin: 20px 0;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 90px;
            height: 40px;
        }

        .switch input[type="checkbox"] {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: gray;
            transition: 0.4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 35px;
            /* Thay đổi chiều cao của dot */
            width: 35px;
            /* Thay đổi chiều rộng của dot */
            left: 4px;
            /* Thay đổi vị trí ban đầu của dot */
            bottom: 3px;
            /* Thay đổi vị trí ban đầu của dot */
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        /* Khi switch được bật */
        input[type="checkbox"]:checked+.slider {
            background-color: #00796b;
        }

        input[type="checkbox"]:checked+.slider:before {
            transform: translateX(50px);
        }

        /* ĐIỀU CHỈNH NGƯỠNG */
        /* Định dạng thanh trượt */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            background: gray;
            border-radius: 5px;
            outline: none;
            opacity: 0.7;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        /* Định dạng phần nút kéo trên thanh trượt (WebKit) */
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 25px;
            height: 25px;
            background:
                #00796b;
            border-radius: 50%;
            cursor: pointer;
        }

        /* Định dạng phần nút kéo trên thanh trượt (Firefox) */
        input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            background: #00796b;
            border-radius: 50%;
            cursor: pointer;
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

        #disconnectDialog {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: red;
            border: 1px solid #ccc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        #disconnectDialog button {
            padding: 10px;
            background-color: #00796b;
            color: white;
            border: none;
            cursor: pointer;
        }

        #disconnectDialog button:hover {
            background-color: #004d40;
        }

        #okButton {
            width: 40%;
            padding: 10px;
            background: #004d40;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            margin: auto;
        }
    </style>
</head>

<body>
    <div id="disconnectDialog" class="text-center">
        <p style="color: yellow;font-weight:300;font-size:20px">Thiết bị bị ngắt kết nối !!! <br> Hãy kết nối lại.</p>
        <button id="okButton">OK</button>
    </div>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg py-3 fixed-top">
        <div class="container-fluid">
            <h1 class="ms-4" id="logo">NHÓM 1</h1>
            <h2 style="color: ;">Chào mừng <?php echo $fullname; ?> !</h2>
            <!-- <h2 style="color: #ffff;font-weight:500;font-size:25px"><?php echo htmlspecialchars($fullname); ?>!</h2> -->
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
                <li><a href="./history-1.php" id="item-sidebar"><i class="fas fa-history fa-2x"></i> Lịch sử</a></li>
                <li><a href="./account.php" id="item-sidebar"><i class="fas fa-user-cog fa-2x"></i> Tài khoản</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div class="content flex-grow-1 p-4" style="margin-top: 0%;">
            <div class="row">
                <div class="col-3">
                    <div class="card text-center" style="width: 14rem;">
                        <!-- Card Header -->
                        <div class="card-header" id="header">
                            LƯỢNG GAS ĐO ĐƯỢC
                        </div>
                        <!-- Card Body -->
                        <div class="card-body" id="body-card">
                            <span style="font-size: 45px; font-weight: 550;" id="gasValue"></span>
                            <h1 style="font-weight: 550;">PPM</h1>
                        </div>
                    </div>
                </div>

                <div class="col-3">
                    <div class="card text-center" style="width: 14rem;">
                        <!-- Card Header -->
                        <div class="card-header" id="header">
                            TRẠNG THÁI HỆ THỐNG
                        </div>
                        <!-- Card Body -->
                        <div class="card-body" id="body-card">
                            <p class="status" id="modeStatus" style="font-size: 32px; font-weight: 550;">OFF</p>
                            <!-- Thêm biểu tượng hình tròn -->
                            <div id="statusSys" class="status-icon"></div>
                        </div>
                    </div>
                </div>

                <div class="col-3">
                    <div class="card text-center" style="width: 14rem;">
                        <!-- Card Header -->
                        <div class="card-header" id="header">
                            TRẠNG THÁI CẢNH BÁO
                        </div>
                        <!-- Card Body -->
                        <div class="card-body" id="body-card">
                            <p class="status" id="alertStatus" style="font-size: 33px; font-weight: 550;">SAFE</p>
                            <div id="statusAlert" class="status-icon"></div>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    <div class="card text-center" style="width: 14rem;">
                        <!-- Card Header -->
                        <div class="card-header" id="header">
                            TRẠNG THÁI QUẠT
                        </div>
                        <!-- Card Body -->
                        <div class="card-body" id="body-card">
                            <p class="status" id="fanStatus" style="font-size: 33px; font-weight: 550;">OFF</p>
                            <div id="statusFan" class="status-icon"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <div class="row">
                    <div class="col-8">
                        <canvas id="gasChart"></canvas>
                    </div>
                    <div class="col-4">
                        <div class="card text-center" style="width: 18rem;">
                            <!-- Card Header -->
                            <div class="card-header" id="header">
                                BẬT/TẮT CẢNH BÁO
                            </div>
                            <!-- Card Body -->
                            <div class="card-body" id="body-card">
                                <label class="switch">
                                    <input type="checkbox" id="toggleMode">
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-5">
                            <div class="card text-center" style="width: 18rem;">
                                <!-- Card Header -->
                                <div class="card-header" id="header">
                                    ĐIỀU CHỈNH NGƯỠNG GAS
                                </div>
                                <!-- Card Body -->
                                <div class="card-body" id="body-card">
                                    <input type="range" id="threshold" value="0" min="0" max="1000" step="10" oninput="updateValue(this.value)">
                                    <p id="currentValue" style="font-size: 35px; font-weight: 550;">0</p>
                                    <p style="font-size: 35px; font-weight: 550;margin-top: -10%;">PPM</p>
                                    <!-- <span id="currentValue">30</span> -->
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">

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
    <!-- Mã Scripts -->
    <script>
        const disconnectDialog = document.getElementById('disconnectDialog');
        const okButton = document.getElementById('okButton');

        // Hiển thị dialog khi mất kết nối
        function showDisconnectDialog() {
            if (disconnectDialog) {
                disconnectDialog.style.display = 'block';
            }
        }

        // Xử lý sự kiện click vào nút OK trong dialog
        if (okButton) {
            okButton.onclick = function() {
                if (disconnectDialog) {
                    disconnectDialog.style.display = 'none';
                }
                // Quay lại giao diện chính hoặc trang khác nếu cần
                window.location.href = 'home.php';
            };
        }
        const socket = new WebSocket('ws://192.168.0.103:81');

        socket.onopen = function() {
            console.log('Đã kết nối WebSocket');
        };

        socket.onerror = function(error) {
            console.error("WebSocket lỗi:", error);
            showDisconnectDialog();
        };

        // Sự kiện khi WebSocket bị đóng
        socket.onclose = function(event) {
            console.log("Kết nối WebSocket đã bị đóng");
            // Kiểm tra mã đóng kết nối, nếu không phải là đóng bình thường thì hiển thị thông báo
            if (event && event.code !== 1000) { // 1000 là mã đóng kết nối bình thường
                showDisconnectDialog();
            } else {
                // Hiển thị dialog ngay lập tức nếu kết nối bị đóng bình thường
                setTimeout(showDisconnectDialog, 0);
            }
        };
        const ctx = document.getElementById('gasChart').getContext('2d');
        const gasChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Lượng khí Gas (ppm)',
                    data: [],
                    borderColor: '#51d4cb',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Thời gian'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'ppm'
                        }
                    }
                }
            }
        });

        function updateChart(value) {
            const now = new Date();
            const timeLabel = now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
            gasChart.data.labels.push(timeLabel);
            gasChart.data.datasets[0].data.push(value);
            // Lấy giá trị hiện tại từ phần tử <p id="currentValue">
            const currentValueElement = document.getElementById('currentValue');
            const currentValue = parseFloat(currentValueElement.textContent) || 0; // Đảm bảo giá trị là số


            if (value > currentValue) {

                gasChart.data.datasets[0].borderColor = 'rgb(255, 0, 0)';
            } else {

                gasChart.data.datasets[0].borderColor = '#51d4cb';
            }
            if (gasChart.data.labels.length > 20) {
                gasChart.data.labels.shift();
                gasChart.data.datasets[0].data.shift();
            }

            gasChart.update();
        }

        let alertHistory = []; // Mảng lưu lịch sử cảnh báo
        let alertTriggered = false; // Biến cờ đánh dấu trạng thái cảnh báo

        socket.onmessage = function(event) {
            const data = JSON.parse(event.data);
            console.log(data);

            if (data.gas) {
                document.getElementById('gasValue').innerText = parseFloat(data.gas).toFixed(2);
                updateChart(data.gas);

                // Lấy ngưỡng cảnh báo từ input
                const threshold = document.getElementById('threshold').value;

                // Kiểm tra nếu mức khí gas vượt ngưỡng và chỉ gửi thông báo 1 lần
                if (data.gas > threshold && !alertTriggered) {
                    // Lấy thời gian hiện tại
                    const alertTime = new Date().toLocaleString();

                    // Tạo thông báo cảnh báo
                    const alertMessage = `Cảnh báo: Khí gas vượt ngưỡng! Mức gas: ${data.gas} tại thời gian: ${alertTime}`;

                    // Thêm vào lịch sử cảnh báo
                    alertHistory.push({
                        gasLevel: data.gas,
                        alertTime: alertTime,
                        message: alertMessage
                    });

                    // Cập nhật số lượng cảnh báo chưa xem
                    const notificationCount = document.getElementById('notice');
                    if (notificationCount) {
                        notificationCount.textContent = alertHistory.length; // Hiển thị số lượng cảnh báo
                    }

                    // Đánh dấu đã gửi cảnh báo
                    alertTriggered = true;

                    // Gửi dữ liệu cảnh báo khí gas vượt ngưỡng về PHP
                    const formData = new FormData();
                    formData.append('gasLevel', data.gas);
                    formData.append('alertTriggered', 1); // Đánh dấu là có cảnh báo
                    fetch('home.php', {
                        method: 'POST',
                        body: formData
                    });
                }

                // Nếu mức khí gas trở lại bình thường, reset biến alertTriggered
                if (data.gas <= threshold) {
                    alertTriggered = false; // Reset cờ khi mức khí gas giảm xuống dưới ngưỡng
                }
            }


            if (data.threshold) {
                document.getElementById('threshold').value = data.threshold;
                document.getElementById('currentValue').textContent = data.threshold;
            }
            if (data.status !== undefined) {
                const modeStatus = document.getElementById('modeStatus');
                const statusSys = document.getElementById('statusSys');
                updateSwitchStatus(data.status);
                modeStatus.innerText = data.status === 1 ? 'ON' : 'OFF';
                statusSys.style.backgroundColor = data.status === 1 ? 'green' : 'gray';
            }
            if (data.canhbao !== undefined) {
                const alertStatus = document.getElementById('alertStatus');
                const statusAlert = document.getElementById('statusAlert');
                const fanStatus = document.getElementById('fanStatus');
                alertStatus.innerText = data.canhbao === 1 ? 'DANGER' : 'SAFE';
                statusAlert.style.backgroundColor = data.canhbao === 1 ? 'red' : 'green';
                fanStatus.innerText = data.canhbao === 1 ? 'ON' : 'OFF';
                statusFan.style.backgroundColor = data.canhbao === 1 ? 'green' : 'gray';
            }

        };

        function updateSwitchStatus(status) {
            var toggle = document.getElementById('toggleMode');
            // Nếu trạng thái là 1 (bật), bật checkbox; nếu là 0 (tắt), tắt checkbox
            if (status === 1) {
                toggle.checked = true; // Bật chế độ
            } else if (status === 0) {
                toggle.checked = false; // Tắt chế độ
            }
        }

        document.getElementById('toggleMode').addEventListener('change', function() {
            const mode = this.checked ? 1 : 0; // Kiểm tra nếu switch đang bật thì mode = 1, nếu tắt thì mode = 0
            if (socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({
                    chedo: mode
                }));

                // Gửi dữ liệu chế độ vào PHP để lưu vào cơ sở dữ liệu
                const formData = new FormData();
                formData.append('chedo', mode);
                fetch('home.php', {
                    method: 'POST',
                    body: formData
                });
            } else {
                console.log("WebSocket chưa kết nối!");
            }
        });

        document.getElementById('threshold').onchange = function() {
            const threshold = parseInt(this.value);
            if (socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({
                    mucCanhbao: threshold
                }));
                // Gửi mức cảnh báo vào PHP để lưu vào cơ sở dữ liệu
                const formData = new FormData();
                formData.append('mucCanhbao', threshold);
                fetch('home.php', {
                    method: 'POST',
                    body: formData
                });
            } else {
                console.log("WebSocket chưa kết nối!");
            }
        };

        window.onload = function() {
            const savedThreshold = localStorage.getItem('threshold');
            if (savedThreshold) {
                document.getElementById('threshold').value = savedThreshold;
            }
        };
        // Điều chỉnh ngưỡng cảnh báo
        function updateValue(value) {
            document.getElementById('currentValue').textContent = value;
        }

        // Hàm cập nhật giá trị hiển thị và gửi giá trị thay đổi từ thanh trượt
        function updateValue(value) {
            document.getElementById('currentValue').textContent = value;

            // Gửi giá trị ngưỡng gas mới tới WebSocket server
            socket.send(JSON.stringify({
                type: 'setThreshold',
                value: value
            }));
        }
        // Khi trang được tải lại, giá trị ngưỡng cũ sẽ được lấy từ WebSocket
        window.onload = function() {
            // Đảm bảo đã kết nối WebSocket trước khi yêu cầu
            if (socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({
                    type: 'getThreshold'
                }));
            } else {
                socket.onopen = function() {
                    socket.send(JSON.stringify({
                        type: 'getThreshold'
                    }));
                };
            }
        };

        // CẬP NHẬT THÔNG BÁO 
        // Hàm để hiển thị/ẩn lịch sử cảnh báo khi nhấp vào chuông
        function toggleAlertHistory() {
            const historyContainer = document.getElementById('alertHistoryContainer');
            historyContainer.style.display = (historyContainer.style.display === 'none') ? 'block' : 'none';
            updateAlertHistoryUI();

            // Sau khi người dùng nhấp để xem lịch sử, xóa số lượng thông báo
            clearNotificationCount();
        }

        // Cập nhật giao diện lịch sử cảnh báo
        function updateAlertHistoryUI() {
            const historyList = document.getElementById('alertHistoryList');
            historyList.innerHTML = ''; // Xóa các phần tử cũ trong danh sách

            alertHistory.slice().reverse().forEach((alert) => {
                const listItem = document.createElement('li');
                listItem.textContent = `${alert.message}`;
                historyList.appendChild(listItem);
            });

            // Cập nhật số lượng cảnh báo chưa xem
            const notificationCount = document.getElementById('notice');
            if (notificationCount) {
                notificationCount.textContent = alertHistory.length; // Hiển thị số lượng cảnh báo
            }
        }

        // Xóa số lượng thông báo sau khi xem
        function clearNotificationCount() {
            // Xóa số lượng hiển thị
            const notificationCount = document.getElementById('notice');
            if (notificationCount) {
                notificationCount.textContent = ''; // Xóa số lượng thông báo hiển thị
            }
            // Đặt lại mảng alertHistory về rỗng nếu cần
            alertHistory = [];
        }
    </script>

</body>

</html>