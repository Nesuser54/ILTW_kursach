<?php
session_start();
include 'db.php'; // Подключение к базе данных

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $rememberMe = isset($_POST['remember_me']);

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            if ($rememberMe) {
                // Генерация токена
                $token = bin2hex(random_bytes(16));
                setcookie('auth_token', $token, time() + 3600, "/"); 
                

                // Сохраните токен в базе данных для текущего пользователя
                $updateTokenSql = "UPDATE users SET auth_token = ? WHERE id = ?";
                $updateTokenStmt = $conn->prepare($updateTokenSql);
                $updateTokenStmt->bind_param("si", $token, $_SESSION['user_id']);
                $updateTokenStmt->execute();
            }
            header("Location: view_recipes.php?message=login");
            exit();
        } else {
            $error = "Неверный пароль";
        }
    } else {
        $error = "Пользователь не найден";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }

        .form-container {
            text-align: center;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        h1 {
            margin-bottom: 20px;
            font-family: 'Arial', sans-serif;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #4caf50;
            outline: none;
        }

        label {
            font-size: 14px;
            color: #555;
        }

        input[type="checkbox"] {
            margin-right: 10px;
        }

        .form-container a {
            display: inline-block;
            background-color: #4caf50;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 15px;
            width: 100%;
            text-align: center;
            transition: background-color 0.3s;
            box-sizing: border-box;
        }

        .form-container a:hover {
            background-color: #45a049;
        }

        .form-container input[type="submit"] {
            background-color: #4caf50;
            color: white;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
            box-sizing: border-box;
        }

        .form-container input[type="submit"]:hover {
            background-color: #45a049;
        }

        .form-container .error {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h1>Вход</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Имя пользователя" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember_me"> Запомнить меня
                </label>
            </div>
            <input type="submit" value="Войти">
        </form>

        <a href="register.php">Нет аккаунта? Зарегистрироваться</a>

        <!-- Кнопка возвращения на главную страницу -->
        <a href="view_recipes.php">Вернуться на главную</a>
    </div>

</body>

</html>
