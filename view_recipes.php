<?php
include 'db.php';
include 'auth.php';


$message = '';

if (isset($_SESSION['user_id'])) {
    $username = $_SESSION['username'];
    $userRole = $_SESSION['role'] ?? 'user';

    $userId = $_SESSION['user_id'];
    $notificationSql = "SELECT message FROM notifications WHERE user_id = ? GROUP BY message ORDER BY MAX(created_at) DESC";
    $notificationStmt = $conn->prepare($notificationSql);

    if (!$notificationStmt) {
        die('Ошибка подготовки запроса: ' . $conn->error);
    }

    $notificationStmt->bind_param("i", $userId);

    if (!$notificationStmt->execute()) {
        die('Ошибка выполнения запроса: ' . $notificationStmt->error);
    }

    $notificationResult = $notificationStmt->get_result();
    $notifications = [];

    if ($notificationResult->num_rows > 0) {
        while ($notificationRow = $notificationResult->fetch_assoc()) {
            $notifications[] = htmlspecialchars($notificationRow['message']);
            $deleteNotificationSql = "DELETE FROM notifications WHERE user_id = ? AND message = ?";
            $deleteNotificationStmt = $conn->prepare($deleteNotificationSql);

            if (!$deleteNotificationStmt) {
                die('Ошибка подготовки запроса на удаление: ' . $conn->error);
            }

            $deleteNotificationStmt->bind_param("is", $userId, $notificationRow['message']);

            if (!$deleteNotificationStmt->execute()) {
                die('Ошибка выполнения запроса на удаление: ' . $deleteNotificationStmt->error);
            }

            $deleteNotificationStmt->close();
        }
    }
}

if (isset($_POST['request_publisher']) && $userRole === 'user') {
    $userId = $_SESSION['user_id'];

    $checkRequestSql = "SELECT * FROM role_requests WHERE user_id = ? AND status = 'pending'";
    $checkRequestStmt = $conn->prepare($checkRequestSql);
    $checkRequestStmt->bind_param("i", $userId);
    $checkRequestStmt->execute();
    $requestResult = $checkRequestStmt->get_result();

    if ($requestResult->num_rows > 0) {
        echo "<script>alert('Ваш запрос уже отправлен. Дождитесь ответа.');</script>";
    } else {
        $requestSql = "INSERT INTO role_requests (user_id, status) VALUES (?, 'pending')";
        $requestStmt = $conn->prepare($requestSql);
        $requestStmt->bind_param("i", $userId);
        $requestStmt->execute();
        $requestStmt->close();
        echo "<script>alert('Запрос админу отправлен.');</script>";
    }

    $checkRequestStmt->close();
}

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    $recipesSql = "SELECT user_id FROM recipes WHERE id = ?";
    $recipesStmt = $conn->prepare($recipesSql);
    $recipesStmt->bind_param("i", $deleteId);
    $recipesStmt->execute();
    $recipeResult = $recipesStmt->get_result();

    if ($recipeResult->num_rows > 0) {
        $recipeRow = $recipeResult->fetch_assoc();

        if (isset($_SESSION['user_id'])) {
            if ($recipeRow['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {

                $deleteCommentsSql = "DELETE FROM comments WHERE recipe_id = ?";
                $deleteCommentsStmt = $conn->prepare($deleteCommentsSql);
                $deleteCommentsStmt->bind_param("i", $deleteId);
                $deleteCommentsStmt->execute();
                $deleteCommentsStmt->close();

                $deleteSql = "DELETE FROM recipes WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $deleteId);
                $deleteStmt->execute();
                $deleteStmt->close();

                header("Location: view_recipes.php?message=deleted");
                exit();
            } else {
                header("Location: view_recipes.php?message=access_denied");
                exit();
            }
        }
    } else {
        header("Location: view_recipes.php?message=recipe_not_found");
        exit();
    }
}

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

    header("Location: view_recipes.php");
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

    header("Location: view_recipes.php");
    exit();
}

$sql = "SELECT weight_likes, weight_recipe_type, weight_author FROM ranking_weights WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $weight_author = $row['weight_author'];
    $weight_recipe_type = $row['weight_recipe_type'];
    $weight_likes = $row['weight_likes'];
} else {
    $weight_author = 0.5;
    $weight_recipe_type = 0.3;
    $weight_likes = 0.2;
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $message = isset($_GET['message']) ? $_GET['message'] : '';

    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $sql = "SELECT recipes.*, users.username, recipe_type.name AS recipe_type_name, 
            (SELECT COUNT(*) FROM likes WHERE recipe_id = recipes.id) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ? AND recipe_id = recipes.id) AS user_liked,
            (SELECT COUNT(*) FROM likes l INNER JOIN recipes p ON l.recipe_id = p.id 
             WHERE l.user_id IN (SELECT user_id FROM likes WHERE user_id = ?)
             AND p.user_id = recipes.user_id) AS user_favorite_count,
            (SELECT COUNT(*) FROM likes l 
             JOIN recipes p ON l.recipe_id = p.id 
             WHERE l.user_id = ? AND p.recipe_type_id = recipes.recipe_type_id) AS liked_recipe_type_count
        FROM recipes 
        JOIN users ON recipes.user_id = users.id 
        JOIN recipe_type ON recipes.recipe_type_id = recipe_type.id
        WHERE (recipes.title LIKE ? OR recipes.recipe_text LIKE ? OR recipe_type.name LIKE ? OR users.username LIKE ?)
";

    if ($date) {
        $sql .= " AND DATE(created_at) = DATE(?)";
    }

    $sql .= "ORDER BY 
        (user_favorite_count * ?) +
        (liked_recipe_type_count * ?) +
        (likes_count * ?) DESC, created_At DESC";

    $stmt = $conn->prepare($sql);

    $searchTerm = "%" . $search . "%";
    $params = [$userId, $userId, $userId, $searchTerm, $searchTerm, $searchTerm, $searchTerm];



    if ($date) {
        $params[] = $date;
    }

    $params[] = $weight_author;
    $params[] = $weight_recipe_type;
    $params[] = $weight_likes;

    $types = "iiisssssddd";

    if (!$date) {
        $types = "iiissssddd";
    }

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
    <title>Мамины рецепты</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


</head>
<style>
    table td {
        text-align: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th,
    table td {
        padding: 15px;
        text-align: center;
        border-bottom: 1px solid #ddd;
    }

    input[type="submit"] {
        margin-bottom: 0px;
    }

    th:nth-child(2),
    td:nth-child(3) {
        text-align: left;
    }

    th:nth-child(2),
    td:nth-child(7) {
        min-width: 42px;
    }

    .message {
        color: #d32f2f;
        padding: 3px;
        margin-bottom: 20px;
        text-align: left;
        margin-left: 5px;
        margin-right: 1000px;
    }
</style>

<body>
    <h1>Мамины рецепты</h1>
    <?php if (isset($_SESSION['user_id'])): ?>
        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Аватар" class="user-avatar">
        <div class="header">
            <h2>
                <?php echo  "Добро пожаловать, " . htmlspecialchars($username) . "!"; ?>
            </h2>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message">
            <?php if ($message === 'success'): ?>
                <p style="color: green;">Рецепт успешно опубликован!</p>
            <?php elseif ($message === 'error'): ?>
                <p style="color: red;">Произошла ошибка при создании публикации.</p>
            <?php elseif ($message === 'deleted'): ?>
                <p style="color: green;">Рецепт успешно удален!</p>
            <?php elseif ($message === 'access_denied'): ?>
                <p style="color: red;">У вас нет прав для удаления этого рецепта.</p>
            <?php elseif ($message === 'recipe_not_found'): ?>
                <p style="color: red;">Рецепт не найден.</p>
            <?php elseif ($message === 'login'): ?>
                <p style="color: green;">Авторизация прошла успешно</p>
            <?php elseif ($message === 'logout'): ?>
                <p style="color: green;">Выход из аккаунта произошел успешно</p>
            <?php elseif ($message === 'role_requested'): ?>
                <p style="color: green;">Запрос на изменение роли отправлен!</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($notifications)): ?>
        <div class="notifications">
            <?php foreach ($notifications as $notification): ?>
                <script>
                    alert('<?php echo $notification; ?>');
                </script>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>

        <?php if ($userRole === 'publisher' || $userRole === 'admin'): ?>
            <a href="add_recipe.php" class="add-recipe-btn">Добавить рецепт</a>
        <?php endif; ?>
        <?php if ($userRole === 'user'): ?>
            <a href="#" class="add-recipe-btn" id="requestpublisherRole">Запросить роль путешественника</a>
        <?php endif; ?>
    <?php else: ?>
        <a href="login.php" class="add-recipe-btn">Войти</a>
        <a href="register.php" class="add-recipe-btn">Регистрация</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id']) && $userRole === 'admin'): ?>
        <a href="view_role_requests.php" class="add-recipe-btn">Посмотреть заявки на роль</a>
        <a href="add_recipe_type.php" class="add-recipe-btn">Управление видами блюд</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="view_favorites.php" class="add-recipe-btn">Посмотреть избранные рецепты</a>
    <?php endif; ?>
    <?php

    if (isset($_SESSION['user_id'])): ?>
        <a href="setting.php" class="add-recipe-btn">Настройки</a>
        <a href="logout.php" class="add-recipe-btn">Выход</a>
    <?php endif; ?>

    <form action="view_recipes.php" method="GET" class="search">
        <input type="text" name="search"
            placeholder="Поиск рецептов"
            value="<?php echo htmlspecialchars($search); ?>">
        Поиск по дате: <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
        <input type="submit" value="Поиск">
    </form>

    <table>
        <thead>
            <tr>
                <th>Название</th>
                <th>Вид блюда</th>
                <th>Текст рецепта</th>
                <th>Дата</th>
                <th>Автор</th>
                <th>Внешний вид</th>
                <th>&#9829; и 💬</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {

                    $recipe_text = htmlspecialchars($row['recipe_text']);

                    $recipe_text = str_replace("Ингредиенты", "<strong>Ингредиенты</strong>", $recipe_text);
                    $recipe_text = str_replace("Приготовление", "<strong>Приготовление</strong>", $recipe_text);

                    echo '<tr>';
                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['recipe_type_name']) . "</td>";

                    echo "<td>" . nl2br($recipe_text) . "</td>";

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
                        echo "<span class='like-button static-like' title='Лайкнуть'>&#9829;</span>";
                    } else {

                        if ($row['user_liked'] > 0) {
                            echo "<a href='?unlike=" . $row['id'] . "' class='like-button unlike' title='Убрать лайк'>&#10084;</a>";
                        } else {
                            echo "<a href='?like=" . $row['id'] . "' class='like-button like' title='Лайкнуть'>&#9825;</a>";
                        }
                    }

                    echo "<div class='comment-btn-container'>";
                    echo "<span class='comment-count'>" . $commentsCount . "</span> ";
                    echo "<a href='comments.php?recipe_id=" . $row['id'] . "' 
                        class='comment-btn'
                        title='Комментарии'> 
                        <i class='fa fa-comments'></i></a>";
                    echo "</div>";
                    echo "<a href='view_likes.php?recipe_id=" . $row['id'] . "' class='view-likes-btn' title='Посмотреть, кто лайкнул'>👥</a>";

                    echo "</td>";

                    if (isset($_SESSION['user_id'])) {
                        if ($row['user_id'] == $_SESSION['user_id'] || $userRole === 'admin') {
                            echo "<td>
                                        <a href='edit_recipe.php?id=" . $row['id'] . "' 
                                        class='edit-btn' title='Редактировать'>
                                        <i class='fa fa-cogs'></i> <!-- Значок гаечного ключа -->
                                        </a>
                                        <a href='?delete=" . $row['id'] . "' 
                                        onclick=\"return confirm('Вы уверены, что хотите удалить?');\" 
                                        class='delete-btn'
                                        title='Удалить'>
                                        <i class='fa fa-trash'></i> <!-- Значок мусорки -->
                                        </a>
                                    </td>";
                        } else {
                            echo "<td>Нет доступа</td>";
                        }
                    } else {
                        echo "<td>Нет доступа</td>";
                    }
                }
            } else {
                echo "<tr><td colspan='8'>Нет рецептов, соответствующих вашему запросу.</td></tr>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </tbody>
    </table>

    <script>
        document.getElementById('requestpublisherRole').addEventListener('click', function(event) {
            event.preventDefault();

            var form = document.createElement('form');
            form.method = 'POST';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'request_publisher';
            input.value = '1';

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
    </script>

    <div id="myModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="img01">
        <div id="caption"></div>
    </div>

    <script>
        var modal = document.getElementById("myModal");

        var img = document.querySelectorAll("img");


        var span = document.getElementsByClassName("close")[0];


        img.forEach(function(image) {
            image.onclick = function() {
                modal.style.display = "block";
                var modalImg = document.getElementById("img01");
                var captionText = document.getElementById("caption");
                modalImg.src = this.src;
                captionText.innerHTML = this.alt;
            };
        });


        span.onclick = function() {
            modal.style.display = "none";
        };

        window.onload = function() {
            modal.style.display = "none";
        };
    </script>

</body>

</html>