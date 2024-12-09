<?php
include 'db.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        $sql = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $post_id, $user_id, $content);
        $stmt->execute();
    }

    header("Location: comments.php?post_id=" . $post_id);
    exit();
}
?>
