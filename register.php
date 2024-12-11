<?php
include 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if (strlen($username) < 6 || strlen($password) < 6) {
        $error = "Имя пользователя и пароль должны содержать минимум 6 символов.";
    } else {
        $checkSql = "SELECT * FROM users WHERE username = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $error = "Пользователь с таким именем уже существует.";
        } else {
            $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $passwordHash);

            if ($stmt->execute()) {
                header("Location: login.php?message=registered");
                exit();
            } else {
                $error = "Ошибка регистрации: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
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
            background-color: #d87f19;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 5px;
            width: 100%;
            text-align: center;
            transition: background-color 0.3s;
            box-sizing: border-box;
        }

        .form-container a:hover {
            background-color: #f5a623;
        }

        .form-container input[type="submit"] {
            color: white;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            border: none;
            width: 100%;
            cursor: pointer;
            transition: background-color 0.3s;
            box-sizing: border-box;
            margin-top: 0px;
            margin-bottom: 20px;
        }



        .form-container .error {
            color: red;
            margin-bottom: 20px;
        }

        .form-container .error {
            color: red;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h1>Регистрация</h1>
        <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
        <form action="register.php" method="POST">
            <div class="form-group">
                <input type="text" name="username" placeholder="Имя пользователя" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Пароль" required>
            </div>
            <input type="submit" value="Зарегистрироваться" class="add-recipe-btn">
        </form>
        <a href="login.php" class="add-recipe-btn"> Уже есть аккаунт? Войти</a>
        <a class="add-recipe-btn" href="view_recipes.php">Вернуться на главную</a>
    </div>
</body>

</html>