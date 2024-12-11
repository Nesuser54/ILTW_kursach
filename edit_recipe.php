<?php

include 'db.php';

if (isset($_GET['id'])) {
    $recipe_id = $_GET['id'];

    $sql = "SELECT * FROM recipes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $recipe_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $recipe = $result->fetch_assoc();
    } else {
        echo "Рецепт не найден!";
        exit;
    }
} else {
    echo "ID рецепта не передан!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать рецепт</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
    .container {
        width: 90%;
        max-width: 800px;
        margin: 0 auto;
        margin-top: 30px;
        padding: 30px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    h1 {
        font-size: 2.5rem;
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    a.add-recipe-btn {
        display: inline-block;
        color: white;
        padding: 10px 20px;
        text-align: center;
        border-radius: 5px;
        text-decoration: none;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    .message .message-text {
        padding: 15px;
        background-color: #f8d7da;
        color: #721c24;
        border-radius: 5px;
        font-size: 1rem;
        border: 1px solid #f5c6cb;
        text-align: center;
    }

    .recipe-form {
        display: grid;
        gap: 20px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #fafafa;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    label {

        font-size: 1.1rem;
        color: #333;
        font-weight: 600;
    }

    input[type="text"],
    textarea {
        width: 96%;
        padding: 12px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #ffffff;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }



    select {
        width: 100%;
        padding: 12px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #ffffff;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    input[type="file"] {
        width: 98%;
        padding: 12px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #ffffff;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    input[type="text"]:focus,
    textarea:focus,
    select:focus,
    input[type="file"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.4);
    }

    textarea {
        height: 150px;
        resize: none;
    }


    .submit-btn {
        padding: 12px 24px;
        background-color: #f5a623;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1.1rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
        background-color: #d87f19;
    }

    .recipe-form input[type="file"] {
        padding: 8px;
    }

    .message {
        margin-bottom: 20px;
    }

    small {
        font-size: 0.9rem;
        color: #888;
        display: block;
        margin-top: -10px;
    }
</style>

<body>
    <div class="container">
        <h1>Редактировать рецепт</h1>

        <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>

        <div class="message">
            <?php if (isset($_GET['message'])): ?>
                <div class="message-text">
                    <?php
                    if ($_GET['message'] === 'title_error') {
                        echo "Заголовок не должен превышать 50 символов.";
                    } elseif ($_GET['message'] === 'recipe_type_error') {
                        echo "Вид блюда не должно превышать 50 символов.";
                    } elseif ($_GET['message'] === 'image_error') {
                        echo "Прикрепляемый файл должен быть изображением (JPEG, PNG, GIF, JPG).";
                    } elseif ($_GET['message'] === 'error') {
                        echo "Произошла ошибка при редактировании публикации.";
                    } elseif ($_GET['message'] === 'file_error') {
                        echo "Произошла ошибка при работе с файлом.";
                    } elseif ($_GET['message'] === 'login_error') {
                        echo "Для редактирования рецептов необходимо авторизоваться.";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="submit_edit_recipe.php" method="POST" enctype="multipart/form-data" class="recipe-form">
            <input type="hidden" name="recipe_id" value="<?= $recipe['id'] ?>">

            <label for="title">Заголовок:</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($recipe['title']) ?>" required maxlength="50">

            <label for="recipe_text">Текст рецепта:</label>
            <textarea id="recipe_text" name="recipe_text" required><?= htmlspecialchars($recipe['recipe_text']) ?></textarea>

            <label for="recipe_type">Вид блюда:</label>
            <select id="recipe_type" name="recipe_type_id" required>
                <option value="">Выберите вид блюда</option>
                <?php
                $recipe_typeSql = "SELECT id, name FROM recipe_type";
                $recipe_typeResult = $conn->query($recipe_typeSql);
                while ($recipe_type = $recipe_typeResult->fetch_assoc()) {
                    $selected = ($recipe['recipe_type_id'] == $recipe_type['id']) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($recipe_type['id']) . "' $selected>" . htmlspecialchars($recipe_type['name']) . "</option>";
                }
                ?>
            </select>


            <label for="images">Прикрепить файл(если файл не будет выбран, на рецепте останется предыдущее изображение):</label>
            <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.gif">

            <input type="submit" value="Обновить рецепт" class="submit-btn">
        </form>
    </div>
</body>

</html>