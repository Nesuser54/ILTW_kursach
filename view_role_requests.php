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
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Заголовок */
        h1 {
            font-size: 2.5rem;
            color: #222;
            margin-bottom: 20px;
        }

        /* Кнопка возврата */
      
        /* Таблица */
        table {
            width: 90%;
            max-width: 1200px;
            
            border-collapse: collapse;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            overflow: hidden;
            background-color: #fff;
        }

        table th, table td {
            padding: 15px 20px;
            text-align: center;
        }

        table th {
            
            color: #fff;
            font-size: 1.1rem;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        table td {
            font-size: 1rem;
            color: #555;
            border-bottom: 1px solid #ddd;



        }

        th, td {
            width: 33%;
        }

        /* Кнопки действий */
        .action-btns a {
            font-size: 1.5rem;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .action-btns a.approve {
            color: #28a745;
        }

        .action-btns a.deny {
            color: #dc3545;
        }

        .action-btns a:hover {
            transform: scale(1.2);
        }

        /* Мобильная адаптация */
        @media (max-width: 768px) {
            table {
                font-size: 0.9rem;
            }

            table th, table td {
                padding: 10px;
            }

            .add-recipe-btn {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <h1>Заявки на роль</h1>
    <a href="view_recipes.php" class="add-recipe-btn">Вернуться на главную</a>
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
                            <a href="?action=approve&id=<?php echo $row['id']; ?>" class="approve" title="Одобрить">✔</a>
                            <a href="?action=deny&id=<?php echo $row['id']; ?>" class="deny" title="Отклонить">✖</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">Нет заявок на роль.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
