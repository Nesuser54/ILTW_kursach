<?php
include 'db.php';
session_start();

function getUserData($conn, $userId)
{
    $userSql = "SELECT avatar, username, role FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    return $userStmt->get_result();
}

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userResult = getUserData($conn, $userId);

    if ($userResult->num_rows > 0) {
        $userRow = $userResult->fetch_assoc();
        $avatar = $userRow['avatar'] ? $userRow['avatar'] : 'uploads/avatar.png';
        $_SESSION['username'] = $userRow['username'];
        $_SESSION['role'] = $userRow['role'] ?? 'user';
    }
} elseif (isset($_COOKIE['auth_token'])) {

    $authToken = $_COOKIE['auth_token'];
    $tokenSql = "SELECT id FROM users WHERE auth_token = ?";
    $tokenStmt = $conn->prepare($tokenSql);
    $tokenStmt->bind_param("s", $authToken);
    $tokenStmt->execute();
    $tokenResult = $tokenStmt->get_result();

    if ($tokenResult->num_rows > 0) {
        $userRow = $tokenResult->fetch_assoc();
        $_SESSION['user_id'] = $userRow['id'];

        $userId = $userRow['id'];
        $userResult = getUserData($conn, $userId);

        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $avatar = $userRow['avatar'] ? $userRow['avatar'] : 'uploads/avatar.png';
            $_SESSION['username'] = $userRow['username'];
            $_SESSION['role'] = $userRow['role'] ?? 'user';
        }
    } else {

        setcookie('auth_token', '', time() - 3600, "/");
        header("Location: login.php");
        exit();
    }
}
