<?php

// Kết nối với cơ sở dữ liệu
include('db.php');

session_start();

if (!isset($_SESSION['fullname'])) {
    header("Location: index.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$user_id = $_SESSION['user_id'];
// Xử lý khi có yêu cầu từ WebSocket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['chedo'])) {
        $mode = $_POST['chedo'];
        // Lưu lịch sử chế độ vào cơ sở dữ liệu
        $sql = "INSERT INTO system_history (mode,user_id, timestamp) VALUES ('$mode','$user_id', NOW())";
        if ($conn->query($sql) === TRUE) {
            echo "Lưu chế độ thành công!";
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
    if (isset($_POST['mucCanhbao'])) {
        $threshold = $_POST['mucCanhbao'];
        // Lưu mức cảnh báo vào cơ sở dữ liệu
        $sql = "INSERT INTO warning_history (threshold, user_id, timestamp) VALUES ('$threshold','$user_id', NOW())";
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
        #header{
            background-color: 
            #00796b;
            color: #ffffff;
            font-weight: 550;
        }
        #body-card{
            border-color: #00796b;
        }
        #gasChart {
            max-width: 600px;
            margin: 20px 0;
        }
        .switch {
        position: relative;
        display: inline-block;
        width: 90px; /* Thay đổi chiều rộng của switch */
        height: 40px; /* Thay đổi chiều cao của switch */
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
        height: 35px; /* Thay đổi chiều cao của dot */
        width: 35px; /* Thay đổi chiều rộng của dot */
        left: 4px; /* Thay đổi vị trí ban đầu của dot */
        bottom: 3px; /* Thay đổi vị trí ban đầu của dot */
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
        }

        /* Khi switch được bật */
        input[type="checkbox"]:checked + .slider {
        background-color: #00796b;
        }

        input[type="checkbox"]:checked + .slider:before {
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
                <div class="col-4">
                    <div class="card text-center"style="width: 18rem;">
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
                
                <div class="col-4">
                    <div class="card text-center"style="width: 18rem;">
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
                
                <div class="col-4">
                    <div class="card text-center"style="width: 18rem;">
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
            </div>
            <div class="mt-5">
                <div class="row">
                    <div class="col-8">
                        <canvas id="gasChart"></canvas>
                    </div>
                    <div class="col-4">
                        <div class="card text-center"style="width: 18rem;">
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
    const socket = new WebSocket('ws://192.168.0.103:81'); 

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
          x: { title: { display: true, text: 'Thời gian' } },
          y: { beginAtZero: true, title: { display: true, text: 'ppm' } }
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

    socket.onopen = function() {
      console.log('Đã kết nối WebSocket');
    };

    socket.onerror = function(error) {
      console.error("WebSocket lỗi:", error);
    };

    socket.onclose = function() {
      console.log("Kết nối WebSocket đã bị đóng");
    };

    
    let alertTriggered = false;  // Biến trạng thái để kiểm tra xem cảnh báo đã được ghi nhận chưa

    socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log(data);

    if (data.gas) {
        document.getElementById('gasValue').innerText = parseFloat(data.gas).toFixed(2);
        updateChart(data.gas);

        // Kiểm tra nếu mức khí gas vượt ngưỡng và gửi thông tin cảnh báo chỉ khi lần đầu tiên vượt ngưỡng
        const threshold = document.getElementById('threshold').value;
        if (data.gas > threshold && !alertTriggered) {
            // Gửi dữ liệu cảnh báo khí gas vượt ngưỡng về PHP
            const formData = new FormData();
            formData.append('gasLevel', data.gas);
            formData.append('alertTriggered', 1); // Đánh dấu là có cảnh báo
            fetch('index.php', { method: 'POST', body: formData });
            
            alertTriggered = true;  // Đánh dấu rằng cảnh báo đã được ghi nhận
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
        alertStatus.innerText = data.canhbao === 1 ? 'DANGER' : 'SAFE';
        statusAlert.style.backgroundColor = data.canhbao === 1 ? 'red' : 'green';
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
    socket.send(JSON.stringify({ chedo: mode }));
    
    // Gửi dữ liệu chế độ vào PHP để lưu vào cơ sở dữ liệu
    const formData = new FormData();
    formData.append('chedo', mode);
    fetch('index.php', { method: 'POST', body: formData });
  } else {
    console.log("WebSocket chưa kết nối!");
  }
});

    document.getElementById('threshold').onchange = function() {
      const threshold = parseInt(this.value);
      if (socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({ mucCanhbao: threshold }));
        // Gửi mức cảnh báo vào PHP để lưu vào cơ sở dữ liệu
        const formData = new FormData();
        formData.append('mucCanhbao', threshold);
        fetch('index.php', { method: 'POST', body: formData });
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
    socket.send(JSON.stringify({ type: 'setThreshold', value: value }));
}
// Khi trang được tải lại, giá trị ngưỡng cũ sẽ được lấy từ WebSocket
window.onload = function () {
    // Đảm bảo đã kết nối WebSocket trước khi yêu cầu
    if (socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({ type: 'getThreshold' }));
    } else {
        socket.onopen = function () {
            socket.send(JSON.stringify({ type: 'getThreshold' }));
        };
    }
};
  </script>
</body>
</html>