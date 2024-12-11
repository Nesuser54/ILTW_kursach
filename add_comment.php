<?php
include 'db.php';
include 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = $_POST['recipe_id'];
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        $sql = "INSERT INTO comments (recipe_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $recipe_id, $user_id, $content);
        $stmt->execute();
    }

    header("Location: comments.php?recipe_id=" . $recipe_id);
    exit();
}
