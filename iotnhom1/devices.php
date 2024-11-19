<?php
session_start();

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kết nối đến cơ sở dữ liệu
require 'db.php';

$user_id = $_SESSION['user_id'];

// Truy vấn danh sách thiết bị theo `user_id`
$stmt = $conn->prepare("SELECT d.id, d.name FROM device d INNER JOIN device_users du ON d.id = du.device_id WHERE du.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$device_count = $result->num_rows; // Kiểm tra số lượng thiết bị
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thiết bị</title>
    <style>
        /* Các style như trước */
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

        .device-list {
            margin: 20px 0;
        }

        .device-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border: 1px solid #b2dfdb;
            border-radius: 5px;
            margin-bottom: 10px;
            transition: all 0.3s ease-in-out;
        }

        .device-item:hover {
            background-color: #e0f2f1;
        }

        .device-name {
            font-size: 16px;
            color: #00796b;
        }

        .select-btn, .delete-btn {
            padding: 5px 10px;
            background: #00796b;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }

        .select-btn:hover, .delete-btn:hover {
            background: #004d40;
        }

        .delete-btn {
            background: #d32f2f;
        }

        footer {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            color: #757575;
        }

        .no-device {
            color: #d32f2f;
            font-size: 16px;
            margin-top: 20px;
        }

        .add-device-btn {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .add-device-btn:hover {
            background-color: #004d40;
        }

        /* Dialog Styles */
        .dialog-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .dialog-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .dialog-buttons {
            margin-top: 10px;
        }

        .dialog-btn {
            padding: 10px 20px;
            background: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .dialog-btn.cancel {
            background: #d32f2f;
        }

        .dialog-btn:hover {
            background: #004d40;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>DANH SÁCH THIẾT BỊ</h2>
            <div class="device-list">
                <?php if ($device_count > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="device-item" data-device-id="<?php echo $row['id']; ?>">
                            <span class="device-name"><?php echo htmlspecialchars($row['name']); ?></span>
                            <button class="delete-btn" onclick="confirmDelete(<?php echo $row['id']; ?>)">Xóa</button>
                            <button class="select-btn">Chọn</button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-device">Không có thiết bị nào.</p>
                <?php endif; ?>
            </div>
            <button class="add-device-btn" onclick="openAddDeviceDialog()">Thêm mới thiết bị</button>
        </div>
    </div>

    <!-- Dialog Xóa Thiết Bị -->
    <div class="dialog-overlay" id="delete-dialog">
        <div class="dialog-box">
            <p>Bạn có chắc chắn muốn xóa thiết bị này?</p>
            <div class="dialog-buttons">
                <button class="dialog-btn" id="confirm-delete">OK</button>
                <button class="dialog-btn cancel" onclick="closeDialog('delete-dialog')">Hủy</button>
            </div>
        </div>
    </div>

    <!-- Dialog Thêm Thiết Bị -->
    <div class="dialog-overlay" id="add-device-dialog">
        <div class="dialog-box">
            <h3>Thêm thiết bị mới</h3>
            <form id="add-device-form">
                <label for="device-name">Tên thiết bị:</label>
                <input type="text" id="device-name" name="device-name" required>
                <input type="hidden" id="user-id" value="<?php echo $_SESSION['user_id']; ?>">
                <div class="dialog-buttons">
                    <button type="submit" class="dialog-btn">Lưu</button>
                    <button type="button" class="dialog-btn cancel" onclick="closeDialog('add-device-dialog')">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <!-- <footer>© 2024 Nhóm Phát triển</footer> -->

    <script>
        // Hàm hiển thị dialog xác nhận xóa
        function confirmDelete(deviceId) {
            const deleteDialog = document.getElementById('delete-dialog');
            const confirmBtn = document.getElementById('confirm-delete');

            deleteDialog.style.display = 'flex';

            confirmBtn.onclick = function() {
                // Gửi yêu cầu xóa thiết bị qua AJAX
                fetch('delete_device.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `delete_device=true&device_id=${deviceId}`
                })
                .then(response => response.text())
                .then(data => {
                    alert(data); // Hiển thị thông báo xóa
                    closeDialog('delete-dialog');
                    location.reload(); // Tải lại trang để cập nhật danh sách thiết bị
                });
            };
        }

        // Hàm mở dialog thêm thiết bị mới
        function openAddDeviceDialog() {
            const addDeviceDialog = document.getElementById('add-device-dialog');
            addDeviceDialog.style.display = 'flex';
        }

        // Hàm đóng dialog
        function closeDialog(dialogId) {
            const dialog = document.getElementById(dialogId);
            dialog.style.display = 'none';
        }

        // Thêm thiết bị mới
        document.getElementById('add-device-form').onsubmit = function(event) {
            event.preventDefault();

            const deviceName = document.getElementById('device-name').value;
            const userId = document.getElementById('user-id').value;

            // Gửi dữ liệu thiết bị mới qua AJAX
            fetch('add_device.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `device_name=${deviceName}&user_id=${userId}`
            })
            .then(response => response.text())
            .then(data => {
                alert(data); // Hiển thị thông báo thêm thiết bị thành công
                closeDialog('add-device-dialog');
                location.reload(); // Tải lại trang để cập nhật danh sách thiết bị
            });
        };
    </script>
     <script>
        // Lấy tất cả các nút "Chọn"
        const selectButtons = document.querySelectorAll('.select-btn');

        // Gán sự kiện click cho mỗi nút
        selectButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Lấy id của thiết bị từ thuộc tính data-device-id
                const deviceId = this.closest('.device-item').getAttribute('data-device-id');
                
                // Chuyển hướng sang trang khác với deviceId (ví dụ: chuyển đến trang chi tiết thiết bị)
                window.location.href = `home.php?id=${deviceId}`;
            });
        });
    </script>
</body>
</html>
