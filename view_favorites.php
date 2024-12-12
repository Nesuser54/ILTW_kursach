<?php
include 'db.php';
include 'auth.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if (isset($_GET['like']) && isset($_SESSION['user_id'])) {
    $likerecipeId = intval($_GET['like']);
    $userId = $_SESSION['user_id'];

    $checkLikeSql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";
    $checkLikeStmt = $conn->prepare($checkLikeSql);
    $checkLikeStmt->bind_param("ii", $userId, $likerecipeId);
    $checkLikeStmt->execute();
    $likeResult = $checkLikeStmt->get_result();

    if ($likeResult->num_rows == 0) {
        $insertLikeSql = "INSERT INTO likes (user_id, recipe_id) VALUES (?, ?)";
        $insertLikeStmt = $conn->prepare($insertLikeSql);
        $insertLikeStmt->bind_param("ii", $userId, $likerecipeId);
        $insertLikeStmt->execute();
        $insertLikeStmt->close();
    }

    header("Location: view_favorites.php");
    exit();
}

if (isset($_GET['unlike']) && isset($_SESSION['user_id'])) {
    $unlikerecipeId = intval($_GET['unlike']);
    $userId = $_SESSION['user_id'];

    $deleteLikeSql = "DELETE FROM likes WHERE user_id = ? AND recipe_id = ?";
    $deleteLikeStmt = $conn->prepare($deleteLikeSql);
    $deleteLikeStmt->bind_param("ii", $userId, $unlikerecipeId);
    $deleteLikeStmt->execute();
    $deleteLikeStmt->close();

    header("Location: view_favorites.php");
    exit();
}

try {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $sql = "SELECT recipes.*, users.username, recipe_type.name AS recipe_type_name, 
                (SELECT COUNT(*) FROM likes WHERE recipe_id = recipes.id) AS likes_count,
                (SELECT COUNT(*) FROM likes WHERE user_id = ? AND recipe_id = recipes.id) AS user_liked
            FROM recipes 
            JOIN users ON recipes.user_id = users.id 
            LEFT JOIN recipe_type on recipes.recipe_type_id = recipe_type.id 
            LEFT JOIN likes ON recipes.id = likes.recipe_id 
            WHERE likes.user_id = ? 
            ORDER BY created_at DESC";



    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $userId);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        echo "<tr class='empty-table'><td colspan='8'></td></tr>";
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Ошибка: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Избранные рецепты</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
    table.empty-table td {
        background-color: #fff;
        color: #333;
        text-align: center;
        font-size: 1.2rem;
        padding: 20px;
    }
</style>

<body>
    <h1>Избранные рецепты</h1>
    <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>
    <table>
        <thead>
            <tr>
                <th>Название</th>
                <th>Вид блюда</th>
                <th>Рецепт</th>
                <th>Дата</th>
                <th>Автор</th>
                <th>Изображения</th>
                <th>&#9829; и 💬</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<tr>';
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['recipe_type_name']) . "</td>";
                    echo "<td>" . nl2br(htmlspecialchars($row['recipe_text'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";

                    $recipe_id = $row['id'];
                    $image_sql = "SELECT image FROM recipe_images WHERE recipe_id = ?";
                    $image_stmt = $conn->prepare($image_sql);
                    $image_stmt->bind_param("i", $recipe_id);
                    $image_stmt->execute();
                    $image_result = $image_stmt->get_result();

                    if ($image_result->num_rows > 0) {
                        echo '<td>';
                        while ($image_row = $image_result->fetch_assoc()) {
                            echo '<img src="data:image/jpeg;base64,' . base64_encode($image_row['image']) . '" alt="Изображение" class="recipe-image">';
                        }
                        echo '</td>';
                    } else {
                        echo "<td>Изображения не найдены</td>";
                    }

                    $commentsCountQuery = "SELECT COUNT(*) AS comments_count FROM comments WHERE recipe_id = " . $row['id'];
                    $commentsCountResult = mysqli_query($conn, $commentsCountQuery);
                    $commentsCount = mysqli_fetch_assoc($commentsCountResult)['comments_count'];

                    echo "<td class='likes-column'>";

                    echo "<span class='likes-count'>" . htmlspecialchars($row['likes_count']) . "</span> ";

                    if (!isset($_SESSION['user_id'])) {
                        echo "<span class='like-button static-like' title='Недоступно'>&#9829;</span>";
                    } else {
                        if ($row['user_liked'] > 0) {
                            echo "<a href='?unlike=" . $row['id'] . "' class='like-button unlike' title='Убрать из избранного'>&#10084;</a>";
                        } else {
                            echo "<a href='?like=" . $row['id'] . "' class='like-button like' title='Добавить в избранное'>&#9825;</a>";
                        }
                    }

                    echo "<div class='comment-btn-container'>";
                    echo "<span class='comment-count'>" . $commentsCount . "</span> ";
                    echo "<a href='comments.php?recipe_id=" . $row['id'] . "' class='comment-btn' title='Комментарии'>💬</a>";
                    echo "</div>";
                    echo "<a href='view_likes.php?recipe_id=" . $row['id'] . "' class='view-likes-btn' title='Посмотреть, кто добавил в избранное'>👥</a>";

                    echo "</td>";

                    echo '</tr>';
                }
            } else {
                echo "<tr><td colspan='8'>Вы не добавили ни один рецепт в избранные.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>

</html>