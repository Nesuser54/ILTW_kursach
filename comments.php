<?php
    include 'db.php';
    include 'auth.php';

    $recipe_id = $_GET['recipe_id'] ?? null;

    if (!$recipe_id) {
        header("Location: view_recipes.php?message=recipe_not_found");
        exit();
    }

    // Получение данных поста
    $recipe_sql = "SELECT p.*, u.username FROM recipes p JOIN users u ON p.user_id = u.id WHERE p.id = ?";
    $recipe_stmt = $conn->prepare($recipe_sql);
    $recipe_stmt->bind_param("i", $recipe_id);
    $recipe_stmt->execute();
    $recipe_result = $recipe_stmt->get_result();
    $recipe = $recipe_result->fetch_assoc();

    if (!$recipe) {
        header("Location: view_recipes.php?message=recipe_not_found");
        exit();
    }

    // Получение комментариев
    $comments_sql = "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.recipe_id = ? ORDER BY c.created_at ASC";
    $comments_stmt = $conn->prepare($comments_sql);
    $comments_stmt->bind_param("i", $recipe_id);
    $comments_stmt->execute();
    $comments_result = $comments_stmt->get_result();
    ?>

    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Комментарии к рецепт</title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    </head>
    <style>

body {
    
    margin-bottom: 107px;
}
    form {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-start; /* Выравнивание по левому краю */
        max-width: 33%;           /* Ограничиваем ширину формы */
        margin-left: 10px;        /* Отступ от левого края */
    }


    h1 {
            
            text-align: center;
            margin-bottom: 10px;
        }
    .char-count {
        font-size: 12px;
        color: #888;
        text-align: right;
        margin-top: 5px;
        position: absolute;
        right: 15px;
        bottom: 5px;
        margin-bottom: 50px;
        margin-right: -30px;
    }

    form textarea {
        width: 100%;              /* Ширина textarea 100% от родителя */
        height: 70px;            /* Умеренная высота */
        padding: 10px;            /* Отступы внутри поля */
        border: 1px solid #ccc;  /* Легкая рамка */
        border-radius: 5px;      /* Скругленные углы */
        font-size: 16px;          /* Увеличиваем размер шрифта */
        resize: none;        /* Разрешаем вертикальное изменение размера */
        margin-bottom: 10px;      /* Отступ снизу */
    }

    form button {
        background-color: #f5a623;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
    }

    form button:hover {
        background-color: #d87f19;
    }

    .msg {
    padding-left: 10px;  /* Добавляет отступ слева */
    }
    a.add-recipe-btn {
            display: inline-block;
            color: white;
            padding: 10px 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }
        

    </style>
    <body>
        <h1>Комментарии к рецепту</h1>
        <!-- Кнопка для возврата на главную страницу -->
        <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>

        <!-- Таблица с информацией о посте -->
        <table>
            <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Вид блюда</th>
                    <th>Содержимое</th>
                    <th>Дата создания</th>
                    <th>Автор</th>
                    <th>Изображения</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($recipe['title']) ?></td>
                    <td><?= htmlspecialchars($recipe['recipe_type']) ?></td>
                    <td><?= nl2br(htmlspecialchars($recipe['recipe_text'])) ?></td>
                    <td><?= htmlspecialchars($recipe['created_at']) ?></td>
                    <td><?= htmlspecialchars($recipe['username']) ?></td>
                    <td>
                        <?php
                        // Изображения поста
                        $image_sql = "SELECT image FROM recipe_images WHERE recipe_id = ?";
                        $image_stmt = $conn->prepare($image_sql);
                        $image_stmt->bind_param("i", $recipe_id);
                        $image_stmt->execute();
                        $image_result = $image_stmt->get_result();

                        if ($image_result->num_rows > 0) {
                            while ($image_row = $image_result->fetch_assoc()) {
                                echo '<img src="data:image/jpeg;base64,' . base64_encode($image_row['image']) . '" alt="Изображение" class="recipe-image">';
                            }
                        } else {
                            echo "Изображения не найдены";
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <hr>
        <h2>Комментарии</h2>

        

        <!-- Список комментариев -->
        <?php while ($comment = $comments_result->fetch_assoc()): ?>
    <div class="comment">
        <p class="comment-content"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
        <p><small class="comment-meta">Автор: <?= htmlspecialchars($comment['username']) ?> | <?= htmlspecialchars($comment['created_at']) ?></small></p>
        
        <!-- Проверка на авторизацию пользователя или права на удаление -->
        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $recipe['user_id'])): ?>
            <!-- Если пользователь авторизован, то кнопка работает -->
            <a href="delete_comment.php?id=<?= $comment['id'] ?>&recipe_id=<?= $recipe_id ?>" 
               onclick="return confirm('Вы уверены, что хотите удалить?');" 
               class="delete-btn">
                <i class="fa fa-trash"></i> <!-- Иконка мусорного ведра -->
            </a>
        <?php else: ?>
            <!-- Для неавторизованных пользователей кнопка есть, но неактивна -->
            <span class="delete-btn disabled">
                <i class="fa fa-trash"></i> <!-- Иконка мусорного ведра -->
            </span>
        <?php endif; ?>
    </div>
<?php endwhile; ?>



    <!-- Форма добавления комментария -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <form action="add_comment.php" method="POST">
            <textarea id="commentContent" name="content" rows="3" placeholder="Напишите ваш комментарий..." maxlength="100" required></textarea>
            <input type="hidden" name="recipe_id" value="<?= $recipe_id ?>">
            <button type="submit">Отправить</button>
            <div id="charCount" class="char-count">0/100</div>
        </form>
        <?php else: ?>
            <p class="msg">Войдите, чтобы оставить комментарий.</p>
        <?php endif; ?>

        <script>
            // Получаем элементы
    const textarea = document.getElementById('commentContent');
    const charCount = document.getElementById('charCount');

    // Функция для обновления счетчика символов
    textarea.addEventListener('input', function() {
        const currentLength = textarea.value.length;
        charCount.textContent = `${currentLength}/100`;
    });

        </script>
    </body>
    </html>
