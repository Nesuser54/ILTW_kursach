<?php
include 'db.php'; // Подключение к базе данных
include 'auth.php';
// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_posts.php?message=access_denied");
    exit();
}

// Обработка добавления нового местоположения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationName = trim($_POST['location_name']);
    
    if (!empty($locationName)) {
        // Проверка на существование местоположения
        $checkSql = "SELECT * FROM locations WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $locationName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Местоположение уже существует
            header("Location: add_location.php?message=exists");
            exit();
        } else {
            // Добавление нового местоположения
            $sql = "INSERT INTO locations (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $locationName);
            
            if ($stmt->execute()) {
                header("Location: add_location.php?message=success");
                exit();
            } else {
                header("Location: add_location.php?message=error");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить местоположение</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
    /* Стиль для страницы */
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 20px;
    background-color: #f4f7fa;
}

/* Заголовок */
h1 {
    color: #333;
    font-size: 24px;
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
    margin-bottom: 20px;
    transition: background-color 0.3s;
}

.add-post-btn:hover {
    background-color: #45a049;
}

/* Стиль формы */
.form {
    max-width: 400px;
    margin: 0 auto;
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* Поля ввода */
.input-field {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

/* Кнопка отправки */
.submit-btn {
    width: 100%;
    padding: 10px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

.submit-btn:hover {
    background-color: #45a049;
}

/* Стиль сообщений */
.message p {
    padding: 10px;
    border-radius: 5px;
    text-align: center;
    font-weight: bold;
}

.success {
    background-color: #d4edda;
    color: #155724;
}

.warning {
    background-color: #fff3cd;
    color: #856404;
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

</style>
<body>
    <a href="view_posts.php" class="add-post-btn">Вернуться на главную страницу</a>
    <h1>Добавить новое местоположение</h1>

    <!-- Сообщение об успехе или ошибке -->
    <?php if (isset($_GET['message'])): ?>
        <div class="message">
            <p class="<?php 
                echo ($_GET['message'] === 'success') ? 'success' : 
                     (($_GET['message'] === 'exists') ? 'warning' : 'error'); ?>">
                <?php 
                echo ($_GET['message'] === 'success') ? 'Местоположение успешно добавлено!' : 
                     (($_GET['message'] === 'exists') ? 'Такое местоположение уже существует!' : 
                     'Ошибка при добавлении.'); 
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Форма для добавления местоположения -->
    <form action="add_location.php" method="POST" class="form">
        <input type="text" name="location_name" placeholder="Название местоположения" required class="input-field">
        <input type="submit" value="Добавить" class="submit-btn">
    </form>
</body>
</html>