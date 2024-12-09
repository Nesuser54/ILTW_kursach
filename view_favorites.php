<?php
include 'db.php';
include 'auth.php';
// session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if (isset($_GET['like']) && isset($_SESSION['user_id'])) {
    $likePostId = intval($_GET['like']);
    $userId = $_SESSION['user_id'];

    // Проверяем, уже ли пользователь лайкнул этот пост
    $checkLikeSql = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
    $checkLikeStmt = $conn->prepare($checkLikeSql);
    $checkLikeStmt->bind_param("ii", $userId, $likePostId);
    $checkLikeStmt->execute();
    $likeResult = $checkLikeStmt->get_result();

    if ($likeResult->num_rows == 0) {
        // Если лайка еще нет, добавляем его
        $insertLikeSql = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
        $insertLikeStmt = $conn->prepare($insertLikeSql);
        $insertLikeStmt->bind_param("ii", $userId, $likePostId);
        $insertLikeStmt->execute();
        $insertLikeStmt->close();
    }

    // Перенаправление после лайка
    header("Location: view_favorites.php")  ;
    exit();
}

// Обработка удаления лайка
if (isset($_GET['unlike']) && isset($_SESSION['user_id'])) {
    $unlikePostId = intval($_GET['unlike']);
    $userId = $_SESSION['user_id'];

    // Удаление лайка
    $deleteLikeSql = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
    $deleteLikeStmt = $conn->prepare($deleteLikeSql);
    $deleteLikeStmt->bind_param("ii", $userId, $unlikePostId);
    $deleteLikeStmt->execute();
    $deleteLikeStmt->close();

    // Перенаправление после удаления лайка
    header("Location: view_favorites.php");
    exit();
}

try {
    $sql = "SELECT posts.*, users.username, 
    (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS likes_count,
    (SELECT COUNT(*) FROM likes WHERE user_id = ? AND post_id = posts.id) AS user_liked,
    (SELECT COUNT(*) FROM likes l INNER JOIN posts p ON l.post_id = p.id 
     WHERE l.user_id IN (SELECT user_id FROM likes WHERE user_id = ?)
     AND p.user_id = posts.user_id) AS user_favorite_count,
    (SELECT COUNT(*) FROM likes l 
     JOIN posts p ON l.post_id = p.id 
     WHERE l.user_id = ? AND p.location = posts.location) AS liked_location_count
FROM posts 
JOIN users ON posts.user_id = users.id
";
    $stmt = $conn->prepare($sql);

    // Параметры для запроса
    $params = [$userId, $userId, $userId];
    $types = "iii"; // типы параметров (int, int, int)

    $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
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
<body>
<style>
    a.add-post-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }

        a.add-post-btn:hover {
            background-color: #0056b3;
        }
        
</style>
<h1>Избранные рецепты</h1>
<a href="view_posts.php" class="add-post-btn">Вернуться на главную страницу</a>
<table>
    <thead>
        <tr>
            <th>Заголовок</th>
            <th>Местоположение</th>
            <th>Содержимое</th>
            <th>Дата создания</th>
            <th>Автор</th>
            <th>Изображения</th>
            <th>Лайки</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($row['content'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                echo "<td>" . htmlspecialchars($row['username']) . "</td>";

                // Получение изображений для текущего поста
                $post_id = $row['id'];
                $image_sql = "SELECT image FROM post_images WHERE post_id = ?";
                $image_stmt = $conn->prepare($image_sql);
                $image_stmt->bind_param("i", $post_id);
                $image_stmt->execute();
                $image_result = $image_stmt->get_result();

                if ($image_result->num_rows > 0) {
                    echo '<td>';
                    while ($image_row = $image_result->fetch_assoc()) {
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($image_row['image']) . '" alt="Изображение">';
                    }
                    echo '</td>';
                } else {
                    echo "<td>Изображения не найдены</td>";
                }

                // Получаем количество комментариев для поста
                $commentsCountQuery = "SELECT COUNT(*) AS comments_count FROM comments WHERE post_id = " . $row['id'];
                $commentsCountResult = mysqli_query($conn, $commentsCountQuery);
                $commentsCount = mysqli_fetch_assoc($commentsCountResult)['comments_count'];

                // Отображаем количество лайков и комментариев для всех пользователей
                echo "<td class='likes-column'>";
                echo "<span class='likes-count'>" . htmlspecialchars($row['likes_count']) . "</span> ";  // Количество лайков

                // Если пользователь не авторизован, показываем статичный значок сердечка
                if (!isset($_SESSION['user_id'])) {
                    echo "<span class='like-button static-like' title='Лайкнуть'>&#9829;</span>"; // Статичное серое сердечко для неавторизованных
                } else {
                    // Если пользователь авторизован, показываем кнопки лайков
                    if ($row['user_liked'] > 0) {
                        echo "<a href='?unlike=" . $row['id'] . "' class='like-button unlike' title='Убрать лайк'>&#10084;</a>"; // Убрать лайк
                    } else {
                        echo "<a href='?like=" . $row['id'] . "' class='like-button like' title='Лайкнуть'>&#9825;</a>"; // Лайкнуть
                    }
                }

                // Секция с комментариями
                echo "<div class='comment-btn-container'>";
                echo "<span class='comment-count'>" . $commentsCount . "</span> ";  // Счетчик комментариев с пробелом
                echo "<a href='comments.php?post_id=" . $row['id'] . "' class='comment-btn' title='Комментарии'>💬</a>";
                echo "</div>";
                echo "<a href='view_likes.php?post_id=" . $row['id'] . "' class='view-likes-btn' title='Посмотреть, кто лайкнул'>👥</a>";
                echo "</td>";
            }
        } else {
            echo "<tr><td colspan='8'>Нет рецептов, соответствующих вашему запросу.</td></tr>";
        }

        $stmt->close();
        $conn->close();
        ?>
    </tbody>
</table>

<!-- Модальное окно -->
<div id="myModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="img01">
    <div id="caption"></div>
</div>

<script>
// Получаем модальное окно
var modal = document.getElementById("myModal");

// Получаем изображение, которое нужно открыть в модальном окне
var img = document.querySelectorAll("img"); // выбираем все изображения

// Получаем элемент <span>, который закрывает модальное окно
var span = document.getElementsByClassName("close")[0];

// Для каждого изображения добавляем обработчик клика
img.forEach(function(image) {
    image.onclick = function() {
        modal.style.display = "block"; // Показываем модальное окно
        var modalImg = document.getElementById("img01");
        var captionText = document.getElementById("caption");
        modalImg.src = this.src; // Устанавливаем src изображения в модальном окне
        captionText.innerHTML = this.alt; // Устанавливаем alt как описание
    };
});

// Когда пользователь нажимает на <span> (кнопка закрытия), скрыть модальное окно
span.onclick = function() {
    modal.style.display = "none";
};
</script>

</body>
</html>
