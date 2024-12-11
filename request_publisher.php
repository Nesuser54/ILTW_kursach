<?php
include 'db.php';
session_start();

if ($_SESSION['role'] === 'user') {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM role_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO role_requests (user_id) VALUES (?)");
        $insert->bind_param("i", $userId);
        $insert->execute();
        header('Location: index.php?message=request_sent');
    } else {
        header('Location: index.php?message=request_pending');
    }
} else {
    header('Location: index.php');
}
