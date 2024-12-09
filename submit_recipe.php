<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: add_recipe.php?message=login_error");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $recipe_type = $_POST['recipe_type'];
    $recipe_text = $_POST['recipe_text'];

    if (strlen($title) > 50) {
        header("Location: add_recipe.php?message=title_error");
        exit();
    }

    if (strlen($recipe_type) > 50) {
        header("Location: add_recipe.php?message=recipe_type_error");
        exit();
    }

    // Ограничение на одно изображение
    if (count($_FILES['images']['name']) > 1) {
        header("Location: add_recipe.php?message=file_error");
        exit();
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $images = [];

    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileType = $_FILES['images']['type'][$key];

                if (in_array($fileType, $allowedTypes)) {
                    $imgData = file_get_contents($tmp_name);
                    $images[] = $imgData; // Сохраняем изображение для дальнейшей вставки
                } else {
                    header("Location: add_recipe.php?message=image_error");
                    exit();
                }
            }
        }
    }

    // Сохранение поста в базу данных
    $link = mysqli_connect("localhost", $username, $password, $dbname);
    if (!$link) {
        die("Ошибка подключения: " . mysqli_connect_error());
    }

    $sql = "INSERT INTO recipes (title, recipe_type, recipe_text, user_id) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "sssi", $title, $recipe_type, $recipe_text, $_SESSION['user_id']);

    if (mysqli_stmt_execute($stmt)) {
        $recipe_id = mysqli_insert_id($link);

        // Вставка изображений в базу данных
        foreach ($images as $imgData) {
            $image_sql = "INSERT INTO recipe_images (recipe_id, image) VALUES (?, ?)";
            $image_stmt = mysqli_prepare($link, $image_sql);
            mysqli_stmt_bind_param($image_stmt, "is", $recipe_id, $imgData);

            if (!mysqli_stmt_execute($image_stmt)) {
                echo "Ошибка при загрузке изображения: " . mysqli_error($link);
            }
            mysqli_stmt_close($image_stmt);
        }

        mysqli_stmt_close($stmt);
        mysqli_close($link);
        header("Location: view_recipes.php?message=success");
        exit();
    } else {
        echo "Ошибка при создании публикации: " . mysqli_error($link);
    }
}
?>
