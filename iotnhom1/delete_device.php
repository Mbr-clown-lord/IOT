<?php
require 'db.php';

if (isset($_POST['delete_device']) && isset($_POST['device_id'])) {
    $device_id = $_POST['device_id'];

    // Xóa thiết bị khỏi bảng device_users
    $stmt = $conn->prepare("DELETE FROM device_users WHERE device_id = ?");
    $stmt->bind_param("i", $device_id);
    if ($stmt->execute()) {
        echo "Thiết bị đã được xóa thành công!";
    } else {
        echo "Xóa thiết bị thất bại!";
    }

    $stmt->close();
}

$conn->close();
?>
