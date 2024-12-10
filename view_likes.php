<?php
include 'db.php'; // Подключение к базе данных
include 'auth.php';

$recipe_id = isset($_GET['recipe_id']) ? intval($_GET['recipe_id']) : 0;

// Получение пользователей, поставивших лайки с их ролями
$sql = "SELECT users.username, users.role, likes.liked_at FROM likes 
        JOIN users ON likes.user_id = users.id 
        WHERE likes.recipe_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recipe_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лайки на рецепте</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Основной стиль для страницы */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        
        h1 {
            
            text-align: center;
            margin-bottom: 10px;
        }

        /* Стили для таблицы */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            color: #555;
        }

        td {
            color: #333;
        }

        /* Равные ширины столбцов (50% на каждый) */
        th, td {
            width: 33%;
        }

        /* Стили для кнопки */
        a.add-recipe-btn {
            display: inline-block;
            color: white;
            padding: 12px 25px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 30px;
            font-size: 16px;
        }

        /* Центрирование таблицы на странице */
        .container {
            width: 90%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Стиль для пустых ячеек */
        .no-likes {
            text-align: center;
            font-style: italic;
            color: #888;
        }
    </style>
</head>

<body>

<div class="container">
    <h1>Лайки на рецепте</h1>
    <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>

    <table>
        <thead>
            <tr>
                <th>Пользователь</th>
                <th>Роль</th>
                <th>Дата</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Преобразуем роль в читаемый формат
                    switch ($row['role']) {
                        case 'admin':
                            $role = 'Админ';
                            break;
                        case 'publisher':
                            $role = 'Публикатор';
                            break;
                        case 'user':
                            $role = 'Пользователь';
                            break;
                        default:
                            $role = 'Неизвестная роль';
                            break;
                    }

                    echo '<tr>';
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td>" . htmlspecialchars($role) . "</td>"; // Выводим роль
                    echo "<td>" . htmlspecialchars($row['liked_at']) . "</td>";
                    echo '</tr>';
                }
            } else {
                echo "<tr><td colspan='3' class='no-likes'>Нет лайков на этом рецепте.</td></tr>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
