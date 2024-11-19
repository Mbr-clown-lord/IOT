<?php

// Kết nối với cơ sở dữ liệu
include('db.php');

// Xử lý khi có yêu cầu từ WebSocket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['chedo'])) {
        $mode = $_POST['chedo'];
        // Lưu lịch sử chế độ vào cơ sở dữ liệu
        $sql = "INSERT INTO system_history (mode, timestamp) VALUES ('$mode', NOW())";
        if ($conn->query($sql) === TRUE) {
            echo "Lưu chế độ thành công!";
        } else {
            echo "Lỗi: " . $conn->error;
        }
    }
    if (isset($_POST['mucCanhbao'])) {
        $threshold = $_POST['mucCanhbao'];
        // Lưu mức cảnh báo vào cơ sở dữ liệu
        $sql = "INSERT INTO warning_history (threshold, timestamp) VALUES ('$threshold', NOW())";
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
  <link rel="stylesheet" href="./style..css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <title>Hệ thống phát hiện rò rỉ khí gas</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav
      class="navbar fixed-top navbar-expand-lg navbar-light"
      style="background-color:#51d4cb"
      data-aos="fade-down"
    >
      <div class="container">
        <a class="navbar-brand logo-text rounded-circle " href="./index.html"><img src="./asset/images/logo-7.png" alt="" width="140px" height="52px"></a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-controls="navbarNav"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="./index.html">Trang chủ</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./about.html">Giới thiệu</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./gallery.html">Thư viện ảnh</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./booking.html">Bảng giá</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./blog-card.html">Câu chuyện</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="./contract.html">Liên hệ</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  <h1>Hệ thống Cảnh báo Gas</h1>
  <p>Gas hiện tại: <span id="gasValue">0</span> ppm</p>
  <p>Mức cảnh báo: <input type="number" id="threshold" value="30" min="0"></p>
  <button id="toggleMode">Bật/Tắt Chế Độ Cảnh Báo</button>
  <p class="status" id="modeStatus">Chế độ: Tắt</p>
  <p class="status" id="alertStatus">Trạng thái: An toàn</p>

  <canvas id="gasChart"></canvas>

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
        modeStatus.innerText = data.status === 1 ? 'Chế độ: Bật' : 'Chế độ: Tắt';
    }
    if (data.canhbao !== undefined) {
        const alertStatus = document.getElementById('alertStatus');
        alertStatus.innerText = data.canhbao === 1 ? 'Trạng thái: Rò rỉ' : 'Trạng thái: An toàn';
    }
};


    document.getElementById('toggleMode').onclick = function() {
      const mode = document.getElementById('modeStatus').innerText.includes('Bật') ? 0 : 1;
      if (socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({ chedo: mode }));
        // Gửi dữ liệu chế độ vào PHP để lưu vào cơ sở dữ liệu
        const formData = new FormData();
        formData.append('chedo', mode);
        fetch('index.php', { method: 'POST', body: formData });
      } else {
        console.log("WebSocket chưa kết nối!");
      }
    };

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
