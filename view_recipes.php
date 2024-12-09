<?php
include 'db.php';
include 'auth.php';
// session_start();

// $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

$message = ''; 


$selectedLanguage = $_COOKIE['language'] ?? 'ru';



if (isset($_SESSION['user_id'])) {
    $username = $_SESSION['username'];
    $userRole = $_SESSION['role'] ?? 'user';

    // Получение уникальных уведомлений
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

    // Если есть уведомления
    if ($notificationResult->num_rows > 0) {
        while ($notificationRow = $notificationResult->fetch_assoc()) {
            $notifications[] = htmlspecialchars($notificationRow['message']);
            // Удаляем уведомление из базы данных
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

    // Проверка на наличие активной заявки
    $checkRequestSql = "SELECT * FROM role_requests WHERE user_id = ? AND status = 'pending'";
    $checkRequestStmt = $conn->prepare($checkRequestSql);
    $checkRequestStmt->bind_param("i", $userId);
    $checkRequestStmt->execute();
    $requestResult = $checkRequestStmt->get_result();

    if ($requestResult->num_rows > 0) {
        // Если заявка уже существует
        echo "<script>alert('Ваш запрос уже отправлен. Дождитесь ответа.');</script>";
    } else {
        // Если заявки нет, отправляем новую
        $requestSql = "INSERT INTO role_requests (user_id, status) VALUES (?, 'pending')";
        $requestStmt = $conn->prepare($requestSql);
        $requestStmt->bind_param("i", $userId);
        $requestStmt->execute();
        $requestStmt->close();
        echo "<script>alert('Запрос админу отправлен.');</script>";
    }

    $checkRequestStmt->close();
}


// Обработка удаления записи
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    // Получаем user_id поста
    $recipesSql = "SELECT user_id FROM recipes WHERE id = ?";
    $recipesStmt = $conn->prepare($recipesSql);
    $recipesStmt->bind_param("i", $deleteId);
    $recipesStmt->execute();
    $recipeResult = $recipesStmt->get_result();

    if ($recipeResult->num_rows > 0) {
        $recipeRow = $recipeResult->fetch_assoc();

        // Проверяем, является ли пользователь администратором или владельцем поста
        if (isset($_SESSION['user_id'])) {
            if ($recipeRow['user_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
                // Если совпадает, удаляем пост
                $deleteSql = "DELETE FROM recipes WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $deleteId);
                $deleteStmt->execute();
                $deleteStmt->close();

                // Перенаправление после удаления
                header("Location: view_recipes.php?message=deleted");
                exit();
            } else {
                // Если не совпадает, выдаем ошибку прав доступа
                header("Location: view_recipes.php?message=access_denied");
                exit();
            }
        }
    } else {
        // Если пост не найден
        header("Location: view_recipes.php?message=recipe_not_found");
        exit();
    }
}

// Обработка лайка
if (isset($_GET['like']) && isset($_SESSION['user_id'])) {
    $likerecipeId = intval($_GET['like']);
    $userId = $_SESSION['user_id'];

    // Проверяем, уже ли пользователь лайкнул этот пост
    $checkLikeSql = "SELECT * FROM likes WHERE user_id = ? AND recipe_id = ?";
    $checkLikeStmt = $conn->prepare($checkLikeSql);
    $checkLikeStmt->bind_param("ii", $userId, $likerecipeId);
    $checkLikeStmt->execute();
    $likeResult = $checkLikeStmt->get_result();

    if ($likeResult->num_rows == 0) {
        // Если лайка еще нет, добавляем его
        $insertLikeSql = "INSERT INTO likes (user_id, recipe_id) VALUES (?, ?)";
        $insertLikeStmt = $conn->prepare($insertLikeSql);
        $insertLikeStmt->bind_param("ii", $userId, $likerecipeId);
        $insertLikeStmt->execute();
        $insertLikeStmt->close();
    }

    // Перенаправление после лайка
    header("Location: view_recipes.php");
    exit();
}

// Обработка удаления лайка
if (isset($_GET['unlike']) && isset($_SESSION['user_id'])) {
    $unlikerecipeId = intval($_GET['unlike']);
    $userId = $_SESSION['user_id'];

    // Удаление лайка
    $deleteLikeSql = "DELETE FROM likes WHERE user_id = ? AND recipe_id = ?";
    $deleteLikeStmt = $conn->prepare($deleteLikeSql);
    $deleteLikeStmt->bind_param("ii", $userId, $unlikerecipeId);
    $deleteLikeStmt->execute();
    $deleteLikeStmt->close();

    // Перенаправление после удаления лайка
    header("Location: view_recipes.php");
    exit();
}

// Получение коэффициентов из базы данных
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
    // Получаем значения поиска, если они есть
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $date = isset($_GET['date']) ? trim($_GET['date']) : '';
    $message = isset($_GET['message']) ? $_GET['message'] : '';

    // Получение user_id из сессии
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $sql = "SELECT recipes.*, users.username, 
            (SELECT COUNT(*) FROM likes WHERE recipe_id = recipes.id) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ? AND recipe_id = recipes.id) AS user_liked,
            (SELECT COUNT(*) FROM likes l INNER JOIN recipes p ON l.recipe_id = p.id 
             WHERE l.user_id IN (SELECT user_id FROM likes WHERE user_id = ?)
             AND p.user_id = recipes.user_id) AS user_favorite_count,
            (SELECT COUNT(*) FROM likes l 
             JOIN recipes p ON l.recipe_id = p.id 
             WHERE l.user_id = ? AND p.recipe_type = recipes.recipe_type) AS liked_recipe_type_count
        FROM recipes 
        JOIN users ON recipes.user_id = users.id 
        WHERE (title LIKE ? OR recipe_text LIKE ? OR recipe_type LIKE ? OR users.username LIKE ?)
    ";

//  (SELECT COUNT(*) FROM recipe_images WHERE recipe_id = recipes.id) AS image_count,

    // Добавляем фильтр по дате, если указано
    if ($date) {
        $sql .= " AND DATE(created_at) = DATE(?)";
    }

    // Сортировка по количеству лайков от любимых пользователей и дате создания
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

     // Добавляем веса
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
    <title>Дневник путешественника</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    
</head>
<style>
    /* Основной стиль для картинок */
.recipe-image {
    width: 150px;  /* Фиксированная ширина */
    height: 150px; /* Фиксированная высота */
    object-fit: cover; /* Обрезка изображения по центру, сохраняя пропорции */
    display: inline-block; /* Размещение изображений в одну линию */
    margin: 5px; /* Небольшие отступы между изображениями */
}

/* Стили для изображения в таблице */
table td {
    text-align: center; /* Центрирование контента внутри ячеек */
}

/* Стили для таблицы */
table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

</style>
    <body>
        <h1>Мамины рецепты</h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Отображение аватарки пользователя -->
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Аватар" class="user-avatar">
            <div class="header">
                <h2 style="color: <?php echo ($selectedLanguage === 'eng') ? 'red' : 'green'; ?>;">
                    <?php echo ($selectedLanguage === 'eng') ? "Welcome back, " . htmlspecialchars($username) . "!" : "Добро пожаловать обратно, " . htmlspecialchars($username) . "!"; ?>
                </h2>
            </div>
        <?php endif; ?>


        <!-- Сообщение о результате создания поста -->
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

        <!-- Отображение уведомлений -->
        <?php if (!empty($notifications)): ?>
            <div class="notifications">
                <?php foreach ($notifications as $notification): ?>
                    <script>alert('<?php echo $notification; ?>');</script>
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
            <a href="add_recipe_type.php" class="add-recipe-btn">Добавить вид блюда</a>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- <h2>Привет, <?php echo htmlspecialchars($username); ?>!</h2> -->
            <a href="view_favorites.php" class="add-recipe-btn">Посмотреть избранные рецепты</a>
        <?php endif; ?>
        <?php
        // Проверка, что пользователь вошел в аккаунт
        if (isset($_SESSION['user_id'])) {
            // Показываем кнопки для настроек и выхода
            echo '<a href="setting.php" class="add-recipe-btn">Настройки</a>';
            echo '<a href="logout.php" class="add-recipe-btn">Выход</a>';
        }
        ?>

        <!-- Форма поиска -->
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
                    <th>Рецепт</th>
                    <th>Дата</th>
                    <th>Автор</th>
                    <th>Изображения</th>
                    <th>&#9829; и 💬</th>
                    <th>Удалить</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['recipe_type']) . "</td>";
                        echo "<td>" . nl2br(htmlspecialchars($row['recipe_text'])) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";

                        // Получение изображений для текущего поста
  
                        // Получение изображений для текущего поста
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

                        // Получаем количество комментариев для поста
                        $commentsCountQuery = "SELECT COUNT(*) AS comments_count FROM comments WHERE recipe_id = " . $row['id'];
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
                        echo "<a href='comments.php?recipe_id=" . $row['id'] . "' class='comment-btn' title='Комментарии'>💬</a>";
                        echo "</div>";
                        echo "<a href='view_likes.php?recipe_id=" . $row['id'] . "' class='view-likes-btn' title='Посмотреть, кто лайкнул'>👥</a>";

                        echo "</td>";




                        // Проверка, если текущий пользователь является автором поста или администратором
                        if (isset($_SESSION['user_id'])) {
                            if ($row['user_id'] == $_SESSION['user_id'] || $userRole === 'admin') {
                                echo "<td>
                                        <a href='?delete=" . $row['id'] . "' 
                                        onclick=\"return confirm('Вы уверены, что хотите удалить?');\" 
                                        class='delete-btn'>
                                            <i class='fa fa-trash'></i>
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
            event.preventDefault(); // Предотвращаем переход по ссылке

            // Создаем форму для отправки запроса
            var form = document.createElement('form');
            form.method = 'POST';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'request_publisher';
            input.value = '1'; // Можно установить любое значение

            form.appendChild(input);
            document.body.appendChild(form); // Добавляем форму в документ
            form.submit(); // Отправляем форму
            });
        </script> 

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