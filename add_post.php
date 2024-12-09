<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить рецепт</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<style>
    /* Общие стили для страницы */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f7fc;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Контейнер для всего контента */
.container {
    width: 90%;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Заголовок */
h1 {
    font-size: 2rem;
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

/* Кнопка возврата */
.add-post-btn {
    display: inline-block;
    padding: 12px 24px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: -30px; /* Уменьшили отступ */
    transition: background-color 0.3s;
}

.add-post-btn:hover {
    background-color: #45a049;
}


/* Стиль для сообщений об ошибках */
.message {
    margin: 20px 0;
}

.message-text {
    padding: 15px;
    background-color: #f8d7da;
    color: #721c24;
    border-radius: 5px;
    font-size: 1rem;
    border: 1px solid #f5c6cb;
    text-align: center;
}

/* Форма добавления поста */
.post-form {
    display: grid;
    gap: 15px;
}

/* Метки для полей */
label {
    font-size: 1rem;
    color: #333;
}

/* Поля ввода */
input[type="text"],
textarea,
select,
input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1rem;
}

/* Текстовое поле */
textarea {
    height: 150px;
}

/* Кнопка отправки */
.submit-btn {
    padding: 12px 24px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.submit-btn:hover {
    background-color: #45a049;
}

</style>
<body>
    <div class="container">
        <h1>Добавить новый рецепт на сайт</h1>

        <!-- Кнопка возврата на таблицу -->
        <a href="view_posts.php" class="add-post-btn">Вернуться на главную страницу</a>

        <!-- Сообщения об ошибках -->
        <div class="message">
            <?php if (isset($_GET['message'])): ?>
                <div class="message-text">
                    <?php
                    if ($_GET['message'] === 'title_error') {
                        echo "Заголовок не должен превышать 50 символов.";
                    } elseif ($_GET['message'] === 'location_error') {
                        echo "Местоположение не должно превышать 50 символов.";
                    } elseif ($_GET['message'] === 'image_error') {
                        echo "Прикрепляемый файл должен быть изображением (JPEG, PNG, GIF, JPG).";
                    } elseif ($_GET['message'] === 'error') {
                        echo "Произошла ошибка при создании публикации.";
                    } elseif ($_GET['message'] === 'file_error') {
                        echo "Произошла ошибка при работе с файлом.";
                    } elseif ($_GET['message'] === 'login_error') {
                        echo "Для создания рецептов необходимо авторизоваться";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Форма для добавления поста -->
        <form action="submit_post.php" method="post" enctype="multipart/form-data" class="post-form">
            <label for="title">Заголовок:</label>
            <input type="text" id="title" name="title" required maxlength="50">

            <label for="content">Текст рецепта:</label>
            <textarea id="content" name="content" required></textarea>

            <label for="location">Местоположение:</label>
            <select id="location" name="location" required>
                <option value="">Выберите местоположение</option>
                <?php
                include 'db.php';
                $locationSql = "SELECT * FROM locations";
                $locationResult = $conn->query($locationSql);
                while ($location = $locationResult->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($location['name']) . "'>" . htmlspecialchars($location['name']) . "</option>";
                }
                ?>
            </select>

            <label for="images">Прикрепить файлы:</label>
            <input type="file" id="images" name="images[]" multiple>

            <input type="submit" value="Добавить рецепт" class="submit-btn">
        </form>
    </div>
</body>
</html>
