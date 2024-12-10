<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: edit_recipe.php?message=login_error");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recipe_id = $_POST['recipe_id'];
    $title = $_POST['title'];
    $recipe_type = $_POST['recipe_type'];
    $recipe_text = $_POST['recipe_text'];

    // Проверка длины заголовка
    if (strlen($title) > 50) {
        header("Location: edit_recipe.php?recipe_id=$recipe_id&message=title_error");
        exit();
    }

    // Проверка длины типа рецепта
    if (strlen($recipe_type) > 50) {
        header("Location: edit_recipe.php?recipe_id=$recipe_id&message=recipe_type_error");
        exit();
    }

    // Ограничение на одно изображение
    if (count($_FILES['images']['name']) > 1) {
        header("Location: edit_recipe.php?recipe_id=$recipe_id&message=file_error");
        exit();
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $images = [];

    if (isset($_FILES['images']) && $_FILES['images']['name'][0] != "") {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['images']['type'][$key];

                if (in_array($fileType, $allowedTypes)) {
                    $imgData = file_get_contents($tmp_name);
                    $images[] = $imgData; // Сохраняем изображение для дальнейшей вставки
                } else {
                    header("Location: edit_recipe.php?recipe_id=$recipe_id&message=image_error");
                    exit();
                }
            }
        }
    }

    // Обновление данных рецепта
    $link = mysqli_connect("localhost", $username, $password, $dbname);
    if (!$link) {
        die("Ошибка подключения: " . mysqli_connect_error());
    }

    $sql = "UPDATE recipes SET title = ?, recipe_type = ?, recipe_text = ? WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssii", $title, $recipe_type, $recipe_text, $recipe_id, $_SESSION['user_id']);

    if (mysqli_stmt_execute($stmt)) {
        // Удаление старых изображений, если загружены новые
        if (!empty($images)) {
            $delete_images_sql = "DELETE FROM recipe_images WHERE recipe_id = ?";
            $delete_stmt = mysqli_prepare($link, $delete_images_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $recipe_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            // Вставка новых изображений
            foreach ($images as $imgData) {
                $image_sql = "INSERT INTO recipe_images (recipe_id, image) VALUES (?, ?)";
                $image_stmt = mysqli_prepare($link, $image_sql);
                mysqli_stmt_bind_param($image_stmt, "is", $recipe_id, $imgData);

                if (!mysqli_stmt_execute($image_stmt)) {
                    echo "Ошибка при загрузке изображения: " . mysqli_error($link);
                }
                mysqli_stmt_close($image_stmt);
            }
        }

        mysqli_stmt_close($stmt);
        mysqli_close($link);
        header("Location: view_recipes.php?message=edit_success");
        exit();
    } else {
        echo "Ошибка при обновлении рецепта: " . mysqli_error($link);
    }
}
?>
