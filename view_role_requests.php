<?php
include 'db.php'; // Подключение к базе данных
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: view_recipes.php"); // Перенаправление, если не админ
    exit();
}

// Обработка одобрения или отклонения заявки
if (isset($_GET['action']) && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        // Одобрение заявки
        $updateSql = "UPDATE role_requests SET status = 'approved' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();
        
        // Присвоение роли "publisher" запрашивающему пользователю
        $userIdSql = "SELECT user_id FROM role_requests WHERE id = ?";
        $userIdStmt = $conn->prepare($userIdSql);
        $userIdStmt->bind_param("i", $requestId);
        $userIdStmt->execute();
        $userIdResult = $userIdStmt->get_result();
        
        if ($userIdResult->num_rows > 0) {
            $userRow = $userIdResult->fetch_assoc();
            $userId = $userRow['user_id'];

            // Обновление роли пользователя
            $updateUserRoleSql = "UPDATE users SET role = 'publisher' WHERE id = ?";
            $updateUserRoleStmt = $conn->prepare($updateUserRoleSql);
            $updateUserRoleStmt->bind_param("i", $userId);
            $updateUserRoleStmt->execute();
            $updateUserRoleStmt->close();

            // Добавление уведомления пользователю
            $notificationMessage = 'Ваша заявка на роль путешественника одобрена.';
            $notificationSql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notificationStmt = $conn->prepare($notificationSql);
            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Установка уведомления в сессии
            $_SESSION['notification'] = $notificationMessage; // Добавить эту строку
        }

        // Удаление заявки
        $deleteSql = "DELETE FROM role_requests WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $requestId);
        $deleteStmt->execute();
        $deleteStmt->close();
    } elseif ($action === 'deny') {
        // Отклонение заявки
        $updateSql = "UPDATE role_requests SET status = 'denied' WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $requestId);
        $updateStmt->execute();

        // Добавление уведомления пользователю
        $userIdSql = "SELECT user_id FROM role_requests WHERE id = ?";
        $userIdStmt = $conn->prepare($userIdSql);
        $userIdStmt->bind_param("i", $requestId);
        $userIdStmt->execute();
        $userIdResult = $userIdStmt->get_result();

        if ($userIdResult->num_rows > 0) {
            $userRow = $userIdResult->fetch_assoc();
            $userId = $userRow['user_id'];

            $notificationMessage = 'Ваша заявка на роль отклонена.';
            $notificationSql = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
            $notificationStmt = $conn->prepare($notificationSql);
            $notificationStmt->bind_param("is", $userId, $notificationMessage);
            $notificationStmt->execute();
            $notificationStmt->close();

            // Установка уведомления в сессии
            $_SESSION['notification'] = $notificationMessage; // Добавить эту строку
        }

        // Удаление заявки
        $deleteSql = "DELETE FROM role_requests WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $requestId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    header("Location: view_role_requests.php");
    exit();
}

// Получение списка заявок
$sql = "SELECT r.id, u.username, r.created_at 
        FROM role_requests r 
        JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки на роль</title>
    <link rel="stylesheet" href="style.css">
    <style>
       /* Общий стиль страницы */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7fc;
    margin: 0;
    padding: 0;
    color: #333;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    flex-direction: column;
}

/* Стиль заголовка */
h1 {
    font-size: 2.5rem;
    color: #333;
    text-align: center;
    margin-bottom: 30px;
}

/* Стиль для кнопки "Вернуться на главную страницу" */
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

/* Центрирование таблицы */
table {
    width: 80%;
    margin: 20px auto;
    border-collapse: collapse;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Стиль заголовков таблицы */
table th {
    background-color: #2196f3;
    color: white;
    padding: 12px 15px;
    text-align: center;
    font-size: 1.1rem;
}

/* Стиль строк таблицы */
table td {
    padding: 12px 15px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table tr:hover {
    background-color: #f1f1f1;
}

/* Стилизация кнопок действий (одобрить/отклонить) */
.action-btns a {
    font-size: 1.5rem;
    margin: 0 10px;
    text-decoration: none;
    display: inline-block;
    padding: 8px;
    border-radius: 50%;
    transition: background-color 0.3s, transform 0.3s;
}

.action-btns a:hover {
    transform: scale(1.2);
}

.action-btns .approve {
    color: #28a745; /* Зеленый цвет для одобрения */
}

.action-btns .deny {
    color: #dc3545; /* Красный цвет для отклонения */
}

/* Стиль для пустой таблицы */
table td[colspan="4"] {
    text-align: center;
    font-style: italic;
    color: #888;
}

/* Мобильные стили */
@media (max-width: 768px) {
    table {
        width: 100%;
    }

    .add-recipe-btn {
        width: 100%;
        font-size: 1rem;
    }
}



    </style>
</head>
<body>
    <h1>Заявки на роль</h1>
    <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную страницу</a>
    <table>
        <thead>
            <tr>
                <th>Пользователь</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td class="action-btns">
                            <a href="?action=approve&id=<?php echo $row['id']; ?>" title="Одобрить">&#128077;</a> <!-- Смайлик зеленой галочки -->
                            <a href="?action=deny&id=<?php echo $row['id']; ?>" title="Отклонить">&#10060;</a> <!-- Смайлик красного креста -->
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Нет заявок на роль.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
