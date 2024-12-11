<?php
include 'db.php';
include 'auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_recipes.php?message=access_denied");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipe_type_name'])) {
    $recipeTypeName = trim($_POST['recipe_type_name']);

    if (!empty($recipeTypeName)) {

        $checkSql = "SELECT id FROM recipe_type WHERE name = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $recipeTypeName);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            header("Location: add_recipe_type.php?add_message=exists");
        } else {
            $sql = "INSERT INTO recipe_type (name) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $recipeTypeName);

            if ($stmt->execute()) {
                header("Location: add_recipe_type.php?add_message=success");
            } else {
                header("Location: add_recipe_type.php?add_message=error");
            }
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe_type_id'])) {

    $recipeTypeId = intval($_POST['delete_recipe_type_id']);
    $deleteTypeSql = "DELETE FROM recipe_type WHERE id = ?";
    $deleteTypeStmt = $conn->prepare($deleteTypeSql);
    $deleteTypeStmt->bind_param("i", $recipeTypeId);

    try {
        $deleteTypeStmt->execute();
        header("Location: add_recipe_type.php?delete_message=deleted");
    } catch (Exception $e) {
        header("Location: add_recipe_type.php?delete_message=delete_error");
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление видами блюд</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            max-width: 600px;
            width: 100%;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .form {
            margin-bottom: 20px;
        }

        .input-field,
        .select-field,
        .submit-btn {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .input-field::placeholder {
            color: #aaa;
        }

        .submit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
            box-sizing: border-box;
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
            color: white;
            padding: 10px 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Управление видами блюд</h1>
        <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>

        <?php if (isset($_GET['add_message'])): ?>
            <div class="message <?php
                                echo ($_GET['add_message'] === 'success') ? 'success' : (($_GET['add_message'] === 'exists') ? 'warning' : 'error'); ?>">
                <?php
                echo ($_GET['add_message'] === 'success') ? 'Вид блюда успешно добавлен!' : (($_GET['add_message'] === 'exists') ? 'Такой вид блюда уже существует!' :
                        'Ошибка при добавлении.');
                ?>
            </div>
        <?php elseif (isset($_GET['delete_message'])): ?>
            <div class="message <?php
                                echo ($_GET['delete_message'] === 'deleted') ? 'success' : 'error'; ?>">
                <?php
                echo ($_GET['delete_message'] === 'deleted') ? 'Вид блюда успешно удалён!' :
                    'Ошибка при удалении. Вид блюда связан с другими данными.';
                ?>
            </div>
        <?php endif; ?>

        <form action="add_recipe_type.php" method="POяST" class="form">
            <input type="text" name="recipe_type_name" placeholder="Название вида блюда" required class="input-field">
            <input type="submit" value="Добавить" class="submit-btn">
        </form>

        <form action="add_recipe_type.php" method="POST" class="form">
            <select name="delete_recipe_type_id" required class="input-field">
                <option value="" disabled selected>Выберите вид блюда для удаления</option>
                <?php
                $typesSql = "SELECT id, name FROM recipe_type";
                $typesResult = $conn->query($typesSql);
                if ($typesResult->num_rows > 0) {
                    while ($row = $typesResult->fetch_assoc()) {
                        echo "<option value='{$row['id']}'>{$row['name']}</option>";
                    }
                } else {
                    echo "<option value='' disabled>Виды блюда отсутствуют</option>";
                }
                ?>
            </select>
            <input type="submit" value="Удалить" class="submit-btn">
        </form>
    </div>
</body>

</html>