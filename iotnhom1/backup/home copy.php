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
  <link rel="stylesheet" href="index.php" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <title>Hệ thống phát hiện rò rỉ khí gas</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .status {
    font-size: 20px;
    margin-top: 10px;
  }
  #gasValue {
    font-size: 25px;
    font-weight: bold;
  }
  button {
    margin-top: 20px;
    padding: 10px;
    font-size: 16px;
  }
  #gasChart {
    max-width: 600px;
    margin: 20px 0;
  }
  /* Sidebar style */
  #sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 250px;
    background-color: #95b0b4;
    padding-top: 60px;
    z-index: 100;
  }

  #sidebar a {
    color: white;
    padding: 15px;
    text-decoration: none;
    font-size: 18px;
    display: block;
  }

  #sidebar a:hover {
    background-color: #ffff;
    color: black;
  }

  /* Content */
  .content {
    margin-top:100px;
    margin-left: 260px; 
    padding: 20px;
  }
/* ICON TRẠNG THÁI */
/* CSS cho biểu tượng hình tròn */
.status-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-top: 10px;
  display: inline-block;
}
/* CARD-HEADER */
#header{
  background-color: #45aed7;
  color: white;
  font-weight: 700;
}
/* SWITCH */
/* CSS for switch */
/* CSS for switch */
.switch {
  position: relative;
  display: inline-block;
  width: 90px; /* Thay đổi chiều rộng của switch */
  height: 40px; /* Thay đổi chiều cao của switch */
}

.switch input {
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
  background-color: #ccc;
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
input:checked + .slider {
  background-color: #4CAF50;
}

input:checked + .slider:before {
  transform: translateX(50px); 
}

/* ĐIỀU CHỈNH NGƯỠNG */
#threshold {
  width: 150px; 
  height: 50px; 
  padding: 0 20px; 
  font-size: 24px; 
  border-radius: 25px; 
  border: 2px solid #4CAF50; 
  background-color: #f5f5f5; 
  transition: all 0.3s ease;
}

/* Hiệu ứng khi input được focus */
#threshold:focus {
  outline: none; /* Bỏ viền mặc định */
  border-color: #2e7d32; /* Đổi màu viền khi focus */
  background-color: #ffffff; /* Thay đổi nền khi focus */
  box-shadow: 0 0 10px rgba(46, 125, 50, 0.6); /* Hiệu ứng bóng khi focus */
}

/* Tùy chỉnh hiệu ứng hover */
#threshold:hover {
  border-color: #388e3c; /* Màu viền khi hover */
  background-color: #f1f8e9; /* Màu nền khi hover */
  cursor: pointer; /* Thay đổi con trỏ khi hover */
}

/* Đặt khoảng cách và font chữ cho thẻ p */
p {
  font-size: 20px; /* Kích thước chữ của thẻ p */
  font-family: 'Roboto', sans-serif; /* Font chữ đẹp */
  font-weight: 500; /* Làm cho chữ đậm hơn */
}

  </style>
</head>
<body>
  <!-- Sidebar -->
  <div id="sidebar">
    <a href="./index.php">Trang chủ</a>
    <a href="./history.php">Xem lịch sử</a>
    <a href="./logout.php">Đăng xuất</a>
  </div>

  <!-- Navbar -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-light" style="background-color:#45aed7" data-aos="fade-down">
    <div class="container">
      <!-- <a class="navbar-brand logo-text rounded-circle " href="./index.php"><img src="./asset/logo_fire.png" alt="" width="160px" height="40px"></a> -->
      <h2 style="color:#ffff">NHÓM 1</h2>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <!-- <a class="nav-link" href="./index.html">Trang chủ</a> -->
            <h2 style="color: #ffff;">Chào mừng, <?php echo htmlspecialchars($fullname); ?>!</h2>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="content">
    <div class="container">
      <div class="row">
        <div class="col-4">
        <div class="card text-center">
  <div class="card-header" id="header">
    <p>TRẠNG THÁI HỆ THỐNG</p>
  </div>
  <div class="card-body">
    <p class="status" id="modeStatus">OFF</p>
    <!-- Thêm biểu tượng hình tròn -->
    <div id="statusSys" class="status-icon"></div>
  </div>
</div>

        </div>
        <div class="col-4">
        <div class="card text-center">
            <div class="card-header" id="header"><p>KHÍ GAS ĐO ĐƯỢC</p></div>
            <div class="card-body">
            <span style="font-size: 45px;" id="gasValue">0</span>
            <h1>PPM</h1>
            </div>
          </div>
        </div>
        <div class="col-4">
        <div class="card text-center">
            <div class="card-header" id="header"><p>TRẠNG THÁI CẢNH BÁO</p></div>
            <div class="card-body">
            <p class="status" id="alertStatus">SAFE</p>
            <div id="statusAlert" class="status-icon"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="mt-5">
        <div class="row">
          <div class="col-8">
          <canvas id="gasChart"></canvas>
          </div>
          <div class="col-4">
          <div class="card text-center">
  <div class="card-header" id="header">
    <p>BẬT/TẮT CẢNH BÁO</p>
  </div>
  <div class="card-body">
    <label class="switch">
      <input type="checkbox" id="toggleMode">
      <span class="slider round"></span>
    </label>
  </div>
</div>

            <div class="mt-5">
            <div class="card text-center">
            <div class="card-header" id="header"><p>ĐIỀU CHỈNH NGƯỠNG</p></div>
            <div class="card-body">
            <p>MỨC CẢNH BÁO<input type="number" id="threshold" value="30" min="0"></p>
            </div>
          </div>
        </div>
            </div>
        </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Mã Scripts -->
  <script>
    const socket = new WebSocket('ws://192.168.0.105:81'); 

    const ctx = document.getElementById('gasChart').getContext('2d');
    const gasChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: [],
        datasets: [{
          label: 'Lượng khí Gas (ppm)',
          data: [],
          borderColor: 'rgb(255, 99, 132)',
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
        document.getElementById('gasValue').innerText = data.gas;
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
  </script>

</body>
</html>
