<?php
include 'db.php';
include 'auth.php';
// session_start();

try {

    $languages = ['ru' => 'Русский', 'eng' => 'English'];
    $selectedLanguage = $_COOKIE['language'] ?? 'ru';
    $userRole = $_SESSION['role'] ?? 'user';

    // Проверка на наличие сообщений
    $error = $_SESSION['error'] ?? null;
    $success = $_SESSION['success'] ?? null;

    // Сбрасываем сообщения после отображения
    unset($_SESSION['error']);
    unset($_SESSION['success']);

    $weight_author = 0.5;
    $weight_location = 0.3;
    $weight_likes = 0.2;

    $sql = "SELECT weight_likes, weight_location, weight_author FROM ranking_weights WHERE id = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $weight_author = $row['weight_author'];
        $weight_location = $row['weight_location'];
        $weight_likes = $row['weight_likes'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Изменение языка
        if (isset($_POST['language'])) {
            $selectedLanguage = $_POST['language'];
            setcookie("language", $selectedLanguage, time() + 1440, "/");
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
                $updateLangSql = "UPDATE users SET lang = ? WHERE id = ?";
                $stmt = $conn->prepare($updateLangSql);
                $stmt->bind_param("si", $selectedLanguage, $userId);
                $stmt->execute();
                $stmt->close();
            }

            header("Location: setting.php");
            exit();
        }

        // Обработка загрузки аватарки
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $userId = $_SESSION['user_id'];
            $avatarFile = $_FILES['avatar'];
            $avatarPath = 'uploads/' . basename($avatarFile['name']);

            // Проверка типа файла
            $fileType = strtolower(pathinfo($avatarPath, PATHINFO_EXTENSION));
            if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $_SESSION['error'] = "Неверный формат файла. Пожалуйста, загрузите изображение";
                header("Location: setting.php");
                exit();
            }

            // Проверка размера файла
            $maxFileSize = 8 * 1024 * 1024; // 8 МБ
            if ($avatarFile['size'] > $maxFileSize) {
                $_SESSION['error'] = "Размер файла превышает 8 МБ. Пожалуйста, загрузите меньший файл";
                header("Location: setting.php");
                exit();
            }

            // Перемещение файла
            if (move_uploaded_file($avatarFile['tmp_name'], $avatarPath)) {
                $updateAvatarSql = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt = $conn->prepare($updateAvatarSql);
                $stmt->bind_param("si", $avatarPath, $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['success'] = "Аватарка успешно обновлена";
            } else {
                $_SESSION['error'] = "Ошибка при загрузке файла";
            }

            header("Location: setting.php");
            exit();
        }
    }
} catch (Exception $exp) {
    $_SESSION['error'] = "Ошибка " . $exp->getMessage();
}

// $greeting = ($selectedLanguage === 'eng') ? 'Welcome!' : 'Добро пожаловать!';
?>

<!DOCTYPE html>
<html lang="<?php echo $selectedLanguage; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: #333;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            font-size: 1rem;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        select, input[type="file"], input[type="number"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1rem;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #4caf50;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #f8d7da;
            color: #721c24;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;  
            color: #721c24;
        }

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

        .message_error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 5px;
}

/* Стили для успешных сообщений */
.message_success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 5px;
}
    </style>
</head>

<body>

    <div class="container">
        <h1>Настройки</h1>
        <a href="view_posts.php" class="add-post-btn">Вернуться на главную страницу</a>
   
        <!-- Форма для выбора языка -->
        <form action="setting.php" method="POST">
            <label for="language">Выберите язык приветствия:</label>
            <select name="language" id="language">
                <?php foreach ($languages as $code => $name): ?>
                    <option value="<?php echo $code; ?>" <?php echo ($selectedLanguage === $code) ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Сохранить">
        </form>

        <!-- Форма для загрузки аватарки -->
        <form action="setting.php" method="POST" enctype="multipart/form-data">
            <label for="avatar">Загрузить аватарку:</label>
            <input type="file" name="avatar" id="avatar" accept="image/*" required>
            <input type="submit" value="Сохранить">
        </form>

        <?php if (isset($_SESSION['user_id']) && $userRole === 'admin'): ?>
            <!-- Форма для изменения коэффициентов -->
            <form method="POST" action="update_weights.php">
                <label for="weight_author">Коэффициент авторов:</label>
                <input type="number" step="0.1" name="weight_author" id="weight_author" value="<?php echo $weight_author; ?>" required>

                <label for="weight_location">Коэффициент местоположения:</label>
                <input type="number" step="0.1" name="weight_location" id="weight_location" value="<?php echo $weight_location; ?>" required>

                <label for="weight_likes">Коэффициент количества лайков:</label>
                <input type="number" step="0.1" name="weight_likes" id="weight_likes" value="<?php echo $weight_likes; ?>" required>

                <input type="submit" value="Сохранить">
            </form>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="message_error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="message_success"><?php echo $success; ?></div>
        <?php endif; ?>
        
    </div>

</body>

</html>
