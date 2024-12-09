<?php
include 'db.php';
include 'auth.php';

$comment_id = $_GET['id'] ?? null;
$recipe_id = $_GET['recipe_id'] ?? null;

if ($comment_id && $recipe_id) {
    // Проверяем права
    $sql = "SELECT * FROM comments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();

    if ($comment && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $comment['recipe_id'])) {
        $delete_sql = "DELETE FROM comments WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $comment_id);
        $delete_stmt->execute();
    }
}

header("Location: comments.php?recipe_id=" . $recipe_id);
exit();
?>
