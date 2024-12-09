<?php
include 'db.php'; // Подключение к базе данных
include 'auth.php';
// session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_recipes.php?message=access_denied");
    exit();
}

// Обработка добавления нового вида блюда
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_typeName = trim($_POST['recipe_type_name']);
    
    if (!empty($recipe_typeName)) {
        // Проверка на существование вида блюда
        $checkSql = "SELECT * FROM recipe_type WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $recipe_typeName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // вид блюда уже существует
            header("Location: add_recipe_type.php?message=exists");
            exit();
        } else {
            // Добавление нового вида блюда
            $sql = "INSERT INTO recipe_type (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $recipe_typeName);
            
            if ($stmt->execute()) {
                header("Location: add_recipe_type.php?message=success");
                exit();
            } else {
                header("Location: add_recipe_type.php?message=error");
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
    <meta name="viewport" recipe_text="width=device-width, initial-scale=1.0">
    <title>Добавить вид блюда</title>
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
.add-recipe-btn {
    display: inline-block;
    padding: 12px 24px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-bottom: 20px;
    transition: background-color 0.3s;
}

.add-recipe-btn:hover {
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
.message {
    border-radius: 10px; /* Закругленные углы */
    max-width: 400px; /* Увеличена ширина для удобства */
    margin: 20px auto; /* Центрирование по горизонтали */
    text-align: center; /* Центрирование текста */
    font-weight: bold;
    font-size: 16px; /* Размер текста */
    background-color: #d4edda; /* Светло-зеленый фон */
    color: #155724; /* Темно-зеленый текст */
    border: 1px solid #c3e6cb; /* Обводка */
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

    a.add-recipe-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
        }

        a.add-recipe-btn:hover {
            background-color: #0056b3;
        }

</style>
<body>
    <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>
    <h1>Добавить новый вид блюда</h1>

    <!-- Сообщение об успехе или ошибке -->
<?php if (isset($_GET['message'])): ?>
    <div class="message">
        <p class="<?php 
            echo ($_GET['message'] === 'success') ? 'success' : 
                 (($_GET['message'] === 'exists') ? 'warning' : 'error'); ?>">
            <?php 
            echo ($_GET['message'] === 'success') ? 'Вид блюда успешно добавлен!' : 
                 (($_GET['message'] === 'exists') ? 'Такой вид блюда уже существует!' : 
                 'Ошибка при добавлении.'); 
            ?>
        </p>
    </div>
<?php endif; ?>


    <!-- Форма для добавления вида блюда -->
    <form action="add_recipe_type.php" method="POST" class="form">
        <input type="text" name="recipe_type_name" placeholder="Название вида блюда" required class="input-field">
        <input type="submit" value="Добавить" class="submit-btn">
    </form>
</body>
</html>