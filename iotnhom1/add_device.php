<?php
require 'db.php';

if (isset($_POST['device_name']) && isset($_POST['user_id'])) {
    $device_name = $_POST['device_name'];
    $user_id = $_POST['user_id'];

    // Kiểm tra xem thiết bị đã tồn tại trong bảng device chưa
    $stmt = $conn->prepare("SELECT id FROM device WHERE name = ?");
    $stmt->bind_param("s", $device_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Nếu thiết bị đã tồn tại, lấy id của thiết bị đó
        $device = $result->fetch_assoc();
        $device_id = $device['id'];
    } else {
        // Nếu thiết bị chưa tồn tại, thêm mới vào bảng device
        $stmt = $conn->prepare("INSERT INTO device (name) VALUES (?)");
        $stmt->bind_param("s", $device_name);
        if ($stmt->execute()) {
            $device_id = $stmt->insert_id; // Lấy id của thiết bị mới thêm
        } else {
            echo "Thêm thiết bị vào bảng device thất bại!";
            $stmt->close();
            exit;
        }
    }
    
    // Thêm thiết bị vào bảng device_users
    $stmt2 = $conn->prepare("INSERT INTO device_users (user_id, device_id) VALUES (?, ?)");
    $stmt2->bind_param("ii", $user_id, $device_id);
    if ($stmt2->execute()) {
        echo "Thiết bị đã được thêm thành công!";
    } else {
        echo "Thêm thiết bị vào người dùng thất bại!";
    }

    $stmt2->close();
    $conn->close();
}
?>
