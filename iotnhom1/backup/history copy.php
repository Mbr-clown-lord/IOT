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

if ($start_date_alert && $end_date_alert) {
    $query_alert = "SELECT * FROM gas_alert_history WHERE alert_time BETWEEN '$start_date_alert' AND '$end_date_alert'";
    $alert_result = $conn->query($query_alert);
    while ($row = $alert_result->fetch_assoc()) {
        $results['alert'][] = $row;
    }
}

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
  <link rel="stylesheet" href="index.php" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <title>Hệ thống phát hiện rò rỉ khí gas</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            text-align: center;
        }

        h2 {
            margin-bottom: 30px;
        }

        .card {
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
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
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
            background-color: #f2f2f2;
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
      <h2 style="color:#ffff">NHÓM 1</h2>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <h2 style="color: #ffff;">Chào mừng, <?php echo htmlspecialchars($fullname); ?>!</h2>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="content">
    <!-- Giao diện xem lịch sử phát hiện rò rỉ khí gas -->
    <div class="container">
      <h2>Xem Lịch Sử Phát Hiện Rò Rỉ Khí Gas</h2>

      <!-- Card: Lịch sử phát hiện rò rỉ khí gas -->
      <div class="card">
          <h3>Lịch sử phát hiện rò rỉ khí gas</h3>
          <label for="start_date_alert">Ngày bắt đầu:</label>
          <input type="date" id="start_date_alert" name="start_date_alert">
          <label for="end_date_alert">Ngày kết thúc:</label>
          <input type="date" id="end_date_alert" name="end_date_alert">
      </div>

      <!-- Nút tìm kiếm -->
      <button id="search_button" onclick="searchHistory()">Tìm kiếm</button>

      <!-- Bảng hiển thị kết quả -->
      <table id="result_table">
          <thead>
              <tr>
                  <th>ID</th>
                  <th>Ngưỡng Gas</th>
                  <th>Thời gian</th>
              </tr>
          </thead>
          <tbody>
              <!-- Kết quả truy vấn sẽ hiển thị ở đây -->
          </tbody>
      </table>
    </div>
  </div>
  <script>
        function searchHistory() {
            // Lấy giá trị từ các ô chọn ngày
            const start_date_alert = document.getElementById('start_date_alert').value;
            const end_date_alert = document.getElementById('end_date_alert').value;

            // Gửi yêu cầu AJAX đến server
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);  // Gọi chính trang PHP
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const results = JSON.parse(xhr.responseText);

                    // Hiển thị kết quả trong bảng
                    const resultTable = document.getElementById('result_table').getElementsByTagName('tbody')[0];
                    resultTable.innerHTML = '';  // Clear previous results

                    // Hiển thị kết quả từ các bảng
                    displayResults(results.alert, resultTable);
                }
            };

            xhr.send(`start_date_alert=${start_date_alert}&end_date_alert=${end_date_alert}`);
        }

        function displayResults(data, tableBody) {
            data.forEach(row => {
                const tr = document.createElement('tr');
                for (let key in row) {
                    const td = document.createElement('td');
                    td.appendChild(document.createTextNode(row[key]));
                    tr.appendChild(td);
                }
                tableBody.appendChild(tr);
            });
        }
    </script>
</body>
</html>
